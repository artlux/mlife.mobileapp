<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>
<?
$templates = array();
$filelist = glob( __DIR__ ."/tmpl/*.tmpl");
if(!empty($filelist)){
	foreach($filelist as $file){
		$page_name = str_replace(array(__DIR__ .'/tmpl/','.tmpl'),"",$file);
		$templates[$page_name] = file_get_contents($file);
	}
}

//print_r($templates);

echo json_encode($templates);
?>