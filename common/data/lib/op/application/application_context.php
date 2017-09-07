<?php
/**
 * Контекст:
 * @author atukmanov
 *
 */
class application_context extends rbcc5_object{	
	
	function __construct($Info){
		parent::__construct($Info);
		$this->table=self::getContextTable();
		
	}
	/**
	 * 
	 * @var string
	 */
	const primaryKey='APPLICATION_CONTEXT_ID';
	/**
	 * Выборка:
	 * @return dbselect
	 */
	static function select(){
		$a= func_get_args();
		return new rbcc5_select(self::getContextTable());
	}
	/**
	 * Прикрепить список:
	 * @return array
	 */
	static function getList($orderBy='TITLE ASC'){
		$sel= new rbcc5_select(self::getContextTable());
		$sel->OrderBy('TITLE','ASC');
		return rbcc5_object::fetchList($sel);
		
	}
	/**
	 * Получить объект
	 * @param $id
	 * @return application_context
	 */
	static function loadByID($id){
		if (!$id=str::natural($id)) return null;
		$res=db::sql_select('SELECT * FROM '.self::getContextTable().' WHERE `'.self::getContextTable().'_ID`=:id', array('id'=>$id));
		if ($res&&count($res)){
			return new application_context($res[0]);
		}
	}
	/**
	 * 
	 * @param $url
	 * @return application_context
	 */
	static function loadByURL($url){
		$res=db::sql_select('SELECT * FROM '.self::getContextTable().' WHERE `URL`=:url', array('url'=>$url));
		if ($res&&count($res)){
			return new application_context($res[0]);
		}
	}
	/**
	 * Получить таблицу
	 * @return string
	 */
	static function getContextTable(){
		if (isset(params::$params['context_table'])) return params::$params['context_table']['value'];
		return 'APPLICATION_CONTEXT';
	}
	
	function getID(){
		return $this->getInfo(self::getContextTable().'_ID');	
	}
	
	function getTitle(){
		return $this->getInfo('TITLE');
	}
	
	function getURL(){
		return $this->getInfo('URL');
	}
	/**
	 * Путь до контекста:
	 * @return array
	 */
	function getPath(){		
		if ($this['PARENT_ID']){		
			return array_merge(array($this->getID()),self::loadByID($this['PARENT_ID'])->getPath());
		}
		else {
			return array($this->getID());
		}
	}
	
}
?>