<?php
/**
 * 通知服务类
 * 统一处理各种通知（Telegram、邮件、短信等）
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/TelegramBot.php';

class NotificationService {
    private $telegramBot;
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->telegramBot = new TelegramBot();
    }
    
    /**
     * 发送新订单通知
     */
    public function sendNewOrderNotification($orderData) {
        $notifications = [];
        
        // Telegram通知
        try {
            $result = $this->telegramBot->sendNewOrderNotification($orderData);
            $notifications['telegram'] = $result;
        } catch (Exception $e) {
            $notifications['telegram'] = false;
            error_log('Telegram通知失败: ' . $e->getMessage());
        }
        
        // 记录通知日志
        $this->logNotification('new_order', $orderData['order_no'], $notifications);
        
        return $notifications;
    }
    
    /**
     * 发送支付成功通知
     */
    public function sendPaymentSuccessNotification($orderData, $paymentData) {
        $notifications = [];
        
        // Telegram通知
        try {
            $result = $this->telegramBot->sendPaymentSuccessNotification($orderData, $paymentData);
            $notifications['telegram'] = $result;
        } catch (Exception $e) {
            $notifications['telegram'] = false;
            error_log('Telegram通知失败: ' . $e->getMessage());
        }
        
        // 记录通知日志
        $this->logNotification('payment_success', $orderData['order_no'], $notifications);
        
        return $notifications;
    }
    
    /**
     * 发送支付失败通知
     */
    public function sendPaymentFailedNotification($orderData, $errorMessage) {
        $notifications = [];
        
        // Telegram通知
        try {
            $result = $this->telegramBot->sendPaymentFailedNotification($orderData, $errorMessage);
            $notifications['telegram'] = $result;
        } catch (Exception $e) {
            $notifications['telegram'] = false;
            error_log('Telegram通知失败: ' . $e->getMessage());
        }
        
        // 记录通知日志
        $this->logNotification('payment_failed', $orderData['order_no'], $notifications);
        
        return $notifications;
    }
    
    /**
     * 发送订单取消通知
     */
    public function sendOrderCancelledNotification($orderData, $cancelReason = '') {
        $notifications = [];
        
        // Telegram通知
        try {
            $result = $this->telegramBot->sendOrderCancelledNotification($orderData, $cancelReason);
            $notifications['telegram'] = $result;
        } catch (Exception $e) {
            $notifications['telegram'] = false;
            error_log('Telegram通知失败: ' . $e->getMessage());
        }
        
        // 记录通知日志
        $this->logNotification('order_cancelled', $orderData['order_no'], $notifications);
        
        return $notifications;
    }
    
    /**
     * 发送库存不足警告
     */
    public function sendLowStockAlert($productData, $currentStock, $minStock = 5) {
        $notifications = [];
        
        // Telegram通知
        try {
            $result = $this->telegramBot->sendLowStockAlert($productData, $currentStock, $minStock);
            $notifications['telegram'] = $result;
        } catch (Exception $e) {
            $notifications['telegram'] = false;
            error_log('Telegram通知失败: ' . $e->getMessage());
        }
        
        // 记录通知日志
        $this->logNotification('low_stock', $productData['name'], $notifications);
        
        return $notifications;
    }
    
    /**
     * 发送系统警告
     */
    public function sendSystemAlert($alertType, $message) {
        $notifications = [];
        
        // Telegram通知
        try {
            $result = $this->telegramBot->sendSystemAlert($alertType, $message);
            $notifications['telegram'] = $result;
        } catch (Exception $e) {
            $notifications['telegram'] = false;
            error_log('Telegram通知失败: ' . $e->getMessage());
        }
        
        // 记录通知日志
        $this->logNotification('system_alert', $alertType, $notifications);
        
        return $notifications;
    }
    
    /**
     * 发送订单详情通知
     */
    public function sendOrderDetails($orderData, $orderItems) {
        $notifications = [];
        
        // Telegram通知
        try {
            $result = $this->telegramBot->sendOrderDetails($orderData, $orderItems);
            $notifications['telegram'] = $result;
        } catch (Exception $e) {
            $notifications['telegram'] = false;
            error_log('Telegram通知失败: ' . $e->getMessage());
        }
        
        return $notifications;
    }
    
    /**
     * 批量发送通知
     */
    public function sendBatchNotifications($notifications) {
        $results = [];
        
        foreach ($notifications as $type => $data) {
            switch ($type) {
                case 'new_order':
                    $results[$type] = $this->sendNewOrderNotification($data);
                    break;
                case 'payment_success':
                    $results[$type] = $this->sendPaymentSuccessNotification($data['order'], $data['payment']);
                    break;
                case 'payment_failed':
                    $results[$type] = $this->sendPaymentFailedNotification($data['order'], $data['error']);
                    break;
                case 'order_cancelled':
                    $results[$type] = $this->sendOrderCancelledNotification($data['order'], $data['reason']);
                    break;
                case 'low_stock':
                    $results[$type] = $this->sendLowStockAlert($data['product'], $data['stock'], $data['min_stock']);
                    break;
                case 'system_alert':
                    $results[$type] = $this->sendSystemAlert($data['type'], $data['message']);
                    break;
            }
        }
        
        return $results;
    }
    
    /**
     * 记录通知日志
     */
    private function logNotification($type, $reference, $results) {
        try {
            $logData = [
                'type' => $type,
                'reference' => $reference,
                'results' => json_encode($results),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('notification_logs', $logData);
        } catch (Exception $e) {
            error_log('记录通知日志失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取通知统计
     */
    public function getNotificationStats($days = 7) {
        try {
            $sql = "SELECT 
                        type,
                        COUNT(*) as total,
                        SUM(CASE WHEN JSON_EXTRACT(results, '$.telegram') = true THEN 1 ELSE 0 END) as telegram_success,
                        SUM(CASE WHEN JSON_EXTRACT(results, '$.telegram') = false THEN 1 ELSE 0 END) as telegram_failed
                    FROM notification_logs 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                    GROUP BY type";
            
            return $this->db->fetchAll($sql, ['days' => $days]);
        } catch (Exception $e) {
            error_log('获取通知统计失败: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 测试通知功能
     */
    public function testNotification($type = 'system_alert') {
        $testData = [
            'type' => 'test',
            'message' => '这是一条测试通知消息'
        ];
        
        return $this->sendSystemAlert($testData['type'], $testData['message']);
    }
    
    /**
     * 检查通知配置
     */
    public function checkNotificationConfig() {
        $config = [];
        
        // 检查Telegram配置
        try {
            $telegramConfig = require_once __DIR__ . '/../config/telegram.php';
            $config['telegram'] = [
                'enabled' => $telegramConfig['enabled'],
                'bot_token' => !empty($telegramConfig['bot_token']),
                'chat_id' => !empty($telegramConfig['chat_id'])
            ];
        } catch (Exception $e) {
            $config['telegram'] = [
                'enabled' => false,
                'error' => $e->getMessage()
            ];
        }
        
        return $config;
    }
}
?>
