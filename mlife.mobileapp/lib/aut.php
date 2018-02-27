<?php
namespace Mlife\Mobileapp;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class AutTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'mlife_mobileapp_pushkeys';
	}
	
	public static function getMap()
	{
		return array(
			new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				)
			),
			new Entity\StringField('DEVICE_ID', array(
				'required' => false,
				)
			),
			new Entity\StringField('PUSH_ID', array(
				'required' => false,
				)
			),
			new Entity\StringField('LOGIN', array(
				'required' => false,
				)
			)
		);
	}
	
}