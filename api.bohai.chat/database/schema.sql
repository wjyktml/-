-- NextsPay 数据库结构
-- 适用于 MySQL 5.7+

-- 创建数据库
CREATE DATABASE IF NOT EXISTS `nextspay` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nextspay`;

-- 用户表
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `email` varchar(100) NOT NULL COMMENT '邮箱',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `status` enum('active','inactive','banned') DEFAULT 'active' COMMENT '状态',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- 管理员表
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '管理员用户名',
  `email` varchar(100) NOT NULL COMMENT '邮箱',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `role` enum('super_admin','admin','operator') DEFAULT 'admin' COMMENT '角色',
  `status` enum('active','inactive') DEFAULT 'active' COMMENT '状态',
  `last_login_at` timestamp NULL DEFAULT NULL COMMENT '最后登录时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表';

-- 商品分类表
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '分类名称',
  `description` text COMMENT '分类描述',
  `image` varchar(255) DEFAULT NULL COMMENT '分类图片',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `status` enum('active','inactive') DEFAULT 'active' COMMENT '状态',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商品分类表';

-- 商品表
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL COMMENT '商品名称',
  `description` text COMMENT '商品描述',
  `category_id` int(11) NOT NULL COMMENT '分类ID',
  `price` decimal(10,2) NOT NULL COMMENT '价格',
  `original_price` decimal(10,2) DEFAULT NULL COMMENT '原价',
  `stock` int(11) DEFAULT 0 COMMENT '库存',
  `image` varchar(255) DEFAULT NULL COMMENT '商品图片',
  `images` text COMMENT '商品图片组（JSON格式）',
  `weight` decimal(8,2) DEFAULT NULL COMMENT '重量（kg）',
  `dimensions` varchar(100) DEFAULT NULL COMMENT '尺寸',
  `sku` varchar(100) DEFAULT NULL COMMENT '商品编码',
  `status` enum('active','inactive','draft') DEFAULT 'active' COMMENT '状态',
  `is_featured` tinyint(1) DEFAULT 0 COMMENT '是否推荐',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `status` (`status`),
  KEY `price` (`price`),
  KEY `stock` (`stock`),
  KEY `is_featured` (`is_featured`),
  KEY `sort_order` (`sort_order`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商品表';

-- 订单表
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) NOT NULL COMMENT '订单号',
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `total_amount` decimal(10,2) NOT NULL COMMENT '订单总金额',
  `discount_amount` decimal(10,2) DEFAULT 0.00 COMMENT '优惠金额',
  `shipping_fee` decimal(10,2) DEFAULT 0.00 COMMENT '运费',
  `final_amount` decimal(10,2) NOT NULL COMMENT '最终金额',
  `payment_type` varchar(20) NOT NULL COMMENT '支付方式',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending' COMMENT '支付状态',
  `order_status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending' COMMENT '订单状态',
  `shipping_name` varchar(100) DEFAULT NULL COMMENT '收货人姓名',
  `shipping_phone` varchar(20) DEFAULT NULL COMMENT '收货人电话',
  `shipping_address` text COMMENT '收货地址',
  `remark` text COMMENT '订单备注',
  `paid_at` timestamp NULL DEFAULT NULL COMMENT '支付时间',
  `shipped_at` timestamp NULL DEFAULT NULL COMMENT '发货时间',
  `delivered_at` timestamp NULL DEFAULT NULL COMMENT '完成时间',
  `expire_at` timestamp NULL DEFAULT NULL COMMENT '过期时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `user_id` (`user_id`),
  KEY `payment_status` (`payment_status`),
  KEY `order_status` (`order_status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单表';

-- 订单商品表
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `product_id` int(11) NOT NULL COMMENT '商品ID',
  `product_name` varchar(200) NOT NULL COMMENT '商品名称',
  `product_image` varchar(255) DEFAULT NULL COMMENT '商品图片',
  `price` decimal(10,2) NOT NULL COMMENT '单价',
  `quantity` int(11) NOT NULL COMMENT '数量',
  `total_price` decimal(10,2) NOT NULL COMMENT '小计',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单商品表';

-- 支付记录表
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `payment_type` varchar(20) NOT NULL COMMENT '支付方式',
  `transaction_id` varchar(100) DEFAULT NULL COMMENT '第三方交易号',
  `amount` decimal(10,2) NOT NULL COMMENT '支付金额',
  `status` enum('pending','success','failed','cancelled') DEFAULT 'pending' COMMENT '支付状态',
  `gateway_response` text COMMENT '网关响应数据',
  `notify_data` text COMMENT '回调数据',
  `paid_at` timestamp NULL DEFAULT NULL COMMENT '支付时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `payment_type` (`payment_type`),
  KEY `status` (`status`),
  KEY `transaction_id` (`transaction_id`),
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付记录表';

-- 支付配置表
CREATE TABLE IF NOT EXISTS `payment_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_type` varchar(20) NOT NULL COMMENT '支付方式',
  `config_key` varchar(100) NOT NULL COMMENT '配置键',
  `config_value` text COMMENT '配置值',
  `is_encrypted` tinyint(1) DEFAULT 0 COMMENT '是否加密',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_type_config_key` (`payment_type`, `config_key`),
  KEY `payment_type` (`payment_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付配置表';

-- 系统设置表
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL COMMENT '设置键',
  `setting_value` text COMMENT '设置值',
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string' COMMENT '设置类型',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置表';

-- 联系记录表
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '联系人姓名',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `phone` varchar(20) DEFAULT NULL COMMENT '电话',
  `company` varchar(200) DEFAULT NULL COMMENT '公司',
  `industry` varchar(100) DEFAULT NULL COMMENT '行业',
  `message` text COMMENT '留言内容',
  `status` enum('pending','processed','replied') DEFAULT 'pending' COMMENT '处理状态',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` text COMMENT '用户代理',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='联系记录表';

-- 操作日志表
CREATE TABLE IF NOT EXISTS `operation_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL COMMENT '管理员ID',
  `action` varchar(100) NOT NULL COMMENT '操作动作',
  `resource_type` varchar(50) DEFAULT NULL COMMENT '资源类型',
  `resource_id` int(11) DEFAULT NULL COMMENT '资源ID',
  `description` text COMMENT '操作描述',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` text COMMENT '用户代理',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `action` (`action`),
  KEY `resource_type` (`resource_type`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_operation_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

-- 通知日志表
CREATE TABLE IF NOT EXISTS `notification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL COMMENT '通知类型',
  `reference` varchar(100) DEFAULT NULL COMMENT '关联对象（如订单号）',
  `results` text COMMENT '发送结果（JSON格式）',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `reference` (`reference`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知日志表';

-- 插入默认数据

-- 插入默认管理员
INSERT INTO `admins` (`username`, `email`, `password`, `role`) VALUES 
('admin', 'admin@bohai.chat', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');

-- 插入默认分类
INSERT INTO `categories` (`name`, `description`, `sort_order`) VALUES 
('手机电脑', '手机和电脑产品', 1),
('配件周边', '各种配件和周边产品', 2),
('数码产品', '数码设备和电子产品', 3);

-- 插入默认商品
INSERT INTO `products` (`name`, `description`, `category_id`, `price`, `stock`, `image`, `status`) VALUES 
('iPhone 15 Pro', '最新款iPhone，配备A17 Pro芯片，钛金属设计', 1, 7999.00, 10, 'images/products/iphone15.jpg', 'active'),
('MacBook Pro 14"', 'M3 Pro芯片，14英寸Liquid Retina XDR显示屏', 1, 15999.00, 5, 'images/products/macbook.jpg', 'active'),
('AirPods Pro', '主动降噪，空间音频，无线充电盒', 2, 1899.00, 20, 'images/products/airpods.jpg', 'active'),
('Apple Watch Series 9', 'S9芯片，Always-On显示屏，健康监测', 2, 2999.00, 15, 'images/products/watch.jpg', 'active');

-- 插入默认支付配置
INSERT INTO `payment_configs` (`payment_type`, `config_key`, `config_value`, `description`) VALUES 
('wechat', 'enabled', 'true', '微信支付是否启用'),
('wechat', 'app_id', '', '微信支付App ID'),
('wechat', 'mch_id', '', '微信支付商户号'),
('wechat', 'api_key', '', '微信支付API密钥'),
('alipay', 'enabled', 'true', '支付宝是否启用'),
('alipay', 'app_id', '', '支付宝应用ID'),
('alipay', 'private_key', '', '支付宝私钥'),
('alipay', 'public_key', '', '支付宝公钥'),
('unionpay', 'enabled', 'true', '银联支付是否启用'),
('unionpay', 'mer_id', '', '银联商户号'),
('unionpay', 'cert_id', '', '银联证书ID'),
('unionpay', 'private_key', '', '银联私钥'),
('stripe', 'enabled', 'true', 'Stripe是否启用'),
('stripe', 'publishable_key', '', 'Stripe发布密钥'),
('stripe', 'secret_key', '', 'Stripe密钥'),
('stripe', 'webhook_secret', '', 'Stripe Webhook密钥');

-- 插入默认系统设置
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES 
('site_name', 'NextsPay', 'string', '网站名称'),
('site_description', '专业的支付解决方案', 'string', '网站描述'),
('contact_email', 'support@bohai.chat', 'string', '联系邮箱'),
('contact_phone', '400-123-4567', 'string', '联系电话'),
('order_timeout', '15', 'number', '订单超时时间（分钟）'),
('maintenance_mode', 'false', 'boolean', '维护模式'),
('currency', 'CNY', 'string', '默认货币'),
('timezone', 'Asia/Shanghai', 'string', '时区'),
('telegram_enabled', 'false', 'boolean', 'Telegram通知是否启用'),
('telegram_bot_token', '', 'string', 'Telegram机器人Token'),
('telegram_chat_id', '', 'string', 'Telegram通知群组ID');
