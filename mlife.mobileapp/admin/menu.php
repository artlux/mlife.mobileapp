<?php
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

global $APPLICATION;
$POST_RIGHT = $APPLICATION->GetGroupRight("mlife.mobileapp");
if ($POST_RIGHT == "D") return;

if(\Bitrix\Main\Loader::includeModule("mlife.mobileapp")){
	$aMenu[] = array(
		"parent_menu" => "global_menu_services",
		"section" => "mlife_mobileapp",
		"sort" => 130,
		"module_id" => "mlife.mobileapp",
		"text" => Loc::getMessage("TITLE_MOBILEAPP_MODULE"),
		"title" => Loc::getMessage("TITLE_MOBILEAPP_MODULE"),
		"items_id" => "mlife_mobileapp",
		"items" => array(
			array(
				"text" => Loc::getMessage("MOBILEAPP_MODULE_MENUPUSH"),
				"url" => "mlife_adminlist.php?mlife_al_page=mlife.mobileapp__admin__list&lang=".LANGUAGE_ID,
				"more_url" => Array("mlife_adminlist.php?mlife_al_page=mlife.mobileapp__admin__list_edit&lang=".LANGUAGE_ID),
				"title" => Loc::getMessage("MOBILEAPP_MODULE_MENUPUSH"),
				"sort" => 100,
			)
		),
	);
}

return $aMenu;
?>
