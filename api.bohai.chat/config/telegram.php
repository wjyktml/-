<?php
/**
 * Telegram 机器人配置
 */

// 加载环境变量
if (file_exists(__DIR__ . '/../config.env')) {
    $env = parse_ini_file(__DIR__ . '/../config.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

return [
    // Telegram Bot Token (从 @BotFather 获取)
    'bot_token' => $_ENV['TELEGRAM_BOT_TOKEN'] ?? '',
    
    // 通知群组ID或用户ID
    'chat_id' => $_ENV['TELEGRAM_CHAT_ID'] ?? '',
    
    // 是否启用通知
    'enabled' => ($_ENV['TELEGRAM_ENABLED'] ?? 'false') === 'true',
    
    // API基础URL
    'api_url' => 'https://api.telegram.org/bot',
    
    // 通知类型配置
    'notifications' => [
        'new_order' => [
            'enabled' => true,
            'template' => "🛒 *新订单通知*\n\n" .
                         "订单号: {order_no}\n" .
                         "客户: {customer_name}\n" .
                         "金额: ¥{amount}\n" .
                         "支付方式: {payment_type}\n" .
                         "时间: {order_time}\n" .
                         "状态: {status}",
            'parse_mode' => 'Markdown'
        ],
        'payment_success' => [
            'enabled' => true,
            'template' => "💰 *支付成功通知*\n\n" .
                         "订单号: {order_no}\n" .
                         "金额: ¥{amount}\n" .
                         "支付方式: {payment_type}\n" .
                         "交易号: {transaction_id}\n" .
                         "支付时间: {pay_time}",
            'parse_mode' => 'Markdown'
        ],
        'payment_failed' => [
            'enabled' => true,
            'template' => "❌ *支付失败通知*\n\n" .
                         "订单号: {order_no}\n" .
                         "金额: ¥{amount}\n" .
                         "支付方式: {payment_type}\n" .
                         "失败原因: {error_message}\n" .
                         "时间: {fail_time}",
            'parse_mode' => 'Markdown'
        ],
        'order_cancelled' => [
            'enabled' => true,
            'template' => "🚫 *订单取消通知*\n\n" .
                         "订单号: {order_no}\n" .
                         "金额: ¥{amount}\n" .
                         "取消原因: {cancel_reason}\n" .
                         "时间: {cancel_time}",
            'parse_mode' => 'Markdown'
        ],
        'low_stock' => [
            'enabled' => true,
            'template' => "⚠️ *库存不足警告*\n\n" .
                         "商品: {product_name}\n" .
                         "当前库存: {current_stock}\n" .
                         "最低库存: {min_stock}\n" .
                         "时间: {alert_time}",
            'parse_mode' => 'Markdown'
        ],
        'system_alert' => [
            'enabled' => true,
            'template' => "🚨 *系统警告*\n\n" .
                         "类型: {alert_type}\n" .
                         "消息: {message}\n" .
                         "时间: {alert_time}",
            'parse_mode' => 'Markdown'
        ]
    ],
    
    // 重试配置
    'retry' => [
        'max_attempts' => 3,
        'delay' => 1000, // 毫秒
    ],
    
    // 日志配置
    'log' => [
        'enabled' => true,
        'file' => 'logs/telegram.log'
    ]
];
?>
