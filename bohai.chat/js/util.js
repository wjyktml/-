const util = {
    uKey: 'swp-web-user',
    tKey: 'swp-web-token',
    getUser() {
        let manager = this.getCache(util.uKey)
        if(manager) {
            manager = JSON.parse(manager)
            return manager
        }
        return null
    },
    getConfig() {
        let domain = 'api.bohai.chat'
        let wss = 'wss'
        let https = 'https'
        return {
            requestUrl: https + '://' + domain + '/web/',
            name: 'NextsPay',
        }
    },
    setToken(token) {
        return this.setCache(util.tKey,token);
    },
    getToken() {
        return this.getCache(util.tKey)
    },
    delToken(token) {
        return this.delCache(token);
    },
    setCache(key,value) {
        return localStorage.setItem(key,value);
    },
    getCache(key) {
        return localStorage.getItem(key);
    },
    isMobileDevices() {
        if ((navigator.userAgent.match(/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i))){
            return true
        }
        return false
    },
    loadScript(url, callback) {
    	var script = document.createElement('script');
    	script.type = 'text/javascript';
    	if (script.readyState) {
    		script.onreadystatechange = function() {
    			if (script.readyState == 'complete' || script.readyState == 'loaded') {
    				callback();
    			}
    		};
    	} else {
    		script.onload = function() {
    			callback();
    		};
    	}
    	script.src = url;
    	document.head.appendChild(script);
    }
}