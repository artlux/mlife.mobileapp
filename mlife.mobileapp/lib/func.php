<?php

namespace Mlife\Mobileapp;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Func {
	
	public static function sendGCM($id, $title, $message, $apple=false) {
		
		$authKey = \COption::GetOptionString("mlife.mobileapp", "gsm_key", "");
		
		if(!$authKey) return;
		
		$url = 'https://fcm.googleapis.com/fcm/send';
		
		$fields = array(
		    "data" => array (
			"body" => preg_replace('/(\#.*?\#)/is','',$message),
			"title" => $title,
			"mlife_lnk"=>'',
			"mlife_lnktitle"=>'',
			"badge" => 0,
			//"no-cache"=> "1",
			//"force-start"=> "1"
		    ),
		    'priority'=>'high',
			//"content_available"=>true
		);
		if($apple){
			$fields["notification"] = array(
				"body" => preg_replace('/(\#.*?\#)/is','',$message),
				"title" => $title,
				"badge" => 0
			);
		}
		if(is_array($id) && count($id)>0) {
			$fields['registration_ids'] = $id;
		}else{
			$fields['to'] = is_array($id) ? $id[0] : $id;
			if($fields['to'] == 'all') $fields['to'] = '/topics/alls';
			if($fields['to'] == 'allios') $fields['to'] = '/topics/allsios';
			if($fields['to'] == 'alln') $fields['to'] = '/topics/alln';
			if($fields['to'] == 'allnios') $fields['to'] = '/topics/allnios';
		}
		if($fields["notification"] && toLower(SITE_CHARSET)=='windows-1251') $fields["notification"] = $GLOBALS["APPLICATION"]->ConvertCharsetArray($fields["notification"], SITE_CHARSET, "utf-8");
		if($fields["data"] && toLower(SITE_CHARSET)=='windows-1251') $fields["data"] = $GLOBALS["APPLICATION"]->ConvertCharsetArray($fields["data"], SITE_CHARSET, "utf-8");
		$fields = json_encode($fields);
		
		$headers = array (
		    'Authorization: key=' . $authKey,
		    'Content-Type: application/json'
		);

		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_POST, true );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );

		$result = curl_exec ( $ch );

		curl_close ( $ch );
		return $result;
	}
	
}