<?php
/**
 * Telegram Bot Integration
 * Commands for managers and dispatchers
 */

class TelegramBot {
    private $botToken;
    private $logger;
    private $db;
    private $testMode;
    
    public function __construct($config = []) {
        $this->botToken = $config['bot_token'] ?? getenv('TELEGRAM_BOT_TOKEN');
        $this->testMode = $config['test_mode'] ?? (getenv('TELEGRAM_TEST_MODE') === 'true');
        
        if (class_exists('Logger')) {
            $this->logger = Logger::getInstance();
        }
        
        if (class_exists('Database')) {
            $database = new Database();
            $this->db = $database->getConnection();
        }
    }
    
    public function sendMessage($chatId, $message, $options = []) {
        if ($this->testMode) {
            if ($this->logger) {
                $this->logger->info("Telegram message sent (test mode)", ['chat_id' => $chatId]);
            }
            return ['success' => true];
        }
        
        if (!$this->botToken) {
            return ['success' => false, 'error' => 'Bot token not configured'];
        }
        
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => $options['parse_mode'] ?? 'HTML'
        ];
        
        if (isset($options['reply_markup'])) {
            $data['reply_markup'] = json_encode($options['reply_markup']);
        }
        
        return $this->apiRequest('sendMessage', $data);
    }
    
    public function processWebhook($update) {
        if (!isset($update['message'])) {
            return ['success' => false, 'error' => 'No message in update'];
        }
        
        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $userId = $message['from']['id'];
        
        $user = $this->getTelegramUser($chatId);
        
        if (!$user) {
            return $this->sendMessage($chatId, 'Привет! Для использования бота свяжите свой аккаунт.');
        }
        
        $this->updateLastInteraction($chatId);
        
        if (strpos($text, '/') === 0) {
            return $this->handleCommand($chatId, $text, $user);
        }
        
        return ['success' => true];
    }
    
    private function handleCommand($chatId, $command, $user) {
        $parts = explode(' ', $command);
        $cmd = $parts[0];
        
        switch ($cmd) {
            case '/start':
                return $this->sendMessage($chatId, 
                    "Добро пожаловать в CRM.PROFTRANSFER Bot!\n\n" .
                    "Доступные команды:\n" .
                    "/status - Активные заявки\n" .
                    "/today - Заявки на сегодня\n" .
                    "/drivers - Статус водителей\n" .
                    "/earnings - Доход за период\n" .
                    "/alerts - Срочные уведомления"
                );
            
            case '/status':
                return $this->sendActiveApplications($chatId, $user);
            
            case '/today':
                return $this->sendTodayApplications($chatId, $user);
            
            case '/drivers':
                return $this->sendDriverStatus($chatId, $user);
            
            case '/earnings':
                return $this->sendEarnings($chatId, $user);
            
            case '/alerts':
                return $this->sendAlerts($chatId, $user);
            
            default:
                return $this->sendMessage($chatId, 'Неизвестная команда. Используйте /start для помощи.');
        }
    }
    
    private function sendActiveApplications($chatId, $user) {
        if (!$this->db) {
            return $this->sendMessage($chatId, 'Ошибка подключения к базе данных');
        }
        
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) as total, status 
                 FROM applications 
                 WHERE status IN ('new', 'confirmed', 'inwork') 
                 GROUP BY status"
            );
            $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $message = "📊 <b>Активные заявки:</b>\n\n";
            
            $statusLabels = [
                'new' => 'Новые',
                'confirmed' => 'Подтверждены',
                'inwork' => 'В работе'
            ];
            
            foreach ($stats as $stat) {
                $label = $statusLabels[$stat['status']] ?? $stat['status'];
                $message .= "{$label}: {$stat['total']}\n";
            }
            
            return $this->sendMessage($chatId, $message);
        } catch (Exception $e) {
            return $this->sendMessage($chatId, 'Ошибка получения данных');
        }
    }
    
    private function sendTodayApplications($chatId, $user) {
        if (!$this->db) {
            return $this->sendMessage($chatId, 'Ошибка подключения к базе данных');
        }
        
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) as total 
                 FROM applications 
                 WHERE DATE(trip_date) = CURDATE()"
            );
            $count = $stmt->fetchColumn();
            
            $message = "📅 <b>Заявки на сегодня:</b> {$count}";
            
            return $this->sendMessage($chatId, $message);
        } catch (Exception $e) {
            return $this->sendMessage($chatId, 'Ошибка получения данных');
        }
    }
    
    private function sendDriverStatus($chatId, $user) {
        if (!$this->db) {
            return $this->sendMessage($chatId, 'Ошибка подключения к базе данных');
        }
        
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) as total, status 
                 FROM drivers 
                 GROUP BY status"
            );
            $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $message = "👨‍💼 <b>Статус водителей:</b>\n\n";
            
            $statusLabels = [
                'work' => 'На работе',
                'dayoff' => 'Выходной',
                'vacation' => 'Отпуск',
                'repair' => 'Ремонт'
            ];
            
            foreach ($stats as $stat) {
                $label = $statusLabels[$stat['status']] ?? $stat['status'];
                $message .= "{$label}: {$stat['total']}\n";
            }
            
            return $this->sendMessage($chatId, $message);
        } catch (Exception $e) {
            return $this->sendMessage($chatId, 'Ошибка получения данных');
        }
    }
    
    private function sendEarnings($chatId, $user) {
        if (!$this->db) {
            return $this->sendMessage($chatId, 'Ошибка подключения к базе данных');
        }
        
        try {
            $stmt = $this->db->query(
                "SELECT 
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN order_amount ELSE 0 END) as today,
                    SUM(CASE WHEN YEARWEEK(created_at) = YEARWEEK(CURDATE()) THEN order_amount ELSE 0 END) as this_week,
                    SUM(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) THEN order_amount ELSE 0 END) as this_month
                 FROM applications 
                 WHERE status = 'completed'"
            );
            $earnings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $message = "💰 <b>Доход:</b>\n\n";
            $message .= "Сегодня: " . number_format($earnings['today'], 2) . " ₽\n";
            $message .= "На этой неделе: " . number_format($earnings['this_week'], 2) . " ₽\n";
            $message .= "В этом месяце: " . number_format($earnings['this_month'], 2) . " ₽\n";
            
            return $this->sendMessage($chatId, $message);
        } catch (Exception $e) {
            return $this->sendMessage($chatId, 'Ошибка получения данных');
        }
    }
    
    private function sendAlerts($chatId, $user) {
        $message = "🔔 <b>Срочные уведомления:</b>\n\n";
        $message .= "Нет срочных уведомлений";
        
        return $this->sendMessage($chatId, $message);
    }
    
    private function apiRequest($method, $data) {
        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/{$method}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            return ['success' => $result['ok'] ?? false, 'data' => $result];
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Telegram API error", ['error' => $e->getMessage()]);
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function getTelegramUser($chatId) {
        if (!$this->db) return null;
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM telegram_users WHERE chat_id = :chat_id AND is_active = 1");
            $stmt->execute([':chat_id' => $chatId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function updateLastInteraction($chatId) {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->prepare("UPDATE telegram_users SET last_interaction_at = NOW() WHERE chat_id = :chat_id");
            $stmt->execute([':chat_id' => $chatId]);
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to update telegram interaction", ['error' => $e->getMessage()]);
            }
        }
    }
    
    public function linkUser($userId, $chatId, $username = null, $firstName = null, $lastName = null) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO telegram_users (user_id, chat_id, username, first_name, last_name, is_active, last_interaction_at) 
                 VALUES (:user_id, :chat_id, :username, :first_name, :last_name, 1, NOW())
                 ON DUPLICATE KEY UPDATE 
                 user_id = :user_id, 
                 username = :username, 
                 first_name = :first_name, 
                 last_name = :last_name, 
                 is_active = 1"
            );
            
            $stmt->execute([
                ':user_id' => $userId,
                ':chat_id' => $chatId,
                ':username' => $username,
                ':first_name' => $firstName,
                ':last_name' => $lastName
            ]);
            
            return true;
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to link telegram user", ['error' => $e->getMessage()]);
            }
            return false;
        }
    }
}
