<?php
/**
 * Базовый класс поля:
 * @author atukmanov
 *
 */
abstract class form_input extends DataStore {
	/**
	 * 	 
	 */
	const nonempty=1;
	const email=2;
	const date=4;
	const time=8;
	const datetime=16;
	const alphastring=32;
	const login=64;
	const dirname=128;
	const int=2048;
	const float=4096;
	/**
	 * Получить поле:
	 * @return form_input
	 */
	static function factory($name,$properties,$form,$value=null){
		if (isset($properties['class'])){
			if (class_exists($properties['class'])){
				$className=$properties['class'];
			}
			else{
				return null;
			}
		}
		else {
			$className='form_'.$properties['type'];
			if (!class_exists($className)) return null;
		}
		return new $className($name, $value, $form, $properties);
	}
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
	function commit($ref=null){
		return $this->obj;
	}
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
		return ob_get_clean();
	}
	/**
	 * Сгенерировать ошибку:
	 */	
	function throwError($code='invalid', $data=1){
		$this->isValid=false;	
		$this->lastError=$code;
		$this->Info['errorMessages'][$code]['active']=true;
		$this->Info['_errors_'][$code]=$data;
		return false;
	}
	
	protected $lastError=null;
	
	function getLastError(){
		return $this->lastError;	
	}
	
	var $isValid=true;
	/**
	 * Провалидировать:
	 * @see lib/DataStore#isValid()
	 */
	function isValid(){
		return $this->isValid;
	}
	/**
	 * Скрытое ли поле:
	 * @var boolean
	 */
	var $isHidden=false;
	/**
	 * Скрыть поле:
	 */
	function hide(){
		$this->isHidden=true;
	}
	/**
	 * Пустое поле
	 * @return boolean
	 */
	function throwEmpryField($data=1){
		if ($this->isRequired()){
			return $this->throwError('empty',$data);
		}
		else{
			return true;
		}
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
				return count($this->Info['_errors_']);
			break;
			case 'isHidden':
				return $this->isHidden;
			break;
		}
		return parent::_getInfo($path); 
	}
	/**
	 * Обязательное поле:
	 * @return boolean
	 */
	function isRequired(){
		return ($this->getInfo('errors')&1)?true:false;
	}
}
?>