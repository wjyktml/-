/**
 * NextsPay API 接口封装
 * 提供统一的API调用方法
 */

class NextsPayAPI {
    constructor() {
        this.baseURL = util.getConfig().requestUrl;
        this.timeout = 30000;
    }
    
    /**
     * 通用请求方法
     */
    async request(action, data = {}, method = 'GET') {
        try {
            const url = this.baseURL + 'Index/' + action;
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                timeout: this.timeout
            };
            
            if (method === 'POST') {
                options.body = JSON.stringify(data);
            } else if (Object.keys(data).length > 0) {
                const params = new URLSearchParams(data);
                url += '?' + params.toString();
            }
            
            const response = await fetch(url, options);
            const result = await response.json();
            
            if (result.code === 200) {
                return result;
            } else {
                throw new Error(result.msg || '请求失败');
            }
        } catch (error) {
            console.error('API请求错误:', error);
            throw error;
        }
    }
    
    /**
     * 获取系统设置
     */
    async getSetting() {
        return await this.request('setting');
    }
    
    /**
     * 提交联系表单
     */
    async submitContact(formData) {
        return await this.request('contact', formData, 'POST');
    }
    
    /**
     * 获取支付配置
     */
    async getPaymentConfig() {
        return await this.request('payment_config');
    }
    
    /**
     * 创建支付订单
     */
    async createOrder(orderData) {
        return await this.request('create_order', orderData, 'POST');
    }
    
    /**
     * 创建支付订单（新版本）
     */
    async createPaymentOrder(orderData) {
        const url = 'https://api.bohai.chat/web/Payment.php?action=create_order';
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        });
        
        const result = await response.json();
        
        if (result.code === 200) {
            return result;
        } else {
            throw new Error(result.msg || '创建支付订单失败');
        }
    }
    
    /**
     * 查询订单状态
     */
    async queryOrder(orderNo) {
        return await this.request('query_order', { order_no: orderNo });
    }
    
    /**
     * 查询订单状态（新版本）
     */
    async queryPaymentOrder(orderNo) {
        const url = `https://api.bohai.chat/web/Payment.php?action=query_order&order_no=${orderNo}`;
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.code === 200) {
            return result;
        } else {
            throw new Error(result.msg || '查询订单状态失败');
        }
    }
    
    /**
     * 获取支付方式列表
     */
    getPaymentMethods() {
        return [
            {
                id: 'wechat',
                name: '微信支付',
                icon: 'images/pay/icon_pay_weixin.png',
                enabled: true
            },
            {
                id: 'alipay',
                name: '支付宝',
                icon: 'images/pay/icon_pay_zhifubao.png',
                enabled: true
            },
            {
                id: 'unionpay',
                name: '银联支付',
                icon: 'images/pay/unionpay.png',
                enabled: true
            },
            {
                id: 'apple_pay',
                name: 'Apple Pay',
                icon: 'images/pay/apple-pay.png',
                enabled: false // 需要特殊配置
            }
        ];
    }
    
    /**
     * 生成支付二维码
     */
    generatePaymentQR(orderData) {
        const qrData = {
            order_no: orderData.order_no,
            amount: orderData.amount,
            payment_type: orderData.payment_type,
            timestamp: Date.now()
        };
        
        return JSON.stringify(qrData);
    }
    
    /**
     * 验证支付结果
     */
    validatePaymentResult(result) {
        const validStatuses = ['success', 'pending', 'failed', 'expired'];
        return validStatuses.includes(result.status);
    }
    
    /**
     * 格式化金额
     */
    formatAmount(amount) {
        return (amount / 100).toFixed(2);
    }
    
    /**
     * 格式化时间
     */
    formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleString('zh-CN');
    }
}

// 创建全局API实例
const api = new NextsPayAPI();

// 导出API实例
if (typeof module !== 'undefined' && module.exports) {
    module.exports = api;
} else if (typeof window !== 'undefined') {
    window.api = api;
}
