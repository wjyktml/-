<?php
/**
 * Telegram Webhook 处理
 */

require_once __DIR__ . '/../classes/TelegramBot.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // 获取Webhook数据
    $input = file_get_contents('php://input');
    $update = json_decode($input, true);
    
    if (!$update) {
        throw new Exception('Invalid JSON data');
    }
    
    // 创建Telegram机器人实例
    $bot = new TelegramBot();
    
    // 处理Webhook
    $bot->handleWebhook($update);
    
    // 返回成功响应
    echo json_encode(['ok' => true]);
    
} catch (Exception $e) {
    // 记录错误
    error_log('Telegram Webhook Error: ' . $e->getMessage());
    
    // 返回错误响应
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
?>
