<?php
/**
 * Поле для фильтрации:
 * @author 1
 *
 */
abstract class filter_input extends DataStore{
	/**
	 * Ключ в запросе:
	 * @var int
	 */
	protected $key=null;
	/**
	 * Фабрика
	 * @param $key
	 * @param $info
	 * @return filter_input
	 */
	static function factory($key,$info){
		$className='filter_'.$info['type'];
		if (class_exists($className)){
			return new $className($key,$info);
		}
		return null;
	}
	/**
	 * Конструктор:
	 * @param $key
	 * @param $info
	 * @return filter_form
	 */
	function __construct($key, $info){
		parent::__construct($info);
		$this->key=$key;	
	}
	/**
	 * hanler фильтрации
	 * @var int
	 */
	protected $handler=null;	
	/**
	 * Проверить:
	 * @param rbcc5_module $module
	 * @return boolean
	 */
	abstract function validate($module);
	/**
	 * Выполняемый или нет фильтр
	 * @param $module
	 * @return unknown_type
	 */	
	protected function isExecutable(&$module){
		if (!$this->obj) return false;
		$module->setLinkParam($this->key, $this->obj->getID());
		return true;
	}
	/**
	 * Выполнить:
	 * @param rbcc5_select $sel
	 * @param rbcc5_module $module
	 * @return 
	 */
	function execute(&$sel, &$module){		
		if ($this->isExecutable($module)){
			$this->handler=$sel->Where($this->key, eq, $this->obj->getID());
		}		
	}
	/**
	 * Получить меню:
	 * @param rbcc5_select $sel
	 * @param rbcc5_module $module
	 * @return array
	 */
	abstract function getMenu($sel1, $module);
}
?>