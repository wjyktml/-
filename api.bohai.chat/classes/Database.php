<?php
/**
 * 数据库连接类
 */

class Database {
    private static $instance = null;
    private $connection = null;
    private $config = null;
    
    private function __construct() {
        $this->config = require_once __DIR__ . '/../config/database.php';
        try {
            $this->connect();
        } catch (Exception $e) {
            // 记录错误但不阻止系统启动
            error_log('数据库连接失败: ' . $e->getMessage());
            $this->connection = null;
        }
    }
    
    /**
     * 获取数据库实例（单例模式）
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 建立数据库连接
     */
    private function connect() {
        try {
            $config = $this->config['connections']['mysql'];
            
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }
    
    /**
     * 获取PDO连接
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * 执行查询
     */
    public function query($sql, $params = []) {
        if ($this->connection === null) {
            throw new Exception("数据库连接未建立");
        }
        
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("查询执行失败: " . $e->getMessage());
        }
    }
    
    /**
     * 获取单行数据
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * 获取多行数据
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * 插入数据
     */
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $fields) . "`) VALUES ({$placeholders})";
        
        $stmt = $this->query($sql, $data);
        return $this->connection->lastInsertId();
    }
    
    /**
     * 更新数据
     */
    public function update($table, $data, $where, $whereParams = []) {
        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = "`{$field}` = :{$field}";
        }
        
        $sql = "UPDATE `{$table}` SET " . implode(', ', $fields) . " WHERE {$where}";
        
        $params = array_merge($data, $whereParams);
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * 删除数据
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * 开始事务
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * 提交事务
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * 回滚事务
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * 检查表是否存在
     */
    public function tableExists($tableName) {
        $sql = "SHOW TABLES LIKE :table_name";
        $result = $this->fetch($sql, ['table_name' => $tableName]);
        return $result !== false;
    }
    
    /**
     * 获取表结构
     */
    public function getTableStructure($tableName) {
        $sql = "DESCRIBE `{$tableName}`";
        return $this->fetchAll($sql);
    }
    
    /**
     * 执行原生SQL
     */
    public function execute($sql) {
        return $this->connection->exec($sql);
    }
    
    /**
     * 获取最后插入的ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * 获取影响的行数
     */
    public function rowCount() {
        return $this->connection->rowCount();
    }
    
    /**
     * 转义字符串
     */
    public function quote($string) {
        return $this->connection->quote($string);
    }
    
    /**
     * 关闭连接
     */
    public function close() {
        $this->connection = null;
    }
    
    /**
     * 防止克隆
     */
    private function __clone() {}
    
    /**
     * 防止反序列化
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>
