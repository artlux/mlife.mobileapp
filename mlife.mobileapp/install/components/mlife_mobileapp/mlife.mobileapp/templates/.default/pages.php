<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
if(!\Bitrix\Main\Loader::includeModule("mlife.app")) return;
\Mlife\App\StatsTable::addCount('mlife.rasp','ALL','updatepages',1);
?>
<?
if($arResult['VARIABLES']['GET_PAGES']){

	$pages = $arResult['VARIABLES']['GET_PAGES'];
	$pages = explode(",",$pages);
	$pages = array_unique($pages);
	$arAllContent = array();
	
	$allTransGroup = array();
	
	foreach($pages as $page){
		if($page == 'demo'){
			
			$arAllContent[] = array(
				"type"=>$page, 
				"text"=>array(
					"list" => array(
						array('Название','Описание'),
						array('Название','Описание'),
						array('Название','Описание'),
						array('Название','Описание'),
					)
				), 
				"page"=>$page
			);
			
		}elseif($page){
			$arAllContent[] = array(
				"type"=> $page, // шаблон
				"text"=>array(), //динамические данные для обработки шаблонизатором
				"page"=>$page //ид внутренний
			);
		}
	}

	echo json_encode($arAllContent);

}else{

	$arPages = array();
	$filelist = glob(__DIR__ ."/tmpl/*.tmpl");
	if(!empty($filelist)){
		foreach($filelist as $file){
			$page_name = str_replace(array(__DIR__ .'/tmpl/','.tmpl'),"",$file);
			$arPages[] = $page_name;
		}
	}
	
	$arPages[] = 'demo';
	
	//например динамические страницы с сайта
	//список маршрутов
	/*$res = \Mlife\Portal\Raspisanie\TransgroupTable::getList(array(
		'select' => array("ID"),
		'filter' => array(),
		"order" => array("NAME"=>"ASC")
	));
	while($arData = $res->fetch()){
		$arPages[] = "marsh_".$arData['ID'];
	}
	*/

	echo json_encode($arPages);

}
?>