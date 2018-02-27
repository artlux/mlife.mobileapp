<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(false);


$PAGE = $arResult['VARIABLES']['PAGE'];

require_once(custom__getPage($arResult['VARIABLES']['PAGE']));

?>
<?
function custom__getPage($page){
	$error = false;
	if($page){
		$file_name = __DIR__.'/pages/'.$page.'.php';
		if(file_exists($file_name)){
			return $file_name;
		}else{
			$error = true;
		}
	}else{
		$error = true;
	}
	if($error) {
		$file_name = __DIR__.'/pages/err.php';
		return $file_name;
	}
}