var loadCnt = {};
window.debug = false;
window.clickBackButton = false;
window.countProblems = 1; //счетчик страниц для показа рекламы
window.bannerLoading = false;
window.banner_prepare = false;
window.initFraimework = function(){
	
	//проверка версии и автообновление
	if(window.mlfConfig.last_version != window.mlfConfig.version) {
		$('body').prepend('<div class="mlf__wrap"></div>');
		loadBaseDefault();
		if(typeof navigator.splashscreen != 'undefined'){ 
			navigator.splashscreen.hide();
		}
		return;
	}
	
	//выход из приложения
	window.exitapp = function(){
		
		if (navigator && navigator.app) {
			navigator.app.exitApp();
		}else{
			if (navigator && navigator.device) {
				navigator.device.exitApp();
			}
		}
	
	}
	
	//выход из приложения по двойному нажатию или кнопка назад
	var dbclick = false;
	document.addEventListener("backbutton", function(){
		if(dbclick) {
			window.exitapp();
			return false;
		}
		dbclick = true;
		window.clickBackButton=true;
		window.mainView.router.back();
		setTimeout(function(){
			dbclick = false;
		},500);
		return false;
	}, false);
	
	//реклама
	window.bannerLoading = false;
	if(typeof admob != 'undefined'){
		admob.interstitial.config({
			id: 'ca-app-pub-1830389177579766/7503968314',
			autoShow: false
		});
		document.addEventListener('admob.interstitial.events.LOAD_FAIL', function(event) {
		  window.bannerLoading = true;
		});
		document.addEventListener('admob.interstitial.events.LOAD', function(event) {
		  window.bannerLoading = true;
		});
	}
	
	//языковые переменные
	window.mlfConfig.lang.ru.NO_CONNECTION_MLIFE = 'Ошибка соединения с сервером MLife.BY, возможно нет подключения к Internet';
	
	//загрузка меню
	loadMenu();
	
	//пуши
	window.mlfConfig.oldPush = localStorage.getItem('push_num');
	if(window.mlfConfig.oldPush){
		Framework7.request({
			url : window.mlfConfig.startUrl+'ajax/updatepush/',
			//async : false,
			dataType: 'json',
			method: 'POST',
			timeout: 3000,
			data : {version: window.mlfConfig.version, device:window.mlfConfig.deviceId, key: localStorage.getItem('authorize_key'), pushId: window.mlfConfig.oldPush},
			success : function(data){
				if(data.RIGHT == 'admin') localStorage.setItem('authorize_right','admin');
			},
			error: function(){
				
			}
		});
		
	}
	
	//таблица для статистики
	window.db.transaction(function(tx){
		tx.executeSql("CREATE TABLE IF NOT EXISTS stats (ID VARCHAR PRIMARY KEY, count INT)",[]);
	});
	
	//отправка статистики на сервер
	window.db.transaction(function(tx){
		tx.executeSql("SELECT * FROM stats WHERE ID!=0",[],function(t,res){
		var stats = '';
		if(res.rows.length > 0){
			for(var i=0;i<res.rows.length;i++){
				stats += res.rows.item(i)['ID']+'::'+res.rows.item(i)['count']+';;';
			}
			
			return;
			
			Framework7.request({
				url : window.mlfConfig.startUrl+'ajax/add_stats/',
				//async : true,
				timeout: 6000,
				dataType: 'html',
				method: 'POST',
				data : {version: window.mlfConfig.version, device:window.mlfConfig.deviceId, key: localStorage.getItem('authorize_key'), stats:stats},
				success : function(data){
					if(data == 'OK') {
						window.db.transaction(function(tx2){tx2.executeSql("DELETE FROM stats WHERE ID>0",[]);});
					}
				}
			});
			
		}
		},
		function(tx,err){
		
		}
		);
	});
	
	//способ открытия панелей
	var slide_left = false;
	if(localStorage.getItem('slide_left')=='yes') slide_left = true;
	var slide_right = false;
	if(localStorage.getItem('slide_right')=='yes') slide_right = true;
	var panelValue = '';
	if(slide_left && slide_right) {
		panelValue = 'both';
	}else if(slide_left){
		panelValue = 'left';
	}else if(slide_right){
		panelValue = 'right';
	}
	
	//запрет усыпления экрана
	if(window.plugins && window.plugins.insomnia){
		if(localStorage.getItem('nosleep')=='yes'){
			window.plugins.insomnia.keepAwake();
		}else{
			window.plugins.insomnia.allowSleepAgain();
		}
	}
	
	//инициализация fraimework7
	window.app = new Framework7({
	  theme : 'ios',
	  id: 'mlife.rasp',
	  panel: {
		swipe: panelValue,
		closeByBackdropClick:false
	  },
	  touch: {
	    disableContextMenu: false,
	    materialRipple: false,
	    fastClicks: true
	    //activeState: false,
	    //tapHold:true
	  },
	  view: {
		xhrCache: false,
		passRouteParamsToRequest: true,
		iosSwipeBack: false,
		uniqueHistory: true,
		preloadPreviousPage: true,
		allowDuplicateUrls: false,
		animate: false
	  }
	});
	
	//главный view
	window.mainView = window.app.views.create('.view-main',{
		url: '/',
		routes: [
		{//главная страница, онлайн - оффлайн
		    path: '/',
			alias: '/main/',
			on: {
				pageMounted : function(pageData){
					if (window.debug) console.log(pageData);
					if(pageData.target.className == 'page page-previous'){
						window.showNavigator();
						window.clickBackButton = false;
					}else if(pageData.target.className == 'page page-next'){
						//window.showNavigator();
						window.clickBackButton = false;
					}
					
				},
				pageBeforeIn : function(pageData){
					if (window.debug) console.log(window.mainView.history);
					if (window.debug) console.log(pageData.target.className);
					if(pageData.target.className == 'page page-previous'){
						window.clickBackButton = false;
						
					}
				}
			},
			
			async : function(routeTo, routeFrom, resolve_main, reject_main) {
				
				if(routeTo.url == routeFrom.url) {
					reject_main();
					return;
				}
				if(!window.mlfConfig.last_version) {
					reject_main();
					return;
				}
				
				if(!window.clickBackButton) window.preloader.show();
				
				//проверка версии
				window.mlfConfig.last_version = false;
				checkVersion();
				//показ сплеш экрана
				
				loadCnt[routeTo.url] = false;
				getDataFromDb('main',routeTo.url);
				
				var dataJson = {checkUpdate: ''};
				
				setTimeout(function t0(){
					
					if(!window.mlfConfig.last_version){
						setTimeout(t0,150);
					}else{
					
						setTimeout(function t(){
							if(!loadCnt[routeTo.url]) {
								setTimeout(t,150);
							}else{
								if (window.debug) console.log('resolve main');
								
								//если нет соединения грузим оффлайн главную
								checkConnection();
		
								if(!window.mlfConfig.connection) {
								
									resolve_main(
										{
											content: getPageSummary(getTemplateFromId('main_offline'),dataJson),
										},
										{
											reloadCurrent: true,
											reloadAll: true,
											
										}
									);
								
								}else{
									
									//если есть обновление базы и есть соединение грузим обнову
									if(window.mlfConfig.last_version != window.mlfConfig.version) {
										$('body').prepend('<div class="mlf__wrap"></div>');
										loadBaseDefault();
										if(typeof navigator.splashscreen != 'undefined'){ 
											navigator.splashscreen.hide();
										}
										reject_main();
										loadCnt[routeTo.url] = false;
										if(!window.clickBackButton) window.preloader.hide();
										return;
									}
									
									resolve_main(
										{
											content: getPageSummary(getTemplateFromId('main'),dataJson),
										},
										{
											reloadCurrent: true,
											reloadAll: true,
											
										}
									);
								
								}
								loadCnt[routeTo.url] = false;
								if(!window.clickBackButton) window.preloader.hide();
								//скрытие сплеш экрана
								if(typeof navigator.splashscreen != 'undefined'){ 
									navigator.splashscreen.hide();
								}
								
							}
						},150);
					
					}
				},150);
			
				
		    }
		    
		},
		{//перезагрузка базы
		    path: '/reload/',
		    on: {
				pageMounted : function(pageData){
					if (window.debug) console.log(pageData);
				},
				pageBeforeIn : function(){
					if (window.debug) console.log(window.mainView.history);
				}
			},
		    async : function(routeTo, routeFrom, resolve_reload, reject_reload) {
			
			
			if(routeTo.url == routeFrom.url) {
				reject_reload();
				return;
			}
			
			window.preloader.show();
				
			$('body').prepend('<div class="mlf__wrap"></div>');
			loadBaseDefault();
			if(typeof navigator.splashscreen != 'undefined'){ 
				navigator.splashscreen.hide();
			}
			reject_reload();
			window.preloader.hide();
			
		    }
		},
		{//страница партнеры
		    path: '/partner/',
		    on: {
				pageMounted : function(pageData){
					if (window.debug) console.log(pageData);
					if(pageData.target.className == 'page page-previous'){
						window.showNavigator();
						window.clickBackButton = false;
					}else if(pageData.target.className == 'page page-next'){
						window.showNavigator(true);
						window.clickBackButton = false;
					}else if(pageData.target.className == 'page page-current'){
						window.showNavigator('reload');
						window.clickBackButton = false;
					}
				},
				pageBeforeIn : function(){
					if (window.debug) console.log(window.mainView.history);
				}
			},
		    async : function(routeTo, routeFrom, resolve_partner, reject_partner) {
			
			
			var __hist = true;
			var __reloadCurrent = false;
			if(routeTo.url == routeFrom.url) {
				__hist = false;
				__reloadCurrent = true;
			}
			
			if(!window.clickBackButton) window.preloader.show();
				
			loadCnt[routeTo.url] = false;
			getDataFromDb('partner',routeTo.url);
				setTimeout(function t(){
					if(!loadCnt[routeTo.url]) {
						setTimeout(t,150);
					}else{
						if (window.debug) console.log('resolve partner');
						resolve_partner(
							{
								content: getPageSummary(getTemplateFromId('partner'), loadCnt[routeTo.url]),
							},
							{
								reloadCurrent: __reloadCurrent,
								history: __hist,
							}
						);
						
						loadCnt[routeTo.url] = false;
						if(!window.clickBackButton) window.preloader.hide();
					}
				},150);
			
		    }
		},
		{//ajax online pages
		    path: '/ajax/reviews/(.*)',
		    on: {
				pageMounted : function(pageData){
					if (window.debug) console.log(pageData);
					if(pageData.target.className == 'page page-previous'){
						window.showNavigator();
						window.clickBackButton = false;
					}else if(pageData.target.className == 'page page-next'){
						window.showNavigator(true);
						window.clickBackButton = false;
					}else if(pageData.target.className == 'page page-current'){
						window.showNavigator('reload');
						window.clickBackButton = false;
					}
					$('#reviews a').each(function(){
						if($(this).attr('href').indexOf('http')!==-1){
							$(this).addClass('external');
							$(this).attr('target','_blank');
						}
					});
				},
				pageBeforeIn : function(){
					if (window.debug) console.log(window.mainView.history);
				}
			},
		    async : function(routeTo, routeFrom, resolve_reviews, reject_reviews) {
			
			if (window.debug) console.log(routeTo);
			
			var __hist = true;
			var __reloadCurrent = false;
			if(routeTo.url == routeFrom.url) {
				__hist = false;
				__reloadCurrent = true;
			}
			
			if(routeTo.url.indexOf('PAGEN')!==-1){
				__hist = false;
				__reloadCurrent = true;
			}
			
			//var __reloadCurrent = false;
			//if(routeFrom.url.indexOf("/stations/")!== -1) __reloadCurrent = true;
			
			if(!window.clickBackButton) window.preloader.show();
				
			loadCnt[routeTo.url] = false;
			getDataFromDb('ajax',routeTo.url,routeTo.params);
				setTimeout(function t(){
					if(!loadCnt[routeTo.url]) {
						setTimeout(t,150);
					}else{
						
						resolve_reviews(
							{
								content: getPageSummary(getTemplateFromId('ajax'), loadCnt[routeTo.url]),
							},
							{
								reloadCurrent: __reloadCurrent,
								history: __hist,
							}
						);
						
						loadCnt[routeTo.url] = false;
						if(!window.clickBackButton) window.preloader.hide();
					}
				},150);
				
			
		    }
		},
		{//404
		    path: '(.*)',
			on: {
				pageMounted : function(pageData){
					if (window.debug) console.log(pageData);
					if(pageData.target.className == 'page page-previous'){
						window.showNavigator();
						window.clickBackButton = false;
					}else if(pageData.target.className == 'page page-next'){
						window.showNavigator(true);
						window.clickBackButton = false;
					}else if(pageData.target.className == 'page page-current'){
						window.showNavigator('reload');
						window.clickBackButton = false;
					}
				},
				pageBeforeIn : function(){
					if (window.debug) console.log(window.mainView.history);
				}
			},
			async : function(routeTo, routeFrom, resolve_404, reject_404) {
				if (window.debug) console.log(routeTo);
			
				var __hist = true;
				var __reloadCurrent = false;
				if(routeTo.url == routeFrom.url) {
					__hist = false;
					__reloadCurrent = true;
				}
				
				//var __reloadCurrent = false;
				//if(routeFrom.url.indexOf("/stations/")!== -1) __reloadCurrent = true;
				
				if(!window.clickBackButton) window.preloader.show();
					
				loadCnt[routeTo.url] = false;
				getDataFromDb('error',routeTo.url,routeTo.params);
					setTimeout(function t(){
						if(!loadCnt[routeTo.url]) {
							setTimeout(t,150);
						}else{
							
							resolve_404(
								{
									content: getPageSummary(getTemplateFromId('error'), loadCnt[routeTo.url]),
								},
								{
									reloadCurrent: __reloadCurrent,
									history: __hist,
								}
							);
							
							loadCnt[routeTo.url] = false;
							if(!window.clickBackButton) window.preloader.hide();
						}
					},150);
			}
		}
	  ],
	});
	
	//обработка пушей
	if(typeof PushNotification != 'undefined'){
		window.push = PushNotification.init({
			android: {
				topics: ['alls']
			},
			browser: {},
			ios: {
			alert: true,
			badge: false,
			sound: true,
			topics: ['allsios']
			},
			windows: {}
		});

		window.push.on('registration', function(data) {
			localStorage.setItem('push_num',data.registrationId);
			if(window.mlfConfig.oldPush != data.registrationId) {
				//обновить пуш
				Framework7.request({
					url : window.mlfConfig.startUrl+'ajax/updatepush/',
					//async : false,
					dataType: 'html',
					timeout: 3000,
					data : {version: window.mlfConfig.version, device:window.mlfConfig.deviceId, key: localStorage.getItem('authorize_key'), pushId: data.registrationId},
					success : function(data){
						//alert(data);
					},
					error: function(){
						
					}
				});
			}
		});
		window.push.on('error', function(e) {

		});
		window.push.on('notification', function(data) {
			
			var ht = '';
			ht += '	<div class="block">'+data.message.replace(/\n/g, '<br>')+'</div>';
			
			window.app.notification.create({
				title: data.title,
				text: ht,
				cssClass: 'picker-push-notice',
				closeButton: true,
			}).open();
			
			
		});
	}
	
	//закрытие пуш окна при клике на ссылку в нем
	$(document).on('click','.picker-push-notice a',function(e){
		e.preventDefault();
		window.app.notification.close();
	});
	
	//открытие и закрытие панелей (вручную для старых версий андроида)
	$(document).on('click','.navbar-current .panel__open__left',function(e){
		e.preventDefault();
		window.app.panel.open('left');
	});
	$(document).on('click','.navbar-current .panel__open__right',function(e){
		e.preventDefault();
		window.app.panel.open('right');
	});
	$(document).on('click','.panel-backdrop',function(e){
		e.preventDefault();
		window.app.panel.close('left');
		window.app.panel.close('right');
	});
	
	//настройки
	$(document).on('click','.show__settings',function(e){
		
		var formData = {
			'slide_left': '',
			'slide_right': '',
			'nosleep': ''
		};
		if(localStorage.getItem('slide_left')=='yes') formData.slide_left = 'yes';
		if(localStorage.getItem('slide_right')=='yes') formData.slide_right = 'yes';
		if(localStorage.getItem('nosleep')=='yes') formData.nosleep = 'yes';
		
		
		var dynamicPopup = window.app.popup.create({
		  content: '<div class="popup">'+
				  '<div class="content-block-title spaceNormal no-margin">Настройки приложения</div>'+
				  '<div class="block">'+
				  '<form class="list" id="formSettings">'+
				  '<ul>'+
				  '<li><div class="item-content"><div class="item-inner"><div class="item-title">Свайп левой панели</div><div class="item-after"><label class="toggle toggle-init"><input type="checkbox" name="slide_left" value="yes"><i class="toggle-icon"></i></label></div></div></div></li>'+
				  '<li><div class="item-content"><div class="item-inner"><div class="item-title">Свайп правой панели</div><div class="item-after"><label class="toggle toggle-init"><input type="checkbox" name="slide_right" value="yes"><i class="toggle-icon"></i></label></div></div></div></li>'+
				  '<li><div class="item-content"><div class="item-inner"><div class="item-title">Не усыплять экран</div><div class="item-after"><label class="toggle toggle-init"><input type="checkbox" name="nosleep" value="yes"><i class="toggle-icon"></i></label></div></div></div></li>'+
				  '</ul>'+
				  '</form>'+
				'<div class="block"><div class="row"><a href="#" class="col button color-green button-fill settings-save">Сохранить</a><a href="#" class="col button color-gray button-fill popup-close">Закрыть окно</a></div></div>'+
				  '</div>'+
				'</div>',
		}).open();
		
		window.app.form.fillFromData('#formSettings', formData);
	});
	
	$(document).on('click','.settings-save',function(e){
		e.preventDefault();
		var formData = window.app.form.convertToData('#formSettings');
		console.log(formData);
		
		if(formData.slide_left.length == 1) {
			localStorage.setItem('slide_left','yes');
		}else{
			localStorage.setItem('slide_left','no');
		}
		if(formData.slide_right.length == 1) {
			localStorage.setItem('slide_right','yes');
		}else{
			localStorage.setItem('slide_right','no');
		}
		if(formData.nosleep.length == 1) {
			localStorage.setItem('nosleep','yes');
		}else{
			localStorage.setItem('nosleep','no');
		}
		
		 window.app.popup.close();
		 location.href = 'index.html';
		
	});
	
}

//показ прелоадера, для старых версий андроида
window.preloader = {
	show: function(){
		$('#minPreloader').show();
	},
	hide: function(){
		$('#minPreloader').hide();
	}
};

//получение онлайн страницы
function getDataFromOnline(page,key,params){
	
	if (window.debug) console.log('getDataFromOnline '+key);
	
	checkConnection();
	if(!window.mlfConfig.connection) {
			loadCnt[key] = {replace_template: 'error', TEXT: 'Нет соединения. Подключитесь к Internet и повторите попытку.', LINK:key};
			if (window.debug) console.log(loadCnt[key]);
			return;
	}
	
	var utl_temp = key;
	utl_temp = utl_temp.replace('/ajax','ajax');
	if (window.debug) console.log(utl_temp);
	
	Framework7.request({
		url : window.mlfConfig.startUrl+utl_temp,
		//async : false,
		cache: false,
		crossDomain: true,
		data : {device:window.mlfConfig.deviceId, key: localStorage.getItem('authorize_key')},
		dataType: 'html',
		timeout: 6000,
		success : function(data){
			
			loadCnt[key] = data;
			
		},
		error : function(){
			
			loadCnt[key] = {replace_template: 'error', TEXT: 'Ошибка получения данных, возможно превышен таймаут ожидания данных в 6 секунд.', LINK:key};
			if (window.debug) console.log(loadCnt[key]);
			
		}
	});
	
}

//получение страницы с базы
function getDataFromDb(page,key,params){
	if (window.debug) console.log(page);
	if((key.indexOf('http:')!==-1) || (key.indexOf('https:')!==-1)){
		loadCnt[key] = {replace_template: 'error', TEXT: 'Приложению не удалось определить страницу.', LINK:key};
		if (window.debug) console.log(loadCnt[key]);
		return;
	}
	window.countProblems = window.countProblems + 1;
	if(window.mlfConfig.connection && !window.bannerLoading && !window.banner_prepare) {
		if(typeof admob != 'undefined'){ admob.interstitial.prepare();}
		window.banner_prepare = true;
	}
	if(window.mlfConfig.connection && window.countProblems>5){
		if(window.bannerLoading){
			window.bannerLoading = false;
			if(typeof admob != 'undefined'){ admob.interstitial.show(); }
			window.countProblems = 1;
			window.banner_prepare = false;
		}
	}
	
	window.setStats(key);
	if(page == 'ajax') return getDataFromOnline(page,key,params);
	
	if(page == 'error') {
		loadCnt[key] = {replace_template: 'error', TEXT: 'Приложению не удалось определить страницу.', LINK:key};
		if (window.debug) console.log(loadCnt[key]);
		return;
	}
	
	if(params && params.id) {
		page = page+'_'+params.id;
	}
	
	if (window.debug) console.log('getDataFromDb '+page);
	
	window.db.transaction(function(tx){
		
		tx.executeSql("SELECT * FROM pages WHERE ID=?",[page],function(t,res){
			if(res.rows.length > 0){
				loadCnt[key] = JSON.parse(res.rows.item(0).text);
			}else{
				loadCnt[key] = {replace_template: 'error', TEXT: 'Страница не найдена в базе данных.', LINK:key};
				if (window.debug) console.log(loadCnt[key]);
			}
		},
		function(tx,err){
			loadCnt[key] = {replace_template: 'error', TEXT: 'Страница не найдена в базе данных.', LINK:key};
			if (window.debug) console.log(loadCnt[key]);
		}
		);
		
	});
	
}

//загрузка меню
function loadMenu(){
	window.db.transaction(function(tx){
		tx.executeSql("SELECT * FROM block WHERE ID=?",['menu'],function(t,res){
		if(res.rows.length > 0){
		var menu = res.rows.item(0).text;
		$('.panel-left').append(menu);
		if (window.debug) console.log('menu Loaded');
		}
		});
	});
}

//формирование html на основании данных и шаблона
function getPageSummary(content,data){

	var html = content;
  
	if(typeof(data) == 'object') {
		
		if(!!data.replace_template){
			var compiled = Template7(getTemplateFromId(data.replace_template)).compile();
			html = compiled(data);
		}else{
		
			var compiled = Template7(html).compile();
			html = compiled(data);
			if (window.debug) console.log(data);
			if (window.debug) console.log('compiled from getPageSummary');
		
		}

	}else{
		if(typeof(data) == 'string' && !html){
			html = data;
		}else if(typeof(data) == 'string' && html){
			var compiled = Template7(html).compile();
			html = compiled({'CONTENT':data});
		}
	}

	return html;
	
}

//отображение кнопки назад
window.showNavigator = function(next){
	$('.page-current .toolbar .left').html('');
	$('.page-next .toolbar .left').html('');
	$('.page-previous .toolbar .left').html('');
	var ht = '<a href="#" class="color-white" onclick="window.clickBackButton=true;window.mainView.router.back();return false;"><i class="f7-icons f7icon-undo"></i></a>';
	if(!next) {
		$('.page-current .toolbar .left').html(ht);
	}else if(next === true){
		if(window.mainView.history.length>1) {
			$('.page-next .toolbar .left').html(ht);
			//$('.page-current .toolbar .left').html(ht);
		}
	}else if(next == 'reload'){
		if(window.mainView.history.length>1) {
			$('.page-current .toolbar .left').html(ht);
		}
	}
}


//функция записи статистики
window.setStats = function(page){
	if (window.debug) console.log('window.setStats '+page);
	if(!page) return;
	window.db.transaction(function(tx){
	
		tx.executeSql("SELECT * FROM stats WHERE ID=?",[page],function(t,res){
		if(res.rows.length > 0){
			var count = parseInt(res.rows.item(0)['count']) + 1;
			tx.executeSql("DELETE FROM stats WHERE ID=?",[page],function(){
				tx.executeSql("INSERT INTO stats (ID, count) VALUES (?,?)",[page,count],function(){
				});
			});
			
		}else{
			var count = 1;
			tx.executeSql("INSERT INTO stats (ID, count) VALUES (?,?)",[page,count],function(){
			});
		}
		});
	});
}