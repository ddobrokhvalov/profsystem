<?php
/**
 * Авторизация
 *
 */
class auth {
	/**
	 * Выборка
	 * @return dbSelect
	 */
	static function select(){
		$a= func_get_args();
		$ret= dbselect::factory(array('CL_CLIENT_ID','SURNAME','NAME','PATRONYMIC','EMAIL','PASSWORD_MD5'),'CL_CLIENT',null,$a);
		if (params::$params['openid_mode']['value']) $ret->Select[]='OPENID';
		return $ret;
	}
	
	static $user=null;
	/**
	 * Пользователь
	 * @return array
	 */
	static function user(){
		if (self::$user!==null) return self::$user;
		
		if (!isset($_COOKIE['client_id'])||(!$id=$_COOKIE['client_id'])||!preg_match('/^\d+$/',$id)){
			return self::$user=false;
		}
		if (!isset($_COOKIE['client_key'])||(!$client_key=$_COOKIE['client_key'])){
			return self::$user=false;
		}
		
		$sel= self::select();
		$sel->Where($sel->primary_key, eq, $id);
		
		if (!$obj=$sel->selectObject()){
			return self::$user=false;
		}
		
		if ($_COOKIE['client_key']!=md5($obj['CL_CLIENT_ID'] . '|' .(($obj['OPEN_ID'])?$obj['OPEN_ID']:$obj['EMAIL']) . '|' . $obj['PASSWORD_MD5'])){		
			return self::$user=false;
		}		
		
		return self::$user=self::fromObj($obj);
	}
	
	static function fromObj($obj){		
		return array(
			'id'=>$obj['CL_CLIENT_ID'],
			'email'=>($obj['OPEN_ID'])?$obj['OPEN_ID']:$obj['EMAIL'],
			'name'=>($obj['NAME'])?$obj['NAME'].(($obj['PATRONYMIC'])?' '.$obj['PATRONYMIC']:'').' '.$obj['SURNAME']:$obj['SURNAME']
		);	
	}
}
?>