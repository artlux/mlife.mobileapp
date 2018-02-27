<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

\Bitrix\Main\Loader::includeModule("mlife.mobileapp");
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
Loc::loadMessages($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/mlife.mobileapp/lib/list.php');

$POST_RIGHT = $APPLICATION->GetGroupRight("mlife.mobileapp");
if ($POST_RIGHT == "D")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
	
	
class mlifeFormCustom extends \Mlife\Mobileapp\Form{
	public function add($arData){
		$arData['TIME'] = \Bitrix\Main\Type\DateTime::createFromTimestamp(time());
		$res = \Mlife\Mobileapp\ListTable::add($arData);
		return $res;
	}
	
	public function update($primary,$arData){
		$res = \Mlife\Mobileapp\ListTable::update($primary,$arData);
		return $res;
	}
}	

$arParams = array(
	//"LANG_CODE" => "MLIFE_PORTAL_RASPISANIE_RASP_STATION_EDIT_",
	"LIST_URL" => "mlife_adminlist.php?mlife_al_page=mlife.mobileapp__admin__list",
	"EDIT_URL" => "mlife_adminlist.php?mlife_al_page=mlife.mobileapp__admin__list_edit",
	//"PRIMARY" => "ID",
	"ID" => (intval($_REQUEST['ID'])) ? intval($_REQUEST['ID']) : false,
	"ENTITY" => "\\Mlife\\Mobileapp\\ListTable", //сущность
	"DISABLED_BUTTON"=>($POST_RIGHT<"W"),
	"BUTTON_CONTECST" => array(), //кнопки для контекстной панели
	"TABS" => array(
		"edit1" => array(
			"NAME" => Loc::getMessage("MLIFE_MOBILEAPP_LIST_LIST_NAV_TEXT2"),
			"FIELDS" => array(
				array("NAME"=>"NAME","REQUIRED"=>true), 
				array("NAME"=>"DESC","TYPE"=>"TEXTAREA","REQUIRED"=>true), 
				array("NAME"=>"LOGIN","TYPE"=>"TEXT","REQUIRED"=>true,'HINT'=>Loc::getMessage('MLIFE_MOBILEAPP_LIST_ENTITY_LOGIN_FIELD_DESC')),
			)
		)
	)
);

$adminForm = new mlifeFormCustom($arParams);
?>
<?
$adminForm->checkAction(array("mlifeFormCustom","add"),array("mlifeFormCustom","update"));
$adminForm->checkDelete();
?>
<?
$adminForm->showForm();
?>