function loadScript(url, callback) {
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

// window.onload = function() {
	new Vue({
		el: '#appPage',
		data() {
			return {
				activeSection: 'home', // 默认激活的section
				isMobile: false,
				manager: {},
				form: {
				    name: '',
				    mobile: '',
				    remark: '',
				    email: '',
				    hy: '',
				},
				rules: {
                    name: [
                        { required: true, message: '请输入联系人名称', trigger: 'blur' },
                        { min: 2, max: 50, message: '长度在 2 到 50 个字符', trigger: 'blur' }
                    ],
                    mobile:[
                        { required:true, message:'手机号必填', trigger:'blur' },
                        {
                            pattern: /^1[3456789]\d{9}$/,
                            message: '手机号格式错误',
                            trigger: 'blur'
                        }
                    ],
                },
				codeTitle: '',
				dialogVisibleCode: false,
				showPage: false,
				options: [
				    {
                        value: '私域',
                        label: '私域'
                    },
                    {
                        value: '微商',
                        label: '微商'
                    },
                    {
                        value: '餐饮',
                        label: '餐饮'
                    }, {
                        value: '零售',
                        label: '零售'
                    }, {
                        value: '电商',
                        label: '电商'
                    }, {
                        value: '游戏',
                        label: '游戏'
                    }, {
                        value: '培训',
                        label: '培训'
                    }, {
                        value: '其他',
                        label: '其他'
                    }
                ],
                disabled: false,
                setting: {},
			}
		},
		created() {
			this.isMobile = this.isMobileDevices()
		},
		mounted() {
			let that = this
			window.addEventListener('scroll', this.updateActiveSection);
        	this.updateActiveSection(); // 初始化时设置激活状态

        	window.onresize = () => {
                return (() => {
                    that.isMobile = that.isMobileDevices()
                })()
            }

			//window.addEventListener('scroll', this.handleScroll);
			//console.log('vue loading')
			//loadScript('js/element.js',function(){
			    //console.log('加载element.js')
    			loadScript('js/core.min.js', function() {
    				//console.log('加载core.min.js')
    				loadScript('js/script.js', function() {
    					//console.log('加载script.js')
    					that.showPage = true
    					
    					if(util.getToken()) {
    					    that.manager = util.getUser()
    					}
    					
    					if(that.isMobile) {
    					    setTimeout(function() {
    					        //console.log(that.$refs.swpVideo)
    					        //that.$refs.swpVideo.$el.style.display = 'none'
    					    }, 500);
    					    
    					}
    					
    				})
    			})
    			
    			let url = util.getConfig().requestUrl + 'Index/setting'
    			fetch(url).then(res => res.json()).then(data => {
                    if(data.code === 200) {
                        that.setting = data.result
                        //console.log('setting',that.setting)
                    } 
                })
			//})
		},
		/*watch: {
            isMobile: {
            	handler: function (val) {
            		console.log(val+'==>')
            	},
            	immediate: true,
                deep:true
            }
        },*/
		methods: {
		    closeCode() {
                this.codeTitle = ''
                this.dialogVisibleCode = false

                $('.showHomePageCode').html('')
            },
		    open(shopName,homePage) {
		        if(!this.setting.default_shop_key) {
		            this.$message.warning('请联系客服')
		            this.scrollToSection('contacts')
		            return
		        }
		        homePage = this.setting.h5_url + '/#/pro/index?key=' + this.setting.default_shop_key
		        let that = this
                this.codeTitle = shopName
                this.dialogVisibleCode = true
                setTimeout(function () {
                    $('.showHomePageCode').qrcode({
                        render:"canvas",
                        width: that.isMobile ? 150 : 200,
                        height: that.isMobile ? 150 : 200,
                        text: homePage
                    })
                },300)
            },
			/*jump(ref) {
				this.$refs[ref].scrollIntoView({
					behavior: 'smooth'
				})
			},
			isScrolledIntoView ( elem ) {
				$window = $(window)
				return elem.offset().top + elem.outerHeight() >= $window.scrollTop() && elem.offset().top <= $window.scrollTop() + $window.height();
			},
			handleScroll() {
				console.log('监听滚动',this.isScrolledIntoView($('#advantage'))) //this.$refs['advantage']
			},*/
			//
			scrollToSection(sectionId) {
	            const targetSection = document.getElementById(sectionId);
	            if (targetSection) {
	                targetSection.scrollIntoView({ behavior: 'smooth' });
	                this.activeSection = sectionId;
	            }
	        },
	        updateActiveSection() {
	            const sections = ['home', 'advantage', 'sdk', 'roadmap', 'contacts'];
	            let currentSection = null;

	            sections.some(section => {
	                const elem = document.getElementById(section);
	                if (this.isScrolledIntoView(elem)) {
	                    currentSection = section;
	                    return true;
	                }
	                return false;
	            });

	            if (currentSection !== this.activeSection) {
	                this.activeSection = currentSection || '';//home
	            }
	        },
	        isScrolledIntoView(elem) {
	            if (!elem) return false;
	            const rect = elem.getBoundingClientRect();
	            return (
	                rect.top >= 0 &&
	                rect.left >= 0 &&
	                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
	                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
	            );
	        },
	        isMobileDevices() {
		        if ((navigator.userAgent.match(/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i))){
		            return true
		        }
		        return false
		    },
		    onSubmit(formName) {
		        let that = this
		        this.$refs[formName].validate((valid) => {
                    if (valid) {
                        that.disabled = true
                        let p = JSON.stringify(this.form)
        	            let url = util.getConfig().requestUrl + 'Index/contact?s=' + p
        	            fetch(url).then(res => res.json()).then(data => {
        	                that.disabled = false
                            if(data.code === 200) {
                                this.form = {
                                    name: '',
                                    mobile: '',
                                    remark: '',
                                    email: '',
                                    hy: ''
                                }
                                this.$message.success(data.msg)
                            } else {
                                this.$message.error(data.msg)
                            }
                        })
                    } else {
                        return false;
                    }
                });
                
    	       
    	       // if($.trim(this.form.name) == '') {
    	       //     this.$message.error('请输入行业名称')
    	       //     return
    	       // }
    	       // if($.trim(this.form.mobile) == '') {
    	       //     this.$message.error('请输入联系手机')
    	       //     return
    	       // }
    	       // let p = JSON.stringify(this.form)
	           // let url = util.getConfig().requestUrl + 'Index/contact?s=' + p
	           // fetch(url).then(res => res.json()).then(data => {
            //         if(data.code === 200) {
            //             this.form = {
            //                 name: '',
            //                 mobile: '',
            //                 remark: '',
            //                 email: '',
            //                 hy: ''
            //             }
            //             this.$message.success(data.msg)
            //         } else {
            //             this.$message.error(data.msg)
            //         }
            //     })
    	        
    	    }
		}
	})
// }
