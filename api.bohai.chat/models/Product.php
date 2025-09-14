<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Model.php';

/**
 * 商品模型
 */
class Product extends Model {
    protected $table = 'products';
    protected $fillable = [
        'name', 'description', 'category_id', 'price', 'original_price', 
        'stock', 'image', 'images', 'weight', 'dimensions', 'sku', 
        'status', 'is_featured', 'sort_order'
    ];
    
    /**
     * 获取商品及其分类信息
     */
    public function getWithCategory($id) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM `{$this->table}` p 
                LEFT JOIN `categories` c ON p.category_id = c.id 
                WHERE p.`{$this->primaryKey}` = :id";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    /**
     * 获取商品列表（带分类信息）
     */
    public function getListWithCategory($where = null, $params = [], $orderBy = 'p.created_at DESC') {
        $sql = "SELECT p.*, c.name as category_name 
                FROM `{$this->table}` p 
                LEFT JOIN `categories` c ON p.category_id = c.id";
        
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $sql .= " ORDER BY {$orderBy}";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * 分页获取商品列表
     */
    public function paginateWithCategory($page = 1, $perPage = 15, $where = null, $params = []) {
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` p";
        if ($where) {
            $countSql .= " WHERE {$where}";
        }
        $total = $this->db->fetch($countSql, $params)['total'];
        
        // 获取数据
        $sql = "SELECT p.*, c.name as category_name 
                FROM `{$this->table}` p 
                LEFT JOIN `categories` c ON p.category_id = c.id";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $sql .= " ORDER BY p.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        
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
    
    /**
     * 搜索商品
     */
    public function search($keyword, $categoryId = null, $page = 1, $perPage = 15) {
        $where = "p.status = 'active' AND (p.name LIKE :keyword OR p.description LIKE :keyword)";
        $params = ['keyword' => "%{$keyword}%"];
        
        if ($categoryId) {
            $where .= " AND p.category_id = :category_id";
            $params['category_id'] = $categoryId;
        }
        
        return $this->paginateWithCategory($page, $perPage, $where, $params);
    }
    
    /**
     * 获取推荐商品
     */
    public function getFeatured($limit = 10) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM `{$this->table}` p 
                LEFT JOIN `categories` c ON p.category_id = c.id 
                WHERE p.status = 'active' AND p.is_featured = 1 
                ORDER BY p.sort_order ASC, p.created_at DESC 
                LIMIT :limit";
        
        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }
    
    /**
     * 获取分类下的商品
     */
    public function getByCategory($categoryId, $limit = null) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM `{$this->table}` p 
                LEFT JOIN `categories` c ON p.category_id = c.id 
                WHERE p.category_id = :category_id AND p.status = 'active' 
                ORDER BY p.sort_order ASC, p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
            return $this->db->fetchAll($sql, ['category_id' => $categoryId, 'limit' => $limit]);
        }
        
        return $this->db->fetchAll($sql, ['category_id' => $categoryId]);
    }
    
    /**
     * 减少库存
     */
    public function decreaseStock($id, $quantity) {
        $sql = "UPDATE `{$this->table}` 
                SET stock = stock - :quantity, updated_at = NOW() 
                WHERE id = :id AND stock >= :quantity";
        
        $stmt = $this->db->query($sql, ['id' => $id, 'quantity' => $quantity]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * 增加库存
     */
    public function increaseStock($id, $quantity) {
        $sql = "UPDATE `{$this->table}` 
                SET stock = stock + :quantity, updated_at = NOW() 
                WHERE id = :id";
        
        $stmt = $this->db->query($sql, ['id' => $id, 'quantity' => $quantity]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * 检查库存是否充足
     */
    public function checkStock($id, $quantity) {
        $product = $this->find($id);
        return $product && $product['stock'] >= $quantity;
    }
    
    /**
     * 获取商品统计信息
     */
    public function getStats() {
        $total = $this->count();
        $active = $this->count("status = 'active'");
        $inactive = $this->count("status = 'inactive'");
        $lowStock = $this->count("stock <= 5");
        $outOfStock = $this->count("stock = 0");
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock
        ];
    }
}
?>
