# NextsPay 宝塔面板部署指南

## 📋 目录

1. [宝塔面板安装](#宝塔面板安装)
2. [环境配置](#环境配置)
3. [项目部署](#项目部署)
4. [数据库配置](#数据库配置)
5. [域名绑定](#域名绑定)
6. [SSL证书配置](#ssl证书配置)
7. [系统配置](#系统配置)
8. [测试验证](#测试验证)
9. [监控维护](#监控维护)
10. [常见问题](#常见问题)

## 🖥️ 宝塔面板安装

### 1. 系统要求

- **操作系统**: CentOS 7.x+ / Ubuntu 18.04+ / Debian 9.0+
- **内存**: 最低1GB，推荐2GB以上
- **硬盘**: 最低20GB，推荐40GB以上
- **带宽**: 最低1Mbps，推荐5Mbps以上

### 2. 安装宝塔面板

#### CentOS安装命令
```bash
yum install -y wget && wget -O install.sh http://download.bt.cn/install/install_6.0.sh && sh install.sh ed8484bec
```

#### Ubuntu/Debian安装命令
```bash
wget -O install.sh http://download.bt.cn/install/install-ubuntu_6.0.sh && sudo bash install.sh ed8484bec
```

### 3. 安装完成信息

安装完成后会显示：
```
==================================================================
Congratulations! Installed successfully!
==================================================================
Bt-Panel: http://YOUR_IP:8888
username: admin
password: your_password
Warning:
If you cannot access the panel,
release the following port (8888|888|80|443|20|21) in the security group
==================================================================
```

### 4. 登录宝塔面板

1. 打开浏览器访问：`http://你的服务器IP:8888`
2. 使用安装时显示的账号密码登录
3. 首次登录会要求绑定宝塔账号（可跳过）

## 🔧 环境配置

### 1. 安装LNMP环境

#### 推荐配置
- **Nginx**: 1.20+
- **MySQL**: 8.0+
- **PHP**: 8.1+
- **phpMyAdmin**: 5.2+

#### 安装步骤
1. 进入宝塔面板
2. 点击左侧菜单"软件商店"
3. 选择"运行环境"标签
4. 安装以下软件：
   - Nginx 1.20.2
   - MySQL 8.0.28
   - PHP 8.1.2
   - phpMyAdmin 5.2.0

### 2. 配置PHP

#### 安装PHP扩展
1. 进入"软件商店" → "已安装"
2. 找到PHP 8.1，点击"设置"
3. 进入"安装扩展"标签
4. 安装以下扩展：
   - `pdo_mysql`
   - `curl`
   - `gd`
   - `mbstring`
   - `openssl`
   - `bcmath`
   - `json`
   - `zip`
   - `fileinfo`

#### 修改PHP配置
1. 在PHP设置中点击"配置修改"
2. 修改以下参数：
```ini
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
post_max_size = 50M
upload_max_filesize = 50M
date.timezone = Asia/Shanghai
```

### 3. 配置MySQL

#### 设置MySQL密码
1. 进入"软件商店" → "已安装"
2. 找到MySQL，点击"设置"
3. 点击"root密码"
4. 设置强密码（建议16位以上）

#### 创建数据库
1. 点击"数据库"标签
2. 点击"添加数据库"
3. 填写信息：
   - 数据库名：`nextspay`
   - 用户名：`nextspay_user`
   - 密码：`your_strong_password`
   - 访问权限：`本地服务器`

## 📁 项目部署

### 1. 创建网站

#### 添加站点
1. 点击左侧菜单"网站"
2. 点击"添加站点"
3. 填写信息：
   - 域名：`bohai.chat`
   - 根目录：`/www/wwwroot/bohai.chat`
   - PHP版本：`8.1`
   - 数据库：`不创建`

#### 添加API站点
1. 再次点击"添加站点"
2. 填写信息：
   - 域名：`api.bohai.chat`
   - 根目录：`/www/wwwroot/api.bohai.chat`
   - PHP版本：`8.1`
   - 数据库：`不创建`

### 2. 上传项目文件

#### 方法1: 使用宝塔文件管理器
1. 进入"文件"菜单
2. 导航到`/www/wwwroot/`
3. 创建目录结构：
   ```
   /www/wwwroot/
   ├── bohai.chat/          # 主站文件
   └── api.bohai.chat/      # API文件
   ```

#### 方法2: 使用FTP
1. 进入"FTP"菜单
2. 创建FTP账号
3. 使用FTP客户端上传文件

#### 方法3: 使用Git
1. 进入"终端"菜单
2. 执行命令：
```bash
cd /www/wwwroot/bohai.chat
git clone https://github.com/your-repo/nextspay.git .

cd /www/wwwroot/api.bohai.chat
# 复制API相关文件
```

### 3. 设置文件权限

#### 使用宝塔文件管理器
1. 选择项目根目录
2. 右键 → "权限"
3. 设置权限：
   - 所有者：`www`
   - 权限：`755`
   - 递归应用到子目录：`是`

#### 使用终端命令
```bash
# 设置主站权限
chown -R www:www /www/wwwroot/bohai.chat
chmod -R 755 /www/wwwroot/bohai.chat

# 设置API权限
chown -R www:www /www/wwwroot/api.bohai.chat
chmod -R 755 /www/wwwroot/api.bohai.chat

# 设置敏感文件权限
chmod 600 /www/wwwroot/api.bohai.chat/config.env
chmod 600 /www/wwwroot/api.bohai.chat/config/database.php
chmod 600 /www/wwwroot/api.bohai.chat/config/telegram.php
```

## 🗄️ 数据库配置

### 1. 导入数据库结构

#### 使用phpMyAdmin
1. 进入"数据库"菜单
2. 找到`nextspay`数据库，点击"管理"
3. 进入phpMyAdmin
4. 选择`nextspay`数据库
5. 点击"导入"标签
6. 选择`schema.sql`文件上传

#### 使用命令行
```bash
# 进入终端
cd /www/wwwroot/api.bohai.chat

# 导入数据库
mysql -u nextspay_user -p nextspay < database/schema.sql

# 或使用PHP初始化脚本
php database/init.php
```

### 2. 验证数据库

#### 检查表结构
```sql
-- 在phpMyAdmin中执行
USE nextspay;
SHOW TABLES;

-- 检查数据
SELECT COUNT(*) FROM admins;
SELECT COUNT(*) FROM categories;
SELECT COUNT(*) FROM products;
```

## 🌐 域名绑定

### 1. 配置主站

#### 修改网站配置
1. 进入"网站"菜单
2. 找到`bohai.chat`，点击"设置"
3. 进入"配置文件"标签
4. 修改配置：

```nginx
server {
    listen 80;
    server_name bohai.chat www.bohai.chat;
    root /www/wwwroot/bohai.chat;
    index index.html index.php;

    # 安全头
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    # 静态文件缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # 主站路由
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

### 2. 配置API站点

#### 修改API配置
1. 找到`api.bohai.chat`，点击"设置"
2. 进入"配置文件"标签
3. 修改配置：

```nginx
server {
    listen 80;
    server_name api.bohai.chat;
    root /www/wwwroot/api.bohai.chat;
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
        include fastcgi_params;
        fastcgi_pass unix:/tmp/php-cgi-81.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
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

### 3. 重启服务

1. 点击"保存"按钮
2. 进入"软件商店" → "已安装"
3. 找到Nginx，点击"重启"

## 🔒 SSL证书配置

### 1. 申请SSL证书

#### 使用Let's Encrypt（免费）
1. 进入"网站"菜单
2. 找到对应网站，点击"设置"
3. 进入"SSL"标签
4. 选择"Let's Encrypt"
5. 填写域名：`bohai.chat,www.bohai.chat`
6. 点击"申请"

#### 使用宝塔SSL（付费）
1. 进入"网站" → "SSL"
2. 选择"宝塔SSL"
3. 选择证书类型
4. 填写域名信息
5. 完成支付

### 2. 配置强制HTTPS

1. 在SSL页面开启"强制HTTPS"
2. 开启"HSTS"
3. 点击"保存"

### 3. 验证SSL

```bash
# 测试SSL证书
curl -I https://bohai.chat
curl -I https://api.bohai.chat

# 检查证书有效期
openssl s_client -connect bohai.chat:443 -servername bohai.chat
```

## ⚙️ 系统配置

### 1. 创建环境配置文件

#### 使用宝塔文件管理器
1. 进入`/www/wwwroot/api.bohai.chat/`
2. 复制`config.env.example`为`config.env`
3. 编辑配置文件

#### 配置文件内容
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

### 2. 配置防火墙

#### 使用宝塔防火墙
1. 进入"安全"菜单
2. 点击"系统防火墙"
3. 确保以下端口开放：
   - `80` (HTTP)
   - `443` (HTTPS)
   - `22` (SSH)
   - `8888` (宝塔面板)

#### 使用系统防火墙
```bash
# CentOS/RHEL
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --permanent --add-service=ssh
firewall-cmd --reload

# Ubuntu/Debian
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 22/tcp
ufw enable
```

### 3. 配置定时任务

#### 设置数据库备份
1. 进入"计划任务"菜单
2. 点击"添加任务"
3. 配置：
   - 任务类型：`备份数据库`
   - 任务名称：`NextsPay数据库备份`
   - 执行周期：`每天`
   - 备份周期：`7天`
   - 数据库：`nextspay`

#### 设置文件备份
1. 再次点击"添加任务"
2. 配置：
   - 任务类型：`备份目录`
   - 任务名称：`NextsPay文件备份`
   - 执行周期：`每天`
   - 备份周期：`7天`
   - 备份目录：`/www/wwwroot/bohai.chat,/www/wwwroot/api.bohai.chat`

## ✅ 测试验证

### 1. 基础功能测试

#### 使用宝塔面板测试
1. 进入"网站"菜单
2. 点击网站域名
3. 检查是否能正常访问

#### 使用命令行测试
```bash
# 测试主站
curl -I https://bohai.chat

# 测试API
curl -I https://api.bohai.chat/web/Index.php?action=setting

# 测试后台
curl -I https://bohai.chat/admin
```

### 2. 数据库连接测试

#### 使用phpMyAdmin
1. 进入"数据库"菜单
2. 点击"管理"进入phpMyAdmin
3. 执行SQL：
```sql
SELECT COUNT(*) FROM admins;
SELECT COUNT(*) FROM categories;
SELECT COUNT(*) FROM products;
```

#### 使用命令行
```bash
# 进入终端
cd /www/wwwroot/api.bohai.chat

# 测试数据库连接
php -r "
require_once 'classes/Database.php';
try {
    \$db = Database::getInstance();
    echo 'Database connection: OK\n';
} catch (Exception \$e) {
    echo 'Database connection: FAILED - ' . \$e->getMessage() . '\n';
}
"
```

### 3. 支付功能测试

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

### 4. Telegram功能测试

```bash
# 测试Telegram配置
curl -X GET https://api.bohai.chat/web/Telegram.php?action=test_connection

# 发送测试消息
curl -X GET https://api.bohai.chat/web/Telegram.php?action=send_test
```

## 📊 监控维护

### 1. 使用宝塔监控

#### 系统监控
1. 进入"监控"菜单
2. 查看系统资源使用情况
3. 设置告警阈值

#### 网站监控
1. 进入"网站"菜单
2. 点击"监控"
3. 查看访问统计和错误日志

### 2. 日志管理

#### 查看日志
1. 进入"日志"菜单
2. 查看系统日志、网站日志、数据库日志

#### 日志轮转
1. 进入"软件商店" → "已安装"
2. 找到对应软件，点击"设置"
3. 配置日志轮转策略

### 3. 性能优化

#### 开启缓存
1. 进入"软件商店"
2. 安装"Redis"或"Memcached"
3. 在PHP中启用对应扩展

#### 开启Gzip压缩
1. 进入"网站" → "设置" → "配置文件"
2. 添加Gzip配置：
```nginx
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
```

## 🔧 常见问题

### 1. 网站无法访问

#### 检查步骤
1. 检查域名解析是否正确
2. 检查防火墙是否开放80/443端口
3. 检查Nginx服务是否正常运行
4. 检查网站配置文件是否正确

#### 解决方案
```bash
# 检查Nginx状态
systemctl status nginx

# 检查Nginx配置
nginx -t

# 重启Nginx
systemctl restart nginx
```

### 2. 数据库连接失败

#### 检查步骤
1. 检查MySQL服务是否运行
2. 检查数据库用户权限
3. 检查配置文件中的数据库信息

#### 解决方案
```bash
# 检查MySQL状态
systemctl status mysql

# 重启MySQL
systemctl restart mysql

# 检查数据库用户
mysql -u root -p -e "SELECT User, Host FROM mysql.user WHERE User='nextspay_user';"
```

### 3. PHP错误

#### 检查步骤
1. 检查PHP版本和扩展
2. 检查PHP配置文件
3. 查看PHP错误日志

#### 解决方案
```bash
# 检查PHP版本
php -v

# 检查PHP扩展
php -m | grep mysql

# 查看PHP错误日志
tail -f /www/server/php/81/var/log/php-fpm.log
```

### 4. SSL证书问题

#### 检查步骤
1. 检查证书是否过期
2. 检查域名是否正确
3. 检查证书链是否完整

#### 解决方案
1. 重新申请SSL证书
2. 检查域名解析
3. 更新证书链

### 5. 文件权限问题

#### 检查步骤
1. 检查文件所有者
2. 检查文件权限
3. 检查SELinux状态

#### 解决方案
```bash
# 设置正确的文件权限
chown -R www:www /www/wwwroot/bohai.chat
chmod -R 755 /www/wwwroot/bohai.chat

# 检查SELinux
getenforce
# 如果启用，可以临时关闭
setenforce 0
```

## 📞 技术支持

### 作者信息
- **作者**: @haotongdao
- **Telegram**: [@haotongdao](https://t.me/haotongdao)
- **QQ**: 553201668

### 宝塔官方支持
- **官网**: https://www.bt.cn
- **论坛**: https://www.bt.cn/bbs
- **文档**: https://www.bt.cn/help
- **QQ群**: 318514457

### NextsPay技术支持
- **邮箱**: support@bohai.chat
- **电话**: 400-123-4567
- **QQ群**: 123456789
- **微信群**: 扫描二维码加入

### 部署检查清单

- [ ] 宝塔面板安装完成
- [ ] LNMP环境安装完成
- [ ] 项目文件上传完成
- [ ] 数据库创建和导入完成
- [ ] 域名解析配置完成
- [ ] SSL证书申请完成
- [ ] 环境配置文件创建完成
- [ ] 防火墙配置完成
- [ ] 基础功能测试通过
- [ ] 支付功能测试通过
- [ ] Telegram功能测试通过
- [ ] 备份策略配置完成

---

**部署完成后，请及时修改默认密码并配置支付密钥！**

**如有问题，请参考常见问题部分或联系技术支持。**

---

**文档更新时间**: 2025年9月14日
