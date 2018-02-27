<?php
namespace Mlife\Mobileapp;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Form {
	
	protected $tabs = array();
	protected $params = null;
	public $tabControl = null;
	public $messages = array();
	public $fieldsValues = array();
	public $saved = true;
	
	public function __construct($params) {
		
		//загрузка языковых сущности
		$entity = $params["ENTITY"];
		Loc::loadMessages($entity::getFilePath());
		
		if(!isset($params["ID"])) $params["ID"] = (intval($_REQUEST['ID'])) ? intval($_REQUEST['ID']) : false;
		
		if(!isset($params["LANG_CODE"])) {
			$params["LANG_CODE"] = strtoupper(str_replace(array("Table","\\"),array("","_"),$params["ENTITY"]))."_EDIT_";
			if(substr($params["LANG_CODE"],0,1)=="_") $params["LANG_CODE"] = substr($params["LANG_CODE"],1);
		}
		$this->setParams($params["LANG_CODE"],"LANG_CODE");
		$this->setParams($params["ENTITY"],"ENTITY");
		
		$params["PAGE"] = ($_REQUEST['mlife_al_page']) ? $GLOBALS["APPLICATION"]->GetCurPage()."?mlife_al_page=".preg_replace("/([^0-9A-z-_.])/is","",$_REQUEST['mlife_al_page']) : $GLOBALS["APPLICATION"]->GetCurPage();
		
		if(!$params["LIST_URL"]) $params["LIST_URL"] = str_replace("_edit","",$params["PAGE"]);
		if(!$params["EDIT_URL"]) $params["EDIT_URL"] = $params["PAGE"];
	//	print_r($params["LIST_URL"]);
		$this->setParams($params["LIST_URL"],"LIST_URL");
		$this->setParams($params["EDIT_URL"],"EDIT_URL");
		
		$this->setParams($params["PAGE"],"PAGE");
		if(strpos($params["PAGE"],"?")!==false) {
			$this->setParams("&","MODIF");
		}else{
			$this->setParams("?","MODIF");
		}
		
		$arTabs = array();
		$arFieldsGroup = array();
		foreach($params["TABS"] as $key=>$val){
			$arTabs[] = array(
				'DIV' => $key,
				'TAB' => $val['NAME'],
				'TITLE' => isset($val['TITLE']) ? $val['TITLE'] : $val['NAME'],
				'ICON' => isset($val['ICON']) ? $val['ICON'] : '',
				'ONSELECT' => isset($val['ONSELECT']) ? $val['ONSELECT'] : '',
			);
			foreach($val["FIELDS"] as $field=>$fieldSett){
				if(!is_array($fieldSett)){
					$arFieldsGroup[$key][$fieldSett] = array("NAME"=>$fieldSett);
				}else{
					$arFieldsGroup[$key][$field] = $fieldSett;
				}
			}
		}
		$this->setParams($arFieldsGroup,"FIELDS");
		
		$this->setTabs($arTabs);
		
		if(isset($params["ID"])) $this->setParams($params["ID"],"ID");
		if(isset($params["PRIMARY"])) {
			$this->setParams($params["PRIMARY"],"PRIMARY");
		}else{
			$this->setParams("ID","PRIMARY");
		}
		
		if(!isset($params["BUTTON_CONTECST"])) $params["BUTTON_CONTECST"] = array();
		$this->setParams($params["BUTTON_CONTECST"],"BUTTON_CONTECST");
		if(!isset($params["DEFAULT_VALUES"])) $params["DEFAULT_VALUES"] = array();
		$this->setParams($params["DEFAULT_VALUES"],"DEFAULT_VALUES");
		if(!isset($params["FUNC_VALUES"])) $params["FUNC_VALUES"] = "getElValue";
		$this->setParams($params["FUNC_VALUES"],"FUNC_VALUES");
		
		$this->setDefaultValues();
	}
	
	public function setDefaultValues(){
		
		$def = $this->getParam("DEFAULT_VALUES");
		if(!empty($def)) {
			$this->fieldsValues = $this->getParam("DEFAULT_VALUES");
		}
		if($this->getParam("ID")){
			call_user_func(array($this,$this->getParam("FUNC_VALUES")));
		}
		
	}
	
	public function getElValue(){
		$id = $this->getParam("ID");
		$entity = $this->getParam("ENTITY");
		$arData = $entity::getRowById($id);
		$fields = $this->getParam("FIELDS");
		foreach($fields as $group=>$fl){
			foreach($fl as $field){
				$arField = array(
					"ID" => $field["NAME"],
					"NAME" => "FIELD_".$field["NAME"],
				);
				$this->fieldsValues[$arField["NAME"]] = $arData[$arField["ID"]];
			}
		}
	}
	
	public function checkAction($funcAdd=null, $funcUpd=null) {
		
		global $POST_RIGHT, $save, $apply, $REQUEST_METHOD;
		
		$entity = $this->getParam("ENTITY");
		
		if($funcAdd===null){
			$funcAdd = array($entity, 'add');
		}
		if($funcUpd===null){
			$funcUpd = array($entity, 'update');
		}
		
		if($REQUEST_METHOD == "POST" && ($save!="" || $apply!="") && $POST_RIGHT=="W" && check_bitrix_sessid()){
			//print_r($_REQUEST);die();
			$arData = array();
			$error = false;
			
			$fields = $this->getParam("FIELDS");
			foreach($fields as $group=>$fl){
				foreach($fl as $field){
					$arField = array(
						"ID" => $field["NAME"],
						"NAME" => "FIELD_".$field["NAME"],
					);
					if(isset($field["TITLE"])) {
						$arField["TITLE"] = $field["TITLE"];
					}else{
						$arField["TITLE"] = $entity::getEntity()->getField($field["NAME"])->getTitle();
					}
					if(!isset($field["TYPE"])){
						$obField = $entity::getEntity()->getField($field["NAME"]);
						if($obField instanceof \Bitrix\Main\Entity\IntegerField){
							$type = "INT";
						}elseif($obField instanceof \Bitrix\Main\Entity\StringField){
							$type = "STRING";
						}elseif($obField instanceof \Bitrix\Main\Entity\BooleanField){
							$type = "BOOL";
						}elseif($obField instanceof \Bitrix\Main\Entity\DatetimeField){
							$type = "DATE";
						}
						$arField["TYPE"] = $type;
					}else{
						$arField["TYPE"] = $field["TYPE"];
					}
					
					if(isset($field["CHECK_FUNK"])){
						$res = call_user_func(array($this,$field["CHECK_FUNK"]),$arField);
					}else{
						$res = call_user_func(array($this,"checkField"),$arField);
					}
					if($res===false) {
						$error = true;
						return;
					}else{
						$arData[$arField["ID"]] = $res;
					}
					
				}
			}
			

			if($this->getParam("ID")){
				$result = call_user_func($funcUpd, array($this->getParam("PRIMARY") => $this->getParam("ID")) ,$arData);
			}else{
				$result = call_user_func($funcAdd, $arData);
				$id = $result->getId();
				if($id){
					$this->setParams($id,"ID");
				}
			}
			if($result !== false){
			$errors = $result->getErrorMessages();
			//print_r($result);die();
			}
			if(!empty($errors)) $this->messages[] = array("MESSAGE"=>implode("; ",$errors), "TYPE"=>"ERROR");
			if($this->getParam("ID")){
				if (strlen($save)) {
					if(empty($errors)) LocalRedirect($this->getParam("LIST_URL").$this->getParam("MODIF").'lang='.LANG);
				} else {
					if(empty($errors)) LocalRedirect($this->getParam("EDIT_URL").$this->getParam("MODIF").''.$this->getParam("PRIMARY").'='.$this->getParam("ID").'&lang='.LANG);
				}
			}else{
				$this->saved = false;
			}
		}
		
	}
	
	public function checkDelete($funcDel=null) {
		$entity = $this->getParam("ENTITY");
		if ($funcDel === null) {
			$funcDel = array($entity, 'delete');
		}
		if ($this->getParam("ID")!==false && $_REQUEST['action']=='delete' && check_bitrix_sessid()) {
			call_user_func($funcDel, $this->getParam("ID"));
			LocalRedirect($this->getParam("LIST_URL").$this->getParam("MODIF").'lang='.LANG);
		}
	}
	
	public function checkField($arField){
		
		switch($arField["TYPE"]){
			case "INT" : 
			if(isset($_REQUEST[$arField["NAME"]])) return intval($_REQUEST[$arField["NAME"]]);
			break;
			case "FILE" : 
			
			$PICTURE = \CFile::makeFileArray(
				array_key_exists($arField["NAME"], $_FILES)? $_FILES[$arField["NAME"]]: $_REQUEST[$arField["NAME"]]
			);
			if($_REQUEST[$arField["NAME"].'_del']) {
				CFile::Delete($_REQUEST[$arField["NAME"]]);
				$PICTURE = false;
			}
			if($PICTURE) $PICTURE['MODULE'] = 'mlife.mobilapp';
			return $PICTURE;
			break;
			if(isset($_REQUEST[$arField["NAME"]])) return intval($_REQUEST[$arField["NAME"]]);
			break;
			case "STRING" : 
			if(isset($_REQUEST[$arField["NAME"]])) return $_REQUEST[$arField["NAME"]];
			break;
			case "TEXTAREA" : 
			if(isset($_REQUEST[$arField["NAME"]])) return trim($_REQUEST[$arField["NAME"]]);
			break;
			default: 
			return $_REQUEST[$arField["NAME"]];
			break;
		}
		
		return true;
		
	}
	
	public function setTabs($tabs){
	 
		//$ob = new \CAdminTabControl("tabControl".toLower($this->getParam("LANG_CODE")), $tabs);
		
		$ob = new \CAdminForm("tabControl".toLower($this->getParam("LANG_CODE")), $tabs);
		
		$this->tabControl = $ob;
	
	}
	
	public function setContecstMenu(){
		
		$arContext['btn_list'] = array(
					'TEXT'	=> GetMessage('MAIN_ADMIN_MENU_LIST'),
					'LINK'	=> $this->getParam("LIST_URL").$this->getParam("MODIF").'lang='.LANG,
					'ICON'	=> 'btn_list');
		//$link = DeleteParam(array("mode"));
		/*$link = $this->getParam("EDIT_URL").$this->getParam("MODIF")."mode=settings";
		$arContext['btn_settings'] = array(
			  "TEXT"=>GetMessage("IBEL_E_SETTINGS"),
			  "TITLE"=>GetMessage("IBEL_E_SETTINGS_TITLE"),
			  "LINK"=>"javascript:"."tabControl".toLower($this->getParam("LANG_CODE")).".ShowSettings('".urlencode($link)."')",
			  "ICON"=>"btn_settings",
		);
		*/
		if ($this->getParam("ID") !== false) {
			$arContext['btn_new'] = array(
						'TEXT'	=> GetMessage('MAIN_ADMIN_MENU_ADD'),
						'LINK'	=> $this->getParam("EDIT_URL").$this->getParam("MODIF").'lang='.LANG,
						'ICON'	=> 'btn_new',
						);
			$arContext['btn_delete'] = array(
						'TEXT'	=> GetMessage('MAIN_ADMIN_MENU_DELETE'),
						'LINK'	=> 'javascript:if(confirm(\''.GetMessage('MAIN_ADMIN_MENU_DELETE').'?\'))'.
									'window.location=\''.$this->getParam("EDIT_URL").$this->getParam("MODIF").''.$this->getParam("PRIMARY").'='.$this->getParam("ID").
									'&amp;action=delete&amp;'.bitrix_sessid_get().'&amp;lang='.LANG.'\';',
						'ICON'	=> 'btn_delete',
						);
		}
		$cn = $this->getParam("BUTTON_CONTECST");

		if (!empty($cn)) {
			$arContext = array_merge($arContext, $cn);
			foreach ($arContext as $k => $arItem) {
				if (empty($arItem) || $arItem===false) {
					unset($arContext[$k]);
				}
			}
		}
		$context = new \CAdminContextMenu($arContext);
		$context->Show();
		
	}
	
	//получение параметра
	public function getParam($param){
		
		if(!isset($this->params[$param])) return false;
		
		return $this->params[$param];
		
	}
	
	//установка параметров
	public function setParams($params,$key=false){
		
		if($key) {
			$this->params[$key] = $params;
		}else{
			if(is_array($params)) {
				$this->params = $params;
			}else{
				throw new Main\ArgumentException(sprintf(
					'Incorrect parameters, should be an array'
				));
			}
		}
		
	}
	
	public function showMessages(){
		if(!empty($this->messages)){
			foreach($this->messages as $mess){
				\CAdminMessage::ShowMessage($mess);
			}
		}
	}
	
	public function showForm(){
		global $APPLICATION, $adminPage, $USER, $adminMenu, $adminChain;
		$APPLICATION->SetTitle(($this->getParam("ID")>0? Loc::getMessage("MAIN_EDIT")." ID=".$this->getParam("ID") : Loc::getMessage("MAIN_ADD")));
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
		$this->setContecstMenu();
		$this->showMessages();
		$entity = $this->getParam("ENTITY");
		?>
		<?=$this->addHtml?>
		<?
		$this->tabControl->BeginPrologContent();
		//if(method_exists($USER_FIELD_MANAGER, 'showscript'))
		//	echo $USER_FIELD_MANAGER->ShowScript();
		//echo \CAdminCalendar::ShowScript();
		$this->tabControl->EndPrologContent();
		$this->tabControl->BeginEpilogContent();
		?>
		<?echo bitrix_sessid_post();?>
		<input type="hidden" name="lang" value="<?=LANG?>">
		<input type="hidden" name="ID" value="<?=$this->getParam("ID")?>">
		<?
		$this->tabControl->EndEpilogContent();
		$this->tabControl->Begin(array(
			"FORM_ACTION" => $this->getParam("PAGE"),
		));
		//$this->tabControl->ShowSettings();
		//$this->tabControl->Begin();
		?>
		<?
		$fields = $this->getParam("FIELDS");
		//echo'<pre>';print_r($fields);echo'</pre>';
		foreach($fields as $group=>$fl){
			//$this->tabControl->BeginNextTab();
			$this->tabControl->BeginNextFormTab();
			//$tabControl->AddSection("IPROPERTY_TEMPLATES_SECTION", GetMessage("IBSEC_E_SEO_FOR_SECTIONS"));
			foreach($fl as $field){
				$arField = array(
					"ID" => $field["NAME"],
					"NAME" => "FIELD_".$field["NAME"],
				);
				if(isset($field["TITLE"])) {
					$arField["TITLE"] = $field["TITLE"];
				}else{
					$arField["TITLE"] = $entity::getEntity()->getField($field["NAME"])->getTitle();
				}
				if(!isset($field["TYPE"])){
					$obField = $entity::getEntity()->getField($field["NAME"]);
					if($obField instanceof \Bitrix\Main\Entity\IntegerField){
						$type = "INT";
					}elseif($obField instanceof \Bitrix\Main\Entity\StringField){
						$type = "STRING";
					}elseif($obField instanceof \Bitrix\Main\Entity\BooleanField){
						$type = "BOOL";
					}elseif($obField instanceof \Bitrix\Main\Entity\DatetimeField){
						$type = "DATE";
					}
					$arField["TYPE"] = $type;
				}else{
					$arField["TYPE"] = $field["TYPE"];
				}
				if(isset($field["VALUES"])) $arField["VALUES"] = $field["VALUES"];
				if(isset($field["ADD_STR"])) $arField["ADD_STR"] = $field["ADD_STR"];
				if(isset($field["SIZE"])) $arField["SIZE"] = $field["SIZE"];
				if(isset($field["FUNC_VIEW"])) $arField["FUNC_VIEW"] = $field["FUNC_VIEW"];
				$arField['REQUIRED'] = (isset($field['REQUIRED'])) ? $field['REQUIRED'] : false;
				$arField['HINT'] = (isset($field['HINT'])) ? $field['HINT'] : false;
				$arField['ZOOM'] = (isset($field['ZOOM'])) ? $field['ZOOM'] : false;
				$arField['CORD'] = (isset($field['CORD'])) ? $field['CORD'] : false;
				?>
				<?
				$this->tabControl->BeginCustomField($arField['ID'],$arField['TITLE'],$arField['REQUIRED'] === true);
				?>
				<tr valign="top">
					<td width="30%" class="adm-detail-content-cell-l">
						<?= isset($arField['HINT'])&&strlen($arField['HINT']) ? ShowJSHint($arField['HINT'], array('return' => true)) : ''?>
						<?if ($arField['REQUIRED']):?>
						<span class="adm-required-field"><?= $this->tabControl->GetCustomLabelHTML()?>:</span>
						<?else:?>
						<?= $this->tabControl->GetCustomLabelHTML()?>:
						<?endif;?>
					</td>
					<td width="70%" class="adm-detail-content-cell-r">
						<?$this->OutputField($arField)?>
					</td>
				</tr>
				<?$this->tabControl->EndCustomField($arField['ID'], '');?>
				<?
				
			}
		}
		?>
		<?
		$this->tabControl->Buttons(
		  array(
			"disabled"=>$this->getParam("DISABLED_BUTTON"),
			"back_url"=>$this->getParam("LIST_URL").$this->getParam("MODIF")."lang=".LANG,
		  )
		);
		?>
		<?
		$this->tabControl->Show();
		?>
		<?
		
		require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');
	}
	
	public function OutputField($arField){
		
		switch($arField["TYPE"]) {
			case "INT" : 
				$size = (isset($arField["SIZE"])) ? $arField["SIZE"] : 40;
				?>
				<input type="text" id="field_<?=$arField["ID"]?>" name="<?=$arField["NAME"]?>" value="<?=intval($this->getFieldValue($arField["NAME"]))?>" size="<?=$size?>" />
				<?
				break;
			case "FILE" : 
				
				echo \Bitrix\Main\UI\FileInput::createInstance(array(
					"name" => $arField["NAME"],
					"description" => false,
					"upload" => true,
					"allowUpload" => "I",
					"medialib" => true,
					"fileDialog" => true,
					"cloud" => true,
					"delete" => true,
					"maxCount" => 1
				))->show($this->getFieldValue($arField["NAME"]));
				break;
			case "STRING" : 
				$size = (isset($arField["SIZE"])) ? $arField["SIZE"] : 40;
				?>
				<input type="text" id="field_<?=$arField["ID"]?>" name="<?=$arField["NAME"]?>" value="<?=htmlspecialcharsbx($this->getFieldValue($arField["NAME"]))?>" size="<?=$size?>" />
				<?
				break;
			case "DATE" : 
				?>
				<?=\CAdminCalendar::CalendarDate($arField["NAME"], $this->getFieldValue($arField["NAME"]), 20)?>
				<?
				break;
			case "TEXTAREA" : 
				$size = (isset($arField["COLS"])) ? $arField["COLS"] : 42;
				$size2 = (isset($arField["ROWS"])) ? $arField["ROWS"] : 5;
				?>
				<textarea id="field_<?=$arField["ID"]?>" name="<?=$arField["NAME"]?>" rows="<?=$size2?>" cols="<?=$size?>"><?=htmlspecialcharsbx($this->getFieldValue($arField["NAME"]))?></textarea>
				<?
				break;
			case 'SELECT':
				$arVariants = isset($arField['VALUES']) ? $arField['VALUES'] : array();
				?>
				<select name="<?=$arField["NAME"]?><?if(isset($arField['ADD_STR']) && strpos($arField['ADD_STR'],"multiple")!==false){?>[]<?}?>"<?= isset($arField['ADD_STR']) ? ' '.$arField['ADD_STR'] : ''?>>
					<?foreach ($arVariants as $k => $v):?>
					<?if(isset($arField['ADD_STR']) && strpos($arField['ADD_STR'],"multiple")!==false){?>
						<option value="<?= $k?>"<?if (in_array($k,$this->getFieldValue($arField["NAME"]))){?> selected="selected"<?}?>><?= $v?></option>
					<?}else{?>
						<option value="<?= $k?>"<?if ($this->getFieldValue($arField["NAME"]) == $k){?> selected="selected"<?}?>><?= $v?></option>
					<?}?>
					<?endforeach;?>
				</select>
				<?
				break;
			case 'YANDEXMAP':
				$arVariants = isset($arField['VALUES']) ? $arField['VALUES'] : array();
				\CUtil::InitJSCore('jquery');
				?>
				<script src="//api-maps.yandex.ru/2.1/?load=package.full&lang=ru-RU" type="text/javascript"></script>
				<input type="text" id="field_<?=$arField["ID"]?>" name="<?=$arField["NAME"]?>" value="<?=htmlspecialcharsbx($this->getFieldValue($arField["NAME"]))?>" size="<?=$size?>" />
				<div id="map<?=$arField["ID"]?>" style="width:100%;height:400px;"></div>
				<script type="text/javascript">
				$(document).ready(function(){
				function init_<?=$arField["ID"]?>(){
				
					window.GLOBAL_arMapObjects["<?=$arField["ID"]?>"] = new ymaps.Map("map<?=$arField["ID"]?>", {
						center: [<?=$arField["CORD"]?>],
						zoom: <?=$arField["ZOOM"]?>
					}, {
						balloonMaxWidth: 200
					});
					<?
					if($this->getFieldValue($arField["NAME"])){
					?>
						window.GLOBAL_arMapObjects["<?=$arField["ID"]?>"].geoObjects.add(new ymaps.Placemark([<?=$this->getFieldValue($arField["NAME"])?>], {
							balloonContent: 'Current: <?=$this->getFieldValue($arField["NAME"])?>'
						}, {
							preset: 'islands#icon',
							iconColor: '#0000000'
						}));
					<?
					}
					?>
					
					 window.GLOBAL_arMapObjects["<?=$arField["ID"]?>"].events.add('click', function (e) {
						if (!window.GLOBAL_arMapObjects["<?=$arField["ID"]?>"].balloon.isOpen()) {
							var coords = e.get('coords');
							$("#field_<?=$arField["ID"]?>").val(coords[0].toPrecision(12)+","+coords[1].toPrecision(12));
							window.GLOBAL_arMapObjects["<?=$arField["ID"]?>"].balloon.open(coords, {
								contentHeader:'',
								contentBody:'' +
									'<p>' + [
									coords[0].toPrecision(12),
									coords[1].toPrecision(12)
									].join(', ') + '</p>',
								contentFooter:'<sup></sup>'
							});
						}
						else {
							window.GLOBAL_arMapObjects["<?=$arField["ID"]?>"].balloon.close();
						}
					});
					
					window.GLOBAL_arMapObjects["<?=$arField["ID"]?>"].setBounds(window.GLOBAL_arMapObjects["<?=$arField["ID"]?>"].geoObjects.getBounds());
					
				}

				if (!window.GLOBAL_arMapObjects)
					window.GLOBAL_arMapObjects = {};

				ymaps.ready(init_<?=$arField["ID"]?>);
				});
				</script>
				<?
				break;	
			case "CUSTOM":
				$func = $arField["FUNC_VIEW"];
				call_user_func(array($this, $func), $arField);
				break;
			default:
			$size = (isset($arField["SIZE"])) ? $arField["SIZE"] : 40;
			?>
			<input type="text" id="field_<?=$arField["ID"]?>" name="<?=$arField["NAME"]?>" value="<?=htmlspecialcharsbx($this->getFieldValue($arField["NAME"]))?>" size="<?=$size?>" />
			<?
			break;
		}
		
	}
	
	public function getFieldValue($name){
		
		if(!$this->saved) {
			return (isset($_REQUEST[$name])) ? $_REQUEST[$name] : "";
		}else{
			return (isset($this->fieldsValues[$name])) ? $this->fieldsValues[$name] : "";
		}
		
	}
	
}