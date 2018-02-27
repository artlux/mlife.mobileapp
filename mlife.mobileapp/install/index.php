<?
IncludeModuleLangFile(__FILE__);

class mlife_mobileapp extends CModule
{
	var $MODULE_ID = "mlife.mobileapp";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;

	function mlife_mobileapp()
	{
		$arModuleVersion = array();
		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->PARTNER_NAME = GetMessage("MLIFE_COMPANY_NAME");
		$this->PARTNER_URI = "https://mlife-media.by/";
		$this->MODULE_NAME = GetMessage("MLIFE_MOBILEAPP_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("MLIFE_MOBILEAPP_MODULE_DESCRIPTION");
		return true;
	}
	
	function InstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		
		$sql = "
		CREATE TABLE IF NOT EXISTS `mlife_mobileapp_pushkeys` (
		  `ID` int(7) NOT NULL AUTO_INCREMENT,
		  `DEVICE_ID` varchar(655) DEFAULT NULL,
		  `PUSH_ID` varchar(655) DEFAULT NULL,
		  `LOGIN` varchar(65) DEFAULT NULL,
		  PRIMARY KEY (`ID`)
		) AUTO_INCREMENT=1 ;
		";
		if(strtolower($DB->type)=="mysql") $res = $DB->Query($sql);
		
		$sql = "
		CREATE TABLE IF NOT EXISTS `mlife_mobileapp_pushlist` (
		  `ID` int(7) NOT NULL AUTO_INCREMENT,
		  `NAME` varchar(255) NOT NULL,
		  `DESC` varchar(1255) NOT NULL,
		  `LOGIN` varchar(65) NOT NULL,
		  `TIME` varchar(655) DEFAULT NULL,
		  PRIMARY KEY (`ID`)
		) AUTO_INCREMENT=1 ;
		";
		if(strtolower($DB->type)=="mysql") $res = $DB->Query($sql);
		
		return true;
	}
	
	function UnInstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		
		$sql = 'DROP TABLE IF EXISTS `mlife_mobileapp_pushkeys`';
		$res = $DB->Query($sql);
		$sql = 'DROP TABLE IF EXISTS `mlife_mobileapp_pushlist`';
		$res = $DB->Query($sql);
		
		return true;
	}
	
	function InstallEvents()
	{
		return true;
	}
	
	function UnInstallEvents()
	{
		return true;
	}
	
	function InstallFiles($arParams = array())
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		return true;
	}
	
	function UnInstallFiles()
	{
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		return true;
	}
	
	function DoInstall()
	{
		global $USER, $APPLICATION;
		
		RegisterModule($this->MODULE_ID);
		
		if ($USER->IsAdmin())
		{
			if ($this->InstallDB()){
				$this->InstallEvents();
				$this->InstallFiles();
			}
		}
	}
	
	function DoUninstall()
	{
		
		global $DB, $USER, $DOCUMENT_ROOT, $APPLICATION, $step;
		if ($USER->IsAdmin())
		{
			if ($this->UnInstallDB()){
				$this->UnInstallEvents();
				$this->UnInstallFiles();
			}
		}
		UnRegisterModule($this->MODULE_ID);
	}

}
?>