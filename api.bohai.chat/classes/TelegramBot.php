<?php
/**
 * Telegram 机器人通知类
 */

require_once __DIR__ . '/Database.php';

class TelegramBot {
    private $config;
    private $botToken;
    private $chatId;
    private $apiUrl;
    
    public function __construct() {
        $this->config = require_once __DIR__ . '/../config/telegram.php';
        $this->botToken = $this->config['bot_token'];
        $this->chatId = $this->config['chat_id'];
        $this->apiUrl = $this->config['api_url'] . $this->botToken;
        
        // 不抛出异常，允许系统在没有Telegram配置时正常运行
        // if (empty($this->botToken)) {
        //     throw new Exception('Telegram Bot Token 未配置');
        // }
    }
    
    /**
     * 发送消息
     */
    public function sendMessage($text, $parseMode = 'Markdown', $chatId = null) {
        if (!$this->config['enabled']) {
            return false;
        }
        
        if (empty($this->botToken)) {
            error_log('Telegram Bot Token 未配置，跳过发送消息');
            return false;
        }
        
        $chatId = $chatId ?: $this->chatId;
        if (empty($chatId)) {
            error_log('Telegram Chat ID 未配置，跳过发送消息');
            return false;
        }
        
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => true
        ];
        
        return $this->makeRequest('sendMessage', $data);
    }
    
    /**
     * 发送新订单通知
     */
    public function sendNewOrderNotification($orderData) {
        $config = $this->config['notifications']['new_order'];
        if (!$config['enabled']) {
            return false;
        }
        
        $message = $this->replacePlaceholders($config['template'], [
            'order_no' => $orderData['order_no'],
            'customer_name' => $orderData['customer_name'] ?? '未知客户',
            'amount' => number_format($orderData['total_amount'], 2),
            'payment_type' => $this->getPaymentTypeName($orderData['payment_type']),
            'order_time' => $orderData['create_time'],
            'status' => $this->getOrderStatusName($orderData['order_status'])
        ]);
        
        return $this->sendMessage($message, $config['parse_mode']);
    }
    
    /**
     * 发送支付成功通知
     */
    public function sendPaymentSuccessNotification($orderData, $paymentData) {
        $config = $this->config['notifications']['payment_success'];
        if (!$config['enabled']) {
            return false;
        }
        
        $message = $this->replacePlaceholders($config['template'], [
            'order_no' => $orderData['order_no'],
            'amount' => number_format($orderData['final_amount'], 2),
            'payment_type' => $this->getPaymentTypeName($orderData['payment_type']),
            'transaction_id' => $paymentData['transaction_id'] ?? 'N/A',
            'pay_time' => $paymentData['paid_at'] ?? date('Y-m-d H:i:s')
        ]);
        
        return $this->sendMessage($message, $config['parse_mode']);
    }
    
    /**
     * 发送支付失败通知
     */
    public function sendPaymentFailedNotification($orderData, $errorMessage) {
        $config = $this->config['notifications']['payment_failed'];
        if (!$config['enabled']) {
            return false;
        }
        
        $message = $this->replacePlaceholders($config['template'], [
            'order_no' => $orderData['order_no'],
            'amount' => number_format($orderData['final_amount'], 2),
            'payment_type' => $this->getPaymentTypeName($orderData['payment_type']),
            'error_message' => $errorMessage,
            'fail_time' => date('Y-m-d H:i:s')
        ]);
        
        return $this->sendMessage($message, $config['parse_mode']);
    }
    
    /**
     * 发送订单取消通知
     */
    public function sendOrderCancelledNotification($orderData, $cancelReason = '') {
        $config = $this->config['notifications']['order_cancelled'];
        if (!$config['enabled']) {
            return false;
        }
        
        $message = $this->replacePlaceholders($config['template'], [
            'order_no' => $orderData['order_no'],
            'amount' => number_format($orderData['final_amount'], 2),
            'cancel_reason' => $cancelReason ?: '用户取消',
            'cancel_time' => date('Y-m-d H:i:s')
        ]);
        
        return $this->sendMessage($message, $config['parse_mode']);
    }
    
    /**
     * 发送库存不足警告
     */
    public function sendLowStockAlert($productData, $currentStock, $minStock = 5) {
        $config = $this->config['notifications']['low_stock'];
        if (!$config['enabled']) {
            return false;
        }
        
        $message = $this->replacePlaceholders($config['template'], [
            'product_name' => $productData['name'],
            'current_stock' => $currentStock,
            'min_stock' => $minStock,
            'alert_time' => date('Y-m-d H:i:s')
        ]);
        
        return $this->sendMessage($message, $config['parse_mode']);
    }
    
    /**
     * 发送系统警告
     */
    public function sendSystemAlert($alertType, $message) {
        $config = $this->config['notifications']['system_alert'];
        if (!$config['enabled']) {
            return false;
        }
        
        $text = $this->replacePlaceholders($config['template'], [
            'alert_type' => $alertType,
            'message' => $message,
            'alert_time' => date('Y-m-d H:i:s')
        ]);
        
        return $this->sendMessage($text, $config['parse_mode']);
    }
    
    /**
     * 发送带按钮的消息
     */
    public function sendMessageWithButtons($text, $buttons, $parseMode = 'Markdown', $chatId = null) {
        if (!$this->config['enabled']) {
            return false;
        }
        
        $chatId = $chatId ?: $this->chatId;
        if (empty($chatId)) {
            throw new Exception('Telegram Chat ID 未配置');
        }
        
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'reply_markup' => json_encode([
                'inline_keyboard' => $buttons
            ])
        ];
        
        return $this->makeRequest('sendMessage', $data);
    }
    
    /**
     * 发送订单详情（带按钮）
     */
    public function sendOrderDetails($orderData, $orderItems) {
        $text = "📋 *订单详情*\n\n";
        $text .= "订单号: `{$orderData['order_no']}`\n";
        $text .= "金额: ¥" . number_format($orderData['final_amount'], 2) . "\n";
        $text .= "支付方式: {$this->getPaymentTypeName($orderData['payment_type'])}\n";
        $text .= "状态: {$this->getOrderStatusName($orderData['order_status'])}\n";
        $text .= "创建时间: {$orderData['create_time']}\n\n";
        
        $text .= "*商品列表:*\n";
        foreach ($orderItems as $item) {
            $text .= "• {$item['product_name']} x{$item['quantity']} = ¥" . number_format($item['total_price'], 2) . "\n";
        }
        
        $buttons = [
            [
                [
                    'text' => '查看订单',
                    'url' => "https://bohai.chat/admin?view=order&id={$orderData['id']}"
                ],
                [
                    'text' => '处理订单',
                    'callback_data' => "process_order_{$orderData['id']}"
                ]
            ]
        ];
        
        return $this->sendMessageWithButtons($text, $buttons);
    }
    
    /**
     * 设置Webhook
     */
    public function setWebhook($webhookUrl) {
        $data = [
            'url' => $webhookUrl,
            'allowed_updates' => ['message', 'callback_query']
        ];
        
        return $this->makeRequest('setWebhook', $data);
    }
    
    /**
     * 获取Webhook信息
     */
    public function getWebhookInfo() {
        return $this->makeRequest('getWebhookInfo');
    }
    
    /**
     * 删除Webhook
     */
    public function deleteWebhook() {
        return $this->makeRequest('deleteWebhook');
    }
    
    /**
     * 获取机器人信息
     */
    public function getMe() {
        return $this->makeRequest('getMe');
    }
    
    /**
     * 处理Webhook回调
     */
    public function handleWebhook($update) {
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }
    }
    
    /**
     * 处理消息
     */
    private function handleMessage($message) {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        
        // 简单的命令处理
        switch ($text) {
            case '/start':
                $this->sendMessage("欢迎使用 NextsPay 通知机器人！\n\n可用命令:\n/status - 查看系统状态\n/orders - 查看今日订单", $chatId);
                break;
            case '/status':
                $this->sendSystemStatus($chatId);
                break;
            case '/orders':
                $this->sendTodayOrders($chatId);
                break;
            default:
                if (strpos($text, '/') === 0) {
                    $this->sendMessage("未知命令: {$text}", $chatId);
                }
                break;
        }
    }
    
    /**
     * 处理回调查询
     */
    private function handleCallbackQuery($callbackQuery) {
        $data = $callbackQuery['data'];
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        
        if (strpos($data, 'process_order_') === 0) {
            $orderId = str_replace('process_order_', '', $data);
            $this->processOrder($orderId, $chatId, $messageId);
        }
    }
    
    /**
     * 发送系统状态
     */
    private function sendSystemStatus($chatId) {
        try {
            require_once __DIR__ . '/../models/Order.php';
            require_once __DIR__ . '/../models/Product.php';
            
            $orderModel = new Order();
            $productModel = new Product();
            
            $orderStats = $orderModel->getStats();
            $todayStats = $orderModel->getTodayStats();
            $productStats = $productModel->getStats();
            
            $text = "📊 *系统状态报告*\n\n";
            $text .= "*今日统计:*\n";
            $text .= "• 订单数: {$todayStats['total']}\n";
            $text .= "• 已支付: {$todayStats['paid']}\n";
            $text .= "• 金额: ¥" . number_format($todayStats['amount'], 2) . "\n\n";
            
            $text .= "*总体统计:*\n";
            $text .= "• 总订单: {$orderStats['total']}\n";
            $text .= "• 总金额: ¥" . number_format($orderStats['total_amount'], 2) . "\n";
            $text .= "• 商品数: {$productStats['total']}\n";
            $text .= "• 低库存: {$productStats['low_stock']}\n\n";
            
            $text .= "⏰ " . date('Y-m-d H:i:s');
            
            $this->sendMessage($text, 'Markdown', $chatId);
            
        } catch (Exception $e) {
            $this->sendMessage("获取系统状态失败: " . $e->getMessage(), 'Markdown', $chatId);
        }
    }
    
    /**
     * 发送今日订单
     */
    private function sendTodayOrders($chatId) {
        try {
            require_once __DIR__ . '/../models/Order.php';
            $orderModel = new Order();
            
            $today = date('Y-m-d');
            $orders = $orderModel->paginateOrders(1, 10, "DATE(created_at) = :today", ['today' => $today]);
            
            if (empty($orders['data'])) {
                $this->sendMessage("今日暂无订单", 'Markdown', $chatId);
                return;
            }
            
            $text = "📋 *今日订单列表*\n\n";
            foreach ($orders['data'] as $order) {
                $status = $this->getOrderStatusName($order['order_status']);
                $text .= "• `{$order['order_no']}` - ¥" . number_format($order['final_amount'], 2) . " - {$status}\n";
            }
            
            $this->sendMessage($text, 'Markdown', $chatId);
            
        } catch (Exception $e) {
            $this->sendMessage("获取今日订单失败: " . $e->getMessage(), 'Markdown', $chatId);
        }
    }
    
    /**
     * 处理订单
     */
    private function processOrder($orderId, $chatId, $messageId) {
        try {
            require_once __DIR__ . '/../models/Order.php';
            $orderModel = new Order();
            
            $order = $orderModel->find($orderId);
            if (!$order) {
                $this->sendMessage("订单不存在", 'Markdown', $chatId);
                return;
            }
            
            // 这里可以添加订单处理逻辑
            $this->sendMessage("订单 {$order['order_no']} 已标记为处理中", 'Markdown', $chatId);
            
        } catch (Exception $e) {
            $this->sendMessage("处理订单失败: " . $e->getMessage(), 'Markdown', $chatId);
        }
    }
    
    /**
     * 替换占位符
     */
    private function replacePlaceholders($template, $data) {
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }
    
    /**
     * 获取支付方式名称
     */
    private function getPaymentTypeName($type) {
        $types = [
            'wechat' => '微信支付',
            'alipay' => '支付宝',
            'unionpay' => '银联支付',
            'stripe' => 'Stripe'
        ];
        return $types[$type] ?? $type;
    }
    
    /**
     * 获取订单状态名称
     */
    private function getOrderStatusName($status) {
        $statuses = [
            'pending' => '待处理',
            'confirmed' => '已确认',
            'shipped' => '已发货',
            'delivered' => '已完成',
            'cancelled' => '已取消'
        ];
        return $statuses[$status] ?? $status;
    }
    
    /**
     * 发送HTTP请求
     */
    private function makeRequest($method, $data = []) {
        $url = $this->apiUrl . '/' . $method;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: " . $httpCode);
        }
        
        $result = json_decode($response, true);
        
        if (!$result['ok']) {
            throw new Exception("Telegram API Error: " . ($result['description'] ?? 'Unknown error'));
        }
        
        // 记录日志
        $this->logRequest($method, $data, $result);
        
        return $result;
    }
    
    /**
     * 记录请求日志
     */
    private function logRequest($method, $data, $result) {
        if (!$this->config['log']['enabled']) {
            return;
        }
        
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $method,
            'data' => $data,
            'result' => $result
        ];
        
        $logFile = $this->config['log']['file'];
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }
}
?>
