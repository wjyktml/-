# NextsPay 支付系统部署指南

## 📋 目录

1. [服务器要求](#服务器要求)
2. [环境准备](#环境准备)
3. [数据库配置](#数据库配置)
4. [文件部署](#文件部署)
5. [系统配置](#系统配置)
6. [域名配置](#域名配置)
7. [SSL证书配置](#ssl证书配置)
8. [服务启动](#服务启动)
9. [测试验证](#测试验证)
10. [监控维护](#监控维护)
11. [故障排除](#故障排除)

## 🖥️ 服务器要求

### 最低配置
- **CPU**: 2核心
- **内存**: 4GB RAM
- **存储**: 50GB SSD
- **带宽**: 5Mbps
- **操作系统**: Ubuntu 20.04+ / CentOS 8+ / Windows Server 2019+

### 推荐配置
- **CPU**: 4核心
- **内存**: 8GB RAM
- **存储**: 100GB SSD
- **带宽**: 10Mbps
- **操作系统**: Ubuntu 22.04 LTS

### 支持的服务器类型
- **云服务器**: 阿里云、腾讯云、华为云、AWS、Azure
- **VPS**: Vultr、DigitalOcean、Linode
- **独立服务器**: 物理服务器、专用服务器
- **容器**: Docker、Kubernetes

## 🔧 环境准备

### 1. 系统更新

#### Ubuntu/Debian
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip
```

#### CentOS/RHEL
```bash
sudo yum update -y
sudo yum install -y curl wget git unzip
```

### 2. 安装PHP 8.1+

#### Ubuntu/Debian
```bash
# 添加PHP仓库
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# 安装PHP及扩展
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-curl php8.1-gd \
    php8.1-mbstring php8.1-xml php8.1-zip php8.1-bcmath php8.1-json \
    php8.1-cli php8.1-common php8.1-opcache php8.1-readline

# 验证安装
php -v
```

#### CentOS/RHEL
```bash
# 安装EPEL仓库
sudo yum install -y epel-release

# 安装Remi仓库
sudo yum install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# 启用PHP 8.1
sudo dnf module enable php:remi-8.1 -y

# 安装PHP及扩展
sudo yum install -y php php-fpm php-mysqlnd php-curl php-gd \
    php-mbstring php-xml php-zip php-bcmath php-json \
    php-cli php-common php-opcache php-readline

# 验证安装
php -v
```

### 3. 安装MySQL 8.0+

#### Ubuntu/Debian
```bash
# 安装MySQL
sudo apt install -y mysql-server mysql-client

# 启动MySQL服务
sudo systemctl start mysql
sudo systemctl enable mysql

# 安全配置
sudo mysql_secure_installation
```

#### CentOS/RHEL
```bash
# 安装MySQL
sudo yum install -y mysql-server mysql

# 启动MySQL服务
sudo systemctl start mysqld
sudo systemctl enable mysqld

# 获取临时密码
sudo grep 'temporary password' /var/log/mysqld.log

# 安全配置
sudo mysql_secure_installation
```

### 4. 安装Nginx

#### Ubuntu/Debian
```bash
sudo apt install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

#### CentOS/RHEL
```bash
sudo yum install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

### 5. 安装其他必要工具

```bash
# 安装Composer (PHP包管理器)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# 安装Node.js (用于前端构建)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# 验证安装
composer --version
node --version
npm --version
```

## 🗄️ 数据库配置

### 1. 创建数据库和用户

```sql
-- 登录MySQL
mysql -u root -p

-- 创建数据库
CREATE DATABASE nextspay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 创建用户
CREATE USER 'nextspay_user'@'localhost' IDENTIFIED BY 'your_strong_password';

-- 授权
GRANT ALL PRIVILEGES ON nextspay.* TO 'nextspay_user'@'localhost';
FLUSH PRIVILEGES;

-- 退出
EXIT;
```

### 2. 导入数据库结构

```bash
# 进入项目目录
cd /var/www/nextspay

# 导入数据库结构
mysql -u nextspay_user -p nextspay < api.bohai.chat/database/schema.sql

# 或者使用PHP初始化脚本
php api.bohai.chat/database/init.php
```

### 3. 验证数据库

```sql
-- 登录数据库
mysql -u nextspay_user -p nextspay

-- 查看表
SHOW TABLES;

-- 检查数据
SELECT COUNT(*) FROM admins;
SELECT COUNT(*) FROM categories;
SELECT COUNT(*) FROM products;

-- 退出
EXIT;
```

## 📁 文件部署

### 1. 创建项目目录

```bash
# 创建项目目录
sudo mkdir -p /var/www/nextspay
sudo chown -R www-data:www-data /var/www/nextspay
sudo chmod -R 755 /var/www/nextspay
```

### 2. 上传项目文件

#### 方法1: 使用Git (推荐)
```bash
cd /var/www/nextspay
sudo git clone https://github.com/your-repo/nextspay.git .
sudo chown -R www-data:www-data /var/www/nextspay
```

#### 方法2: 使用FTP/SFTP
```bash
# 使用scp上传
scp -r ./nextspay/* user@server:/var/www/nextspay/

# 或使用rsync
rsync -avz ./nextspay/ user@server:/var/www/nextspay/
```

#### 方法3: 使用压缩包
```bash
# 在本地打包
tar -czf nextspay.tar.gz nextspay/

# 上传到服务器
scp nextspay.tar.gz user@server:/tmp/

# 在服务器解压
cd /var/www
sudo tar -xzf /tmp/nextspay.tar.gz
sudo chown -R www-data:www-data nextspay
```

### 3. 设置文件权限

```bash
# 设置基本权限
sudo chown -R www-data:www-data /var/www/nextspay
sudo chmod -R 755 /var/www/nextspay

# 设置敏感文件权限
sudo chmod 600 /var/www/nextspay/api.bohai.chat/config.env
sudo chmod 600 /var/www/nextspay/api.bohai.chat/config/database.php
sudo chmod 600 /var/www/nextspay/api.bohai.chat/config/telegram.php

# 创建日志目录
sudo mkdir -p /var/www/nextspay/api.bohai.chat/logs
sudo chown -R www-data:www-data /var/www/nextspay/api.bohai.chat/logs
sudo chmod -R 755 /var/www/nextspay/api.bohai.chat/logs

# 创建上传目录
sudo mkdir -p /var/www/nextspay/bohai.chat/images/products
sudo mkdir -p /var/www/nextspay/bohai.chat/images/pay
sudo chown -R www-data:www-data /var/www/nextspay/bohai.chat/images
sudo chmod -R 755 /var/www/nextspay/bohai.chat/images
```

## ⚙️ 系统配置

### 1. 创建环境配置文件

```bash
# 复制配置文件模板
sudo cp /var/www/nextspay/api.bohai.chat/config.env.example /var/www/nextspay/api.bohai.chat/config.env

# 编辑配置文件
sudo nano /var/www/nextspay/api.bohai.chat/config.env
```

### 2. 配置环境变量

```env
# 数据库配置
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nextspay
DB_USERNAME=nextspay_user
DB_PASSWORD=your_strong_password

# 应用配置
APP_NAME=NextsPay
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bohai.chat

# 支付配置
WECHAT_APP_ID=your_wechat_app_id
WECHAT_MCH_ID=your_wechat_mch_id
WECHAT_API_KEY=your_wechat_api_key

ALIPAY_APP_ID=your_alipay_app_id
ALIPAY_PRIVATE_KEY=your_alipay_private_key
ALIPAY_PUBLIC_KEY=your_alipay_public_key

UNIONPAY_MER_ID=your_unionpay_mer_id
UNIONPAY_CERT_ID=your_unionpay_cert_id
UNIONPAY_PRIVATE_KEY=your_unionpay_private_key

STRIPE_PUBLISHABLE_KEY=your_stripe_publishable_key
STRIPE_SECRET_KEY=your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=your_stripe_webhook_secret

# Telegram配置
TELEGRAM_ENABLED=true
TELEGRAM_BOT_TOKEN=your_telegram_bot_token
TELEGRAM_CHAT_ID=your_telegram_chat_id

# 安全配置
JWT_SECRET=your_jwt_secret_key_here
ENCRYPTION_KEY=your_encryption_key_here

# 邮件配置
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_email@example.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls

# 文件上传配置
UPLOAD_MAX_SIZE=10485760
UPLOAD_ALLOWED_TYPES=jpg,jpeg,png,gif,pdf,doc,docx

# 缓存配置
CACHE_DRIVER=file
CACHE_PREFIX=nextspay

# 日志配置
LOG_LEVEL=info
LOG_FILE=logs/app.log
```

### 3. 配置PHP

```bash
# 编辑PHP配置
sudo nano /etc/php/8.1/fpm/php.ini
```

主要配置项：
```ini
# 基本配置
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
post_max_size = 50M
upload_max_filesize = 50M

# 错误报告
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

# 时区
date.timezone = Asia/Shanghai

# 扩展
extension=pdo_mysql
extension=curl
extension=gd
extension=mbstring
extension=openssl
```

### 4. 配置PHP-FPM

```bash
# 编辑PHP-FPM配置
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```

主要配置：
```ini
# 进程管理
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35

# 用户和组
user = www-data
group = www-data

# 监听
listen = /run/php/php8.1-fpm.sock
listen.owner = www-data
listen.group = www-data
```

## 🌐 域名配置

### 1. 配置Nginx虚拟主机

```bash
# 创建Nginx配置
sudo nano /etc/nginx/sites-available/nextspay
```

主站配置：
```nginx
server {
    listen 80;
    server_name bohai.chat www.bohai.chat;
    root /var/www/nextspay/bohai.chat;
    index index.html index.php;

    # 安全头
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # 静态文件缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # 主站
    location / {
        try_files $uri $uri/ /index.html;
    }

    # 后台管理
    location /admin {
        try_files $uri $uri/ /admin/index.html;
    }

    # 商品页面
    location /products {
        try_files $uri $uri/ /products.html;
    }

    # 收银台
    location /checkout {
        try_files $uri $uri/ /checkout.html;
    }

    # 成功页面
    location /success {
        try_files $uri $uri/ /success.html;
    }

    # 禁止访问敏感文件
    location ~ /\. {
        deny all;
    }

    location ~ \.(env|log|sql)$ {
        deny all;
    }
}
```

API配置：
```nginx
server {
    listen 80;
    server_name api.bohai.chat;
    root /var/www/nextspay/api.bohai.chat;
    index index.php;

    # 安全头
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    # API路由
    location / {
        try_files $uri $uri/ /web/Index.php?$query_string;
    }

    # 后台API
    location /web/Admin.php {
        try_files $uri /web/Admin.php?$query_string;
    }

    # 支付API
    location /web/Payment.php {
        try_files $uri /web/Payment.php?$query_string;
    }

    # 支付回调
    location /web/notify.php {
        try_files $uri /web/notify.php?$query_string;
    }

    # Telegram API
    location /web/Telegram.php {
        try_files $uri /web/Telegram.php?$query_string;
    }

    # Telegram Webhook
    location /web/telegram_webhook.php {
        try_files $uri /web/telegram_webhook.php?$query_string;
    }

    # PHP处理
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 禁止访问敏感文件
    location ~ /\. {
        deny all;
    }

    location ~ \.(env|log|sql)$ {
        deny all;
    }

    # 限制上传大小
    client_max_body_size 50M;
}
```

### 2. 启用站点

```bash
# 启用站点
sudo ln -s /etc/nginx/sites-available/nextspay /etc/nginx/sites-enabled/

# 测试配置
sudo nginx -t

# 重启Nginx
sudo systemctl restart nginx
```

## 🔒 SSL证书配置

### 1. 安装Certbot

```bash
# Ubuntu/Debian
sudo apt install -y certbot python3-certbot-nginx

# CentOS/RHEL
sudo yum install -y certbot python3-certbot-nginx
```

### 2. 获取SSL证书

```bash
# 获取证书
sudo certbot --nginx -d bohai.chat -d www.bohai.chat -d api.bohai.chat

# 自动续期
sudo crontab -e
# 添加以下行
0 12 * * * /usr/bin/certbot renew --quiet
```

### 3. 验证SSL配置

```bash
# 测试SSL
curl -I https://bohai.chat
curl -I https://api.bohai.chat

# 检查证书
openssl s_client -connect bohai.chat:443 -servername bohai.chat
```

## 🚀 服务启动

### 1. 启动所有服务

```bash
# 启动MySQL
sudo systemctl start mysql
sudo systemctl enable mysql

# 启动PHP-FPM
sudo systemctl start php8.1-fpm
sudo systemctl enable php8.1-fpm

# 启动Nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# 检查服务状态
sudo systemctl status mysql
sudo systemctl status php8.1-fpm
sudo systemctl status nginx
```

### 2. 配置防火墙

```bash
# Ubuntu/Debian (UFW)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# CentOS/RHEL (Firewalld)
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

## ✅ 测试验证

### 1. 基础功能测试

```bash
# 测试主站
curl -I https://bohai.chat

# 测试API
curl -I https://api.bohai.chat/web/Index.php?action=setting

# 测试后台
curl -I https://bohai.chat/admin

# 测试数据库连接
php -r "
require_once '/var/www/nextspay/api.bohai.chat/classes/Database.php';
try {
    \$db = Database::getInstance();
    echo 'Database connection: OK\n';
} catch (Exception \$e) {
    echo 'Database connection: FAILED - ' . \$e->getMessage() . '\n';
}
"
```

### 2. 支付功能测试

```bash
# 测试支付API
curl -X POST https://api.bohai.chat/web/Payment.php?action=create_order \
  -H "Content-Type: application/json" \
  -d '{
    "items": [{"product_id": 1, "quantity": 1}],
    "payment_type": "wechat",
    "customer_info": {
      "name": "测试用户",
      "phone": "13800138000"
    }
  }'
```

### 3. Telegram功能测试

```bash
# 测试Telegram配置
curl -X GET https://api.bohai.chat/web/Telegram.php?action=test_connection

# 发送测试消息
curl -X GET https://api.bohai.chat/web/Telegram.php?action=send_test
```

## 📊 监控维护

### 1. 日志监控

```bash
# 查看Nginx日志
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log

# 查看PHP日志
sudo tail -f /var/log/php_errors.log

# 查看应用日志
sudo tail -f /var/www/nextspay/api.bohai.chat/logs/app.log
sudo tail -f /var/www/nextspay/api.bohai.chat/logs/telegram.log

# 查看MySQL日志
sudo tail -f /var/log/mysql/error.log
```

### 2. 性能监控

```bash
# 系统资源监控
htop
iostat -x 1
df -h
free -h

# 数据库监控
mysql -u root -p -e "SHOW PROCESSLIST;"
mysql -u root -p -e "SHOW STATUS LIKE 'Connections';"
```

### 3. 备份策略

```bash
# 创建备份脚本
sudo nano /usr/local/bin/backup-nextspay.sh
```

备份脚本内容：
```bash
#!/bin/bash
BACKUP_DIR="/backup/nextspay"
DATE=$(date +%Y%m%d_%H%M%S)

# 创建备份目录
mkdir -p $BACKUP_DIR

# 备份数据库
mysqldump -u nextspay_user -p'your_password' nextspay > $BACKUP_DIR/database_$DATE.sql

# 备份文件
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/nextspay

# 删除7天前的备份
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

设置定时备份：
```bash
# 设置执行权限
sudo chmod +x /usr/local/bin/backup-nextspay.sh

# 添加到crontab
sudo crontab -e
# 添加以下行（每天凌晨2点备份）
0 2 * * * /usr/local/bin/backup-nextspay.sh
```

## 🔧 故障排除

### 1. 常见问题

#### 数据库连接失败
```bash
# 检查MySQL服务
sudo systemctl status mysql

# 检查数据库配置
mysql -u nextspay_user -p nextspay

# 检查防火墙
sudo ufw status
```

#### PHP错误
```bash
# 检查PHP配置
php -m | grep mysql
php -m | grep curl

# 检查PHP-FPM
sudo systemctl status php8.1-fpm

# 查看PHP错误日志
sudo tail -f /var/log/php_errors.log
```

#### Nginx错误
```bash
# 检查Nginx配置
sudo nginx -t

# 查看Nginx错误日志
sudo tail -f /var/log/nginx/error.log

# 检查端口占用
sudo netstat -tlnp | grep :80
sudo netstat -tlnp | grep :443
```

### 2. 性能优化

#### PHP优化
```bash
# 编辑PHP配置
sudo nano /etc/php/8.1/fpm/php.ini
```

优化配置：
```ini
# 内存和性能
memory_limit = 512M
max_execution_time = 300
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 60
```

#### MySQL优化
```bash
# 编辑MySQL配置
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

优化配置：
```ini
[mysqld]
# 基本配置
max_connections = 200
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
query_cache_size = 64M
query_cache_type = 1

# 慢查询日志
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

#### Nginx优化
```bash
# 编辑Nginx配置
sudo nano /etc/nginx/nginx.conf
```

优化配置：
```nginx
worker_processes auto;
worker_connections 1024;

# Gzip压缩
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

# 缓存配置
open_file_cache max=1000 inactive=20s;
open_file_cache_valid 30s;
open_file_cache_min_uses 2;
open_file_cache_errors on;
```

### 3. 安全加固

#### 系统安全
```bash
# 更新系统
sudo apt update && sudo apt upgrade -y

# 配置SSH
sudo nano /etc/ssh/sshd_config
# 设置：PermitRootLogin no, PasswordAuthentication no

# 安装fail2ban
sudo apt install -y fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

#### 应用安全
```bash
# 设置文件权限
sudo chmod 600 /var/www/nextspay/api.bohai.chat/config.env
sudo chmod 600 /var/www/nextspay/api.bohai.chat/config/database.php

# 隐藏敏感信息
sudo nano /var/www/nextspay/api.bohai.chat/.htaccess
```

.htaccess内容：
```apache
# 禁止访问敏感文件
<Files "*.env">
    Order allow,deny
    Deny from all
</Files>

<Files "*.log">
    Order allow,deny
    Deny from all
</Files>

<Files "*.sql">
    Order allow,deny
    Deny from all
</Files>

# 禁止目录浏览
Options -Indexes

# 安全头
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
Header always set X-Content-Type-Options "nosniff"
```

## 📞 技术支持

### 作者信息
- **作者**: @haotongdao
- **Telegram**: [@haotongdao](https://t.me/haotongdao)
- **QQ**: 553201668

### 联系方式
- **邮箱**: support@bohai.chat
- **电话**: 400-123-4567
- **QQ群**: 123456789
- **微信群**: 扫描二维码加入

### 文档资源
- **用户手册**: [用户使用指南](USER_MANUAL.md)
- **API文档**: [API接口文档](API_DOCUMENTATION.md)
- **Telegram设置**: [Telegram配置指南](TELEGRAM_SETUP.md)
- **常见问题**: [FAQ常见问题](FAQ.md)

### 更新日志
- **v1.0.0** (2025-09-14): 初始版本发布
- **v1.1.0** (2025-09-14): 添加Telegram通知功能
- **v1.2.0** (2025-09-14): 优化数据库性能
- **v1.3.0** (2025-09-14): 完善部署文档，添加宝塔面板部署指南

---

**部署完成后，请及时修改默认密码并配置支付密钥！**

**如有问题，请参考故障排除部分或联系技术支持。**
