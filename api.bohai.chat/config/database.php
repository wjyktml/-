<?php
/**
 * 数据库配置文件
 */

// 加载环境变量
if (file_exists(__DIR__ . '/../config.env')) {
    $env = parse_ini_file(__DIR__ . '/../config.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

return [
    // 数据库连接配置
    'default' => 'mysql',
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'nextspay',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ]
    ],
    
    // 数据库连接池配置
    'pool' => [
        'min_connections' => 1,
        'max_connections' => 10,
        'connect_timeout' => 10.0,
        'wait_timeout' => 3.0,
        'heartbeat' => -1,
        'max_idle_time' => 60.0,
    ]
];
?>
