<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'База данных работает',
        'data' => $result
    ]);
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
}
?>