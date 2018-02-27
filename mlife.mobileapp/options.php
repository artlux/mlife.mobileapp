<?php

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

\Bitrix\Main\Loader::includeModule("mlife.mobileapp");
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("mlife.mobileapp");

if ($POST_RIGHT == "D")
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

?>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>

<?
$aTabs = array(
  array("DIV" => "edit1", "TAB" => Loc::getMessage("MOBILEAPP_MODULE_TAB1"), "ICON"=>"main_user_edit", "TITLE"=>Loc::getMessage("MOBILEAPP_MODULE_TAB1"))
);
$aTabs[] = array("DIV" => "edit4", "TAB" => Loc::getMessage("MOBILEAPP_MODULE_TAB2"), "ICON" => "vote_settings2", "TITLE" => Loc::getMessage("MOBILEAPP_MODULE_TAB2"));


if($REQUEST_METHOD == "POST" && $_REQUEST['Update']=="Y" && $POST_RIGHT=="W"){
	
	\COption::SetOptionString("mlife.mobileapp", "gsm_key", $_REQUEST["gsm_key"]);
	
}

$APPLICATION->SetTitle(Loc::getMessage("TITLE_MOBILEAPP_MODULE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

?>

<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?=LANGUAGE_ID?>" id="options">
<?
$tabControl = new CAdminTabControl("tabControl", $aTabs,false,true);
$tabControl->Begin();
?>

<?
$tabControl->BeginNextTab();
?>
	
	<tr>
		<td width="40%"><?=Loc::getMessage("MOBILEAPP_MODULE_SETT1")?>:</td>
		<td><?$val = \COption::GetOptionString("mlife.mobileapp", "gsm_key", "");?>
			<input type="text" value="<?=$val?>" name="gsm_key" id="gsm_key">
		</td>
	</tr>

<?
$tabControl->BeginNextTab();
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
$tabControl->Buttons();
?>
	<input <?if ($MODULE_RIGHT<"W") echo "disabled" ?> type="submit" class="adm-btn-green" name="Update" value="<?=Loc::getMessage("MOBILEAPP_MODULE_SETT_SAVE")?>" />
	<input type="hidden" name="Update" value="Y" />
<?$tabControl->End();
?>
</form>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>