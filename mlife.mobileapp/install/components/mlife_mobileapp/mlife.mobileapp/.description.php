<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("MLIFE_APP_APP_NAME2"),
	"DESCRIPTION" => GetMessage("MLIFE_APP_APP_DESC2"),
	"ICON" => "/images/cat_list.gif",
	"CACHE_PATH" => "Y",
	"SORT" => 30,
	"PATH" => array(
		"ID" => "mlife",
		"NAME" => GetMessage("MLIFE_NAME"),
		"CHILD" => array(
			"ID" => "mlife_app",
			"NAME" => GetMessage("MLIFE_APP"),
			"SORT" => 30,
		),
	),
);

?>