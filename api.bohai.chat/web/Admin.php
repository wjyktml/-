<?php
/**
 * NextsPay 后台管理API接口
 * 处理商品管理、分类管理、支付配置等
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// 引入必要的类文件
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Model.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Order.php';

class AdminAPI {
    private $db;
    private $productModel;
    private $categoryModel;
    private $orderModel;
    
    public function __construct() {
        // 初始化数据库连接
        $this->db = Database::getInstance();
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->orderModel = new Order();
    }
    
    
    /**
     * 获取统计数据
     */
    public function getStats() {
        try {
            $orderStats = $this->orderModel->getStats();
            $todayStats = $this->orderModel->getTodayStats();
            $productStats = $this->productModel->getStats();
            
            $stats = [
                'total_orders' => $orderStats['total'],
                'total_revenue' => $orderStats['total_amount'],
                'total_products' => $productStats['total'],
                'total_users' => $this->getTotalUsers(),
                'today_orders' => $todayStats['total'],
                'today_revenue' => $todayStats['amount']
            ];
            
            return $this->success($stats);
        } catch (Exception $e) {
            return $this->error('获取统计数据失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 商品管理
     */
    public function getProducts() {
        try {
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 15;
            
            $products = $this->productModel->paginateWithCategory($page, $perPage);
            return $this->success($products);
        } catch (Exception $e) {
            return $this->error('获取商品列表失败: ' . $e->getMessage());
        }
    }
    
    public function saveProduct() {
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            // 验证必填字段
            if (empty($data['name']) || empty($data['price'])) {
                return $this->error('商品名称和价格不能为空');
            }
            
            $productData = [
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'category_id' => intval($data['category_id'] ?? 1),
                'price' => floatval($data['price']),
                'original_price' => floatval($data['original_price'] ?? 0),
                'stock' => intval($data['stock'] ?? 0),
                'image' => $data['image'] ?? '',
                'images' => $data['images'] ?? '',
                'weight' => floatval($data['weight'] ?? 0),
                'dimensions' => $data['dimensions'] ?? '',
                'sku' => $data['sku'] ?? '',
                'status' => $data['status'] ?? 'active',
                'is_featured' => intval($data['is_featured'] ?? 0),
                'sort_order' => intval($data['sort_order'] ?? 0)
            ];
            
            if (isset($data['id']) && $data['id']) {
                // 更新商品
                $product = $this->productModel->update($data['id'], $productData);
            } else {
                // 创建商品
                $product = $this->productModel->create($productData);
            }
            
            return $this->success($product, '商品保存成功');
            
        } catch (Exception $e) {
            return $this->error('保存商品失败: ' . $e->getMessage());
        }
    }
    
    public function deleteProduct() {
        try {
            $productId = $_GET['id'] ?? '';
            
            if (empty($productId)) {
                return $this->error('商品ID不能为空');
            }
            
            $result = $this->productModel->delete($productId);
            
            if ($result) {
                return $this->success([], '商品删除成功');
            } else {
                return $this->error('商品删除失败');
            }
            
        } catch (Exception $e) {
            return $this->error('删除商品失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 分类管理
     */
    public function getCategories() {
        try {
            $categories = $this->getCategoriesFromStorage();
            return $this->success($categories);
        } catch (Exception $e) {
            return $this->error('获取分类列表失败: ' . $e->getMessage());
        }
    }
    
    public function saveCategory() {
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            if (empty($data['name'])) {
                return $this->error('分类名称不能为空');
            }
            
            $category = [
                'id' => $data['id'] ?? $this->generateId(),
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'product_count' => $this->getCategoryProductCount($data['id'] ?? 0),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->saveCategoryToStorage($category);
            
            return $this->success($category, '分类保存成功');
            
        } catch (Exception $e) {
            return $this->error('保存分类失败: ' . $e->getMessage());
        }
    }
    
    public function deleteCategory() {
        try {
            $categoryId = $_GET['id'] ?? '';
            
            if (empty($categoryId)) {
                return $this->error('分类ID不能为空');
            }
            
            $this->deleteCategoryFromStorage($categoryId);
            
            return $this->success([], '分类删除成功');
            
        } catch (Exception $e) {
            return $this->error('删除分类失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 订单管理
     */
    public function getOrders() {
        try {
            $orders = $this->getOrdersFromStorage();
            return $this->success($orders);
        } catch (Exception $e) {
            return $this->error('获取订单列表失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 支付配置管理
     */
    public function getPaymentConfig() {
        try {
            $config = $this->getPaymentConfigFromStorage();
            return $this->success($config);
        } catch (Exception $e) {
            return $this->error('获取支付配置失败: ' . $e->getMessage());
        }
    }
    
    public function savePaymentConfig() {
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            $this->savePaymentConfigToStorage($data);
            
            return $this->success([], '支付配置保存成功');
            
        } catch (Exception $e) {
            return $this->error('保存支付配置失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 系统设置管理
     */
    public function getSystemSettings() {
        try {
            $settings = $this->getSystemSettingsFromStorage();
            return $this->success($settings);
        } catch (Exception $e) {
            return $this->error('获取系统设置失败: ' . $e->getMessage());
        }
    }
    
    public function saveSystemSettings() {
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            $this->saveSystemSettingsToStorage($data);
            
            return $this->success([], '系统设置保存成功');
            
        } catch (Exception $e) {
            return $this->error('保存系统设置失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 文件存储方法
     */
    private function getProductsFromStorage() {
        $file = 'data/products.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?: [];
        }
        return [];
    }
    
    private function saveProductToStorage($product) {
        $products = $this->getProductsFromStorage();
        
        if ($product['id']) {
            // 更新现有商品
            $index = array_search($product['id'], array_column($products, 'id'));
            if ($index !== false) {
                $products[$index] = $product;
            } else {
                $products[] = $product;
            }
        } else {
            $products[] = $product;
        }
        
        $this->ensureDirectory('data');
        file_put_contents('data/products.json', json_encode($products, JSON_PRETTY_PRINT));
    }
    
    private function deleteProductFromStorage($productId) {
        $products = $this->getProductsFromStorage();
        $products = array_filter($products, function($product) use ($productId) {
            return $product['id'] != $productId;
        });
        
        $this->ensureDirectory('data');
        file_put_contents('data/products.json', json_encode(array_values($products), JSON_PRETTY_PRINT));
    }
    
    private function getCategoriesFromStorage() {
        $file = 'data/categories.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?: [];
        }
        return [];
    }
    
    private function saveCategoryToStorage($category) {
        $categories = $this->getCategoriesFromStorage();
        
        if ($category['id']) {
            $index = array_search($category['id'], array_column($categories, 'id'));
            if ($index !== false) {
                $categories[$index] = $category;
            } else {
                $categories[] = $category;
            }
        } else {
            $categories[] = $category;
        }
        
        $this->ensureDirectory('data');
        file_put_contents('data/categories.json', json_encode($categories, JSON_PRETTY_PRINT));
    }
    
    private function deleteCategoryFromStorage($categoryId) {
        $categories = $this->getCategoriesFromStorage();
        $categories = array_filter($categories, function($category) use ($categoryId) {
            return $category['id'] != $categoryId;
        });
        
        $this->ensureDirectory('data');
        file_put_contents('data/categories.json', json_encode(array_values($categories), JSON_PRETTY_PRINT));
    }
    
    private function getOrdersFromStorage() {
        $file = 'data/orders.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?: [];
        }
        return [];
    }
    
    private function getPaymentConfigFromStorage() {
        $file = 'data/payment_config.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?: [];
        }
        
        // 返回默认配置
        return [
            'wechat' => [
                'enabled' => true,
                'app_id' => '',
                'mch_id' => '',
                'api_key' => '',
                'cert_path' => ''
            ],
            'alipay' => [
                'enabled' => true,
                'app_id' => '',
                'private_key' => '',
                'public_key' => ''
            ],
            'unionpay' => [
                'enabled' => true,
                'mer_id' => '',
                'cert_id' => '',
                'private_key' => ''
            ],
            'stripe' => [
                'enabled' => true,
                'publishable_key' => '',
                'secret_key' => '',
                'webhook_secret' => ''
            ]
        ];
    }
    
    private function savePaymentConfigToStorage($config) {
        $this->ensureDirectory('data');
        file_put_contents('data/payment_config.json', json_encode($config, JSON_PRETTY_PRINT));
    }
    
    private function getSystemSettingsFromStorage() {
        $file = 'data/system_settings.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?: [];
        }
        
        return [
            'site_name' => 'NextsPay',
            'site_description' => '专业的支付解决方案',
            'contact_email' => 'support@bohai.chat',
            'contact_phone' => '400-123-4567',
            'order_timeout' => 15,
            'maintenance_mode' => false
        ];
    }
    
    private function saveSystemSettingsToStorage($settings) {
        $this->ensureDirectory('data');
        file_put_contents('data/system_settings.json', json_encode($settings, JSON_PRETTY_PRINT));
    }
    
    /**
     * 辅助方法
     */
    private function ensureDirectory($dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    private function generateId() {
        return time() . rand(1000, 9999);
    }
    
    private function getTotalOrders() {
        $orders = $this->getOrdersFromStorage();
        return count($orders);
    }
    
    private function getTotalRevenue() {
        $orders = $this->getOrdersFromStorage();
        $total = 0;
        foreach ($orders as $order) {
            if ($order['status'] === 'success') {
                $total += $order['total_amount'];
            }
        }
        return $total;
    }
    
    private function getTotalProducts() {
        try {
            return $this->productModel->count();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getTotalUsers() {
        try {
            return $this->db->fetch("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getTodayOrders() {
        try {
            $today = date('Y-m-d');
            return $this->orderModel->count("DATE(created_at) = :today", ['today' => $today]);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getTodayRevenue() {
        try {
            $today = date('Y-m-d');
            return $this->orderModel->sum('final_amount', "DATE(created_at) = :today AND payment_status = 'paid'", ['today' => $today]);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getCategoryProductCount($categoryId) {
        try {
            return $this->productModel->count("category_id = :category_id", ['category_id' => $categoryId]);
        } catch (Exception $e) {
            return 0;
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
$api = new AdminAPI();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'stats':
        echo $api->getStats();
        break;
        
    case 'products':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo $api->getProducts();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo $api->saveProduct();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            echo $api->deleteProduct();
        }
        break;
        
    case 'categories':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo $api->getCategories();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo $api->saveCategory();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            echo $api->deleteCategory();
        }
        break;
        
    case 'orders':
        echo $api->getOrders();
        break;
        
    case 'payment_config':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo $api->getPaymentConfig();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo $api->savePaymentConfig();
        }
        break;
        
    case 'system_settings':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo $api->getSystemSettings();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo $api->saveSystemSettings();
        }
        break;
        
    default:
        echo $api->error('无效的请求', 404);
        break;
}
?>
