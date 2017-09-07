<?php
/**
 * Товар
 * @author atukmanov
 *
 */
class shop_good extends rbcc5_object{
	/**
	 * Выборка
	 * @var rbcc5_select
	 */
	protected $sel;	
	/**
	 * Загрузить по id
	 * @param $id
	 * @param $class
	 * @return shop_good
	 */
	static function loadByID($pk){		
		/**
		 * 1. проверяем класс:
		 */		
		if (!isset($pk['class'])) return null;//Класс не задан
		
		/**
		 * Лог объекта должен быть залинковон с таблицей SHOP_ORDER:
		 */
		$shop_order= new rbcc5_select('SHOP_ORDER');
		$enabled=$shop_order->getProperty('links');		
		if (!isset($enabled['SHOP_ORDER_'.$pk['class']])){			
			return null;
		}		
		/**
		 * Поехали:
		 */
		$sel= new rbcc5_select($pk['class']);
		$sel->Where($sel->primary_key, eq, $pk['id']);
		$sel->applyEnv($pk, rbcc5_select::skipBlocks);
		return rbcc5_object::fetchObject($sel);
	}
	/**
	 * Получить список товаров по массиву {id,class}
	 * @param $arr
	 * @param $env
	 * @return array
	 */
	static function fetchList($arr, $env=null){
		$ret=array();
		foreach ($arr as $pk){
			if (!isset($res[$pk['class']])) $res[$pk['class']]=array();
			$res[$pk['class']][]=$pk['id'];
		}
		/**
		 * Собираем результат:
		 */
		foreach ($res as $class=>$id){
			$sel= new rbcc5_select($class);
			$sel->Where($sel->primary_key,eq,$id);
			$sel->applyEnv($env);
			foreach ($sel as $obj){
				$el=new shop_good($obj);
				$el->sel=$sel;
				$ret[]=$el;
			}
		}
		return $ret;
	}
	/**
	 * Получить покупку:
	 * @return shop_purchase_interface
	 */
	function getPurchase($properties){		
		$sel= new rbcc5_select($this->table);
		$purchaseClass=$sel->getProperty('purchase_class','shop_purchase');
		$ret= new $purchaseClass($this);
		/*@var $ret shop_purchase_interface*/
		$ret->setProperties($properties);
		return $ret;
	}
	/**
	 * Получить заголовок
	 * @return string
	 */
	function getTitle(){
		return $this->Info['TITLE'];
	}
	
	function getClass(){
		return $this->sel->table;
	}

//	/**
//	 * Получить id
//	 * @see lib/DataStore#getID()
//	 */
//	function getID(){
//		return $this->Info[$this->sel->primary_key];
//	}
	/**
	 * Получить данные
	 * @return string
	 */
	function getDetails(){
		return $this->Info;
	}
	/**
	 * (non-PHPdoc)
	 * @see lib/DataStore#_getInfo()
	 */
	function _getInfo($path){
		if (isset($path[0])){
			switch (str::lower($path[0])){
				case 'title':
					return $this->getTitle();
				break;				
				case 'details':
					return parent::_getInfo(array_slice($path,1));		
				break;
				case 'special_price':
					return $this->getSpecialPrice();
				break;
			}
		}
		return parent::_getInfo($path);
	}
	/**
	 * Есть ли "специальная цена"
	 * @return unknown_type
	 */
	function getSpecialPrice(){
		if (!$ret=parent::_getInfo(array('SPECIAL_PRICE'))) return 0;
		if ($from=parent::_getInfo(array('SPECIAL_PRICE_FROM'))){
			if (time()<strtotime($from)) return 0;
		}
		if ($till=parent::_getInfo(array('SPECIAL_PRICE_TILL'))){
			if (time()>strtotime($till)) return 0;
		}
		return $ret;
	}
}
?>