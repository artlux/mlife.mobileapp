<?php
namespace Mlife\Mobileapp;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class ListTable extends Entity\DataManager
{
	
	public static function getFilePath()
	{
		return __FILE__;
	}
	
	public static function getTableName()
	{
		return 'mlife_mobileapp_pushlist';
	}
	
	public static function getMap()
	{
		return array(
			new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				)
			),
			new Entity\StringField('NAME', array(
				'required' => true,
				'validation' => function(){
					return array(
						new Entity\Validator\Length(2, 255),
					);
				}
				)
			),
			new Entity\StringField('DESC', array(
				'required' => true,
				'validation' => function(){
					return array(
						new Entity\Validator\Length(2, 1255),
					);
				}
				)
			),
			new Entity\StringField('LOGIN', array(
				'required' => true,
				'validation' => function(){
					return array(
						new Entity\Validator\Length(3, 65),
					);
				}
				)
			),
			new Entity\DatetimeField('TIME', array(
				'required' => false
				)
			),
		);
	}
	
	public static function checkLogin($login){
		//$login = preg_replace('/([^0-9A-z_])/is','',$login);
		$aut = \Mlife\Mobileapp\AutTable::getList(array(
			"select" => array("ID"),
			"filter" => array(
				"LOGIC"=>"OR",
				array('LOGIN'=>$login),
				array("DEVICE_ID"=>$login)
			)
		))->fetch();
		if($aut) return true;
		return false;
	}
	
	public static function onBeforeAdd(Entity\Event $event){
		$result = new Entity\EventResult;
		$fields = $event->getParameter("fields");
		$result->modifyFields(array('TIME' => \Bitrix\Main\Type\DateTime::createFromTimestamp(time()))); 
		$aut = false;
		if($fields['LOGIN']!='ALL'){
			//$fields['LOGIN'] = preg_replace('/([^0-9A-z_])/is','',$fields['LOGIN']);
			$aut = \Mlife\Mobileapp\ListTable::checkLogin($fields['LOGIN']);
			if($aut){
				$result->modifyFields(array('LOGIN' => $fields['LOGIN'], 'TIME' => \Bitrix\Main\Type\DateTime::createFromTimestamp(time())));
				return $result;
			}
			$result->addError(new Entity\FieldError(
				$event->getEntity()->getField('LOGIN'),
				Loc::getMessage('MLIFE_MOBILEAPP_LIST_ENTITY_ERR1')
			));
			return $result;
			
		}
		
	}
	
	public static function onAfterAdd(Entity\Event $event){
		
		$fields = $event->getParameter("fields");
		if($fields['LOGIN'] == 'ALL'){
			\Mlife\Mobileapp\Func::sendGCM('all',$fields['NAME'],$fields['DESC']);
			\Mlife\Mobileapp\Func::sendGCM('allios',$fields['NAME'],$fields['DESC'],true);
		}else{
			
			$aut = \Mlife\Mobileapp\AutTable::getList(array(
				"select" => array("ID","DEVICE_ID","PUSH_ID"),
				"filter" => array(
					"LOGIC"=>"OR",
					array('LOGIN'=>$fields['LOGIN']),
					array("DEVICE_ID"=>$fields['LOGIN'])
				)
			));
			$pushIds = array();
			while($data = $aut->fetch()){
				if(strpos($data["DEVICE_ID"],'android_')!==false){
					$pushIds[0][] = $data["PUSH_ID"];
				}else{
					$pushIds[1][] = $data["PUSH_ID"];
				}
			}
			if(!empty($pushIds[0])) \Mlife\Mobileapp\Func::sendGCM($pushIds[0],$fields['NAME'],$fields['DESC']);
			if(!empty($pushIds[1])) \Mlife\Mobileapp\Func::sendGCM($pushIds[1],$fields['NAME'],$fields['DESC'],true);
			
		}
		
	}
	
}