<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Model.php';

/**
 * 分类模型
 */
class Category extends Model {
    protected $table = 'categories';
    protected $fillable = ['name', 'description', 'image', 'sort_order', 'status'];
    
    /**
     * 获取分类及其商品数量
     */
    public function getWithProductCount($id) {
        $sql = "SELECT c.*, COUNT(p.id) as product_count 
                FROM `{$this->table}` c 
                LEFT JOIN `products` p ON c.id = p.category_id AND p.status = 'active'
                WHERE c.id = :id 
                GROUP BY c.id";
        
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    /**
     * 获取所有分类及其商品数量
     */
    public function getAllWithProductCount() {
        $sql = "SELECT c.*, COUNT(p.id) as product_count 
                FROM `{$this->table}` c 
                LEFT JOIN `products` p ON c.id = p.category_id AND p.status = 'active'
                WHERE c.status = 'active'
                GROUP BY c.id 
                ORDER BY c.sort_order ASC, c.created_at ASC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * 获取活跃分类
     */
    public function getActive() {
        return $this->where('status', 'active');
    }
    
    /**
     * 检查分类下是否有商品
     */
    public function hasProducts($id) {
        $sql = "SELECT COUNT(*) as count FROM `products` WHERE category_id = :id";
        $result = $this->db->fetch($sql, ['id' => $id]);
        return $result['count'] > 0;
    }
    
    /**
     * 删除分类（检查是否有商品）
     */
    public function delete($id) {
        if ($this->hasProducts($id)) {
            throw new Exception('该分类下还有商品，无法删除');
        }
        
        return parent::delete($id);
    }
}
?>
