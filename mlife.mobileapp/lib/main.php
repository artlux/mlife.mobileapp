<?php
namespace Mlife\Mobileapp;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Main {
	
	protected $params = null;
	protected $filter = array();
	protected $adminList = false;
	protected $adminResult = false;
	
	public function __construct($params) {
		//загрузка языковых сущности
		
		$params = \Mlife\Mobileapp\Params::getPrepareParams($params);
		
		$entity = $params["ENTITY"];
		Loc::loadMessages($entity::getFilePath());
		
		if(!isset($params["PRIMARY"])) {
			$params["PRIMARY"] = "ID";
		}
		
		if(!isset($params["SQL_COLS"])) {
			$params["SQL_COLS"] = false;
		}
		
		if(!is_array($params["COLS"])) {
			$params["COLS"] = true;
		}elseif(empty($params["COLS"])) {
			$params["COLS"] = true;
		}
		
		if(!isset($params["LANG_CODE"])) {
			$params["LANG_CODE"] = strtoupper(preg_replace('/(.)([A-Z])/', '$1_$2',str_replace(array("Table","\\"),array("",""),$params["ENTITY"])))."_LIST_";
			if(substr($params["LANG_CODE"],0,1)=="_") $params["LANG_CODE"] = substr($params["LANG_CODE"],1);
		}
		//print_r($params["LANG_CODE"]);
		
		if($params["GEN_LANG"]=="Y"){
			\Mlife\Mobileapp\Params::generateLang($entity,$params["LANG_CODE"]);
		}
		
		if(!isset($params["ADD_GROUP_ACTION"])){
			$params["ADD_GROUP_ACTION"] = false;
		}
		if(!isset($params["FILE_EDIT"])){
			$params["FILE_EDIT"] = false;
		}
		
		if(!isset($params["FIND"])){
			$cols = $entity::getEntity()->getFields();
			foreach ($cols as $col){
			
			//проверка на референс поля
			$type = $col->getDataType();
			if(method_exists($col,"getRefEntity")){
			
			}else{
				$params["FIND"][] = $col->getName();
			}
			}
		}
		
		if(!isset($params["TABLEID"])) $params["TABLEID"] = strtolower(str_replace("_LIST_","",$params["LANG_CODE"]));
		
		//сортировка по умолчанию
		if(!isset($params["ORDER"])){
			
			//костыль с перегоном сортировки из сессии в глобальную переменную
			$oSort = new \CAdminSorting($params["TABLEID"], $params["PRIMARY"], "desc");
			
			//echo'<pre>';print_r($oSort);echo'</pre>';
			
			$by = $GLOBALS['by'];
			$setBy = false;
			if(!empty($params["COLS"])){
				foreach($params["COLS"] as $col){
					if(is_array($col) && $col['id']==$by){
						$setBy = true;
						break;
					}else{
						if($col == $by) {
							$setBy = true;
							break;
						}
					}
				}
			}
			if (!$setBy && !array_key_exists($by, $entity::getEntity()->getFields())) $by = $params["PRIMARY"];
			
			$order = strtolower($GLOBALS['order']);
			$order = $order=='desc'||$order=='asc' ? $order : 'desc';
			$params["ORDER"] = array($by => $order);
		}
		
		$params["PAGE"] = ($_REQUEST['mlife_al_page']) ? $GLOBALS["APPLICATION"]->GetCurPage()."?mlife_al_page=".preg_replace("/([^0-9A-z-_.])/is","",$_REQUEST['mlife_al_page']) : $GLOBALS["APPLICATION"]->GetCurPage();
		
		if(!$params["FILE_EDIT"]) $params["FILE_EDIT"] = $params["PAGE"]."_edit&";
		
		$this->setParams($params);
	}
	
	public function getMlifeRowListAdmin($arRes){
		if(strpos($this->getParam("FILE_EDIT"),"?")!==false){
		$editFile = $this->getParam("FILE_EDIT").''.$this->getParam("PRIMARY").'='.$arRes[$this->getParam("PRIMARY")].'&amp;lang='.LANG;
		}else{
		$editFile = $this->getParam("FILE_EDIT").'?'.$this->getParam("PRIMARY").'='.$arRes[$this->getParam("PRIMARY")].'&amp;lang='.LANG;
		}
		
		$row =& $this->getAdminList()->AddRow($arRes[$this->getParam("PRIMARY")], $arRes);
		
		$this->getMlifeRowListAdminCustomRow($row);
		
		$arT = $this->getParam("LIST");
		if(!empty($arT["ACTIONS"])){
			$arActions = array();
			foreach($arT["ACTIONS"] as $val){
				if($val=='delete'){
					$replace = "mlife_adminlist.php?mlife_al_page=".preg_replace("/([^0-9A-z-_.])/is","",$_REQUEST['mlife_al_page'])."&";
					if(strpos($this->getParam("PAGE"),'mlife_adminlist.php')!==false){
						$dd = str_replace("mlife_adminlist.php?",$replace,$this->getAdminList()->ActionDoGroup($arRes[$this->getParam("PRIMARY")], "delete"));
					}else{
						$dd = $this->getAdminList()->ActionDoGroup($arRes[$this->getParam("PRIMARY")], "delete");
					}
					$arActions[] = array(
						"ICON" => "delete",
						"TEXT" => Loc::getMessage("MAIN_ADMIN_MENU_DELETE"),
						"TITLE" => Loc::getMessage("MAIN_ADMIN_MENU_DELETE"),
						"ACTION" => "if(confirm('".GetMessageJS("MAIN_ADMIN_MENU_DELETE")."')) ".$dd,
					);
				}elseif($val=='edit'){
					$arActions[] = array(
						"ICON"=>"edit",
						"DEFAULT"=>true,
						"TEXT"=>Loc::getMessage("MAIN_ADMIN_MENU_EDIT"),
						"TITLE"=>Loc::getMessage("MAIN_ADMIN_MENU_EDIT"),
						"ACTION"=> $this->getAdminList()->ActionRedirect($editFile)
						);
				}else{
					$arActions[] = $val;
				}
			}
			$row->AddActions($arActions);
		}
	}
	
	public function addFilter($filter){
		$this->filter = array_merge($this->filter,$filter);
	}
	
	public function getFilter(){
		return $this->filter;
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
	
	public function defaultGetSort(){
		return new \CAdminSorting($this->getParam("TABLEID"), $this->getParam("PRIMARY"), "ASC");
	}
	
	public function getAdminList(){
		if(!$this->adminList)
			$this->adminList = new \CAdminList($this->getParam("TABLEID"), $this->defaultGetSort());
		return $this->adminList;
	}
	
	public function defaultGetActionId($arID,$orderAr=false){
		
		$entity = $this->getParam("ENTITY");
		
		if(!$orderAr) $orderAr = $this->getParam("ORDER");
		
		if($_REQUEST['action_target']=='selected')
		{
			$rsData = $entity::getList(
				array(
					'order' => $orderAr,
					'select' => array($this->getParam("PRIMARY")),
					'filter' => $this->getFilter()
				)
			);
			while($arRes = $rsData->Fetch())
			  $arID[] = $arRes[$this->getParam("PRIMARY")];
		}
		return $arID;
		
	}
	
	public function defaultGetAction($arID){
		
		$entity = $this->getParam("ENTITY");
		
		$act = $this->getParam("CALLBACK_ACTIONS");
		
		if($_REQUEST['action']=="delete") {
			foreach($arID as $ID)
			{
				if(strlen($ID)<=0)
					continue;
					$ID = IntVal($ID);
				
				if(isset($act[$_REQUEST['action']])){
					call_user_func($act[$_REQUEST['action']], $ID);
				}else{
					$res = $entity::delete(array($this->getParam("PRIMARY")=>$ID));
				}
			}
		}else{
			if(isset($act[$_REQUEST['action']])){
				if(is_array($act[$_REQUEST['action']]) && $act[$_REQUEST['action']][0] === 'this'){
					call_user_func(array($this,$act[$_REQUEST['action']][1]), $arID);
				}else{
					call_user_func($act[$_REQUEST['action']], $arID);
				}
				
			}
			return false;
		}
		return true;
		
	}
	
	public function checkActions($right){
		
		// обработка одиночных и групповых действий
		if(($arID = $this->getAdminList()->GroupAction()) && $right=="W")
		{
			$arID = $this->defaultGetActionId($arID);
			//print_r($arID);die();
			$resActions = $this->defaultGetAction($arID);
		}
		
		// сохранение отредактированных элементов
		if($this->getAdminList()->EditAction() && $right=="W")
		{
			global $FIELDS;
			
			$act = $this->getParam("CALLBACK_ACTIONS");
			
			// пройдем по списку переданных элементов
			foreach($FIELDS as $ID=>$arFields)
			{
				if(!$this->getAdminList()->IsUpdated($ID))
				continue;

				$ID = IntVal($ID);
				
				$entity = $this->getParam("ENTITY");
				
				foreach($arFields as $key=>$value){
					$obField = $entity::getEntity()->getField($key);
					if($obField instanceof \Bitrix\Main\Entity\DatetimeField){
						$arData[$key]=\Bitrix\Main\Type\DateTime::createFromUserTime($value);
					}else{
						$arData[$key]=$value;
					}
				}

				
				if(isset($act["edit"])){
					call_user_func($act["edit"], $ID, $arData);
				}else{
					$entity::update(array($this->getParam("PRIMARY")=>$ID),$arData);
				}

			}
		}
		
	}
	
	public function getAdminResult(){
		
		if(!$this->adminResult){
			$colsVisible = array();
			$entity = $this->getParam("ENTITY");
			if($this->getParam("SQL_COLS")===false) {
				$colsVisible = $this->getAdminList()->GetVisibleHeaderColumns();
			}elseif(is_array($this->getParam("SQL_COLS"))){
				$colsVisible = $this->getAdminList()->GetVisibleHeaderColumns();
				$keys_colsVisible = array_values($arVisible);
				$colsVisibleSql = $this->getParam("SQL_COLS");
				//$colsVisible = array();
				foreach($colsVisibleSql as $k=>$v){
					if(is_numeric($k)) $k = '';
					if(is_array($v)){
						if(!$v['KEY'] && $k) $v['KEY'] = $k;
						if(isset($v['KEY'])){
							if(isset($v['LOGIC'])){
								$logic = $v['LOGIC'];
								$logic_p = $v['LOGIC_PARAM'];
							}
							if(($key_v = array_search($v['KEY'],$colsVisible))!==false) {
								unset($colsVisible[$key_v]);
								$colsVisible[$k] = $v['KEY'];
							}
							if($logic=='add'){
								if($logic_p && in_array($logic_p,$colsVisible)){
									$colsVisible[$k] = $v['KEY'];
								}else{
									$colsVisible[$k] = $v['KEY'];
								}
							}
						}
					}else{
						if(($key_v = array_search($v,$colsVisible))!==false) {
							unset($colsVisible[$key_v]);
							$colsVisible[$k] = $v;
						}
					}
				}
				
			}
			if(empty($colsVisible)) $colsVisible = array("*");
			//echo'<pre>';print_r($colsVisible);echo'</pre>';
			//$colsVisible = $this->getAdminList()->GetVisibleHeaderColumns();
			$res = $entity::getList(
				array(
					'select' => $colsVisible,
					'order' => $this->getParam("ORDER"),
					'filter' => $this->getFilter(),
				)
			);
			$ob = new \CAdminResult($res, $this->getParam("TABLEID"));
			$ob->NavStart();
			
			$this->adminResult = $ob;
		}
		
		return $this->adminResult;
		
	}
	
	public function setNavText(){
		$this->getAdminList()->NavText($this->getAdminResult()->GetNavPrint(Loc::getMessage($this->getParam("LANG_CODE")."NAV_TEXT")));
	}
	
	public function AddHeaders(){
		
		$entity = $this->getParam("ENTITY");
		$cols = $entity::getEntity()->getFields();
		//echo'<pre>';print_r($cols);echo'</pre>';die();
		$colHeaders = array();
		//$arKeys = array();
		if($ar = $this->getParam("COLS")){
			//print_r($ar);
			//параметры колонки полностью переданы
			foreach($ar as $valCol){
				if(is_array($valCol)) {
					$colHeaders[] = $valCol;
				}
			}
			//добавление колонок по ключу
			foreach ($cols as $col){
			
				//проверка на референс поля
				$type = $col->getDataType();
				if(method_exists($col,"getRefEntity")){

					$pname = $col->getName();
					//print_r($pname);die();
					$refEntClass = $col->getRefEntity()->getDataClass();
					//print_r($refEnt);die();
					$refCols = $col->getRefEntity()->getFields();
					Loc::loadMessages($refEntClass::getFilePath());
					
					foreach($refCols as $col_ref){
						$pnameSummary = $pname.'.'.$col_ref->getName();
						//print_r($pnameSummary);echo'<br>';
						
						$setCol = false;
						
						if(is_array($ar)){
							if(in_array($pnameSummary,$ar)){
								$setCol = true;
							}
						}else{
							$setCol = true;
						}
						
						if($setCol){
							$colHeaders[] = array(
								"id" => $pnameSummary,
								"content" => $col->getTitle()." - ".$col_ref->getTitle(),
								"sort" => $pnameSummary,
								"default" => true,
							);
						}
						
					}
					
					
				}else{
				
					$name = $col->getName();
					$setCol = false;
					
					if(is_array($ar)){
						if(in_array($name,$ar)){
							$setCol = true;
						}
					}else{
						$setCol = true;
					}
					if($setCol){
						$colHeaders[] = array(
							"id" => $name,
							"content" => $col->getTitle(),
							"sort" => $name,
							"default" => true,
						);
					}
					
				}
				
			}
		}
		
		$this->getAdminList()->AddHeaders($colHeaders);
		
		/*if(empty($colHeaders)) {
			$this->setParams(array("*"),"SELECT");
		}else{
			$this->setParams($arKeys,"SELECT");
		}*/
		
		/*$visibleHeaderColumns = $this->getAdminList()->GetVisibleHeaderColumns();
		$this->setParams($visibleHeaderColumns,"SELECT");*/
		
	}
	
	public function addFooter(){
		
		$this->getAdminList()->AddFooter(
			array(
				array(
					"title" => Loc::getMessage("MAIN_ADMIN_LIST_SELECTED"),
					"value" => $this->getAdminResult()->SelectedRowsCount()
				),
				array(
					"counter" => true,
					"title" => Loc::getMessage("MAIN_ADMIN_LIST_CHECKED"),
					"value" => "0"
				),
			)
		);
		
	}
	
	public function AddAdminContextMenu(){
		
		if ($this->getParam("BUTTON_CONTECST") !== false) {
			if($this->getParam("BUTTON_CONTECST")===true){
			$arContext['add'] = array(
				'TEXT' => Loc::getMessage($this->getParam("LANG_CODE")."BUTTON_CONTECST_".strtoupper("btn_new")),
				'ICON' => 'btn_new',
				'LINK' => $this->getParam("FILE_EDIT").'lang='.LANG,
			);
			}else{
			$arContext = $this->getParam("BUTTON_CONTECST");
			}
			if (!empty($arAddContext)) {
				$arContext = array_merge($arContext, $arAddContext);
			}
			foreach ($arContext as $k => $v) {
				if (empty($v)) {
					unset($arContext[$k]);
				}
			}
			
			$this->getAdminList()->AddAdminContextMenu($arContext);
		}else{
			$this->getAdminList()->AddAdminContextMenu(array());
		}
		
	}
	
	public function getFindHtml(){
		
		if(!$this->getParam("FIND")) return;
		
		$title = array();
		$entity = $this->getParam("ENTITY");
		$arGroups = array();
		
		$arFields = array();
		
		foreach($this->getParam("FIND") as $val){
			if(is_array($val)){
				if(isset($val["GROUP"])){
					if(!in_array($val["GROUP"], $arGroups)) {
						$arGroups[] = $val["GROUP"];
						if(isset($val["TITLE"])){
							$title[] = $val["TITLE"];
						}else{
							if(strpos($val["NAME"],".")!==false){
								$t_ref = $entity::getEntity()->getField(preg_replace("/([^.]+)\.(.*?)$/is","$1",$val["NAME"]))->getRefEntity()->getField(preg_replace("/[^.]+\.(.*?)$/is","$1",$val["NAME"]))->getTitle();
								$t_main = $entity::getEntity()->getField(preg_replace("/([^.]+)\.(.*?)$/is","$1",$val["NAME"]))->getTitle();
								$val["TITLE"] = $t_main." - ".$t_ref;
							}else{
								$val["TITLE"] = $entity::getEntity()->getField($val["NAME"])->getTitle();
							}
							$title[] = $val["TITLE"];
						}
					}
					$arFields[$val["GROUP"]][] = $val;
				}else{
					$val = array("NAME"=>$val,"KEY"=>$val);
					if(strpos($val["NAME"],".")!==false){
						$t_ref = $entity::getEntity()->getField(preg_replace("/([^.]+)\.(.*?)$/is","$1",$val["NAME"]))->getRefEntity()->getField(preg_replace("/[^.]+\.(.*?)$/is","$1",$val["NAME"]))->getTitle();
						$t_main = $entity::getEntity()->getField(preg_replace("/([^.]+)\.(.*?)$/is","$1",$val["NAME"]))->getTitle();
						$val["TITLE"] = $t_main." - ".$t_ref;
					}else{
						$val["TITLE"] = $entity::getEntity()->getField($val["NAME"])->getTitle();
					}
					$title[] = $val["TITLE"];
					$arFields[$val["NAME"]][] = $val;
				}
			}else{
				//print_r($val["NAME"]);echo'<br>';
				if(strpos($val,".")!==false){
					$t_ref = $entity::getEntity()->getField(preg_replace("/([^.]+)\.(.*?)$/is","$1",$val))->getRefEntity()->getField(preg_replace("/[^.]+\.(.*?)$/is","$1",$val))->getTitle();
					$t_main = $entity::getEntity()->getField(preg_replace("/([^.]+)\.(.*?)$/is","$1",$val))->getTitle();
					$title[] = $t_main." - ".$t_ref;
				}else{
					$title[] = $entity::getEntity()->getField($val)->getTitle();
				}
				//$title[] = $entity::getEntity()->getField($val)->getTitle();
				$arFields[$val][] = $val;
			}
		}
		
		// создадим объект фильтра
		$oFilter = new \CAdminFilter(
		  $this->getParam("TABLEID")."_filter", $title
		);
		//echo'<pre>';print_r($arFields);echo'</pre>';
		?>
		<form name="find_form" method="get" action="<?echo $this->getParam("PAGE");?>">
		<?$oFilter->Begin();?>
		<?
		$key = -1;
		foreach($arFields as $group=>$val){
		$key++;
			?>
			<tr>
			  <td><?=$title[$key]?>:</td>
			  <td>
				<?foreach($val as $field){
					$type = false;
					if(is_array($field)){
						$val_n = $field["NAME"];
						$row = "find_".$field["KEY"];
						$row = str_replace(".","_",$row);
						if($field["TYPE"]) $type = $field["TYPE"];
					}else{
						$row = "find_".$field;
						$row = str_replace(".","_",$row);
						$val_n = $field;
					}
					if(strpos($val_n,".")!==false){
						$obField = $entity::getEntity()->getField(preg_replace("/([^.]+)\.(.*?)$/is","$1",$val_n))->getRefEntity()->getField(preg_replace("/[^.]+\.(.*?)$/is","$1",$val_n));
					}else{
						$obField = $entity::getEntity()->getField($val_n);
					}
					
					if($obField instanceof \Bitrix\Main\Entity\IntegerField){
						if(!$type) $type = "INT";
					}elseif($obField instanceof \Bitrix\Main\Entity\StringField){
						if(!$type) $type = "STRING";
					}elseif($obField instanceof \Bitrix\Main\Entity\BooleanField){
						if(!$type) $type = "BOOL";
					}
					global ${$row};
					//if($_REQUEST[$row]) ${$row} = urlencode($_REQUEST[$row]);
					if($type=="INT"){
						?>
						<input type="text" name="<?=$row?>" value="<?echo ${$row}?>">
						<?
					}elseif($type=="STRING"){
						?>
						<input type="text" name="<?=$row?>" value="<?echo htmlspecialchars(${$row})?>">
						<?
					}elseif($type=="BOOL"){
						if(is_array($field["VALUES"])){
							$values = $field["VALUES"];
						}else{
							$values = array(
								"reference" => array(
									Loc::getMessage("MLIFE_ADMIN_LIST_SELECT_Y"),
									Loc::getMessage("MLIFE_ADMIN_LIST_SELECT_N"),
								),
								"reference_id" => array(
									"Y",
									"N",
								)
							);
						}
						?>
						<?echo SelectBoxFromArray($row, $values, ${$row}, Loc::getMessage("MLIFE_ADMIN_LIST_SELECT_EMPTY"), "");?>
						<?
					}elseif($type=="LIST"){
						$values = array();
						if(is_array($field["VALUES"])){
							$values = $field["VALUES"];
						}else{
							//TODO - добавить получение полей списка с описания сущности
						}
					?>
						<?echo SelectBoxFromArray($row, $values, ${$row}, Loc::getMessage("MLIFE_ADMIN_LIST_SELECT_EMPTY"), "");?>
					<?
					}elseif($type=="CALENDAR"){
					?>
					<input type="text" name="<?=$row?>" value="<?echo ${$row}?>">
					<?
					}
				}?>
			  </td>
			</tr>
		<?}?>
		<?
		$oFilter->Buttons(array("table_id"=>$this->getParam("TABLEID"),"url"=>$this->getParam("PAGE"),"form"=>"find_form"));
		$oFilter->End();
		?>
		</form>
		<?
		
	}
	
	public function initFilter(){
		
		if(!$this->getParam("FIND")) return;
		
		$title = array();
		
		foreach($this->getParam("FIND") as $val){
			if(is_array($val)){
				$val["KEY"] = str_replace(".","_",$val["KEY"]);
				$FilterArr[] = "find_".$val["KEY"];
			}else{
				$val = str_replace(".","_",$val);
				$FilterArr[] = "find_".$val;
			}
		}
		
		//print_r($FilterArr);
		
		$this->getAdminList()->InitFilter($FilterArr);
		
	}
	
	public function checkFilter(){
		
		if(!$this->getParam("FIND")) return;
		
		$title = array();
		$entity = $this->getParam("ENTITY");
		
		$arFilter = array();
		$error = false;
		
		foreach($this->getParam("FIND") as $val){
			$keyFilterAdd = "";
			if(is_array($val)){
				$row = "find_".$val["KEY"];
				$row = str_replace(".","_",$row);
				global ${$row};
				//if($_REQUEST[$row]) ${$row} = $_REQUEST[$row];
				$tmp = ${$row};
				if(isset($val["FILTER_TYPE"])) $keyFilterAdd = $val["FILTER_TYPE"];
				$validate = false;
				$val_n = $val["NAME"];
			}else{
				$row = "find_".$val;
				$row = str_replace(".","_",$row);
				global ${$row};
				//if($_REQUEST[$row]) ${$row} = $_REQUEST[$row];
				$tmp = ${$row};
				$validate = false;
				$val_n = $val;
			}
			//print_r($row);echo'<br>';
			if(strpos($val_n,".")!==false) {
				//референс
				$obField = $entity::getEntity()->getField(preg_replace("/([^.]+)\.(.*?)$/is","$1",$val_n))->getRefEntity()->getField(preg_replace("/[^.]+\.(.*?)$/is","$1",$val_n));
				//print_r($val_n);
			
			}else{
				$obField = $entity::getEntity()->getField($val_n);
			}
			
			if($obField instanceof \Bitrix\Main\Entity\IntegerField){
				$tmp = intval($tmp);
			}elseif($obField instanceof \Bitrix\Main\Entity\StringField){
				if(!$keyFilterAdd) $keyFilterAdd = "?";
			}elseif($obField instanceof \Bitrix\Main\Entity\BooleanField){
				$validate = (is_array($val) && isset($val["VALIDATE"])) ? $val["VALIDATE"] : true;
			}
			if($tmp && $validate){
				$res = new \Bitrix\Main\Entity\Result();
				$obField->validateValue($tmp, $this->getParam("PRIMARY"), array($val_n=>$tmp), $res);
				if($res->isSuccess()){
					$arFilter[$keyFilterAdd.$val_n] = $tmp;
				}else{
					foreach($res->getErrorMessages() as $err)
						$this->getAdminList()->AddFilterError($err);
				}
			}elseif($tmp){
				$arFilter[$keyFilterAdd.$val_n] = $tmp;
			}
			
		}
		
		if(!empty($arFilter)) $this->addFilter($arFilter);
		return $error;
		
	}
	
	public function AddGroupActionTable(){
		
		if(!$this->getParam("ADD_GROUP_ACTION")) return;
		
		$arActions = array();
		foreach($this->getParam("ADD_GROUP_ACTION") as $key=>$val){
			if(is_array($val)){
				$arActions[] = $val;
			}else{
				if($val==='delete') {
					$arActions[$val] = Loc::getMessage($this->getParam("LANG_CODE")."GROUP_".strtoupper($val));
					continue;
				}
				$arActions[((strlen(intval($key)) == strlen($key)) ? $name : $key)] = (strlen(intval($key)) == strlen($key)) ? Loc::getMessage($this->getParam("LANG_CODE")."GROUP_".strtoupper($val)) : $val;
				
			}
			
		}
		
		$this->getAdminList()->AddGroupActionTable($arActions);
		
	}
	
	public function getAdminRow(){
		while ($arRes = $this->getAdminResult()->GetNext())
		{
			$this->getMlifeRowListAdmin($arRes);
		}
	}
	
	//вывод заметки в подвале
	public function getNote(){
		if(Loc::getMessage($this->getParam("LANG_CODE")."NOTE")){
			echo BeginNote();
			echo Loc::getMessage($this->getParam("LANG_CODE")."NOTE");
			echo EndNote();
		}
		return;
	}
	
	public function defaultInterface(){
		
		global $APPLICATION, $adminPage, $USER, $adminMenu, $adminChain, $POST_RIGHT;
		
		//инициализация фильтра
		$this->initFilter();
		//добавление фильтра
		$this->checkFilter();
		//проверка действий
		$this->checkActions($POST_RIGHT);
		
		//доступные колонки
		$this->AddHeaders();
		//устанавливает только нужные поля в выборку
		
		//формирование списка
		$this->getAdminRow();
		
		//текст навигации
		$this->setNavText();
		
		//групповые действия
		$this->AddGroupActionTable();
		//навигация и подсчет
		$this->addFooter();
		//кнопка на панели
		$this->AddAdminContextMenu();
		//непонятно
		$this->getAdminList()->CheckListMode();
		//заголовок
		$APPLICATION->SetTitle(Loc::getMessage($this->getParam("LANG_CODE")."TITLE"));
		
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
		
		$this->getFindHtml();
		$this->getAdminList()->DisplayList();
		$this->getNote();
		
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
		
	}
	
}