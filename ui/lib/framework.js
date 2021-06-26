;
(function() {
    const mem = {}; //统一管理的数据
    const me = { //统一管理的配置,component可以控制自己的挂载，并保存需要的值,config部分
        appName: 'S', //挂载的名称
        funKey: 'funs', //模块方法挂载的位置
        memKeys: { //memory的基础挂载点,可以随机生成
            //database:'database',		//运行数据的根挂载点(和不同的id相关的运行数据)
            cache: 'cache', //运行时数据挂载点(和id无关的运行数据),公用部分
            component: 'coms', //组件信息的根挂载点
            timer: 'timer', //定时器的根挂载点
            queue: 'queue', //队列挂载点
        },
        hooks: { //基础的钩子部分,定义组件头部的基础钩子位置,可以写死,可以调用	
            root: 'hooks', //注册到根下的方法
            loop: 'loops', //加入循环的方法
            //init:'init',				//初始化组件的方法
        },
        debug: { //是否进行调试，监控整理框架的运行效率
            enable: true,
            start: 0,
        },
        host: {}, //自动分析保存的服务器访问信息
        device: null, //设备检测
        version: 1, //内部版本号
        auth: ['fuu'],
        nickName: 'Monkey', //内部开发代号
        delay: 200, //自动检测的时间间隔
    };
    const run = { timer: null };

    const self = {
        /*基础功能函数部分，可以对me进行操作*/
        init: function() {
            if (me.debug.enable) me.debug.start = self.stamp(); //调试时打时间戳，可以看运行时间
            for (let k in me.memKeys) self.regMemory({}, [me.memKeys[k]], true); //构建基础的数据结构
            me.device = self.detect();
            me.host = self.analysisUrl();
            //console.log(me.host);
        },

        dump: function(k) {
            if (mem[k] != undefined) return console.log(mem[k]);
            return console.log(mem);
        },

        load: function(name) {

        },

        /*	cookie的设置，这里需要解决兼容性问题
         *	IE,chrome,微信等所有的浏览器入口都要兼容
         * 
         * 
         * */
        setCookie: function(k, v) {
            if (self.isWeixin()) {
                localStorage[k] = v;
            } else {
                localStorage[k] = v;
            }
            return true;
        },

        getCookie: function(k) {
            if (self.isWeixin()) {
                return localStorage[k] ? localStorage[k] : false;
            } else {
                return localStorage[k] ? localStorage[k] : false;
            }
        },

        removeCookie: function(k) {
            if (self.isWeixin()) {

            } else {
                return localStorage.removeItem(k);
            }
        },
        clearCookie: function() {
            if (self.isWeixin()) {
                localStorage.clear();
            } else {
                localStorage.clear();
            }
            return true;
        },

        isWeixin: function() {
            //console.log(navigator.userAgent.toLowerCase());
            const nav = navigator.userAgent.toLowerCase();
            //console.log(nav)
            return (nav.indexOf('micromessenger') != -1);
        },

        /*组件注册方法
         * @param	r	object	//组件的注册信息
         * 		r[me.funKey]		object	//组件所有方法的挂载位置
         *		r.name				string	//组件名称
         * 		r.hooks				array	//直接挂载到root下的方法名
         * 		r.loops					array	//直接挂载到root下的循环方法
         * 		r.autoRun			string	//组件注册时自动运行的方法名
         * 		
         * */
        regComponent: function(r) {
            if (self.type(r) != 'object') return self.error();
            const fun = r[me.funKey];
            self[r.name] = fun;

            //挂载方法到root下
            const hks = r.hooks,
                lps = r.loops;
            if (hks && hks.length > 0)
                for (let i in hks) window[me.appName][hks[i]] = fun[hks[i]];
            if (lps && lps.length > 0)
                for (let i in lps) window[me.appName][lps[i]] = fun[lps[i]];

            if (r.autoRun) fun[r.autoRun]();
            delete(r[me.funKey]);
            if (mem[me.memKeys.component][r.name] != undefined) console.log('module ' + r.name + ' is already existing..');
            mem[me.memKeys.component][r.name] = r;
        },


        /*核心的数据处理部分，最基础的部分*/
        getConfig: function(chain) {
            if (chain === undefined) return me;
            if (self.type(chain) == 'array') {
                const rst = false;
                for (let i in chain)
                    if (me[chain[i]]) { rst = me[chain[i]] } else { return false }
                return rst;
            }
            return me;
        },

        /*向me里添加配置
         * @param	data	object	//需要写入的数据，不能使function
         * @param	chian	array	//写入的链
         * @param	forec	boolean	//是否强制新建链(防止复写)
         * */
        regConfig: function(data, chain, force) {
            if (!chain || !self.isType(chain, 'array')) return self.error('chain is not array...');
            if (self.type(data) === 'function') return self.error('function can not add to me');
            force = force == undefined ? false : force;
            return self.extend(me, data, chain, force);
        },

        /*定时任务，统一到framework里进行处理，不同的渲染器都可以调用
         * 
         * */
        ticktack: function() {

        },


        checkTimer: function() {
            //1.检测检查队列是否为空，不然就开始定时器
            //2.遍历队列，进行状态检查
            //3.杀死不运行的死队列（根据时间戳检查，主要是ajax的问题）
            if (run.timer == undefined) run.timer = window.setInterval(self.checkTimer, me.delay);
            const tms = self.getMemory([me.memKeys.timer]),
                after = {};
            if (!self.empty(tms))
                for (k in tms)
                    if (!tms[k]()) after[k] = tms[k];
            self.regMemory(after, [me.memKeys.timer], true);
        },

        killTimer: function(name) { //杀掉时间检测进程

        },

        regTimer: function(name, fun) {
            const chain = [me.memKeys.timer, name];
            root.regMemory(fun, chain, true);
            //1.回调的时候处理掉标志位
        },

        //获取memory的数据,需要拷贝的话请加clone
        getMemory: function(chain, clone) {
            if (chain && self.isType(chain, 'array')) {
                let rst = mem;
                for (let i in chain) {
                    if (rst[chain[i]] == undefined) return false;
                    rst = rst[chain[i]];
                }
                return clone ? self.clone(rst) : rst;
            }
            return false;
        },

        /*注册管理数据的方法
         *  @param	data 	no function	//需要注册的数据
         *	@param	chain	array			//注册的路径
         *  @param	force	boolean		//是否强制复写,true的话原节点数据全部覆盖，false会添加节点
         * 	return
         * 	写好数据/false
         * */
        regMemory: function(data, chain, force) {
            if (!chain || !self.isType(chain, 'array')) return self.error('memory chain is not array...');
            if (self.type(data) === 'function') return self.error('function can not add to memory..');
            force = force == undefined ? false : force;
            //console.log(data)
            return self.extend(mem, data, chain, force);
        },

        /*清理对应的节点及数据*/
        clearNode: function(chain) {
            const len = chain.length,
                p = mem;
            for (let i = 0; i < len; i++) {
                const k = chain[i];
                if (i == (len - 1)) {
                    if (!p[k]) return false;
                    delete p[k];
                    return true;
                } else {
                    if (!p[k]) return false;
                    else p = p[k];
                }
            }
        },

        /*运行加载到struct的组件程序,实现运行app环境的构建
         *@param	p	object	//{"target":"#dwg","conWidth":770,"conHeight":858,"convert":1000,"setting":{}},交给APP处理的数据
         * return
         * 调用组件的hooks运行程序
         * */
        struct: function(p) {
            const cps = mem[me.memKeys.component];
            for (let k in cps) {
                if (!self.empty(cps[k].hooks)) {
                    const hks = cps[k].hooks,
                        len = hks.length;
                    for (let j = 0; j < len; j++) window[me.appName][hks[j]](p);
                }
            }
        },

        /*APP运行的循环方法，实现APP的运行
         *@param	data	object	//APP需要处理的数据
         * return
         * 调用组件的hooks运行程序
         * */
        loop: function() {
            self.checkTimer(); //对定时器进行处理
            const cps = mem[me.memKeys.component];
            for (let k in cps) {
                if (!self.empty(cps[k].loops)) {
                    const lps = cps[k].loops;
                    for (let j in lps) window[me.appName][lps[j]]();
                }
            }
        },

        /*AJAX实现的数据获取
         * @param	server	string	//服务器位置
         * @param	cfg		object	//{mod:'',act:'',param:{}} 接口数据结构
         * @param	ck		function	//回调函数
         * return
         * 处理成json的返回数据
         * */
        jsonp: function(server, cfg, ck) {
            let furl = server + '?dv=jsonp&mod=' + cfg.mod + '&act=' + cfg.act;
            if (cfg.token) furl += '&t=' + cfg.token
            if (cfg.param != undefined)
                for (let k in cfg.param) furl += '&' + k + '=' + cfg.param[k];
            furl += '&callback=?'

            console.log(furl);

            $.getJSON({
                type: 'get',
                url: furl,
                async: false,
                success: function(res) {
                    if (!res.success) {
                        self.error('server failed:' + cfg.mod + '->' + cfg.act + ',messsage:' + res.message);
                        if (res.expired && self.keepAlive) self.keepAlive(); //维持spam的活性
                    }

                    if (cfg.check) {
                        if (cfg.check(res)) return ck && ck(res);
                        self.error('[ important ]JSONP check error:' + furl);
                    } else {
                        ck && ck(res);
                    }
                }
            })
        },

        error: function(t) {
            t = t || 'function error...';
            return console.log('error:' + t);
            //dhtmlx.message({type: "error",top:'200',left:'10',text: t,expire: -1})
        },

        /*浅复制对象,注意array的处理*/
        clone: function(obj, dep) {
            if (self.isType(obj, 'object')) return $.extend({}, obj);
            if (self.isType(obj, 'array')) return $.extend([], obj);
            return obj;
        },

        //fuu,按照jquery的逻辑进行重写,提升效率
        extend: function(target, data, chain, force) {
            const len = chain.length
            let p = target;
            for (let i in chain) {
                const kk = chain[i];
                if (i == len - 1) {
                    if (p[kk]) {
                        if (force) {
                            delete p[kk];
                            p[kk] = data;
                        } else {
                            if (self.isType(data, 'object')) {
                                for (dk in data) {
                                    p[kk][dk] = data[dk];
                                }
                            } else {
                                delete p[kk];
                                p[kk] = data;
                            }
                        }
                    } else {
                        if (force) p[kk] = data;
                        else return false;
                    }
                } else {
                    if (!p[kk]) {
                        if (force) p = p[kk] = {};
                        else return false;
                    } else {
                        p = p[kk];
                    }
                }
            }
            return true;
        },

        //获取obj下的节点数据
        getNode: function(path, obj) {
            const rst = obj;
            for (let i in path) {
                const s = path[i];
                if (rst[s] == undefined) return false;
                rst = rst[s];
            }
            return rst;
        },

        //队列处理部分,历史保存部分要用到
        queueClear: function(k) {
            const chain = [me.memKeys.queue, k];
            return self.regMemory([], chain, true);
        },

        queueAdd: function(id, k) {
            const chain = [me.memKeys.queue, k];
            let arr = self.getMemory(chain);
            if (self.empty(arr)) arr = [];
            arr.push(id);
            self.regMemory(arr, chain, true);
            return arr.length - 1;
        },

        queueUnique: function(arr, k) {
            const chain = [me.memKeys.queue, k];
            let qu = self.getMemory(chain);
            if (self.empty(qu)) qu = [];
            for (let k in arr) qu.push(arr[k]);
            return self.regMemory(arr, chain, true);
        },

        queueRemove: function(id, k) {
            const chain = [me.memKeys.queue, k];
            let arr = self.getMemory(chain),
                narr = [];
            for (let i in arr)
                if (i == id) arr[i] = null;
            for (let i in arr) {
                if (arr[i] != null) narr.push(arr[i]);
            }
            return self.regMemory(narr, chain);
        },

        queuePop: function(k) {
            const chain = [me.memKeys.queue, k],
                arr = self.getMemory(chain);
            return arr.pop();
        },

        queueInit: function(k) {
            const chain = [me.memKeys.queue, k]
            return self.regMemory([], chain, true);
        },

        getQueue: function(k) {
            return self.getMemory([me.memKeys.queue, k]);
        },

        //按照屏幕的分辨率进行设备处理，需要在有宽度的时候进行处理


        //把json转换成单一字符串,特殊情况使用
        encode: function(obj, kv, cnt) {
            kv = kv || ':';
            cnt = cnt || '-';
            const arr = [];
            for (let k in obj) arr.push(k + ':' + obj[k]);
            return arr.join(cnt);
        },

        //把字符串转换成json,特殊情况使用
        decode: function(str, kv, cnt) {
            if (!str) return {};
            kv = kv || ':';
            cnt = cnt || '-';
            const arr = str.split(cnt),
                rst = {};
            if (self.empty(arr)) return rst;
            for (let k in arr) {
                const tmp = arr[k].split(kv);
                rst[tmp[0]] = tmp[1];
            }
            return rst;
        },
        debug: function(txt, force) {
            txt = txt || '';
            if (me.debug.enable) {
                if (force) return console.log(txt + ' range:' + (self.stamp() - me.debug.start));
                const end = self.stamp();
                return console.log('Debug info:\ns-time:' + me.debug.start + '\ne-time:' + end + '\n' + 'cost:' + (end - me.debug.start) + 'ms');
            }
        },
        getUrlXY: function() { const h = window.location.hash,
                rst = []; const b = h.substring(1, h.length).split('-'); for (let i = 0, len = b.length; i < len; i++) rst.push(parseInt(b[i])); return rst; },
        getID: function(s) { const a = s.split('_'); return a[a.length - 1] },
        analysisUrl: function() {
            let arr = location.pathname.split('/');
            arr.pop();
            let svc = location.host,
                pt = '/';
            for (let i = 0, len = arr.length; i < len - 1; i++) svc += arr[i] + (i == (len - 1) ? "" : "/");
            for (let i = 1, len = arr.length; i < len; i++) pt += arr[i] += "/";
            return { protocol: location.protocol, domain: location.host, ajax: location.protocol + "//" + svc, path: pt };
        },
        getLocal: function(url, ck) { $.ajax({ type: "get", url: url, async: false, success: function(data) { ck && ck(data) } }) },
        hash: hash,
        rand: function(m, n) { return Math.floor(Math.random() * (m - n + 1) + n) },
        chr: function(n, pre) { n = n || 7;
            pre = pre || ''; for (let i = 0; i < n; i++) pre += i % 2 ? String.fromCharCode(self.rand(65, 90)) : String.fromCharCode(self.rand(97, 122)); return pre },
        stamp: function() { return new Date().getTime() },
        empty: function(o) { for (let z in o) { return false } return true },
        isType: function(obj, type) { return !!type ? self.type(obj) === type.toLowerCase() : self.type(obj) },
        type: function(obj) { return Object.prototype.toString.call(obj).slice(8, -1).toLowerCase() },
        inArray: function(k, a) { if (!a) { return false }; for (i = 0; i < a.length; i++)
                if (k == a[i]) return true;
            return false },
        trim: function(s) { return s.replace(/(^\s*)|(\s*$)/g, "") },
        toF: function(a, fix) { fix = fix || 3; return parseFloat(a.toFixed(fix)) },

        detect: function() {
            const ua = navigator.userAgent,
                platform = navigator.platform;
            const os = {},
                browser = {},
                webkit = ua.match(/Web[kK]it[\/]{0,1}([\d.]+)/),
                android = ua.match(/(Android);?[\s\/]+([\d.]+)?/),
                osx = !!ua.match(/\(Macintosh\; Intel /),
                ipad = ua.match(/(iPad).*OS\s([\d_]+)/),
                ipod = ua.match(/(iPod)(.*OS\s([\d_]+))?/),
                iphone = !ipad && ua.match(/(iPhone\sOS)\s([\d_]+)/),
                webos = ua.match(/(webOS|hpwOS)[\s\/]([\d.]+)/),
                win = /Win\d{2}|Windows/.test(platform),
                wp = ua.match(/Windows Phone ([\d.]+)/),
                touchpad = webos && ua.match(/TouchPad/),
                kindle = ua.match(/Kindle\/([\d.]+)/),
                silk = ua.match(/Silk\/([\d._]+)/),
                blackberry = ua.match(/(BlackBerry).*Version\/([\d.]+)/),
                bb10 = ua.match(/(BB10).*Version\/([\d.]+)/),
                rimtabletos = ua.match(/(RIM\sTablet\sOS)\s([\d.]+)/),
                playbook = ua.match(/PlayBook/),
                chrome = ua.match(/Chrome\/([\d.]+)/) || ua.match(/CriOS\/([\d.]+)/),
                firefox = ua.match(/Firefox\/([\d.]+)/),
                firefoxos = ua.match(/\((?:Mobile|Tablet); rv:([\d.]+)\).*Firefox\/[\d.]+/),
                ie = ua.match(/MSIE\s([\d.]+)/) || ua.match(/Trident\/[\d](?=[^\?]+).*rv:([0-9.].)/),
                webview = !chrome && ua.match(/(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/),
                safari = webview || ua.match(/Version\/([\d.]+)([^S](Safari)|[^M]*(Mobile)[^S]*(Safari))/);

            if (browser.webkit = !!webkit) browser.version = webkit[1];

            if (android) os.android = true, os.version = android[2];
            if (iphone && !ipod) os.ios = os.iphone = true, os.version = iphone[2].replace(/_/g, '.');
            if (ipad) os.ios = os.ipad = true, os.version = ipad[2].replace(/_/g, '.');
            if (ipod) os.ios = os.ipod = true, os.version = ipod[3] ? ipod[3].replace(/_/g, '.') : null;
            if (wp) os.wp = true, os.version = wp[1];
            if (webos) os.webos = true, os.version = webos[2];
            if (touchpad) os.touchpad = true;
            if (blackberry) os.blackberry = true, os.version = blackberry[2];
            if (bb10) os.bb10 = true, os.version = bb10[2];
            if (rimtabletos) os.rimtabletos = true, os.version = rimtabletos[2];
            if (playbook) browser.playbook = true;
            if (kindle) os.kindle = true, os.version = kindle[1];
            if (silk) browser.silk = true, browser.version = silk[1];
            if (!silk && os.android && ua.match(/Kindle Fire/)) browser.silk = true;
            if (chrome) browser.chrome = true, browser.version = chrome[1];
            if (firefox) browser.firefox = true, browser.version = firefox[1];
            if (firefoxos) os.firefoxos = true, os.version = firefoxos[1];
            if (ie) browser.ie = true, browser.version = ie[1];
            if (safari && (osx || os.ios || win)) {
                browser.safari = true;
                if (!os.ios) browser.version = safari[1];
            }
            if (webview) browser.webview = true;

            os.tablet = !!(ipad || playbook || (android && !ua.match(/Mobile/)) ||
                (firefox && ua.match(/Tablet/)) || (ie && !ua.match(/Phone/) && ua.match(/Touch/)))
            os.phone = !!(!os.tablet && !os.ipod && (android || iphone || webos || blackberry || bb10 ||
                (chrome && ua.match(/Android/)) || (chrome && ua.match(/CriOS\/([\d.]+)/)) ||
                (firefox && ua.match(/Mobile/)) || (ie && ua.match(/Touch/))));
            return { browser: browser, os: os };
        },
    };

    function hash(n) { return Math.random().toString(36).substr(n != undefined ? n : 6) };
    window[me.appName] = self;
    self.init();
})();