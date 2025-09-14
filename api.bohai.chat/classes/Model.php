<?php
/**
 * 模型基类
 */

require_once __DIR__ . '/Database.php';

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $timestamps = true;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 获取所有记录
     */
    public function all($columns = ['*']) {
        $columns = is_array($columns) ? implode(', ', $columns) : $columns;
        $sql = "SELECT {$columns} FROM `{$this->table}`";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * 根据ID查找记录
     */
    public function find($id, $columns = ['*']) {
        $columns = is_array($columns) ? implode(', ', $columns) : $columns;
        $sql = "SELECT {$columns} FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    /**
     * 根据条件查找记录
     */
    public function where($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$column}` {$operator} :value";
        return $this->db->fetchAll($sql, ['value' => $value]);
    }
    
    /**
     * 查找单条记录
     */
    public function whereFirst($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$column}` {$operator} :value LIMIT 1";
        return $this->db->fetch($sql, ['value' => $value]);
    }
    
    /**
     * 创建记录
     */
    public function create($data) {
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        // 只允许填充fillable字段
        $data = $this->filterFillable($data);
        
        $id = $this->db->insert($this->table, $data);
        return $this->find($id);
    }
    
    /**
     * 更新记录
     */
    public function update($id, $data) {
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        // 只允许填充fillable字段
        $data = $this->filterFillable($data);
        
        $affected = $this->db->update($this->table, $data, "`{$this->primaryKey}` = :id", ['id' => $id]);
        return $affected > 0 ? $this->find($id) : false;
    }
    
    /**
     * 删除记录
     */
    public function delete($id) {
        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        return $this->db->query($sql, ['id' => $id])->rowCount() > 0;
    }
    
    /**
     * 分页查询
     */
    public function paginate($page = 1, $perPage = 15, $columns = ['*']) {
        $offset = ($page - 1) * $perPage;
        $columns = is_array($columns) ? implode(', ', $columns) : $columns;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}`";
        $total = $this->db->fetch($countSql)['total'];
        
        // 获取数据
        $sql = "SELECT {$columns} FROM `{$this->table}` LIMIT {$perPage} OFFSET {$offset}";
        $data = $this->db->fetchAll($sql);
        
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
     * 统计记录数
     */
    public function count($where = null, $params = []) {
        $sql = "SELECT COUNT(*) as count FROM `{$this->table}`";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
    
    /**
     * 求和
     */
    public function sum($column, $where = null, $params = []) {
        $sql = "SELECT SUM(`{$column}`) as sum FROM `{$this->table}`";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['sum'] ?? 0;
    }
    
    /**
     * 最大值
     */
    public function max($column, $where = null, $params = []) {
        $sql = "SELECT MAX(`{$column}`) as max FROM `{$this->table}`";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['max'];
    }
    
    /**
     * 最小值
     */
    public function min($column, $where = null, $params = []) {
        $sql = "SELECT MIN(`{$column}`) as min FROM `{$this->table}`";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['min'];
    }
    
    /**
     * 平均值
     */
    public function avg($column, $where = null, $params = []) {
        $sql = "SELECT AVG(`{$column}`) as avg FROM `{$this->table}`";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['avg'];
    }
    
    /**
     * 过滤可填充字段
     */
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * 隐藏字段
     */
    protected function hideFields($data) {
        if (empty($this->hidden)) {
            return $data;
        }
        
        return array_diff_key($data, array_flip($this->hidden));
    }
    
    /**
     * 执行原生查询
     */
    public function query($sql, $params = []) {
        return $this->db->query($sql, $params);
    }
    
    /**
     * 获取单行查询结果
     */
    public function fetch($sql, $params = []) {
        return $this->db->fetch($sql, $params);
    }
    
    /**
     * 获取多行查询结果
     */
    public function fetchAll($sql, $params = []) {
        return $this->db->fetchAll($sql, $params);
    }
}
?>
