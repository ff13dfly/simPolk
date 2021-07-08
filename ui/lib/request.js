(function() {
    var me = {
        debug: true,
        type: {
            'storage': {},
            'event': {},
            'contact': {},
            'basic': {},
        },

        server: 'http://localhost/simPolk/api/entry.php',
    };
    var tasks = [];
    var enableTasks = false;

    var self = {
        load: function(page, target) {
            var purl = 'tpl/' + page + '.html';
            $(target).hide();
            $.ajax({
                url: purl,
                timeout: 20000,
                success: function(res) {
                    //console.log(res)
                    var dom = '<div id="wp_' + page + '" class="animate_page">' + res + '</div>';
                    $(target).html(dom).show();
                }
            });
        },

        /* main method of simulator
         *
         * */
        request: function(mod, act, param, ck) {
            var cfg = { mod: mod, act: act, param: param };
            self.jsonp(me.server, cfg, function(res) {
                if (ck) ck(res);
            });
        },

        jsonp: function(server, cfg, ck) {
            var furl = server + '?mod=' + cfg.mod + '&act=' + cfg.act;
            if (cfg.param != undefined)
                for (var k in cfg.param) furl += '&' + k + '=' + cfg.param[k];
            furl += '&callback=?';

            if (me.debug) console.log(furl);

            $.getJSON({
                type: 'get',
                url: furl,
                async: true,
                success: function(res) {
                    if (!res.success) self.error('server failed:' + cfg.mod + '->' + cfg.act + ',messsage:' + res.message);
                    if (ck) ck(res);
                    //ck && ck(res);
                }
            });
        },
        error: function(txt) {
            console.log(txt);
        },

        setTask: function(fun) {
            tasks.push(fun);
            return true;
        },
        cleanTask: function() {
            for (var k in tasks) {
                delete taks[k];
            }
            tasks = [];
            return true;
        },
        runTask: function() {
            if (!enableTasks) return false;
            for (var k in tasks) tasks[k]();
            return true;
        },
        disableTask: function() {
            enableTasks = false;
        },
        enableTask: function() {
            enableTasks = true;
        },
    };

    window.simPolk = self;
})();