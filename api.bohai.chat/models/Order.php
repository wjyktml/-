<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Model.php';

/**
 * 订单模型
 */
class Order extends Model {
    protected $table = 'orders';
    protected $fillable = [
        'order_no', 'user_id', 'total_amount', 'discount_amount', 'shipping_fee', 
        'final_amount', 'payment_type', 'payment_status', 'order_status',
        'shipping_name', 'shipping_phone', 'shipping_address', 'remark',
        'paid_at', 'shipped_at', 'delivered_at', 'expire_at'
    ];
    
    /**
     * 获取订单及其商品信息
     */
    public function getWithItems($id) {
        $order = $this->find($id);
        if (!$order) {
            return null;
        }
        
        // 获取订单商品
        $sql = "SELECT * FROM `order_items` WHERE order_id = :order_id";
        $order['items'] = $this->db->fetchAll($sql, ['order_id' => $id]);
        
        return $order;
    }
    
    /**
     * 根据订单号获取订单
     */
    public function getByOrderNo($orderNo) {
        return $this->whereFirst('order_no', $orderNo);
    }
    
    /**
     * 创建订单
     */
    public function createOrder($orderData, $items) {
        try {
            $this->db->beginTransaction();
            
            // 创建订单
            $orderId = $this->db->insert($this->table, $orderData);
            
            // 创建订单商品
            foreach ($items as $item) {
                $item['order_id'] = $orderId;
                $this->db->insert('order_items', $item);
            }
            
            $this->db->commit();
            return $this->find($orderId);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * 更新支付状态
     */
    public function updatePaymentStatus($orderNo, $status, $transactionId = null) {
        $data = [
            'payment_status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($status === 'paid') {
            $data['paid_at'] = date('Y-m-d H:i:s');
            $data['order_status'] = 'confirmed';
        }
        
        $where = "order_no = :order_no";
        $params = ['order_no' => $orderNo];
        
        $affected = $this->db->update($this->table, $data, $where, $params);
        
        // 记录支付信息
        if ($affected > 0 && $transactionId) {
            $this->recordPayment($orderNo, $transactionId, $status);
        }
        
        return $affected > 0;
    }
    
    /**
     * 记录支付信息
     */
    private function recordPayment($orderNo, $transactionId, $status) {
        $order = $this->getByOrderNo($orderNo);
        if (!$order) {
            return;
        }
        
        $paymentData = [
            'order_id' => $order['id'],
            'payment_type' => $order['payment_type'],
            'transaction_id' => $transactionId,
            'amount' => $order['final_amount'],
            'status' => $status,
            'paid_at' => $status === 'paid' ? date('Y-m-d H:i:s') : null
        ];
        
        $this->db->insert('payments', $paymentData);
    }
    
    /**
     * 获取订单统计
     */
    public function getStats() {
        $total = $this->count();
        $pending = $this->count("payment_status = 'pending'");
        $paid = $this->count("payment_status = 'paid'");
        $failed = $this->count("payment_status = 'failed'");
        $refunded = $this->count("payment_status = 'refunded'");
        
        $totalAmount = $this->sum('final_amount', "payment_status = 'paid'");
        
        return [
            'total' => $total,
            'pending' => $pending,
            'paid' => $paid,
            'failed' => $failed,
            'refunded' => $refunded,
            'total_amount' => $totalAmount
        ];
    }
    
    /**
     * 获取今日订单统计
     */
    public function getTodayStats() {
        $today = date('Y-m-d');
        
        $total = $this->count("DATE(created_at) = :today", ['today' => $today]);
        $paid = $this->count("DATE(created_at) = :today AND payment_status = 'paid'", ['today' => $today]);
        $amount = $this->sum('final_amount', "DATE(created_at) = :today AND payment_status = 'paid'", ['today' => $today]);
        
        return [
            'total' => $total,
            'paid' => $paid,
            'amount' => $amount
        ];
    }
    
    /**
     * 分页获取订单列表
     */
    public function paginateOrders($page = 1, $perPage = 15, $where = null, $params = []) {
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}`";
        if ($where) {
            $countSql .= " WHERE {$where}";
        }
        $total = $this->db->fetch($countSql, $params)['total'];
        
        // 获取数据
        $sql = "SELECT * FROM `{$this->table}`";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $sql .= " ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        
        $data = $this->db->fetchAll($sql, $params);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }
}
?>
