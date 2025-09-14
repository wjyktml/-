<?php
/**
 * Telegram 管理API
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/TelegramBot.php';
require_once __DIR__ . '/../classes/NotificationService.php';

class TelegramAPI {
    private $db;
    private $telegramBot;
    private $notificationService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->telegramBot = new TelegramBot();
        $this->notificationService = new NotificationService();
    }
    
    /**
     * 获取Telegram配置
     */
    public function getConfig() {
        try {
            $config = [];
            
            // 从数据库获取配置
            $settings = $this->db->fetchAll("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'telegram_%'");
            foreach ($settings as $setting) {
                $key = str_replace('telegram_', '', $setting['setting_key']);
                $config[$key] = $setting['setting_value'];
            }
            
            // 检查配置状态
            $config['status'] = $this->checkBotStatus();
            
            return $this->success($config);
        } catch (Exception $e) {
            return $this->error('获取Telegram配置失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 保存Telegram配置
     */
    public function saveConfig() {
        try {
    $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            $configs = [
                'telegram_enabled' => $data['enabled'] ? 'true' : 'false',
                'telegram_bot_token' => $data['bot_token'] ?? '',
                'telegram_chat_id' => $data['chat_id'] ?? ''
            ];
            
            foreach ($configs as $key => $value) {
                $this->db->query(
                    "INSERT INTO system_settings (setting_key, setting_value) VALUES (:key, :value) 
                     ON DUPLICATE KEY UPDATE setting_value = :value",
                    ['key' => $key, 'value' => $value]
                );
            }
            
            return $this->success([], 'Telegram配置保存成功');
        } catch (Exception $e) {
            return $this->error('保存Telegram配置失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 测试Telegram连接
     */
    public function testConnection() {
        try {
            $result = $this->telegramBot->getMe();
            
            if ($result && $result['ok']) {
                $botInfo = $result['result'];
                return $this->success([
                    'connected' => true,
                    'bot_info' => $botInfo
                ], 'Telegram连接成功');
            } else {
                return $this->error('Telegram连接失败');
            }
        } catch (Exception $e) {
            return $this->error('Telegram连接测试失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 发送测试消息
     */
    public function sendTestMessage() {
        try {
            $message = "🤖 *NextsPay 测试消息*\n\n" .
                      "时间: " . date('Y-m-d H:i:s') . "\n" .
                      "系统: NextsPay 支付系统\n" .
                      "状态: 正常运行";
            
            $result = $this->telegramBot->sendMessage($message);
            
            if ($result && $result['ok']) {
                return $this->success([], '测试消息发送成功');
            } else {
                return $this->error('测试消息发送失败');
            }
        } catch (Exception $e) {
            return $this->error('发送测试消息失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 设置Webhook
     */
    public function setWebhook() {
        try {
            $webhookUrl = $_POST['webhook_url'] ?? '';
            
            if (empty($webhookUrl)) {
                return $this->error('Webhook URL不能为空');
            }
            
            $result = $this->telegramBot->setWebhook($webhookUrl);
            
            if ($result && $result['ok']) {
                return $this->success([], 'Webhook设置成功');
            } else {
                return $this->error('Webhook设置失败');
            }
        } catch (Exception $e) {
            return $this->error('设置Webhook失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取Webhook信息
     */
    public function getWebhookInfo() {
        try {
            $result = $this->telegramBot->getWebhookInfo();
            
            if ($result && $result['ok']) {
                return $this->success($result['result']);
            } else {
                return $this->error('获取Webhook信息失败');
            }
        } catch (Exception $e) {
            return $this->error('获取Webhook信息失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 删除Webhook
     */
    public function deleteWebhook() {
        try {
            $result = $this->telegramBot->deleteWebhook();
            
            if ($result && $result['ok']) {
                return $this->success([], 'Webhook删除成功');
            } else {
                return $this->error('Webhook删除失败');
            }
        } catch (Exception $e) {
            return $this->error('删除Webhook失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取通知统计
     */
    public function getNotificationStats() {
        try {
            $stats = $this->notificationService->getNotificationStats(7);
            return $this->success($stats);
        } catch (Exception $e) {
            return $this->error('获取通知统计失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 测试通知功能
     */
    public function testNotification() {
        try {
            $type = $_GET['type'] ?? 'system_alert';
            $result = $this->notificationService->testNotification($type);
            
            return $this->success($result, '通知测试完成');
        } catch (Exception $e) {
            return $this->error('通知测试失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 检查通知配置
     */
    public function checkNotificationConfig() {
        try {
            $config = $this->notificationService->checkNotificationConfig();
            return $this->success($config);
        } catch (Exception $e) {
            return $this->error('检查通知配置失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 发送系统状态报告
     */
    public function sendSystemStatus() {
        try {
            require_once __DIR__ . '/../models/Order.php';
            require_once __DIR__ . '/../models/Product.php';
            
            $orderModel = new Order();
            $productModel = new Product();
            
            $orderStats = $orderModel->getStats();
            $todayStats = $orderModel->getTodayStats();
            $productStats = $productModel->getStats();
            
            $message = "📊 *系统状态报告*\n\n";
            $message .= "*今日统计:*\n";
            $message .= "• 订单数: {$todayStats['total']}\n";
            $message .= "• 已支付: {$todayStats['paid']}\n";
            $message .= "• 金额: ¥" . number_format($todayStats['amount'], 2) . "\n\n";
            
            $message .= "*总体统计:*\n";
            $message .= "• 总订单: {$orderStats['total']}\n";
            $message .= "• 总金额: ¥" . number_format($orderStats['total_amount'], 2) . "\n";
            $message .= "• 商品数: {$productStats['total']}\n";
            $message .= "• 低库存: {$productStats['low_stock']}\n\n";
            
            $message .= "⏰ " . date('Y-m-d H:i:s');
            
            $result = $this->telegramBot->sendMessage($message);
            
            if ($result && $result['ok']) {
                return $this->success([], '系统状态报告发送成功');
            } else {
                return $this->error('系统状态报告发送失败');
            }
        } catch (Exception $e) {
            return $this->error('发送系统状态报告失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 检查机器人状态
     */
    private function checkBotStatus() {
        try {
            $result = $this->telegramBot->getMe();
            return $result && $result['ok'] ? 'connected' : 'disconnected';
} catch (Exception $e) {
            return 'error';
        }
    }
    
    /**
     * 成功响应
     */
    private function success($data = [], $msg = '操作成功') {
        return json_encode([
            'code' => 200,
            'msg' => $msg,
            'result' => $data,
            'timestamp' => time()
        ]);
    }
    
    /**
     * 错误响应
     */
    private function error($msg = '操作失败', $code = 400) {
        return json_encode([
            'code' => $code,
            'msg' => $msg,
            'result' => null,
            'timestamp' => time()
        ]);
    }
}

// 路由处理
$api = new TelegramAPI();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'config':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo $api->getConfig();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo $api->saveConfig();
        }
        break;
        
    case 'test_connection':
        echo $api->testConnection();
        break;
        
    case 'send_test':
        echo $api->sendTestMessage();
        break;
        
    case 'set_webhook':
        echo $api->setWebhook();
        break;
        
    case 'get_webhook':
        echo $api->getWebhookInfo();
        break;
        
    case 'delete_webhook':
        echo $api->deleteWebhook();
        break;
        
    case 'notification_stats':
        echo $api->getNotificationStats();
        break;
        
    case 'test_notification':
        echo $api->testNotification();
        break;
        
    case 'check_config':
        echo $api->checkNotificationConfig();
        break;
        
    case 'system_status':
        echo $api->sendSystemStatus();
        break;
        
    default:
        echo $api->error('无效的请求', 404);
        break;
}
?>
                $key = str_replace('telegram_', '', $setting['setting_key']);
                $config[$key] = $setting['setting_value'];
            }
            
            // 检查配置状态
            $config['status'] = $this->checkBotStatus();
            
            return $this->success($config);
        } catch (Exception $e) {
            return $this->error('获取Telegram配置失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 保存Telegram配置
     */
    public function saveConfig() {
        try {
    $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            $configs = [
                'telegram_enabled' => $data['enabled'] ? 'true' : 'false',
                'telegram_bot_token' => $data['bot_token'] ?? '',
                'telegram_chat_id' => $data['chat_id'] ?? ''
            ];
            
            foreach ($configs as $key => $value) {
                $this->db->query(
                    "INSERT INTO system_settings (setting_key, setting_value) VALUES (:key, :value) 
                     ON DUPLICATE KEY UPDATE setting_value = :value",
                    ['key' => $key, 'value' => $value]
                );
            }
            
            return $this->success([], 'Telegram配置保存成功');
        } catch (Exception $e) {
            return $this->error('保存Telegram配置失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 测试Telegram连接
     */
    public function testConnection() {
        try {
            $result = $this->telegramBot->getMe();
            
            if ($result && $result['ok']) {
                $botInfo = $result['result'];
                return $this->success([
                    'connected' => true,
                    'bot_info' => $botInfo
                ], 'Telegram连接成功');
            } else {
                return $this->error('Telegram连接失败');
            }
        } catch (Exception $e) {
            return $this->error('Telegram连接测试失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 发送测试消息
     */
    public function sendTestMessage() {
        try {
            $message = "🤖 *NextsPay 测试消息*\n\n" .
                      "时间: " . date('Y-m-d H:i:s') . "\n" .
                      "系统: NextsPay 支付系统\n" .
                      "状态: 正常运行";
            
            $result = $this->telegramBot->sendMessage($message);
            
            if ($result && $result['ok']) {
                return $this->success([], '测试消息发送成功');
            } else {
                return $this->error('测试消息发送失败');
            }
        } catch (Exception $e) {
            return $this->error('发送测试消息失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 设置Webhook
     */
    public function setWebhook() {
        try {
            $webhookUrl = $_POST['webhook_url'] ?? '';
            
            if (empty($webhookUrl)) {
                return $this->error('Webhook URL不能为空');
            }
            
            $result = $this->telegramBot->setWebhook($webhookUrl);
            
            if ($result && $result['ok']) {
                return $this->success([], 'Webhook设置成功');
            } else {
                return $this->error('Webhook设置失败');
            }
        } catch (Exception $e) {
            return $this->error('设置Webhook失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取Webhook信息
     */
    public function getWebhookInfo() {
        try {
            $result = $this->telegramBot->getWebhookInfo();
            
            if ($result && $result['ok']) {
                return $this->success($result['result']);
            } else {
                return $this->error('获取Webhook信息失败');
            }
        } catch (Exception $e) {
            return $this->error('获取Webhook信息失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 删除Webhook
     */
    public function deleteWebhook() {
        try {
            $result = $this->telegramBot->deleteWebhook();
            
            if ($result && $result['ok']) {
                return $this->success([], 'Webhook删除成功');
            } else {
                return $this->error('Webhook删除失败');
            }
        } catch (Exception $e) {
            return $this->error('删除Webhook失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取通知统计
     */
    public function getNotificationStats() {
        try {
            $stats = $this->notificationService->getNotificationStats(7);
            return $this->success($stats);
        } catch (Exception $e) {
            return $this->error('获取通知统计失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 测试通知功能
     */
    public function testNotification() {
        try {
            $type = $_GET['type'] ?? 'system_alert';
            $result = $this->notificationService->testNotification($type);
            
            return $this->success($result, '通知测试完成');
        } catch (Exception $e) {
            return $this->error('通知测试失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 检查通知配置
     */
    public function checkNotificationConfig() {
        try {
            $config = $this->notificationService->checkNotificationConfig();
            return $this->success($config);
        } catch (Exception $e) {
            return $this->error('检查通知配置失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 发送系统状态报告
     */
    public function sendSystemStatus() {
        try {
            require_once __DIR__ . '/../models/Order.php';
            require_once __DIR__ . '/../models/Product.php';
            
            $orderModel = new Order();
            $productModel = new Product();
            
            $orderStats = $orderModel->getStats();
            $todayStats = $orderModel->getTodayStats();
            $productStats = $productModel->getStats();
            
            $message = "📊 *系统状态报告*\n\n";
            $message .= "*今日统计:*\n";
            $message .= "• 订单数: {$todayStats['total']}\n";
            $message .= "• 已支付: {$todayStats['paid']}\n";
            $message .= "• 金额: ¥" . number_format($todayStats['amount'], 2) . "\n\n";
            
            $message .= "*总体统计:*\n";
            $message .= "• 总订单: {$orderStats['total']}\n";
            $message .= "• 总金额: ¥" . number_format($orderStats['total_amount'], 2) . "\n";
            $message .= "• 商品数: {$productStats['total']}\n";
            $message .= "• 低库存: {$productStats['low_stock']}\n\n";
            
            $message .= "⏰ " . date('Y-m-d H:i:s');
            
            $result = $this->telegramBot->sendMessage($message);
            
            if ($result && $result['ok']) {
                return $this->success([], '系统状态报告发送成功');
            } else {
                return $this->error('系统状态报告发送失败');
            }
        } catch (Exception $e) {
            return $this->error('发送系统状态报告失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 检查机器人状态
     */
    private function checkBotStatus() {
        try {
            $result = $this->telegramBot->getMe();
            return $result && $result['ok'] ? 'connected' : 'disconnected';
} catch (Exception $e) {
            return 'error';
        }
    }
    
    /**
     * 成功响应
     */
    private function success($data = [], $msg = '操作成功') {
        return json_encode([
            'code' => 200,
            'msg' => $msg,
            'result' => $data,
            'timestamp' => time()
        ]);
    }
    
    /**
     * 错误响应
     */
    private function error($msg = '操作失败', $code = 400) {
        return json_encode([
            'code' => $code,
            'msg' => $msg,
            'result' => null,
            'timestamp' => time()
        ]);
    }
}

// 路由处理
$api = new TelegramAPI();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'config':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo $api->getConfig();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo $api->saveConfig();
        }
        break;
        
    case 'test_connection':
        echo $api->testConnection();
        break;
        
    case 'send_test':
        echo $api->sendTestMessage();
        break;
        
    case 'set_webhook':
        echo $api->setWebhook();
        break;
        
    case 'get_webhook':
        echo $api->getWebhookInfo();
        break;
        
    case 'delete_webhook':
        echo $api->deleteWebhook();
        break;
        
    case 'notification_stats':
        echo $api->getNotificationStats();
        break;
        
    case 'test_notification':
        echo $api->testNotification();
        break;
        
    case 'check_config':
        echo $api->checkNotificationConfig();
        break;
        
    case 'system_status':
        echo $api->sendSystemStatus();
        break;
        
    default:
        echo $api->error('无效的请求', 404);
        break;
}
?>
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
