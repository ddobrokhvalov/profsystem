<?php
class medialib extends DataStore {
	static $basePath=null;
	static $baseURL='/medialib/';
	static $conf=null;
	/**
	 * Версии:	 
	 */
	const test=1;
	const published=0;
	/**
	 * Типы объектов
	 */
	const file=1;
	const image=2;
	const video=4;	
	const audio=8;
	const flash=16;
				
	const all=31;
	/**
	 * Область	 
	 */
	const mainArea='main';
	const allAreas=true;
	/**
	 * Контекст
	 * @var datastore
	 */
	static $context=array();
	
	static $_loaded=array();
	/**
	 * Выборка:
	 * @return dbselect
	 */
	static function select(){
		return medialib_select::libs();
	}
	/**
	 * Получить объект по ключу
	 * 	 
	 * @param array $key
	 * @return medialib
	 */
	function instance($key){
		if ($key===null) return null;
		$sel=self::select();
		$ret=new medialib();			
		/**
		 * Переданный ключ:
		 */	
		foreach ($key as $k=>$v){			
			if (!in_array($k, $sel->Select)) throw new Exception('Invalid key: '.$k);
			$sel->Where($k,eq,$v);
			$ret->Info[$k]=$v;			
		}
		/**
		 * Контекст:
		 */
		foreach (self::$context as $k=>$v){
			if (!isset($key[$k])){				
				$sel->Where($k,eq,$v);
				$ret->Info[$k]=$v;
			}			
		}
		str::print_r($sel->getSql());
		if ($info=$sel->selectObject()) $ret->Info=$info;
		return $ret;		
	}
	/**
	 * Текущая версия
	 * @return int
	 */
	static function getCurrentVersion(){
		if (preg_match('/^test./',$_SERVER['SERVER_NAME'])){
			//Тестовая версия:
			return self::test;
		}
		if (preg_match('/^adm./',$_SERVER['SERVER_NAME'])){
			//В админке надо указать верcию явно:
			throw new Exception('Can not detect right version');	
		}
		return self::published;
	}
	/**
	 * Получить список
	 * 
	 * @param $type
	 * @param $area
	 * @return medialib_list
	 */
	function getList($type=medialib::all, $area=null){
		if ($this->getID()){
			return new medialib_list($this, $type, $area);
		}
		else return null;
	}
	/**
	 * Получить объект для области
	 * 
	 * @param $area
	 * @return medialib_item
	 */
	function getItem($area=self::mainArea){
		 $list= $this->getList(null, $area);
		 $list->Limit(0,1);
		 return $list->next();
	}
	/**
	 * Сохранить
	 * @return int id
	 */
	function save(){
		if ($this->getID()) return $this->getID();
		$this->Info['ts']=time();
		$this->Info['id']=self::select()->Insert($this->Info);
	}
}
?>