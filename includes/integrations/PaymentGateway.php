<?php
/**
 * Payment Gateway Integration
 * Supports Yandex.Kassa and Stripe
 */

abstract class PaymentGateway {
    protected $apiKey;
    protected $secretKey;
    protected $testMode;
    protected $logger;
    protected $db;
    protected $config;
    
    public function __construct($config = []) {
        $this->config = $config;
        $this->apiKey = $config['api_key'] ?? null;
        $this->secretKey = $config['secret_key'] ?? null;
        $this->testMode = $config['test_mode'] ?? false;
        
        if (class_exists('Logger')) {
            $this->logger = Logger::getInstance();
        }
        
        if (class_exists('Database')) {
            $database = new Database();
            $this->db = $database->getConnection();
        }
    }
    
    abstract public function createPayment($amount, $options = []);
    abstract public function getPaymentStatus($transactionId);
    abstract public function refundPayment($transactionId, $amount = null);
    abstract public function verifyWebhook($payload, $signature);
    abstract public function processWebhook($payload);
    
    public static function create($provider = null) {
        $provider = $provider ?? getenv('PAYMENT_PROVIDER') ?? 'yandex';
        
        switch (strtolower($provider)) {
            case 'yandex':
            case 'yandex_kassa':
                return new YandexKassaGateway([
                    'api_key' => getenv('YANDEX_KASSA_API_KEY'),
                    'secret_key' => getenv('YANDEX_KASSA_SECRET_KEY'),
                    'shop_id' => getenv('YANDEX_KASSA_SHOP_ID'),
                    'test_mode' => getenv('PAYMENT_TEST_MODE') === 'true'
                ]);
            case 'stripe':
                return new StripeGateway([
                    'api_key' => getenv('STRIPE_API_KEY'),
                    'secret_key' => getenv('STRIPE_SECRET_KEY'),
                    'test_mode' => getenv('PAYMENT_TEST_MODE') === 'true'
                ]);
            default:
                throw new Exception("Unsupported payment provider: {$provider}");
        }
    }
    
    protected function logTransaction($paymentId, $applicationId, $userId, $transactionId, $amount, $status, $metadata = []) {
        if (!$this->db) return null;
        
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO payment_transactions 
                 (payment_id, application_id, user_id, provider, provider_transaction_id, amount, status, metadata, created_at) 
                 VALUES (:payment_id, :application_id, :user_id, :provider, :transaction_id, :amount, :status, :metadata, NOW())"
            );
            
            $stmt->execute([
                ':payment_id' => $paymentId,
                ':application_id' => $applicationId,
                ':user_id' => $userId,
                ':provider' => get_class($this),
                ':transaction_id' => $transactionId,
                ':amount' => $amount,
                ':status' => $status,
                ':metadata' => json_encode($metadata)
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to log payment transaction", ['error' => $e->getMessage()]);
            }
            return null;
        }
    }
    
    protected function updateTransaction($transactionId, $status, $metadata = []) {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->prepare(
                "UPDATE payment_transactions 
                 SET status = :status, metadata = :metadata, updated_at = NOW()
                 WHERE provider_transaction_id = :transaction_id"
            );
            
            $stmt->execute([
                ':status' => $status,
                ':metadata' => json_encode($metadata),
                ':transaction_id' => $transactionId
            ]);
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to update payment transaction", ['error' => $e->getMessage()]);
            }
        }
    }
}

class YandexKassaGateway extends PaymentGateway {
    private $shopId;
    private $baseUrl = 'https://api.yookassa.ru/v3';
    
    public function __construct($config = []) {
        parent::__construct($config);
        $this->shopId = $config['shop_id'] ?? null;
    }
    
    public function createPayment($amount, $options = []) {
        if ($this->testMode) {
            $transactionId = 'test_' . uniqid();
            $paymentLink = 'https://test.yookassa.ru/checkout/' . $transactionId;
            
            $this->logTransaction(
                $options['payment_id'] ?? null,
                $options['application_id'] ?? null,
                $options['user_id'] ?? null,
                $transactionId,
                $amount,
                'pending',
                ['payment_link' => $paymentLink, 'test_mode' => true]
            );
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'payment_link' => $paymentLink,
                'status' => 'pending'
            ];
        }
        
        if (!$this->shopId || !$this->apiKey) {
            throw new Exception('Yandex.Kassa credentials not configured');
        }
        
        $idempotenceKey = $options['idempotence_key'] ?? uniqid('payment_', true);
        
        $data = [
            'amount' => [
                'value' => number_format($amount, 2, '.', ''),
                'currency' => $options['currency'] ?? 'RUB'
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $options['return_url'] ?? getenv('APP_URL') . '/payment-success.php'
            ],
            'capture' => $options['auto_capture'] ?? true,
            'description' => $options['description'] ?? 'Оплата заявки'
        ];
        
        if (isset($options['customer_email'])) {
            $data['receipt'] = [
                'customer' => ['email' => $options['customer_email']],
                'items' => [[
                    'description' => $data['description'],
                    'quantity' => '1',
                    'amount' => $data['amount'],
                    'vat_code' => 1
                ]]
            ];
        }
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/payments');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Idempotence-Key: ' . $idempotenceKey
            ]);
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->shopId}:{$this->apiKey}");
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode === 200 && isset($result['id'])) {
                $transactionId = $result['id'];
                $paymentLink = $result['confirmation']['confirmation_url'] ?? null;
                
                $this->logTransaction(
                    $options['payment_id'] ?? null,
                    $options['application_id'] ?? null,
                    $options['user_id'] ?? null,
                    $transactionId,
                    $amount,
                    'pending',
                    ['payment_link' => $paymentLink]
                );
                
                if ($this->logger) {
                    $this->logger->info("Payment created", [
                        'transaction_id' => $transactionId,
                        'amount' => $amount
                    ]);
                }
                
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'payment_link' => $paymentLink,
                    'status' => $result['status']
                ];
            } else {
                $error = $result['description'] ?? 'Unknown error';
                
                if ($this->logger) {
                    $this->logger->error("Payment creation failed", ['error' => $error]);
                }
                
                return ['success' => false, 'error' => $error];
            }
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Payment creation exception", ['error' => $e->getMessage()]);
            }
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function getPaymentStatus($transactionId) {
        if ($this->testMode) {
            return ['status' => 'succeeded'];
        }
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/payments/' . $transactionId);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->shopId}:{$this->apiKey}");
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            return ['status' => $result['status'] ?? 'unknown'];
        } catch (Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }
    
    public function refundPayment($transactionId, $amount = null) {
        if ($this->testMode) {
            return ['success' => true, 'refund_id' => 'test_refund_' . uniqid()];
        }
        
        $idempotenceKey = uniqid('refund_', true);
        
        $data = ['payment_id' => $transactionId];
        
        if ($amount !== null) {
            $data['amount'] = [
                'value' => number_format($amount, 2, '.', ''),
                'currency' => 'RUB'
            ];
        }
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/refunds');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Idempotence-Key: ' . $idempotenceKey
            ]);
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->shopId}:{$this->apiKey}");
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode === 200 && isset($result['id'])) {
                $this->updateTransaction($transactionId, 'refunded', ['refund_id' => $result['id']]);
                
                return ['success' => true, 'refund_id' => $result['id']];
            } else {
                return ['success' => false, 'error' => $result['description'] ?? 'Refund failed'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function verifyWebhook($payload, $signature) {
        if ($this->testMode) {
            return true;
        }
        
        $calculatedSignature = hash_hmac('sha256', $payload, $this->secretKey);
        
        return hash_equals($calculatedSignature, $signature);
    }
    
    public function processWebhook($payload) {
        $data = json_decode($payload, true);
        
        if (!isset($data['object']) || !isset($data['object']['id'])) {
            return ['success' => false, 'error' => 'Invalid webhook payload'];
        }
        
        $payment = $data['object'];
        $transactionId = $payment['id'];
        $status = $payment['status'];
        
        $statusMap = [
            'succeeded' => 'succeeded',
            'canceled' => 'cancelled',
            'pending' => 'pending',
            'waiting_for_capture' => 'processing'
        ];
        
        $newStatus = $statusMap[$status] ?? 'pending';
        
        $this->updateTransaction($transactionId, $newStatus, $payment);
        
        if ($this->logger) {
            $this->logger->info("Webhook processed", [
                'transaction_id' => $transactionId,
                'status' => $newStatus
            ]);
        }
        
        return ['success' => true, 'transaction_id' => $transactionId, 'status' => $newStatus];
    }
}

class StripeGateway extends PaymentGateway {
    private $baseUrl = 'https://api.stripe.com/v1';
    
    public function createPayment($amount, $options = []) {
        if ($this->testMode) {
            $transactionId = 'test_' . uniqid();
            $paymentLink = 'https://checkout.stripe.com/test/' . $transactionId;
            
            $this->logTransaction(
                $options['payment_id'] ?? null,
                $options['application_id'] ?? null,
                $options['user_id'] ?? null,
                $transactionId,
                $amount,
                'pending',
                ['payment_link' => $paymentLink, 'test_mode' => true]
            );
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'payment_link' => $paymentLink,
                'status' => 'pending'
            ];
        }
        
        if (!$this->apiKey) {
            throw new Exception('Stripe API key not configured');
        }
        
        $amountCents = intval($amount * 100);
        
        $data = [
            'amount' => $amountCents,
            'currency' => strtolower($options['currency'] ?? 'rub'),
            'description' => $options['description'] ?? 'Payment',
            'metadata' => [
                'application_id' => $options['application_id'] ?? null
            ]
        ];
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/payment_intents');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . ':');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode === 200 && isset($result['id'])) {
                $transactionId = $result['id'];
                
                $this->logTransaction(
                    $options['payment_id'] ?? null,
                    $options['application_id'] ?? null,
                    $options['user_id'] ?? null,
                    $transactionId,
                    $amount,
                    'pending',
                    ['client_secret' => $result['client_secret']]
                );
                
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'client_secret' => $result['client_secret'],
                    'status' => $result['status']
                ];
            } else {
                return ['success' => false, 'error' => $result['error']['message'] ?? 'Unknown error'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function getPaymentStatus($transactionId) {
        if ($this->testMode) {
            return ['status' => 'succeeded'];
        }
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/payment_intents/' . $transactionId);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . ':');
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            return ['status' => $result['status'] ?? 'unknown'];
        } catch (Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }
    
    public function refundPayment($transactionId, $amount = null) {
        if ($this->testMode) {
            return ['success' => true, 'refund_id' => 'test_refund_' . uniqid()];
        }
        
        $data = ['payment_intent' => $transactionId];
        
        if ($amount !== null) {
            $data['amount'] = intval($amount * 100);
        }
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/refunds');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . ':');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode === 200 && isset($result['id'])) {
                $this->updateTransaction($transactionId, 'refunded', ['refund_id' => $result['id']]);
                
                return ['success' => true, 'refund_id' => $result['id']];
            } else {
                return ['success' => false, 'error' => $result['error']['message'] ?? 'Refund failed'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function verifyWebhook($payload, $signature) {
        if ($this->testMode) {
            return true;
        }
        
        $signedPayload = $signature . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $this->secretKey);
        
        return hash_equals($expectedSignature, $signature);
    }
    
    public function processWebhook($payload) {
        $data = json_decode($payload, true);
        
        if (!isset($data['type']) || !isset($data['data']['object'])) {
            return ['success' => false, 'error' => 'Invalid webhook payload'];
        }
        
        $event = $data['type'];
        $object = $data['data']['object'];
        
        if (strpos($event, 'payment_intent.') === 0) {
            $transactionId = $object['id'];
            $status = $object['status'];
            
            $statusMap = [
                'succeeded' => 'succeeded',
                'canceled' => 'cancelled',
                'processing' => 'processing',
                'requires_payment_method' => 'failed'
            ];
            
            $newStatus = $statusMap[$status] ?? 'pending';
            
            $this->updateTransaction($transactionId, $newStatus, $object);
            
            return ['success' => true, 'transaction_id' => $transactionId, 'status' => $newStatus];
        }
        
        return ['success' => true, 'message' => 'Event type not processed'];
    }
}
