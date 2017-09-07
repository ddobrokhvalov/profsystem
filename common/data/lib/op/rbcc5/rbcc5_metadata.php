<?php
class rbcc5_metadata {
	/**
	 * Запрос:
	 * @return unknown_type
	 */
	static function query($path, $default=null){
		if (!class_exists('metadata')){
			$langRoot=(isset(rbcc5_object::$env['lang_root_dir']))?rbcc5_object::$env['lang_root_dir']:'ru';
			include_once(params::$params['adm_data_server']['value'].'prebuild/metadata_'.$langRoot.'.php');
		}
		
		$ret=&metadata::$objects;
		
		foreach (DataStore::normalPath((is_string($path))?explode('.',$path):$path) as $key){	
					
			if (!is_array($ret)||!isset($ret[$key])) return $default;
			$ret=&$ret[$key];
		}
		return $ret;
		//return (is_array($ret))?new DataStore($ret):$ret;
	}
	/**
	 * Есть ли декоратор:
	 */
	static function hasDecorator($table, $decorator){
		return isset(metadata::$objects[$table]['decorators'][$decorator]);
	}
	/**
	 * Получить данные по полю:
	 * @return boolean
	 */
	static function getFieldData($table, $field){
	
		if (!$ret=self::query(array($table,'fields',$field))){
		
			if ($ret=self::query(array($table,'m2m',$field))){
				$ret->Info['type']='m2m';
			}
		}
		return $ret;
	}
}
?>