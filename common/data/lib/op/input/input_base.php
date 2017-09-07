<?php
/**
 * Базовый класс поля:
 * @author atukmanov
 *
 */
abstract class input_base extends DataStore {
	/**
	 * Имя поля:
	 * @var string
	 */
	protected $name=null;
	/**
	 * Значение:
	 * @var string
	 */
	protected $value=null;
	/**
	 * Форма
	 * @var input_form_interface
	 */
	protected $form=null;
	/**
	 * 
	 * @param string $name	имя поля
	 * @param string $value	значение
	 * @param input_form_interface $form	форма
	 * @param array  $properties	свойста
	 * 
	 * @return input_base
	 */
	function __construct($name, $value, $form, $properties){
		$this->name=$name;
		$this->value=$value;
		$this->form=$form;
		parent::__construct($properties);		
	}
	/**
	 * Проверить запрос
	 * 
	 * @return boolean	 
	 */
	abstract function validate();
	/**
	 * Принять изменения
	 * @param $ref
	 * @return mixed
	 */
	abstract function commit($ref=null);
	/**
	 * Получить форму:
	 * @param $ref
	 * @return mixed
	 */
	abstract function printForm();
	/**
	 * Привести к строке:
	 * @return mixed
	 */
	function __toString(){
		ob_start();
		$this->printForm();
		returnob_get_clean();
	}
	/**
	 * Сгенерировать ошибку:
	 */	
	function throwError($code, $data){
		$this->Info['errors'][$code]=$data;
	}
	
	var $isValid;
	/**
	 * Провалидировать:
	 * @see lib/DataStore#isValid()
	 */
	function isValid(){
		return $this->isValid;
	}
	
	function getHtmlFor(){
		return $this->form->getFieldID($this->name);
	}
	
	function _getInfo($path){
		switch ($path[0]){
			case 'htmlFor':
				return $this->getHtmlFor();
			break;
			case 'hasErrors':
				return count($this->Info['errors']);
			break;
		}
		return parent::_getInfo($path); 
	}
}
?>