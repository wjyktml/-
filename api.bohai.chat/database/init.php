<?php
/**
 * 数据库初始化脚本
 * 用于创建数据库和表结构
 */

require_once __DIR__ . '/../classes/Database.php';

class DatabaseInitializer {
    private $db;
    
    public function __construct() {
        // 延迟初始化数据库连接
        $this->db = null;
    }
    
    private function getDb() {
        if ($this->db === null) {
            $this->db = Database::getInstance();
        }
        return $this->db;
    }
    
    /**
     * 初始化数据库
     */
    public function init() {
        try {
            echo "开始初始化数据库...\n";
            
            // 读取SQL文件
            $sqlFile = __DIR__ . '/schema.sql';
            if (!file_exists($sqlFile)) {
                throw new Exception("SQL文件不存在: {$sqlFile}");
            }
            
            $sql = file_get_contents($sqlFile);
            
            // 分割SQL语句
            $statements = $this->splitSqlStatements($sql);
            
            // 执行SQL语句
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    try {
                        $this->getDb()->execute($statement);
                        echo "执行成功: " . substr($statement, 0, 50) . "...\n";
                    } catch (Exception $e) {
                        echo "执行失败: " . $e->getMessage() . "\n";
                        echo "SQL: " . substr($statement, 0, 100) . "...\n";
                    }
                }
            }
            
            echo "数据库初始化完成！\n";
            
        } catch (Exception $e) {
            echo "数据库初始化失败: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 分割SQL语句
     */
    private function splitSqlStatements($sql) {
        // 移除注释
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // 按分号分割
        $statements = explode(';', $sql);
        
        // 清理语句
        $cleaned = [];
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^(CREATE DATABASE|USE)/i', $statement)) {
                $cleaned[] = $statement;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * 检查数据库连接
     */
    public function checkConnection() {
        try {
            $result = $this->getDb()->fetch("SELECT 1 as test");
            if ($result && $result['test'] == 1) {
                echo "数据库连接正常\n";
                return true;
            }
        } catch (Exception $e) {
            echo "数据库连接失败: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * 检查表是否存在
     */
    public function checkTables() {
        $tables = [
            'users', 'admins', 'categories', 'products', 'orders', 
            'order_items', 'payments', 'payment_configs', 'system_settings', 
            'contacts', 'operation_logs', 'notification_logs'
        ];
        
        echo "检查数据表...\n";
        foreach ($tables as $table) {
            if ($this->getDb()->tableExists($table)) {
                echo "✓ 表 {$table} 存在\n";
            } else {
                echo "✗ 表 {$table} 不存在\n";
            }
        }
    }
    
    /**
     * 插入测试数据
     */
    public function insertTestData() {
        try {
            echo "插入测试数据...\n";
            
            // 检查是否已有数据
            $adminCount = $this->getDb()->fetch("SELECT COUNT(*) as count FROM admins")['count'];
            if ($adminCount > 0) {
                echo "管理员数据已存在，跳过\n";
                return;
            }
            
            // 插入默认管理员
            $adminData = [
                'username' => 'admin',
                'email' => 'admin@bohai.chat',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'super_admin',
                'status' => 'active'
            ];
            
            $this->getDb()->insert('admins', $adminData);
            echo "✓ 默认管理员创建成功 (用户名: admin, 密码: admin123)\n";
            
        } catch (Exception $e) {
            echo "插入测试数据失败: " . $e->getMessage() . "\n";
        }
    }
}

// 如果直接运行此脚本
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $initializer = new DatabaseInitializer();
    
    echo "=== NextsPay 数据库初始化工具 ===\n\n";
    
    // 检查连接
    if (!$initializer->checkConnection()) {
        echo "请检查数据库配置后重试\n";
        exit(1);
    }
    
    // 初始化数据库
    $initializer->init();
    
    // 检查表
    $initializer->checkTables();
    
    // 插入测试数据
    $initializer->insertTestData();
    
    echo "\n=== 初始化完成 ===\n";
    echo "默认管理员账号:\n";
    echo "用户名: admin\n";
    echo "密码: admin123\n";
    echo "请及时修改默认密码！\n";
}
?>
