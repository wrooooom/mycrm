<?php
/**
 * Telegram Bot Webhook Endpoint
 */

header("Content-Type: application/json; charset=UTF-8");

require_once '../config/database.php';
require_once '../includes/logger.php';
require_once '../includes/integrations/TelegramBot.php';

$update = json_decode(file_get_contents('php://input'), true);

if (empty($update)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No update data']);
    exit();
}

logger()->info("Telegram webhook received", ['update_id' => $update['update_id'] ?? null]);

try {
    $bot = new TelegramBot();
    $result = $bot->processWebhook($update);
    
    http_response_code(200);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    logger()->error("Telegram webhook error", ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
