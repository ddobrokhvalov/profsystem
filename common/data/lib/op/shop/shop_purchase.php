<?php
/**
 * Базовый класс покупки
 * @author atukmanov
 *
 */
class shop_purchase extends DataStore implements shop_purchase_interface{
	/**
	 * Валидность:
	 * @var boolean
	 */
	var $isValid=false;
	/**
	 * Товар:
	 * @var shop_good
	 */
	var $good=null;
	/**
	 * 
	 * @var цена
	 */
	var $price=null;
	/**
	 * 
	 * @return создать
	 */
	function __construct($good){
		$this->good=$good;
		$this->price=$this->good->price;		
	}
	
	/**
	 * Выставить свойства
	 * @param input_form_interface $form
	 * @return boolean
	 */
	function setProperties($form){	
		
		$this->isValid=true;
		/**
		 * Проходимся по полям:
		 */
		$inputs= $this->getForm($form);
		foreach ($inputs as $name=>&$input){			
			if ($input){
				/*@var input_base $input*/
				$this->isValid&=$input->validate();				
			}
			
		}		
		if ($this->isValid){
			foreach ($inputs as $name=>&$input){				
				$this->Info['properties'][$name]=$input->commit();		
			}
		}
		return $this->isValid;
	}
	/**
	 * Очистить:
	 * @return
	 */
	function clean(){
		$this->input=null;
	}
	/**
	 * Получить товар
	 * @return op_good
	 */
	function getGood(){
		return $this->good;
	}
	/**
	 * Получить заголовок
	 * @return string
	 */
	function getTitle(){
		$tpl=str_replace('{$title}', $this->good->getTitle(), $this->good->sel->getProperty('title','{$title}'));
		foreach ($this->Info as $k=>$v){
			$tpl=str_replace('{$'.$k.'}',$v,$tpl);
		}
		return $tpl;
	}
	/**
	 * Получить цену
	 * @return int
	 */	
	function getPrice(){
		return $this->good->PRICE;
	}
	/**
	 * Поля:
	 * @var array
	 */
	protected $inputs=null;
	/**
	 * Получить список полей для выбора свойств
	 * @param form_interface $form
	 * @return array
	 */
	function getForm($form){
		if ($this->inputs) return $this->inputs;
		$sel= new rbcc5_select($this->good['table']);
		$properties=$this->properties;
		foreach ($sel->getProperty('properties',array()) as $name=>$fieldData){
			$fieldData['element']=$this->good;		
			$this->inputs[$name]=form_input::factory($name, $fieldData, $form, (isset($properties[$name]))?$properties[$name]:null);		
		}
		return $this->inputs;
	}
	/**
	 * Получить участок запроса для добавления в корзину
	 * @return array
	 */
	function getQuery(){
		return 'good='.$this->good->getID();
	}
	/**
	 * Получить UID
	 * @return string
	 */
	function getUID(){
		return md5('{good:'.$this->good->getID().',class:'.$this->good->getClass().'}');
	}
	/**
	 * Получить список ошибок
	 * @return array
	 */
	function getErrors(){
		return array();//no stubs
	}
	

	/**
	 * Проверить доступность добавления в корзину
	 * @return array
	 */
	function isValid(){
		return $this->isValid;
	}
	/**
	 * Проверить доступность данного количества покупок
	 * @return boolean
	 */
	function validateCount($count){
		return true;
	}
	/**
	 * Зарезервировать $count элементов
	 * @param shop_basket_element $element	элемент
	 * @param int $basketID	id корзины
	 * @return int
	 */
	function reserve($element, $basketID){		
		$sel= new rbcc5_select('SHOP_ORDER_'.$this->good->table);
		$insert=array();
		$insert['SHOP_ORDER_ID']=$basketID;
		$insert[$this->good->table.'_ID']=$this->good->getID();
		$insert['COUNT']=$element->getCount();
		$insert['TOTAL']=$element->getFinalTotal();
		foreach ($this->Info['properties'] as $name=>$property){
			$insert[$name]=($property)?$property->getID():null;
		}
		$id=$sel->Insert($insert, 'INSERT');
		return $sel->table.'.'.$id;
	}
	
	function free($count){
		return true;
	}
	/**
	 * Получить список тегов, для поиска скидок
	 * @return array
	 */
	function getTags(){
		return array($this->good->getClass());
	}
	
	function _getInfo($path){
		
		if ($path[0]=='form'){			
			return $this->inputs;
		}
		if (null!==$ret=parent::_getInfo($path)) return $ret;
		return $this->good->getInfo($path);
	}
}
?>