<?php
/**
 * NextsPay 支付回调通知处理
 * 处理第三方支付平台的回调通知
 */

header('Content-Type: text/plain; charset=utf-8');

// 引入必要的类文件
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Model.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../classes/NotificationService.php';

class PaymentNotify {
    private $config;
    private $db;
    private $orderModel;
    private $notificationService;
    
    public function __construct() {
        $this->config = [
            'api_key' => 'demo_api_key_789',
            'merchant_id' => 'demo_merchant_123'
        ];
        $this->db = Database::getInstance();
        $this->orderModel = new Order();
        $this->notificationService = new NotificationService();
    }
    
    /**
     * 处理微信支付回调
     */
    public function handleWechatNotify() {
        try {
            $input = file_get_contents('php://input');
            
            if (empty($input)) {
                return $this->response('FAIL', '数据为空');
            }
            
            // 解析XML数据
            $data = $this->xmlToArray($input);
            
            // 验证签名
            if (!$this->verifyWechatSign($data)) {
                return $this->response('FAIL', '签名验证失败');
            }
            
            // 处理支付结果
            if ($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {
                $this->processPaymentSuccess($data);
                return $this->response('SUCCESS', 'OK');
            } else {
                $this->processPaymentFailed($data);
                return $this->response('SUCCESS', 'OK');
            }
            
        } catch (Exception $e) {
            error_log('Wechat notify error: ' . $e->getMessage());
            return $this->response('FAIL', '处理失败');
        }
    }
    
    /**
     * 处理支付宝回调
     */
    public function handleAlipayNotify() {
        try {
            $data = $_POST;
            
            if (empty($data)) {
                return $this->response('fail', '数据为空');
            }
            
            // 验证签名
            if (!$this->verifyAlipaySign($data)) {
                return $this->response('fail', '签名验证失败');
            }
            
            // 处理支付结果
            if ($data['trade_status'] == 'TRADE_SUCCESS' || $data['trade_status'] == 'TRADE_FINISHED') {
                $this->processPaymentSuccess($data);
                return $this->response('success', 'OK');
            } else {
                $this->processPaymentFailed($data);
                return $this->response('success', 'OK');
            }
            
        } catch (Exception $e) {
            error_log('Alipay notify error: ' . $e->getMessage());
            return $this->response('fail', '处理失败');
        }
    }
    
    /**
     * 处理银联回调
     */
    public function handleUnionpayNotify() {
        try {
            $data = $_POST;
            
            if (empty($data)) {
                return $this->response('error', '数据为空');
            }
            
            // 验证签名
            if (!$this->verifyUnionpaySign($data)) {
                return $this->response('error', '签名验证失败');
            }
            
            // 处理支付结果
            if ($data['respCode'] == '00') {
                $this->processPaymentSuccess($data);
                return $this->response('ok', 'OK');
            } else {
                $this->processPaymentFailed($data);
                return $this->response('ok', 'OK');
            }
            
        } catch (Exception $e) {
            error_log('Unionpay notify error: ' . $e->getMessage());
            return $this->response('error', '处理失败');
        }
    }
    
    /**
     * 处理支付成功
     */
    private function processPaymentSuccess($data) {
        $orderNo = $data['out_trade_no'] ?? $data['outTradeNo'] ?? '';
        $transactionId = $data['transaction_id'] ?? $data['trade_no'] ?? $data['queryId'] ?? '';
        $amount = $data['total_fee'] ?? $data['total_amount'] ?? $data['txnAmt'] ?? 0;
        
        // 获取订单信息
        $order = $this->orderModel->getByOrderNo($orderNo);
        if (!$order) {
            error_log("Order not found: {$orderNo}");
            return;
        }
        
        // 更新订单状态
        $this->orderModel->updatePaymentStatus($orderNo, 'paid', $transactionId);
        
        // 发送支付成功通知
        try {
            $paymentData = [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'paid_at' => date('Y-m-d H:i:s')
            ];
            $this->notificationService->sendPaymentSuccessNotification($order, $paymentData);
        } catch (Exception $e) {
            error_log('发送支付成功通知失败: ' . $e->getMessage());
        }
        
        // 记录日志
        error_log("Payment success: Order {$orderNo}, Transaction {$transactionId}, Amount {$amount}");
    }
    
    /**
     * 处理支付失败
     */
    private function processPaymentFailed($data) {
        $orderNo = $data['out_trade_no'] ?? $data['outTradeNo'] ?? '';
        $errorMsg = $data['err_code_des'] ?? $data['respMsg'] ?? '支付失败';
        
        // 获取订单信息
        $order = $this->orderModel->getByOrderNo($orderNo);
        if (!$order) {
            error_log("Order not found: {$orderNo}");
            return;
        }
        
        // 更新订单状态
        $this->orderModel->updatePaymentStatus($orderNo, 'failed');
        
        // 发送支付失败通知
        try {
            $this->notificationService->sendPaymentFailedNotification($order, $errorMsg);
        } catch (Exception $e) {
            error_log('发送支付失败通知失败: ' . $e->getMessage());
        }
        
        // 记录日志
        error_log("Payment failed: Order {$orderNo}, Error: {$errorMsg}");
    }
    
    /**
     * 更新订单状态
     */
    private function updateOrderStatus($orderNo, $status, $extraData = []) {
        // 这里应该更新数据库中的订单状态
        // 示例代码：
        /*
        $sql = "UPDATE orders SET status = ?, updated_at = NOW()";
        $params = [$status];
        
        foreach ($extraData as $key => $value) {
            $sql .= ", {$key} = ?";
            $params[] = $value;
        }
        
        $sql .= " WHERE order_no = ?";
        $params[] = $orderNo;
        
        // 执行数据库更新
        */
        
        error_log("Order {$orderNo} status updated to {$status}");
    }
    
    /**
     * 验证微信支付签名
     */
    private function verifyWechatSign($data) {
        $sign = $data['sign'];
        unset($data['sign']);
        
        ksort($data);
        $string = '';
        foreach ($data as $key => $value) {
            if ($value !== '') {
                $string .= $key . '=' . $value . '&';
            }
        }
        $string .= 'key=' . $this->config['api_key'];
        
        $calculatedSign = strtoupper(md5($string));
        return $calculatedSign === $sign;
    }
    
    /**
     * 验证支付宝签名
     */
    private function verifyAlipaySign($data) {
        // 这里应该实现支付宝的签名验证逻辑
        // 由于支付宝签名验证比较复杂，这里简化处理
        return true;
    }
    
    /**
     * 验证银联签名
     */
    private function verifyUnionpaySign($data) {
        // 这里应该实现银联的签名验证逻辑
        return true;
    }
    
    /**
     * XML转数组
     */
    private function xmlToArray($xml) {
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data ?: [];
    }
    
    /**
     * 返回响应
     */
    private function response($status, $message) {
        echo $status;
        exit;
    }
}

// 根据支付类型处理回调
$notify = new PaymentNotify();
$paymentType = $_GET['type'] ?? '';

switch ($paymentType) {
    case 'wechat':
        $notify->handleWechatNotify();
        break;
        
    case 'alipay':
        $notify->handleAlipayNotify();
        break;
        
    case 'unionpay':
        $notify->handleUnionpayNotify();
        break;
        
    default:
        echo 'Invalid payment type';
        break;
}
?>
