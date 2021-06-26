;
(function() {
    const me = {
        debug: true,
        type: {
            'storage': {},
            'event': {},
            'contact': {},
            'basic': {},
        },

        server: 'http://localhost/simPolk/api/entry.php',
    }

    const self = {
        load: function(page, target) {
            const purl = 'tpl/' + page + '.html';
            $(target).hide();
            $.ajax({
                url: purl,
                timeout: 20000,
                success: function(res) {
                    //console.log(res)
                    let dom = '<div id="wp_' + page + '" class="animate_page">' + res + '</div>';
                    $(target).html(dom).show();
                }
            });
        },

        /* main method of simulator
         *
         * */
        request: function(mod, act, param, ck) {
            const cfg = { mod: mod, act: act, param: param }
            self.jsonp(me.server, cfg, function(res) {
                ck && ck(res);
            });
        },

        jsonp: function(server, cfg, ck) {
            let furl = server + '?mod=' + cfg.mod + '&act=' + cfg.act;
            if (cfg.param != undefined)
                for (let k in cfg.param) furl += '&' + k + '=' + cfg.param[k];
            furl += '&callback=?'

            if (me.debug) console.log(furl);

            $.getJSON({
                type: 'get',
                url: furl,
                async: true,
                success: function(res) {
                    if (!res.success) return self.error('server failed:' + cfg.mod + '->' + cfg.act + ',messsage:' + res.message);
                    ck && ck(res);
                }
            })
        },
        error: function(txt) {
            console.log(txt);
        },
    }
    window['simPolk'] = self;
})();