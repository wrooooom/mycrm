<?php
/**
 * Push Notification Integration
 * Firebase Cloud Messaging (FCM)
 */

class PushNotification {
    private $apiKey;
    private $senderId;
    private $logger;
    private $db;
    private $testMode;
    
    public function __construct($config = []) {
        $this->apiKey = $config['api_key'] ?? getenv('FCM_API_KEY');
        $this->senderId = $config['sender_id'] ?? getenv('FCM_SENDER_ID');
        $this->testMode = $config['test_mode'] ?? (getenv('FCM_TEST_MODE') === 'true');
        
        if (class_exists('Logger')) {
            $this->logger = Logger::getInstance();
        }
        
        if (class_exists('Database')) {
            $database = new Database();
            $this->db = $database->getConnection();
        }
    }
    
    public function send($userId, $title, $body, $data = []) {
        $tokens = $this->getUserDeviceTokens($userId);
        
        if (empty($tokens)) {
            if ($this->logger) {
                $this->logger->warning("No device tokens for user", ['user_id' => $userId]);
            }
            return ['success' => false, 'error' => 'No device tokens found'];
        }
        
        $results = [];
        
        foreach ($tokens as $token) {
            $result = $this->sendToToken($token['token'], $title, $body, $data);
            $results[] = $result;
            
            $this->log($userId, $token['id'], $title, $body, $data, $result['success'] ? 'sent' : 'failed', $result['error'] ?? null);
        }
        
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        
        return [
            'success' => $successCount > 0,
            'sent_count' => $successCount,
            'total_count' => count($results)
        ];
    }
    
    public function sendToToken($token, $title, $body, $data = []) {
        if ($this->testMode) {
            if ($this->logger) {
                $this->logger->info("Push notification sent (test mode)", [
                    'token' => substr($token, 0, 20) . '...',
                    'title' => $title
                ]);
            }
            return ['success' => true];
        }
        
        if (!$this->apiKey) {
            throw new Exception('FCM API key not configured');
        }
        
        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'badge' => 1
            ],
            'data' => $data,
            'priority' => 'high'
        ];
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: key=' . $this->apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode === 200 && $result['success'] === 1) {
                if ($this->logger) {
                    $this->logger->info("Push notification sent", ['title' => $title]);
                }
                return ['success' => true];
            } else {
                $error = $result['results'][0]['error'] ?? 'Unknown error';
                
                if ($error === 'InvalidRegistration' || $error === 'NotRegistered') {
                    $this->removeToken($token);
                }
                
                if ($this->logger) {
                    $this->logger->error("Push notification failed", ['error' => $error]);
                }
                
                return ['success' => false, 'error' => $error];
            }
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Push notification exception", ['error' => $e->getMessage()]);
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function registerDeviceToken($userId, $token, $deviceType, $deviceName = null) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO device_tokens (user_id, token, device_type, device_name, is_active, last_used_at) 
                 VALUES (:user_id, :token, :device_type, :device_name, 1, NOW())
                 ON DUPLICATE KEY UPDATE 
                 is_active = 1, 
                 device_name = :device_name, 
                 last_used_at = NOW()"
            );
            
            $stmt->execute([
                ':user_id' => $userId,
                ':token' => $token,
                ':device_type' => $deviceType,
                ':device_name' => $deviceName
            ]);
            
            if ($this->logger) {
                $this->logger->info("Device token registered", ['user_id' => $userId]);
            }
            
            return true;
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to register device token", ['error' => $e->getMessage()]);
            }
            return false;
        }
    }
    
    public function removeToken($token) {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->prepare("UPDATE device_tokens SET is_active = 0 WHERE token = :token");
            $stmt->execute([':token' => $token]);
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to remove token", ['error' => $e->getMessage()]);
            }
        }
    }
    
    private function getUserDeviceTokens($userId) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare(
                "SELECT id, token, device_type FROM device_tokens 
                 WHERE user_id = :user_id AND is_active = 1"
            );
            $stmt->execute([':user_id' => $userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to get device tokens", ['error' => $e->getMessage()]);
            }
            return [];
        }
    }
    
    private function log($userId, $deviceTokenId, $title, $body, $data, $status, $error = null) {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO push_notification_log (user_id, device_token_id, title, body, data, status, error_message, sent_at) 
                 VALUES (:user_id, :device_token_id, :title, :body, :data, :status, :error, :sent_at)"
            );
            
            $stmt->execute([
                ':user_id' => $userId,
                ':device_token_id' => $deviceTokenId,
                ':title' => $title,
                ':body' => $body,
                ':data' => json_encode($data),
                ':status' => $status,
                ':error' => $error,
                ':sent_at' => $status === 'sent' ? date('Y-m-d H:i:s') : null
            ]);
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to log push notification", ['error' => $e->getMessage()]);
            }
        }
    }
    
    public function sendApplicationNotification($userId, $application) {
        return $this->send(
            $userId,
            'Новая заявка',
            "Заявка #{$application['application_number']} назначена",
            [
                'type' => 'application',
                'application_id' => $application['id'],
                'action' => 'view'
            ]
        );
    }
    
    public function sendStatusChangeNotification($userId, $application, $newStatus) {
        $statusLabels = [
            'new' => 'Новая',
            'confirmed' => 'Подтверждена',
            'inwork' => 'В работе',
            'completed' => 'Завершена',
            'cancelled' => 'Отменена'
        ];
        
        return $this->send(
            $userId,
            'Статус заявки изменен',
            "Заявка #{$application['application_number']}: " . ($statusLabels[$newStatus] ?? $newStatus),
            [
                'type' => 'status_change',
                'application_id' => $application['id'],
                'status' => $newStatus
            ]
        );
    }
    
    public function sendUrgentMessage($userId, $message) {
        return $this->send(
            $userId,
            'Срочное сообщение',
            $message,
            [
                'type' => 'urgent',
                'priority' => 'high'
            ]
        );
    }
}
