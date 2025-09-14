# Telegram 机器人设置指南

## 🤖 创建Telegram机器人

### 1. 创建机器人

1. 在Telegram中搜索 `@BotFather`
2. 发送 `/newbot` 命令
3. 按提示输入机器人名称和用户名
4. 获取Bot Token（类似：`123456789:ABCdefGHIjklMNOpqrsTUVwxyz`）

### 2. 获取Chat ID

#### 方法1：个人聊天
1. 将机器人添加到个人聊天
2. 发送任意消息给机器人
3. 访问：`https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
4. 在返回的JSON中找到 `chat.id` 字段

#### 方法2：群组聊天
1. 将机器人添加到群组
2. 给机器人管理员权限
3. 在群组中发送 `/start` 命令
4. 访问：`https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
5. 在返回的JSON中找到群组的 `chat.id`（通常是负数）

## ⚙️ 配置系统

### 1. 环境配置

在 `api.bohai.chat/config.env` 文件中添加：

```env
TELEGRAM_ENABLED=true
TELEGRAM_BOT_TOKEN=你的机器人Token
TELEGRAM_CHAT_ID=你的Chat ID
```

### 2. 后台配置

1. 登录后台管理系统
2. 进入 "Telegram通知" 页面
3. 填写Bot Token和Chat ID
4. 点击"保存配置"
5. 点击"测试连接"验证配置

## 🔗 Webhook设置

### 1. 设置Webhook

1. 在后台管理系统中进入"Webhook设置"标签
2. 确认Webhook URL：`https://api.bohai.chat/web/telegram.php`
3. 点击"设置Webhook"
4. 点击"查看信息"确认设置成功

### 2. 验证Webhook

访问：`https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo`

应该返回类似：
```json
{
  "ok": true,
  "result": {
    "url": "https://api.bohai.chat/web/telegram.php",
    "has_custom_certificate": false,
    "pending_update_count": 0
  }
}
```

## 📱 通知功能

### 自动通知类型

系统会在以下情况自动发送Telegram通知：

1. **新订单通知** 🛒
   - 订单号、客户信息、金额、支付方式

2. **支付成功通知** 💰
   - 订单号、金额、支付方式、交易号

3. **支付失败通知** ❌
   - 订单号、金额、失败原因

4. **订单取消通知** 🚫
   - 订单号、取消原因

5. **库存不足警告** ⚠️
   - 商品名称、当前库存、最低库存

6. **系统警告** 🚨
   - 系统错误、异常情况

### 手动命令

在Telegram中发送以下命令：

- `/start` - 开始使用机器人
- `/status` - 查看系统状态
- `/orders` - 查看今日订单

## 🛠️ 高级功能

### 1. 订单详情通知

系统会发送带按钮的订单详情，包括：
- 查看订单链接
- 处理订单按钮

### 2. 系统状态报告

可以手动发送系统状态报告，包含：
- 今日订单统计
- 总体数据统计
- 商品库存状态

### 3. 通知统计

后台可以查看通知发送统计：
- 各类型通知数量
- 发送成功率
- 配置状态检查

## 🔧 故障排除

### 常见问题

1. **机器人无响应**
   - 检查Bot Token是否正确
   - 确认机器人已启动（发送/start）

2. **收不到通知**
   - 检查Chat ID是否正确
   - 确认机器人有发送消息权限
   - 检查Webhook是否设置成功

3. **Webhook设置失败**
   - 确认服务器支持HTTPS
   - 检查防火墙设置
   - 验证URL可访问性

### 调试方法

1. **检查机器人状态**
   ```bash
   curl "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getMe"
   ```

2. **查看Webhook信息**
   ```bash
   curl "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo"
   ```

3. **测试发送消息**
   ```bash
   curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/sendMessage" \
        -d "chat_id=<YOUR_CHAT_ID>" \
        -d "text=测试消息"
   ```

## 📋 通知模板

### 新订单通知
```
🛒 *新订单通知*

订单号: NP202509140001
客户: 张三
金额: ¥99.00
支付方式: 微信支付
时间: 2025-09-14 12:00:00
状态: 待处理
```

### 支付成功通知
```
💰 *支付成功通知*

订单号: NP202509140001
金额: ¥99.00
支付方式: 微信支付
交易号: 4200001234567890
支付时间: 2025-09-14 12:05:00
```

### 系统状态报告
```
📊 *系统状态报告*

今日统计:
• 订单数: 15
• 已支付: 12
• 金额: ¥1,200.00

总体统计:
• 总订单: 1,250
• 总金额: ¥125,000.00
• 商品数: 50
• 低库存: 3

⏰ 2025-09-14 18:00:00
```

## 🔒 安全建议

1. **保护Bot Token**
   - 不要在代码中硬编码Token
   - 使用环境变量存储
   - 定期更换Token

2. **限制访问**
   - 只允许特定群组接收通知
   - 设置机器人权限
   - 监控异常活动

3. **日志记录**
   - 启用通知日志
   - 定期检查日志文件
   - 监控发送失败情况

## 📞 技术支持

如有问题，请联系：
- 邮箱: support@bohai.chat
- 电话: 400-123-4567

---

**注意**: 请确保服务器支持HTTPS，Telegram Webhook需要安全的连接。
