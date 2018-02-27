<?php
namespace Mlife\Mobileapp;

class Params {
	
	public static function getPrepareParams($params=false){
		
		if($params['TYPE']){
			$p = explode("_",$params['TYPE']);
			foreach($p as $v){
				if($v=='E0'){ //нет edit
					
				}elseif($v=='E1'){ //есть edit
					
				}elseif($v=='L0'){ //запрет действий
					
				}elseif($v=='L1'){ //разрешить действия
					
				}elseif($v=='L2'){ //разрешить только в подвале
					
				}elseif($v=='F0'){ //Отключение фильтра
					
				}elseif($v=='F1'){ //Включение фильтра
					
				}
			}
		}
		
		return $params;
		
	}
	
	public static function generateLang($entity=false,$code){
		if(!$entity) return;
		$file = $_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/mlife.mobileapp/lang/ru/default_entity_lang.php';
		$c = file_get_contents($file);
		$code = str_replace("_LIST_","",$code);
		$cols = $entity::getEntity()->getFields();
		$lang = "";
		foreach ($cols as $col){
			$type = $col->getDataType();
			if(method_exists($col,"getRefEntity")){
			
			}else{
				$lang .= "\$MESS[\"#CODE#_ENTITY_".$col->getName()."_FIELD\"] = \"".$col->getName()."\";\n";
			}
		}
		$c = str_replace("#ENT#",$lang,$c);
		$c = str_replace("#CODE#",$code,$c);
		$path = toLower($entity);
		$path = str_replace(array("table","\\"),array("",'_'),$path).".php";
		$path = preg_replace("#.([^_]+)(.)([^_]+)(.)(.*?)$#is",$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/$1.$3/lang/ru/lib/$5",$path);
		if(!file_exists($path)) file_put_contents($path,$c);
	}
	
}