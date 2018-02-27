<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
header("Access-Control-Allow-Origin: *");
header('Content-Type: text/html; charset=utf-8');
global $APPLICATION;

$addType = 'ios_';
if(strpos($_SERVER['HTTP_USER_AGENT'],'Android')!==false) $addType = 'android_';

$arResult['DEVICE_ID'] = htmlspecialcharsEx($addType.'ru_'.$_REQUEST['device']);
$arResult['KEY'] = htmlspecialcharsEx($_REQUEST['key']);
if($arResult['KEY'] == 'false') $arResult['KEY'] = '';
$arResult['VERSION'] = htmlspecialcharsEx($_REQUEST['version']);

$arDelParams = array('key','deviceId','device','version');
foreach($_REQUEST as $key=>$val){
	if(substr($key,0,8) == '_nocache') $arDelParams[] = $key;
}

$arResult['DELETE_URL_PARAMS'] = $arDelParams;

if($APPLICATION->GetCurPage(false) == $arParams["SEF_FOLDER"].'version/') {
	$componentPage = 'version';
}elseif($APPLICATION->GetCurPage(false) == $arParams["SEF_FOLDER"].'tmpl/') {
	$componentPage = 'tmpl';
}elseif($APPLICATION->GetCurPage(false) == $arParams["SEF_FOLDER"].'menu/') {
	$componentPage = 'menu';
}elseif($APPLICATION->GetCurPage(false) == $arParams["SEF_FOLDER"].'js/') {
	$componentPage = 'js';
}elseif($APPLICATION->GetCurPage(false) == $arParams["SEF_FOLDER"].'css/') {
	$componentPage = 'css';
}elseif($APPLICATION->GetCurPage(false) == $arParams["SEF_FOLDER"].'page/') {
	$componentPage = 'pages';
}else{

	$arDefaultUrlTemplates404 = array(
		"version" => "version/",
		"pages" => "getpages/#GET_PAGES#/",
		"ajax" => "ajax/#PAGE#/"
	);

	$arDefaultVariableAliases404 = array();

	$arComponentVariables = array(
		"GET_PAGES"
	);

	if($arParams["SEF_MODE"] == "Y")
	{
		
		$arVariables = array();
		$engine = new CComponentEngine($this);
		
		$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams["SEF_URL_TEMPLATES"]);
		$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams["VARIABLE_ALIASES"]);

		$componentPage = $engine->guessComponentPath(
			$arParams["SEF_FOLDER"],
			$arUrlTemplates,
			$arVariables
		);

		CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);
		$arResult["FOLDER"] = $arParams["SEF_FOLDER"];
		$arResult["URL_TEMPLATES"] = $arUrlTemplates;
		$arResult["VARIABLES"] = $arVariables;
		$arResult["ALIASES"] = $arVariableAliases;

		$arResult["VARIABLES"]["UID_USER"] = false;
		
	}

}

$this->IncludeComponentTemplate($componentPage);

function prepareText($text){
	return $GLOBALS["APPLICATION"]->ConvertCharset($text, SITE_CHARSET, "utf-8");
}
function prepareLoadText($text){
	return $GLOBALS["APPLICATION"]->ConvertCharset($text, "utf-8", SITE_CHARSET);
}
?>