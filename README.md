# NextsPay 支付系统

一个完整的支付解决方案，支持多种支付方式，包含前端购买页面、收银台和后台管理系统。

## 功能特性

### 前端功能
- 🛒 商品展示和购买页面
- 💳 多种支付方式（微信、支付宝、银联、Stripe）
- 📱 响应式设计，支持移动端
- 🛍️ 购物车功能
- 📊 实时支付状态检查

### 后台管理
- 📈 数据统计仪表盘
- 🏷️ 商品和分类管理
- 📦 订单管理
- ⚙️ 支付配置管理
- 🔧 系统设置

### 支付集成
- 💰 微信支付（Native扫码支付）
- 💰 支付宝（扫码支付）
- 💰 银联支付（银行卡支付）
- 💰 Stripe（国际信用卡支付）

## 技术栈

- **前端**: HTML5, CSS3, JavaScript, Vue.js, Element UI
- **后端**: PHP 7.4+, MySQL 5.7+
- **数据库**: MySQL
- **支付**: 微信支付、支付宝、银联、Stripe

## 安装部署

### 1. 环境要求

- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- Web服务器（Apache/Nginx）
- 支持HTTPS（支付需要）

### 2. 数据库配置

1. 创建MySQL数据库：
```sql
CREATE DATABASE nextspay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. 导入数据库结构：
```bash
mysql -u root -p nextspay < api.bohai.chat/database/schema.sql
```

或者使用PHP脚本初始化：
```bash
php api.bohai.chat/database/init.php
```

### 3. 配置文件

1. 复制环境配置文件：
```bash
cp api.bohai.chat/config.env.example api.bohai.chat/config.env
```

2. 修改数据库配置：
```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nextspay
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. 支付配置

在后台管理系统中配置各种支付方式的密钥：

#### 微信支付
- App ID
- 商户号
- API密钥
- 证书路径

#### 支付宝
- 应用ID
- 私钥
- 支付宝公钥

#### 银联支付
- 商户号
- 证书ID
- 私钥

#### Stripe
- 发布密钥
- 密钥
- Webhook密钥

### 5. 文件权限

确保以下目录有写入权限：
```bash
chmod -R 755 api.bohai.chat/data/
chmod -R 755 bohai.chat/images/
```

## 使用说明

### 访问地址

- 前端网站: `https://bohai.chat`
- 后台管理: `https://bohai.chat/admin`
- API接口: `https://api.bohai.chat/web/`

### 默认管理员账号

- 用户名: `admin`
- 密码: `admin123`

**⚠️ 请及时修改默认密码！**

### 商品管理

1. 登录后台管理系统
2. 在"分类管理"中添加商品分类
3. 在"商品管理"中添加商品信息
4. 上传商品图片

### 支付测试

1. 配置支付密钥
2. 使用测试环境进行支付测试
3. 检查支付回调是否正常

## API接口

### 主要接口

- `GET /web/Index.php?action=setting` - 获取系统设置
- `POST /web/Index.php?action=contact` - 提交联系表单
- `POST /web/Payment.php?action=create_order` - 创建支付订单
- `GET /web/Payment.php?action=query_order` - 查询订单状态

### 后台管理接口

- `GET /web/Admin.php?action=stats` - 获取统计数据
- `GET /web/Admin.php?action=products` - 获取商品列表
- `POST /web/Admin.php?action=products` - 保存商品
- `DELETE /web/Admin.php?action=products&id=1` - 删除商品

## 目录结构

```
bohai.chat/                    # 前端文件
├── index.html                 # 主页
├── products.html              # 商品页面
├── checkout.html              # 收银台
├── success.html               # 支付成功页
├── admin/                     # 后台管理
│   └── index.html
├── js/                        # JavaScript文件
├── css/                       # 样式文件
└── images/                    # 图片资源

api.bohai.chat/                # 后端API
├── web/                       # API接口
│   ├── Index.php              # 主要接口
│   ├── Admin.php              # 后台管理接口
│   ├── Payment.php            # 支付处理
│   └── notify.php             # 支付回调
├── classes/                   # 核心类
│   ├── Database.php           # 数据库类
│   └── Model.php              # 模型基类
├── models/                    # 数据模型
│   ├── Product.php            # 商品模型
│   ├── Category.php           # 分类模型
│   └── Order.php              # 订单模型
├── database/                  # 数据库文件
│   ├── schema.sql             # 数据库结构
│   └── init.php               # 初始化脚本
└── config/                    # 配置文件
    └── database.php           # 数据库配置
```

## 安全注意事项

1. **修改默认密码**: 及时修改默认管理员密码
2. **HTTPS配置**: 生产环境必须使用HTTPS
3. **支付密钥**: 妥善保管支付配置密钥
4. **数据库安全**: 使用强密码，限制访问权限
5. **文件权限**: 设置合适的文件权限
6. **定期备份**: 定期备份数据库和重要文件

## 常见问题

### Q: 支付回调失败怎么办？
A: 检查服务器是否能接收外部请求，确保回调URL可访问。

### Q: 如何添加新的支付方式？
A: 在Payment.php中添加新的支付处理逻辑，并在后台配置中添加相应配置项。

### Q: 数据库连接失败？
A: 检查数据库配置、用户名密码、数据库是否存在。

### Q: 图片上传失败？
A: 检查images目录权限，确保有写入权限。

## 技术支持

如有问题，请联系：
- 邮箱: support@bohai.chat
- 电话: 400-123-4567

## 作者信息

- **作者**: @haotongdao
- **Telegram**: [@haotongdao](https://t.me/haotongdao)
- **QQ**: 553201668

## 许可证

本项目采用 MIT 许可证。
