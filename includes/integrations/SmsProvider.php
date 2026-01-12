<?php
/**
 * SMS Provider Integration
 * Supports multiple SMS providers: SMS.ru, Twilio, Nexmo
 */

abstract class SmsProvider {
    protected $apiKey;
    protected $config;
    protected $testMode;
    protected $logger;
    protected $db;
    
    public function __construct($config = []) {
        $this->config = $config;
        $this->apiKey = $config['api_key'] ?? null;
        $this->testMode = $config['test_mode'] ?? false;
        
        if (class_exists('Logger')) {
            $this->logger = Logger::getInstance();
        }
        
        if (class_exists('Database')) {
            $database = new Database();
            $this->db = $database->getConnection();
        }
    }
    
    abstract public function send($phone, $message, $options = []);
    abstract public function getBalance();
    abstract public function getStatus($messageId);
    
    public static function create($provider = null) {
        $provider = $provider ?? getenv('SMS_PROVIDER') ?? 'smsru';
        
        $config = [
            'api_key' => getenv('SMS_API_KEY'),
            'test_mode' => getenv('SMS_TEST_MODE') === 'true',
            'from' => getenv('SMS_FROM_NUMBER')
        ];
        
        switch (strtolower($provider)) {
            case 'smsru':
                return new SmsRuProvider($config);
            case 'twilio':
                return new TwilioProvider($config);
            default:
                return new SmsRuProvider($config);
        }
    }
    
    protected function log($userId, $applicationId, $phone, $message, $status, $providerId = null, $error = null) {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO sms_log (user_id, application_id, phone, message, provider, provider_message_id, status, error_message, sent_at) 
                 VALUES (:user_id, :application_id, :phone, :message, :provider, :provider_id, :status, :error, :sent_at)"
            );
            
            $stmt->execute([
                ':user_id' => $userId,
                ':application_id' => $applicationId,
                ':phone' => $phone,
                ':message' => $message,
                ':provider' => get_class($this),
                ':provider_id' => $providerId,
                ':status' => $status,
                ':error' => $error,
                ':sent_at' => $status === 'sent' ? date('Y-m-d H:i:s') : null
            ]);
            
            if ($this->logger) {
                $this->logger->info("SMS logged", [
                    'phone' => $phone,
                    'status' => $status,
                    'provider' => get_class($this)
                ]);
            }
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to log SMS", ['error' => $e->getMessage()]);
            }
        }
    }
}

class SmsRuProvider extends SmsProvider {
    private $baseUrl = 'https://sms.ru/sms/send';
    
    public function send($phone, $message, $options = []) {
        if ($this->testMode) {
            $this->log(
                $options['user_id'] ?? null,
                $options['application_id'] ?? null,
                $phone,
                $message,
                'sent',
                'test_' . uniqid()
            );
            return ['success' => true, 'message_id' => 'test_' . uniqid()];
        }
        
        if (!$this->apiKey) {
            throw new Exception('SMS API key not configured');
        }
        
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        $params = [
            'api_id' => $this->apiKey,
            'to' => $phone,
            'msg' => $message,
            'json' => 1
        ];
        
        if (isset($this->config['from'])) {
            $params['from'] = $this->config['from'];
        }
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '?' . http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode === 200 && $result['status'] === 'OK') {
                $messageId = $result['sms'][$phone]['sms_id'] ?? null;
                
                $this->log(
                    $options['user_id'] ?? null,
                    $options['application_id'] ?? null,
                    $phone,
                    $message,
                    'sent',
                    $messageId
                );
                
                return ['success' => true, 'message_id' => $messageId];
            } else {
                $error = $result['status_text'] ?? 'Unknown error';
                
                $this->log(
                    $options['user_id'] ?? null,
                    $options['application_id'] ?? null,
                    $phone,
                    $message,
                    'failed',
                    null,
                    $error
                );
                
                return ['success' => false, 'error' => $error];
            }
        } catch (Exception $e) {
            $this->log(
                $options['user_id'] ?? null,
                $options['application_id'] ?? null,
                $phone,
                $message,
                'failed',
                null,
                $e->getMessage()
            );
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function getBalance() {
        if ($this->testMode) {
            return ['balance' => 999.99];
        }
        
        $url = 'https://sms.ru/my/balance';
        $params = ['api_id' => $this->apiKey, 'json' => 1];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        return ['balance' => $result['balance'] ?? 0];
    }
    
    public function getStatus($messageId) {
        if ($this->testMode) {
            return ['status' => 'delivered'];
        }
        
        $url = 'https://sms.ru/sms/status';
        $params = ['api_id' => $this->apiKey, 'sms_id' => $messageId, 'json' => 1];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        $statusCode = $result['status'] ?? null;
        $statusMap = [
            100 => 'delivered',
            101 => 'sent',
            102 => 'pending',
            103 => 'failed'
        ];
        
        return ['status' => $statusMap[$statusCode] ?? 'unknown'];
    }
}

class TwilioProvider extends SmsProvider {
    private $accountSid;
    private $authToken;
    
    public function __construct($config = []) {
        parent::__construct($config);
        $this->accountSid = $config['account_sid'] ?? getenv('TWILIO_ACCOUNT_SID');
        $this->authToken = $config['auth_token'] ?? getenv('TWILIO_AUTH_TOKEN');
    }
    
    public function send($phone, $message, $options = []) {
        if ($this->testMode) {
            $this->log(
                $options['user_id'] ?? null,
                $options['application_id'] ?? null,
                $phone,
                $message,
                'sent',
                'test_' . uniqid()
            );
            return ['success' => true, 'message_id' => 'test_' . uniqid()];
        }
        
        if (!$this->accountSid || !$this->authToken) {
            throw new Exception('Twilio credentials not configured');
        }
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
        
        $data = [
            'To' => $phone,
            'From' => $this->config['from'] ?? getenv('TWILIO_FROM_NUMBER'),
            'Body' => $message
        ];
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->accountSid}:{$this->authToken}");
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode === 201) {
                $this->log(
                    $options['user_id'] ?? null,
                    $options['application_id'] ?? null,
                    $phone,
                    $message,
                    'sent',
                    $result['sid']
                );
                
                return ['success' => true, 'message_id' => $result['sid']];
            } else {
                $error = $result['message'] ?? 'Unknown error';
                
                $this->log(
                    $options['user_id'] ?? null,
                    $options['application_id'] ?? null,
                    $phone,
                    $message,
                    'failed',
                    null,
                    $error
                );
                
                return ['success' => false, 'error' => $error];
            }
        } catch (Exception $e) {
            $this->log(
                $options['user_id'] ?? null,
                $options['application_id'] ?? null,
                $phone,
                $message,
                'failed',
                null,
                $e->getMessage()
            );
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function getBalance() {
        return ['balance' => 'N/A'];
    }
    
    public function getStatus($messageId) {
        if ($this->testMode) {
            return ['status' => 'delivered'];
        }
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages/{$messageId}.json";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->accountSid}:{$this->authToken}");
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        return ['status' => $result['status'] ?? 'unknown'];
    }
}
