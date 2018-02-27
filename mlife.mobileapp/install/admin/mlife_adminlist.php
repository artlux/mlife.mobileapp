<?php
$page = preg_replace("/([^0-9A-z-_.])/is","",$_REQUEST["mlife_al_page"]);
$page = str_replace("__","/",$page);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$page.".php");
?>
