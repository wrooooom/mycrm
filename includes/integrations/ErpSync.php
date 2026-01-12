<?php
/**
 * ERP/1C Integration
 * Sync data with external ERP systems
 */

class ErpSync {
    private $apiUrl;
    private $apiKey;
    private $erpType;
    private $logger;
    private $db;
    private $testMode;
    
    public function __construct($config = []) {
        $this->apiUrl = $config['api_url'] ?? getenv('ERP_API_URL');
        $this->apiKey = $config['api_key'] ?? getenv('ERP_API_KEY');
        $this->erpType = $config['erp_type'] ?? getenv('ERP_TYPE') ?? '1c';
        $this->testMode = $config['test_mode'] ?? (getenv('ERP_TEST_MODE') === 'true');
        
        if (class_exists('Logger')) {
            $this->logger = Logger::getInstance();
        }
        
        if (class_exists('Database')) {
            $database = new Database();
            $this->db = $database->getConnection();
        }
    }
    
    public function syncApplication($applicationId) {
        return $this->syncEntity('application', $applicationId, 'to_erp');
    }
    
    public function syncCompany($companyId) {
        return $this->syncEntity('company', $companyId, 'to_erp');
    }
    
    public function syncDriver($driverId) {
        return $this->syncEntity('driver', $driverId, 'to_erp');
    }
    
    public function syncPayment($paymentId) {
        return $this->syncEntity('payment', $paymentId, 'to_erp');
    }
    
    private function syncEntity($entityType, $entityId, $direction) {
        if ($this->testMode) {
            $this->logSync($entityType, $entityId, $direction, 'success', [], ['test_mode' => true]);
            return ['success' => true, 'erp_id' => 'test_' . uniqid()];
        }
        
        if (!$this->apiUrl || !$this->apiKey) {
            return ['success' => false, 'error' => 'ERP not configured'];
        }
        
        $entityData = $this->getEntityData($entityType, $entityId);
        
        if (!$entityData) {
            return ['success' => false, 'error' => 'Entity not found'];
        }
        
        $this->logSync($entityType, $entityId, $direction, 'syncing', $entityData, null);
        
        try {
            $endpoint = $this->apiUrl . '/' . $entityType . '/sync';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($entityData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode === 200 && isset($result['id'])) {
                $this->logSync($entityType, $entityId, $direction, 'success', $entityData, $result, $result['id']);
                
                if ($this->logger) {
                    $this->logger->info("ERP sync successful", [
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                        'erp_id' => $result['id']
                    ]);
                }
                
                return ['success' => true, 'erp_id' => $result['id']];
            } else {
                $error = $result['error'] ?? 'Sync failed';
                $this->logSync($entityType, $entityId, $direction, 'failed', $entityData, $result, null, $error);
                
                return ['success' => false, 'error' => $error];
            }
        } catch (Exception $e) {
            $this->logSync($entityType, $entityId, $direction, 'failed', $entityData, null, null, $e->getMessage());
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function getEntityData($entityType, $entityId) {
        if (!$this->db) return null;
        
        $tableMap = [
            'application' => 'applications',
            'company' => 'companies',
            'driver' => 'drivers',
            'payment' => 'payments',
            'vehicle' => 'vehicles'
        ];
        
        $table = $tableMap[$entityType] ?? null;
        
        if (!$table) return null;
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id = :id");
            $stmt->execute([':id' => $entityId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to get entity data", ['error' => $e->getMessage()]);
            }
            return null;
        }
    }
    
    private function logSync($entityType, $entityId, $direction, $status, $requestData = [], $responseData = null, $erpEntityId = null, $error = null) {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO erp_sync_log (entity_type, entity_id, sync_direction, erp_system, erp_entity_id, status, request_data, response_data, error_message, synced_at) 
                 VALUES (:entity_type, :entity_id, :direction, :erp_system, :erp_id, :status, :request, :response, :error, :synced_at)"
            );
            
            $stmt->execute([
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':direction' => $direction,
                ':erp_system' => $this->erpType,
                ':erp_id' => $erpEntityId,
                ':status' => $status,
                ':request' => json_encode($requestData),
                ':response' => json_encode($responseData),
                ':error' => $error,
                ':synced_at' => $status === 'success' ? date('Y-m-d H:i:s') : null
            ]);
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to log ERP sync", ['error' => $e->getMessage()]);
            }
        }
    }
    
    public function getSyncStatus($entityType, $entityId) {
        if (!$this->db) return null;
        
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM erp_sync_log 
                 WHERE entity_type = :entity_type AND entity_id = :entity_id 
                 ORDER BY created_at DESC LIMIT 1"
            );
            $stmt->execute([
                ':entity_type' => $entityType,
                ':entity_id' => $entityId
            ]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
}
