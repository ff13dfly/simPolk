;(function($){
	const config={
		name:'pager',	//app的名称
	}
	
	const lang={
		end:'已经是最后一页',
		loading:'正在加载内容...',
		//empty:'<img style="width:66%" src="view/v1/static/none.png"><h4>没有内容</h4>',
		empty:'<div class="page" style="text-align:center;padding:50px 0px 250px 0px"><div class="weui-msg__icon-area"><i class="weui-icon-warn weui-icon_msg"></i></div><div class="weui-msg__text-area"> <h2 class="weui-msg__title">暂无数据</h2></div>'
	}
	
	const run={
		page:0,				//初始化的页码数;
		end:false,			//是否到了最后一页;
		lock:false,			//请求锁，防止重复请求;
		append:true,		//是否为添加数据;
		container:'',		//scroller容器的ID;
		
		tpl:'',
		auto:null,
	}
	
	const cache={}
	
	const self={
		/* 	tpl	string template				//{value}进行字符串替换
		 * 	con	string						//target id
		 *  translate	array				//{type:function(val){return val+1}};
		 * 	ajax	function				//fun(page,param,ck)
		 * 	auto	object or function		//[{selector:'.class_name',event:'click',fun:function(){}},...]
		 * 	setting	object					//{lang:lang}
		 * 	events	object					//{before:function(){},after:function(){}}
		 */
		show:function(tpl,translate,con,ajax,auto,setting,events){
			self.init();		//重置参数
			run.auto=auto;
			run.tpl=tpl;
			run.translate=translate;
			
			//准备dom结构,兼容iscroll
			if(run.container==''){
				run.container=self.hash();
				$(con).html(`<div id="${run.container}"></div>`);
			}
			
			//填充前处理事件
			if(events && events.before) events.before();
			
			//开始加载页面
			self.page(ajax,function(){
				if(events && events.after) events.after();
				
				self.scroll(con,ajax)
			});
		},
		
		scroll:function(con,ajax){
			const sel=$(window);
			sel.off('scroll').on('scroll',function(){
				if(run.lock) return false;
				
				const h =sel.height(),sh=sel.scrollTop();		//滚动高度
				const ch=$(document).height(),fix=2;			//fix是防止文档过长之后，计算存在误差
				if(sh+h+fix < ch) return false;
				
				run.lock=true;		//锁定请求;
				
				if(run.end){
					self.end();
				}else{
					self.loading(function(hash){
						self.page(ajax,function(){
							run.lock=false;
							$('#'+hash).remove();
						});
					});
				}
			});
		},
		page:function(ajax,ck){
			ajax(run.page+1,function(list,max){
				if(list.length!=0){
					const dom=self.domList(list,run.tpl,run.translate);
					const container=$('#'+run.container);
					run.page==0?container.html(dom):container.append(dom);
					
					if(run.auto!=null)self.autoRun(run.auto);
					
					run.page++;
					if(max==run.page) run.end=true;
				}else{
					self.empty()
				}
				
				ck && ck();
			});
		},
		autoRun:function(arr){
			for(let i=0;i<arr.length;i++){
				const todo=arr[i];
				$(todo.selector).off(todo.event).on(todo.event,todo.fun);
			}
		},
		init:function(){
			run.page=0;
			run.lock=false;
			run.end=false;
			run.container='';
		},
		loading:function(ck){
			const hash=self.hash();
			const dom=`<div id="${hash}" style="text-align:center">${lang.loading}</div>`;
			$('#'+run.container).append(dom);
			setTimeout(function(){
				ck && ck(hash);
			},500);
		},
		empty:function(){
			$('#'+run.container).html(`<div style="text-align:center;margin-top:10px;">${lang.empty}</div>`);
		},
		end:function(){
			const hash=self.hash();
			const dom=`<div id="${hash}" style="text-align:center">${lang.end}</div>`;
			$('#'+run.container).append(dom);
			
			setTimeout(function(){
				$('#'+hash).remove();
			},1500);
		},
		domList:function(list,tpl,translate){
			let dom='';
			const fill=self.fillTemplate;
			for(let i=0;i<list.length;i++){
				dom+=fill(list[i],tpl,translate);
			}
			return dom;
		},
		fillTemplate:function(row,tpl,translate){
			for(let k in row){
				const pattern=new RegExp('{'+k+'}','g');
				tpl=tpl.replace(pattern,translate[k]?translate[k](row[k]):row[k]);
			}
			return tpl;
		},
		hash:function(n){return Math.random().toString(36).substr(n!=undefined?n:6)},
	}
	
	window[config.name]={};
	$.extend(true, window[config.name], self);
})(jQuery)
