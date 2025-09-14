<?php
/**
 * NextsPay 支付集成处理
 * 集成微信支付、支付宝、银联、Stripe等支付方式
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// 引入必要的类文件
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Model.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../classes/NotificationService.php';

class PaymentProcessor {
    private $config;
    private $db;
    private $orderModel;
    private $notificationService;
    
    public function __construct() {
        $this->config = $this->loadPaymentConfig();
        $this->db = Database::getInstance();
        $this->orderModel = new Order();
        $this->notificationService = new NotificationService();
    }
    
    /**
     * 加载支付配置
     */
    private function loadPaymentConfig() {
        $file = 'data/payment_config.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }
        
        return [
            'wechat' => [
                'enabled' => true,
                'app_id' => 'your_wechat_app_id',
                'mch_id' => 'your_wechat_mch_id',
                'api_key' => 'your_wechat_api_key',
                'cert_path' => ''
            ],
            'alipay' => [
                'enabled' => true,
                'app_id' => 'your_alipay_app_id',
                'private_key' => 'your_alipay_private_key',
                'public_key' => 'your_alipay_public_key'
            ],
            'unionpay' => [
                'enabled' => true,
                'mer_id' => 'your_unionpay_mer_id',
                'cert_id' => 'your_unionpay_cert_id',
                'private_key' => 'your_unionpay_private_key'
            ],
            'stripe' => [
                'enabled' => true,
                'publishable_key' => 'pk_test_your_stripe_publishable_key',
                'secret_key' => 'sk_test_your_stripe_secret_key',
                'webhook_secret' => 'whsec_your_webhook_secret'
            ]
        ];
    }
    
    /**
     * 创建支付订单
     */
    public function createPaymentOrder() {
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            // 验证订单数据
            if (empty($data['items']) || empty($data['payment_type'])) {
                return $this->error('订单数据不完整');
            }
            
            // 生成订单号
            $orderNo = 'NP' . date('YmdHis') . rand(1000, 9999);
            
            // 计算总金额
            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                $totalAmount += $item['price'] * $item['quantity'];
            }
            
            $orderData = [
                'order_no' => $orderNo,
                'items' => $data['items'],
                'total_amount' => $totalAmount,
                'payment_type' => $data['payment_type'],
                'subject' => $data['subject'] ?? '商品购买',
                'body' => $data['body'] ?? '商品描述',
                'status' => 'pending',
                'create_time' => date('Y-m-d H:i:s'),
                'expire_time' => date('Y-m-d H:i:s', time() + 1800) // 30分钟过期
            ];
            
            // 保存订单到数据库
            $order = $this->orderModel->createOrder($orderData, $data['items']);
            
            // 发送新订单通知
            try {
                $this->notificationService->sendNewOrderNotification($order);
            } catch (Exception $e) {
                error_log('发送新订单通知失败: ' . $e->getMessage());
            }
            
            // 根据支付方式创建支付
            $paymentResult = $this->createPaymentByType($orderData);
            
            if ($paymentResult['success']) {
                return $this->success([
                    'order_no' => $orderNo,
                    'payment_url' => $paymentResult['payment_url'],
                    'qr_code' => $paymentResult['qr_code'] ?? '',
                    'expire_time' => $orderData['expire_time']
                ], '订单创建成功');
            } else {
                return $this->error($paymentResult['message']);
            }
            
        } catch (Exception $e) {
            return $this->error('创建支付订单失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 根据支付方式创建支付
     */
    private function createPaymentByType($orderData) {
        $paymentType = $orderData['payment_type'];
        
        switch ($paymentType) {
            case 'wechat':
                return $this->createWechatPayment($orderData);
            case 'alipay':
                return $this->createAlipayPayment($orderData);
            case 'unionpay':
                return $this->createUnionpayPayment($orderData);
            case 'stripe':
                return $this->createStripePayment($orderData);
            default:
                return ['success' => false, 'message' => '不支持的支付方式'];
        }
    }
    
    /**
     * 创建微信支付
     */
    private function createWechatPayment($orderData) {
        try {
            $config = $this->config['wechat'];
            
            if (!$config['enabled']) {
                return ['success' => false, 'message' => '微信支付未启用'];
            }
            
            // 微信支付参数
            $params = [
                'appid' => $config['app_id'],
                'mch_id' => $config['mch_id'],
                'nonce_str' => $this->generateNonceStr(),
                'body' => $orderData['subject'],
                'out_trade_no' => $orderData['order_no'],
                'total_fee' => intval($orderData['total_amount'] * 100), // 转换为分
                'spbill_create_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'notify_url' => 'https://api.bohai.chat/web/notify.php?type=wechat',
                'trade_type' => 'NATIVE'
            ];
            
            // 生成签名
            $params['sign'] = $this->generateWechatSign($params, $config['api_key']);
            
            // 调用微信支付API
            $result = $this->callWechatAPI($params);
            
            if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
                return [
                    'success' => true,
                    'payment_url' => $result['code_url'],
                    'qr_code' => $result['code_url']
                ];
            } else {
                return ['success' => false, 'message' => $result['return_msg'] ?? '微信支付创建失败'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => '微信支付创建失败: ' . $e->getMessage()];
        }
    }
    
    /**
     * 创建支付宝支付
     */
    private function createAlipayPayment($orderData) {
        try {
            $config = $this->config['alipay'];
            
            if (!$config['enabled']) {
                return ['success' => false, 'message' => '支付宝支付未启用'];
            }
            
            // 支付宝支付参数
            $params = [
                'app_id' => $config['app_id'],
                'method' => 'alipay.trade.precreate',
                'charset' => 'utf-8',
                'sign_type' => 'RSA2',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'notify_url' => 'https://api.bohai.chat/web/notify.php?type=alipay',
                'biz_content' => json_encode([
                    'out_trade_no' => $orderData['order_no'],
                    'total_amount' => $orderData['total_amount'],
                    'subject' => $orderData['subject'],
                    'body' => $orderData['body']
                ])
            ];
            
            // 生成签名
            $params['sign'] = $this->generateAlipaySign($params, $config['private_key']);
            
            // 调用支付宝API
            $result = $this->callAlipayAPI($params);
            
            if (isset($result['alipay_trade_precreate_response']['code']) && 
                $result['alipay_trade_precreate_response']['code'] === '10000') {
                $response = $result['alipay_trade_precreate_response'];
                return [
                    'success' => true,
                    'payment_url' => $response['qr_code'],
                    'qr_code' => $response['qr_code']
                ];
            } else {
                return ['success' => false, 'message' => '支付宝支付创建失败'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => '支付宝支付创建失败: ' . $e->getMessage()];
        }
    }
    
    /**
     * 创建银联支付
     */
    private function createUnionpayPayment($orderData) {
        try {
            $config = $this->config['unionpay'];
            
            if (!$config['enabled']) {
                return ['success' => false, 'message' => '银联支付未启用'];
            }
            
            // 银联支付参数
            $params = [
                'version' => '5.1.0',
                'encoding' => 'utf-8',
                'certId' => $config['cert_id'],
                'signMethod' => '01',
                'txnType' => '01',
                'txnSubType' => '01',
                'bizType' => '000201',
                'channelType' => '07',
                'accessType' => '0',
                'merId' => $config['mer_id'],
                'orderId' => $orderData['order_no'],
                'txnTime' => date('YmdHis'),
                'txnAmt' => intval($orderData['total_amount'] * 100), // 转换为分
                'currencyCode' => '156',
                'frontUrl' => 'https://bohai.chat/success.html',
                'backUrl' => 'https://api.bohai.chat/web/notify.php?type=unionpay'
            ];
            
            // 生成签名
            $params['signature'] = $this->generateUnionpaySign($params, $config['private_key']);
            
            // 构建支付URL
            $paymentUrl = 'https://gateway.95516.com/gateway/api/appTransReq.do?' . http_build_query($params);
            
            return [
                'success' => true,
                'payment_url' => $paymentUrl,
                'qr_code' => $paymentUrl
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => '银联支付创建失败: ' . $e->getMessage()];
        }
    }
    
    /**
     * 创建Stripe支付
     */
    private function createStripePayment($orderData) {
        try {
            $config = $this->config['stripe'];
            
            if (!$config['enabled']) {
                return ['success' => false, 'message' => 'Stripe支付未启用'];
            }
            
            // 使用Stripe API创建支付会话
            $stripe = new \Stripe\StripeClient($config['secret_key']);
            
            $session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => array_map(function($item) {
                    return [
                        'price_data' => [
                            'currency' => 'cny',
                            'product_data' => [
                                'name' => $item['name'],
                                'description' => $item['description'] ?? '',
                            ],
                            'unit_amount' => intval($item['price'] * 100), // 转换为分
                        ],
                        'quantity' => $item['quantity'],
                    ];
                }, $orderData['items']),
                'mode' => 'payment',
                'success_url' => 'https://bohai.chat/success.html?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'https://bohai.chat/products.html',
                'metadata' => [
                    'order_no' => $orderData['order_no']
                ]
            ]);
            
            return [
                'success' => true,
                'payment_url' => $session->url,
                'session_id' => $session->id
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Stripe支付创建失败: ' . $e->getMessage()];
        }
    }
    
    /**
     * 查询订单状态
     */
    public function queryOrderStatus() {
        try {
            $orderNo = $_GET['order_no'] ?? '';
            
            if (empty($orderNo)) {
                return $this->error('订单号不能为空');
            }
            
            $order = $this->orderModel->getByOrderNo($orderNo);
            
            if (!$order) {
                return $this->error('订单不存在');
            }
            
            return $this->success($order);
            
        } catch (Exception $e) {
            return $this->error('查询订单状态失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 生成微信支付签名
     */
    private function generateWechatSign($params, $apiKey) {
        ksort($params);
        $string = '';
        foreach ($params as $key => $value) {
            if ($value !== '') {
                $string .= $key . '=' . $value . '&';
            }
        }
        $string .= 'key=' . $apiKey;
        return strtoupper(md5($string));
    }
    
    /**
     * 生成支付宝签名
     */
    private function generateAlipaySign($params, $privateKey) {
        ksort($params);
        $string = '';
        foreach ($params as $key => $value) {
            if ($value !== '') {
                $string .= $key . '=' . $value . '&';
            }
        }
        $string = rtrim($string, '&');
        
        $privateKey = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($privateKey, 64, "\n") . "-----END PRIVATE KEY-----";
        openssl_sign($string, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }
    
    /**
     * 生成银联签名
     */
    private function generateUnionpaySign($params, $privateKey) {
        ksort($params);
        $string = '';
        foreach ($params as $key => $value) {
            if ($value !== '') {
                $string .= $key . '=' . $value . '&';
            }
        }
        $string = rtrim($string, '&');
        
        $privateKey = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($privateKey, 64, "\n") . "-----END PRIVATE KEY-----";
        openssl_sign($string, $signature, $privateKey, OPENSSL_ALGO_SHA1);
        return base64_encode($signature);
    }
    
    /**
     * 调用微信支付API
     */
    private function callWechatAPI($params) {
        $xml = $this->arrayToXml($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.mch.weixin.qq.com/pay/unifiedorder');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $this->xmlToArray($response);
    }
    
    /**
     * 调用支付宝API
     */
    private function callAlipayAPI($params) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://openapi.alipay.com/gateway.do');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * 数组转XML
     */
    private function arrayToXml($array) {
        $xml = '<xml>';
        foreach ($array as $key => $value) {
            $xml .= "<{$key}>{$value}</{$key}>";
        }
        $xml .= '</xml>';
        return $xml;
    }
    
    /**
     * XML转数组
     */
    private function xmlToArray($xml) {
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data ?: [];
    }
    
    /**
     * 生成随机字符串
     */
    private function generateNonceStr($length = 32) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    
    /**
     * 保存订单
     */
    private function saveOrder($orderData) {
        $orders = $this->getOrders();
        $orders[] = $orderData;
        
        $this->ensureDirectory('data');
        file_put_contents('data/orders.json', json_encode($orders, JSON_PRETTY_PRINT));
    }
    
    /**
     * 获取订单
     */
    private function getOrder($orderNo) {
        $orders = $this->getOrders();
        foreach ($orders as $order) {
            if ($order['order_no'] === $orderNo) {
                return $order;
            }
        }
        return null;
    }
    
    /**
     * 获取所有订单
     */
    private function getOrders() {
        $file = 'data/orders.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?: [];
        }
        return [];
    }
    
    /**
     * 确保目录存在
     */
    private function ensureDirectory($dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
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
$payment = new PaymentProcessor();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create_order':
        echo $payment->createPaymentOrder();
        break;
        
    case 'query_order':
        echo $payment->queryOrderStatus();
        break;
        
    default:
        echo $payment->error('无效的请求', 404);
        break;
}
?>
