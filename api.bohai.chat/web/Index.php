<?php
/**
 * NextsPay API 接口文件
 * 处理前端请求的主要接口
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

class NextsPayAPI {
    private $config;
    
    public function __construct() {
        $this->config = [
            'default_shop_key' => 'demo_shop_key_123',
            'h5_url' => 'https://h5.bohai.chat',
            'api_version' => '1.0.0'
        ];
    }
    
    /**
     * 获取系统设置
     */
    public function getSetting() {
        try {
            $setting = [
                'default_shop_key' => $this->config['default_shop_key'],
                'h5_url' => $this->config['h5_url'],
                'api_version' => $this->config['api_version'],
                'supported_payments' => [
                    'wechat' => '微信支付',
                    'alipay' => '支付宝',
                    'unionpay' => '银联支付',
                    'apple_pay' => 'Apple Pay'
                ],
                'contact_info' => [
                    'phone' => '400-123-4567',
                    'email' => 'support@bohai.chat',
                    'address' => '北京市朝阳区xxx大厦'
                ]
            ];
            
            return $this->success($setting);
        } catch (Exception $e) {
            return $this->error('获取设置失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 处理联系表单提交
     */
    public function contact() {
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            // 验证必填字段
            if (empty($data['name']) || empty($data['mobile'])) {
                return $this->error('姓名和手机号不能为空');
            }
            
            // 验证手机号格式
            if (!preg_match('/^1[3456789]\d{9}$/', $data['mobile'])) {
                return $this->error('手机号格式不正确');
            }
            
            // 保存联系信息（这里可以保存到数据库）
            $contactData = [
                'name' => $data['name'],
                'mobile' => $data['mobile'],
                'email' => $data['email'] ?? '',
                'hy' => $data['hy'] ?? '',
                'remark' => $data['remark'] ?? '',
                'create_time' => date('Y-m-d H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ];
            
            // 这里可以添加数据库保存逻辑
            // $this->saveContact($contactData);
            
            // 发送邮件通知（可选）
            // $this->sendNotification($contactData);
            
            return $this->success([], '提交成功，我们会尽快与您联系！');
            
        } catch (Exception $e) {
            return $this->error('提交失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取支付配置
     */
    public function getPaymentConfig() {
        try {
            $config = [
                'merchant_id' => 'demo_merchant_123',
                'app_id' => 'demo_app_456',
                'api_key' => 'demo_api_key_789',
                'notify_url' => 'https://api.bohai.chat/web/notify.php',
                'return_url' => 'https://bohai.chat/success.html',
                'sandbox' => true
            ];
            
            return $this->success($config);
        } catch (Exception $e) {
            return $this->error('获取支付配置失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 创建支付订单
     */
    public function createOrder() {
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            // 验证订单数据
            if (empty($data['amount']) || empty($data['payment_type'])) {
                return $this->error('订单金额和支付方式不能为空');
            }
            
            // 生成订单号
            $orderNo = 'NP' . date('YmdHis') . rand(1000, 9999);
            
            $orderData = [
                'order_no' => $orderNo,
                'amount' => $data['amount'],
                'payment_type' => $data['payment_type'],
                'subject' => $data['subject'] ?? 'NextsPay订单',
                'body' => $data['body'] ?? '商品描述',
                'status' => 'pending',
                'create_time' => date('Y-m-d H:i:s'),
                'expire_time' => date('Y-m-d H:i:s', time() + 1800) // 30分钟过期
            ];
            
            // 这里可以保存到数据库
            // $this->saveOrder($orderData);
            
            return $this->success($orderData, '订单创建成功');
            
        } catch (Exception $e) {
            return $this->error('创建订单失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 查询订单状态
     */
    public function queryOrder() {
        try {
            $orderNo = $_GET['order_no'] ?? '';
            
            if (empty($orderNo)) {
                return $this->error('订单号不能为空');
            }
            
            // 这里应该从数据库查询订单状态
            $orderData = [
                'order_no' => $orderNo,
                'status' => 'success', // pending, success, failed, expired
                'amount' => 100.00,
                'payment_type' => 'wechat',
                'pay_time' => date('Y-m-d H:i:s')
            ];
            
            return $this->success($orderData);
            
        } catch (Exception $e) {
            return $this->error('查询订单失败: ' . $e->getMessage());
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
    
    /**
     * 保存联系信息到数据库（示例）
     */
    private function saveContact($data) {
        // 这里可以添加数据库保存逻辑
        // 例如使用PDO或mysqli连接数据库
        error_log('Contact saved: ' . json_encode($data));
    }
    
    /**
     * 发送通知邮件（示例）
     */
    private function sendNotification($data) {
        // 这里可以添加邮件发送逻辑
        error_log('Notification sent: ' . json_encode($data));
    }
}

// 路由处理
$api = new NextsPayAPI();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'setting':
        echo $api->getSetting();
        break;
        
    case 'contact':
        echo $api->contact();
        break;
        
    case 'payment_config':
        echo $api->getPaymentConfig();
        break;
        
    case 'create_order':
        echo $api->createOrder();
        break;
        
    case 'query_order':
        echo $api->queryOrder();
        break;
        
    default:
        echo $api->error('无效的请求', 404);
        break;
}
?>
