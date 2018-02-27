<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
\Bitrix\Main\Loader::includeModule('mlife.mobileapp');
if(!$arResult['KEY']) $arResult['KEY'] = 'empty---empty';
$pushId = trim($_REQUEST['pushId']);
if($arResult['DEVICE_ID'] && $arResult['KEY'] && $pushId){
	$login = explode('---',$arResult['KEY']);
	if(count($login)==2){
		$aut = \Mlife\Mobileapp\AutTable::getList(array(
			"select" => array("ID"),
			"filter" => array(
				"DEVICE_ID"=>$arResult['DEVICE_ID']
			)
		))->fetch();
		if($aut){
			\Mlife\Mobileapp\AutTable::update(
			array("ID"=>$aut['ID']),
			array(
				"PUSH_ID"=>$pushId,
				"LOGIN"=>preg_replace('/([^0-9])/is','',$login[0])
			)
			);
		}else{
			\Mlife\Mobileapp\AutTable::add(
			array(
				"PUSH_ID"=>$pushId,
				"LOGIN"=>preg_replace('/([^0-9])/is','',$login[0]),
				"DEVICE_ID"=>$arResult['DEVICE_ID']
			)
			);
		}
	}
}
?>