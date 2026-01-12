<?php
/**
 * Export Service
 * Generate exports in various formats (CSV, Excel, PDF, JSON)
 */

class ExportService {
    private $logger;
    private $db;
    
    public function __construct() {
        if (class_exists('Logger')) {
            $this->logger = Logger::getInstance();
        }
        
        if (class_exists('Database')) {
            $database = new Database();
            $this->db = $database->getConnection();
        }
    }
    
    public function exportApplications($format, $filters = []) {
        $data = $this->getApplicationsData($filters);
        
        return $this->export($data, $format, 'applications');
    }
    
    public function exportDrivers($format, $filters = []) {
        $data = $this->getDriversData($filters);
        
        return $this->export($data, $format, 'drivers');
    }
    
    public function exportVehicles($format, $filters = []) {
        $data = $this->getVehiclesData($filters);
        
        return $this->export($data, $format, 'vehicles');
    }
    
    public function exportPayments($format, $filters = []) {
        $data = $this->getPaymentsData($filters);
        
        return $this->export($data, $format, 'payments');
    }
    
    private function export($data, $format, $name) {
        switch (strtolower($format)) {
            case 'csv':
                return $this->exportCsv($data, $name);
            case 'xlsx':
            case 'excel':
                return $this->exportExcel($data, $name);
            case 'pdf':
                return $this->exportPdf($data, $name);
            case 'json':
                return $this->exportJson($data, $name);
            default:
                return ['success' => false, 'error' => 'Unsupported format'];
        }
    }
    
    private function exportCsv($data, $name) {
        if (empty($data)) {
            return ['success' => false, 'error' => 'No data to export'];
        }
        
        $filename = $name . '_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = __DIR__ . '/../../exports/' . $filename;
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $fp = fopen($filepath, 'w');
        
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($fp, array_keys($data[0]), ';');
        
        foreach ($data as $row) {
            fputcsv($fp, $row, ';');
        }
        
        fclose($fp);
        
        if ($this->logger) {
            $this->logger->info("CSV export created", ['filename' => $filename, 'rows' => count($data)]);
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'rows' => count($data)
        ];
    }
    
    private function exportExcel($data, $name) {
        return $this->exportCsv($data, $name);
    }
    
    private function exportPdf($data, $name) {
        if (empty($data)) {
            return ['success' => false, 'error' => 'No data to export'];
        }
        
        $filename = $name . '_' . date('Y-m-d_H-i-s') . '.html';
        $filepath = __DIR__ . '/../../exports/' . $filename;
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $html = $this->generatePdfHtml($data, $name);
        file_put_contents($filepath, $html);
        
        if ($this->logger) {
            $this->logger->info("PDF export created", ['filename' => $filename, 'rows' => count($data)]);
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'rows' => count($data)
        ];
    }
    
    private function exportJson($data, $name) {
        $filename = $name . '_' . date('Y-m-d_H-i-s') . '.json';
        $filepath = __DIR__ . '/../../exports/' . $filename;
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'rows' => count($data)
        ];
    }
    
    private function generatePdfHtml($data, $title) {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>body{font-family:Arial;} table{border-collapse:collapse;width:100%;}';
        $html .= 'th,td{border:1px solid #ddd;padding:8px;text-align:left;}';
        $html .= 'th{background:#4CAF50;color:white;}</style></head><body>';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        $html .= '<p>Дата: ' . date('d.m.Y H:i') . '</p>';
        $html .= '<table><thead><tr>';
        
        if (!empty($data)) {
            foreach (array_keys($data[0]) as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                }
                $html .= '</tr>';
            }
        }
        
        $html .= '</tbody></table></body></html>';
        
        return $html;
    }
    
    private function getApplicationsData($filters) {
        if (!$this->db) return [];
        
        $query = "SELECT 
                    a.id,
                    a.application_number,
                    a.status,
                    a.customer_name,
                    a.customer_phone,
                    a.trip_date,
                    a.service_type,
                    a.tariff,
                    a.order_amount,
                    a.created_at
                  FROM applications a
                  WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['status'])) {
            $query .= " AND a.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (isset($filters['date_from'])) {
            $query .= " AND a.trip_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (isset($filters['date_to'])) {
            $query .= " AND a.trip_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $query .= " ORDER BY a.created_at DESC";
        
        if (isset($filters['limit'])) {
            $query .= " LIMIT " . intval($filters['limit']);
        }
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Export query failed", ['error' => $e->getMessage()]);
            }
            return [];
        }
    }
    
    private function getDriversData($filters) {
        if (!$this->db) return [];
        
        $query = "SELECT 
                    d.id,
                    d.first_name,
                    d.last_name,
                    d.phone,
                    d.email,
                    d.status,
                    d.rating,
                    c.name as company_name
                  FROM drivers d
                  LEFT JOIN companies c ON d.company_id = c.id
                  WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['status'])) {
            $query .= " AND d.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        $query .= " ORDER BY d.last_name, d.first_name";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getVehiclesData($filters) {
        if (!$this->db) return [];
        
        $query = "SELECT 
                    v.id,
                    v.brand,
                    v.model,
                    v.class,
                    v.license_plate,
                    v.year,
                    v.status,
                    c.name as company_name
                  FROM vehicles v
                  LEFT JOIN companies c ON v.company_id = c.id
                  WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['status'])) {
            $query .= " AND v.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        $query .= " ORDER BY v.brand, v.model";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getPaymentsData($filters) {
        if (!$this->db) return [];
        
        $query = "SELECT 
                    p.id,
                    p.amount,
                    p.status,
                    p.method,
                    p.payment_date,
                    a.application_number,
                    a.customer_name
                  FROM payments p
                  LEFT JOIN applications a ON p.application_id = a.id
                  WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['status'])) {
            $query .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        $query .= " ORDER BY p.created_at DESC";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
