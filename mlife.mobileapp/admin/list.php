<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

\Bitrix\Main\Loader::includeModule("mlife.mobileapp");
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("mlife.mobileapp");
if ($POST_RIGHT == "D")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
	
class MlifeRowListAdmin extends \Mlife\Mobileapp\Main {
	
	public function __construct($params) {
		parent::__construct($params);
	}
	
	public function getMlifeRowListAdminCustomRow($row){
		//$row->AddInputField("NAME", array("size"=>40));
		$row->AddViewField("DESC", $row->arRes['DESC']);
		//$row->AddInputField("KOL", array("size"=>40));
		//$row->AddInputField("PHONE", array("size"=>80));
		//$row->AddInputField("USER_ID", array("size"=>40));
	}
	
}

$arParams = array(
	//"LANG_CODE" => "BRONLINE_RASPSTATION_LIST_",
	"PRIMARY" => "ID",
	"ENTITY" => "\\Mlife\\Mobileapp\\ListTable", //сущность
	"FILE_EDIT" => "mlife_adminlist.php?mlife_al_page=mlife.mobileapp__admin__list_edit&",
	"BUTTON_CONTECST" => true, //кнопки для контекстной панели
	"ADD_GROUP_ACTION" => array("delete"),//групповые действия
	"COLS" => true, //перечислить нужные array("ID","NAME")
	"FIND" => array(
		'LOGIN','NAME','DESC'
	),
	/*"FIND" => array(
		//array("GROUP"=>"IDN","NAME"=>"ID","KEY"=>"IDN","FILTER_TYPE"=>"=","TITLE"=>"ID"), //test
		array("GROUP"=>"ID","NAME"=>"ID","KEY"=>"ID1","FILTER_TYPE"=>">="),
		array("GROUP"=>"ID","NAME"=>"ID","KEY"=>"ID2","FILTER_TYPE"=>"<="),
		"CODE",
		"BASE",
		array("GROUP"=>"CURS", "NAME"=>"CURS", "KEY"=>"CURS1", "FILTER_TYPE"=>">=", "TYPE"=>"INT"),
		array("GROUP"=>"CURS", "NAME"=>"CURS", "KEY"=>"CURS2", "FILTER_TYPE"=>"<=", "TYPE"=>"INT"),
	),*/
	"LIST" => array(
		"ACTIONS" => array("delete")
	),
	"CALLBACK_ACTIONS" => array(), //edit => function($id, $fields){} //delete => function($id){}
	//"LANG_GEN"=>"Y"
);

//начало задокопипаста
$adminCustom = new MlifeRowListAdmin($arParams);
$adminCustom->defaultInterface();