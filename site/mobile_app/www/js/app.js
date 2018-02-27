window.curentLoadBase = false; //ожидание загрузки данных в кеш-базу
window.curentLang = 'ru'; //язык по умолчанию
window.mlfConfig = {
	debug: false, //отладка
	startUrl: 'https://mlife-media.by/mobile_app/', //стартовый адрес
	deviceId: '', //ид устройства
	connection: false, //статус соединения
	version: localStorage.getItem('version'),
	tmpl: false, //templates
	loadCnt: false, //таймаут получения контента
	baseName: 'mlifedemo', //имя базы
	last_version: false, //последняя версия полученая с сервера обновлений
	pageUpdateCount: 15, //максимум страниц за раз при обновлении
	browser: true, //если приложение запущено в браузере, использовать для разработки
	lang: {
		ru: {
			'FIRST_LOAD': '',
			'NO_CONNECTION_START': 'Нет соединения. Подключитесь к сети и повторите попытку загрузки данных.',
			'LOAD_DATA': 'Загрузить данные',
			'START_LOADING': 'Загрузка данных',
			'NO_BASE_START': 'Данные не загружены.',
			'APP_NAME':'MLife.Расписание.',
		}
	}
}
var loadCnt = {};

var $ = Dom7;
//var _app = new Framework7();

document.addEventListener("deviceready",onRd,false);
var db = false;
var pages_arr = [];

function onRd(){
	
	if(typeof navigator.splashscreen != 'undefined'){ 
		navigator.splashscreen.show();
	}
	
	//уникальный идентификатор устройства
	if(typeof device != 'undefined') {
		window.mlfConfig.deviceId = device.uuid;
	}else{
		function makeid(){
			var text = "";
			var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
			for( var i=0; i < 12; i++ )
				text += possible.charAt(Math.floor(Math.random() * possible.length));
			return text;
		}
		window.mlfConfig.deviceId = localStorage.getItem('device_id');
		if(!window.mlfConfig.deviceId) {
			window.mlfConfig.deviceId = makeid();
			localStorage.setItem('device_id',window.mlfConfig.deviceId);
		}
	}
	
	//создание базы
	if(typeof(window.openDatabase) !== 'undefined'){
		db = window.openDatabase(window.mlfConfig.baseName+'.db','1.0',window.mlfConfig.baseName,2*1024*1024);
	}else{
		db = window.sqlitePlugin.openDatabase({name: window.mlfConfig.baseName,location: 'default'},function(db){});
	}
	window.db = db;

	window.db.transaction(function(tx){
	tx.executeSql("CREATE TABLE IF NOT EXISTS pages (ID VARCHAR PRIMARY KEY, type VARCHAR, text TEXT)",[]);
	tx.executeSql("CREATE TABLE IF NOT EXISTS block (ID VARCHAR PRIMARY KEY, text TEXT)",[]);
	tx.executeSql("CREATE TABLE IF NOT EXISTS tmpl (ID VARCHAR PRIMARY KEY, text TEXT)",[]);
	tx.executeSql("CREATE TABLE IF NOT EXISTS book (ID VARCHAR PRIMARY KEY, text TEXT)",[]);
	},function(){
	},function(){
		checkConnection();
		setTmpl();
	});
	
}

//проверка версии базы

function checkVersion(){
	var curVersion = window.mlfConfig.version;
	if(!curVersion) window.mlfConfig.version = '1.0.0';
	
	checkConnection();
		
	if(!window.mlfConfig.connection) {
		window.mlfConfig.last_version = window.mlfConfig.version;
	}else{
		//window.mlfConfig.last_version = window.mlfConfig.version;
		//return;
		Framework7.request({
			url : window.mlfConfig.startUrl+'version/',
			//async : false,
			cache: false,
			crossDomain: true,
			data : {version: window.mlfConfig.version, device:window.mlfConfig.deviceId, key: localStorage.getItem('authorize_key')},
			dataType: 'json',
			timeout: 3000,
			success : function(data){
				if(data.version != window.mlfConfig.version){
					window.mlfConfig.last_version = data.version;
				}else{
					window.mlfConfig.last_version = window.mlfConfig.version;
				}
			},
			error : function(){
				
				window.mlfConfig.last_version = window.mlfConfig.version;
				
			}
		});
	}
	
}

//запуск приложения
function startPageContent(){
	checkVersion();
	
	mainHtml = getTemplateFromId('html_page');
	
	if(!mainHtml) { //нет шаблонов
		
		loadBaseDefault();
		if(typeof navigator.splashscreen != 'undefined'){ 
			navigator.splashscreen.hide();
		}
		
	}else{
		//есть шаблоны
		
		setTimeout(function t(){
			if(!window.mlfConfig.last_version) {
				setTimeout(t,300);
			}else{
				
				loadJs();
				loadCss();
				
				setTimeout(function(){
				
					if(typeof window.initFraimework == 'function') {
						
						$('.mlf__wrap').remove();
						$('body').prepend(mainHtml);
						window.initFraimework();
						
					}else{
						
						window.curentLoadBase = false;
						setIndikator('error_from_base');
						
					}
				
				},150);
				
			}
		},300);
		
		
		
		
		
	}
	
}

//загрузка js
function loadJs(){
if (window.debug) console.log('loadJs');
db.transaction(function(tx){
	tx.executeSql("SELECT * FROM block WHERE ID=?",['js'],function(t,res){
	if(res.rows.length > 0){
	var d = res.rows.item(0)['text'];
	if(!window.mlfConfig.browser) eval(d);
	}
	});
});
}

//загрузка css
function loadCss(){
if (window.debug) console.log('loadCss');
db.transaction(function(tx){
	tx.executeSql("SELECT * FROM block WHERE ID=?",['css'],function(t,res){
	if(res.rows.length > 0){
	var d = res.rows.item(0)['text'];
	if(!window.mlfConfig.browser) $('#stylesBlock').html(d);
	}
	});
});
}

//загрузка данных по умолчанию
function loadBaseDefault(step,step2){
	setTimeout(function(){
		
		window.curentLoadBase = true;
		checkConnection();
		
		if(!window.mlfConfig.connection) {
			
			setIndikator('error');
			window.curentLoadBase = false;
			return;
		}
		
		if(!step){
			
			
			setIndikator(1);
			
			db.transaction(function(tx){
				tx.executeSql("DELETE FROM block WHERE ID>0",[]);
				tx.executeSql("DELETE FROM pages WHERE ID>0",[]);
				tx.executeSql("DELETE FROM tmpl WHERE ID>0",[]);
			},function(){},function(){
				window.mlfConfig.tmpl = {};
				loadBaseDefault(2);
				setIndikator(2);
			});
			
		}else if(step==2){
			
			Framework7.request({
				url : window.mlfConfig.startUrl+'tmpl/',
				//async : false,
				cache: false,
				crossDomain: true,
				data : {version: window.mlfConfig.version, device:window.mlfConfig.deviceId, key: localStorage.getItem('authorize_key')},
				dataType: 'json',
				success : function(data){
					
					setIndikator(5);

					db.transaction(function(tx){
						for (key in data) {
							tx.executeSql("INSERT INTO tmpl (ID, text) VALUES (?,?)",[key,data[key]],function(){});
						}
					},function(r){
					},function(){
						loadBaseDefault(3);
					});
				},
				error : function(){
					
					db.transaction(function(tx){
						tx.executeSql("DELETE FROM tmpl WHERE ID>0",[]);
					},function(){},function(){
						window.curentLoadBase = false;
						setIndikator('error');

					});
					
					
				}
			});
			
		}else if(step==3){
			
			Framework7.request({
				url : window.mlfConfig.startUrl+'menu/',
				//async : false,
				cache: false,
				crossDomain: true,
				data : {version: window.mlfConfig.version, device:window.mlfConfig.deviceId, key: localStorage.getItem('authorize_key')},
				dataType: 'html',
				success : function(data){
					
					setIndikator(10);

					db.transaction(function(tx){
					tx.executeSql("INSERT INTO block (ID, text) VALUES (?,?)",['menu',data],function(){
					loadBaseDefault(4);
					});
					});
				},
				error : function(){
					
					db.transaction(function(tx){
						tx.executeSql("DELETE FROM tmpl WHERE ID>0",[]);
					},function(){},function(){
						window.curentLoadBase = false;
						setIndikator('error');

					});
					
					
				}
			});
			
		}else if(step==4){
			
			Framework7.request({
				url : window.mlfConfig.startUrl+'js/',
				//async : false,
				cache: false,
				crossDomain: true,
				data : {version: window.mlfConfig.version, device:window.mlfConfig.deviceId, key: localStorage.getItem('authorize_key')},
				dataType: 'html',
				success : function(data){
					
					setIndikator(15);

					db.transaction(function(tx){
					tx.executeSql("INSERT INTO block (ID, text) VALUES (?,?)",['js',data],function(){
					loadBaseDefault(5);
					});
					});
				},
				error : function(){
					
					db.transaction(function(tx){
						tx.executeSql("DELETE FROM tmpl WHERE ID>0",[]);
					},function(){},function(){
						window.curentLoadBase = false;
						setIndikator('error');

					});
					
					
				}
			});
			
		}else if(step==5){
			
			Framework7.request({
				url : window.mlfConfig.startUrl+'css/',
				//async : false,
				cache: false,
				crossDomain: true,
				data : {version: window.mlfConfig.version, device:window.mlfConfig.deviceId, key: localStorage.getItem('authorize_key')},
				dataType: 'html',
				success : function(data){
					
					setIndikator(20);

					db.transaction(function(tx){
					tx.executeSql("INSERT INTO block (ID, text) VALUES (?,?)",['css',data],function(){
					loadBaseDefault(6);
					});
					});
				},
				error : function(){
					
					db.transaction(function(tx){
						tx.executeSql("DELETE FROM tmpl WHERE ID>0",[]);
					},function(){},function(){
						window.curentLoadBase = false;
						setIndikator('error');

					});
					
					
				}
			});
			
		}else if(step==6){
			
			Framework7.request({
				url : window.mlfConfig.startUrl+'page/',
				//async : false,
				cache: false,
				crossDomain: true,
				data : {version: window.mlfConfig.version, device:window.mlfConfig.deviceId, key: localStorage.getItem('authorize_key')},
				dataType: 'json',
				success : function(data){
					setIndikator(25);
					loadPages(false,data);
				},
				error : function(){
					
					db.transaction(function(tx){
						tx.executeSql("DELETE FROM tmpl WHERE ID>0",[]);
					},function(){},function(){
						window.curentLoadBase = false;
						setIndikator('error');
					});
					
				}
			});
			
		}
		
	},150);
}


function loadPages(step,data){
	setTimeout(function(){
		if(step === false){
			
			var cnt = 0;
			var pages = '';
			
			pages_arr = [];
			
			for(key in data){
				cnt = cnt + 1;
				if(cnt < window.mlfConfig.pageUpdateCount){
					pages += data[key]+",";
				}else{
					pages += data[key]+",";
					pages_arr.push(pages);
					pages = '';
					cnt = 0;
				}
			}
			
			if(cnt>0) {
				pages_arr.push(pages);
			}
			loadPages(0,0);
		}else{
			
			for(k in pages_arr){
				if(step == parseInt(k)){
					var next_k = parseInt(k)+1;
					
					Framework7.request({
						url : window.mlfConfig.startUrl+'getpages/'+pages_arr[k]+'/',
						//async : false,
						cache: false,
						crossDomain: true,
						data : {version: window.mlfConfig.version, device:window.mlfConfig.deviceId, key: localStorage.getItem('authorize_key')},
						dataType: 'json',
						success : function(dt){
							
							if(next_k != pages_arr.length) {
								var pers = 75/pages_arr.length;
								pers = 25+next_k*pers;
								pers = Math.ceil(pers);
								
								setIndikator(pers);
								
								db.transaction(function(tx){
									for(key in dt){
										tx.executeSql("INSERT INTO pages (ID, type, text) VALUES (?,?,?)",[dt[key]['page'],dt[key]['type'],JSON.stringify(dt[key]['text'])],function(){});
									}
								},function(){},function(){
									loadPages(next_k);
								});
								
								
							}else{
								
								db.transaction(function(tx){
									for(key in dt){
										tx.executeSql("INSERT INTO pages (ID, type, text) VALUES (?,?,?)",[dt[key]['page'],dt[key]['type'],JSON.stringify(dt[key]['text'])],function(){});
									}
								},function(){},function(){
									
									setIndikator(99);
									
									Framework7.request({
										url : window.mlfConfig.startUrl+'version/',
										//async : false,
										cache: false,
										crossDomain: true,
										data : {version: window.mlfConfig.version, device:window.mlfConfig.deviceId, key: localStorage.getItem('authorize_key')},
										dataType: 'json',
										success : function(data){
											setIndikator(100);
											localStorage.setItem('version',data.version);
											location.href = 'index.html';
										},
										error : function(){
											
											db.transaction(function(tx){
												tx.executeSql("DELETE FROM tmpl WHERE ID>0",[]);
											},function(){},function(){
												window.curentLoadBase = false;
												setIndikator('error');
											});
											
										}
									});
									
								});
								
							}
							
						},
						error : function(){
							
							db.transaction(function(tx){
								tx.executeSql("DELETE FROM tmpl WHERE ID>0",[]);
							},function(){},function(){
								window.curentLoadBase = false;
								setIndikator('error');
							});
							
						}
					});
					
				}
			}
			
		}
	},150);
}

$(document).on('click','.loadData',function(e){
	e.preventDefault();
	$('.loadData').remove();
	loadBaseDefault();
	if(typeof navigator.splashscreen != 'undefined'){ 
		navigator.splashscreen.hide();
	}
});

//установка индикатора загрузки
function setIndikator(persent){
	
	if(persent == 'error'){
		$('.mlf__wrap').html('<div class="loadingBlock"><div class="loadingBlock__error">'+getLang('NO_CONNECTION_START')+'</div><div class="buttonLoad"><a class="loadData" href="#">'+getLang('LOAD_DATA')+'</a></div></div>');
	}else if(persent == 'error_from_base'){
		$('.mlf__wrap').html('<div class="loadingBlock"><div class="loadingBlock__error">'+getLang('NO_BASE_START')+'</div><div class="buttonLoad"><a class="loadData" href="#">'+getLang('LOAD_DATA')+'</a></div></div>');
	}else{
	
		if((!$('.loadingBlock').html()) || (persent=='1')){
			$('.mlf__wrap').html('<div class="loadingBlock"><div class="loadingBlock__title">'+getLang('START_LOADING')+'</div><div class="loadingBlock__indikator"></div></div>');
		}
	
		$('.loadingBlock__indikator').html('<span style="width:'+persent+'%;">'+persent+'%</span>');
	}
}

//проверка соединения
function checkConnection() {
	//window.mlfConfig.connection = false; return;
	if(typeof navigator.connection != 'undefined'){
	
	var Connection = {
		UNKNOWN: "unknown",
        ETHERNET: "ethernet",
        WIFI: "wifi",
        CELL_2G: "2g",
        CELL_3G: "3g",
        CELL_4G: "4g",
        CELL:"cellular",
        NONE: "none"
	};
	var networkState = navigator.connection.type;
    var states = {};
    states[Connection.UNKNOWN]  = true;
    states[Connection.ETHERNET] = true;
    states[Connection.WIFI]     = true;
    states[Connection.CELL_2G]  = true;
    states[Connection.CELL_3G]  = true;
    states[Connection.CELL_4G]  = true;
    states[Connection.CELL]     = true;
    states[Connection.NONE]     = false;

    if(states[networkState]===true) {
		window.mlfConfig.connection = states[networkState];
	}else if(states[networkState]===false){
		window.mlfConfig.connection = states[networkState];
	}else{
		window.mlfConfig.connection = true;
	}
	}else{
	window.mlfConfig.connection = true;
	}
	
}

//получение шаблонов
function setTmpl(){
	db.transaction(function(tx){
	tx.executeSql("SELECT * FROM tmpl WHERE ID!=0",[],function(t,res){
	window.mlfConfig.tmpl = {};
	if(res.rows.length > 0){
		for(var i=0;i<res.rows.length;i++){
			window.mlfConfig.tmpl[res.rows.item(i)['ID']] = res.rows.item(i)['text'];
		}
	}
	startPageContent();
	},
	function(tx,err){
	window.mlfConfig.tmpl = {};
	startPageContent();
	}
	);
	});
}

//получение шаблона по его id
function getTemplateFromId(id){
	//console.log(window.mlfConfig.tmpl);
	if(!window.mlfConfig.tmpl[id]) return "";
	return window.mlfConfig.tmpl[id];
}

//получение фразы по коду
function getLang(id){
	if(window.mlfConfig.lang[window.curentLang][id]) return window.mlfConfig.lang[window.curentLang][id];
	return id;
}

