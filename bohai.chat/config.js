/**
 * NextsPay 配置文件
 * 包含API配置、支付配置等
 */

const CONFIG = {
    // API配置
    API: {
        BASE_URL: 'https://api.bohai.chat/web/',
        TIMEOUT: 30000,
        VERSION: '1.0.0'
    },
    
    // 支付配置
    PAYMENT: {
        // 支持的支付方式
        METHODS: {
            WECHAT: 'wechat',
            ALIPAY: 'alipay', 
            UNIONPAY: 'unionpay',
            APPLE_PAY: 'apple_pay'
        },
        
        // 支付方式显示名称
        METHOD_NAMES: {
            wechat: '微信支付',
            alipay: '支付宝',
            unionpay: '银联支付',
            apple_pay: 'Apple Pay'
        },
        
        // 支付方式图标
        METHOD_ICONS: {
            wechat: 'images/pay/icon_pay_weixin.png',
            alipay: 'images/pay/icon_pay_zhifubao.png',
            unionpay: 'images/pay/unionpay.png',
            apple_pay: 'images/pay/apple-pay.png'
        }
    },
    
    // 应用配置
    APP: {
        NAME: 'NextsPay',
        VERSION: '1.0.0',
        DESCRIPTION: '专业的支付解决方案',
        LOGO: 'images/logo-default-1-324x88.png',
        LOGO_LIGHT: 'images/logo-default-2-324x88.png'
    },
    
    // 联系信息
    CONTACT: {
        PHONE: '400-123-4567',
        EMAIL: 'support@bohai.chat',
        ADDRESS: '北京市朝阳区xxx大厦',
        WORK_TIME: '周一至周五 9:00-18:00'
    },
    
    // 行业选项
    INDUSTRIES: [
        { value: '私域', label: '私域' },
        { value: '微商', label: '微商' },
        { value: '餐饮', label: '餐饮' },
        { value: '零售', label: '零售' },
        { value: '电商', label: '电商' },
        { value: '游戏', label: '游戏' },
        { value: '培训', label: '培训' },
        { value: '其他', label: '其他' }
    ],
    
    // 页面配置
    PAGES: {
        SECTIONS: ['home', 'advantage', 'sdk', 'roadmap', 'contacts'],
        DEFAULT_SECTION: 'home'
    },
    
    // 二维码配置
    QR_CODE: {
        WIDTH: 200,
        HEIGHT: 200,
        MOBILE_WIDTH: 150,
        MOBILE_HEIGHT: 150
    },
    
    // 表单验证规则
    VALIDATION: {
        NAME: {
            required: true,
            minLength: 2,
            maxLength: 50,
            message: '请输入联系人名称'
        },
        MOBILE: {
            required: true,
            pattern: /^1[3456789]\d{9}$/,
            message: '手机号格式错误'
        },
        EMAIL: {
            pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            message: '邮箱格式错误'
        }
    },
    
    // 错误消息
    MESSAGES: {
        SUCCESS: {
            CONTACT_SUBMIT: '提交成功，我们会尽快与您联系！',
            ORDER_CREATE: '订单创建成功',
            PAYMENT_SUCCESS: '支付成功'
        },
        ERROR: {
            NETWORK: '网络连接失败，请检查网络设置',
            SERVER: '服务器错误，请稍后重试',
            VALIDATION: '请检查输入信息',
            CONTACT_SUBMIT: '提交失败，请稍后重试',
            ORDER_CREATE: '订单创建失败',
            PAYMENT_FAILED: '支付失败'
        }
    },
    
    // 开发环境配置
    DEBUG: {
        ENABLED: false, // 生产环境请设置为false
        LOG_LEVEL: 'info', // debug, info, warn, error
        MOCK_DATA: false // 是否使用模拟数据
    }
};

// 导出配置
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CONFIG;
} else if (typeof window !== 'undefined') {
    window.CONFIG = CONFIG;
}
