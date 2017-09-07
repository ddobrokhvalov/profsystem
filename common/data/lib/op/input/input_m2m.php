<?php
/**
 * 
 * @author atukmanov
 *
 */
class input_m2m extends input_base {
	/**
	 * (non-PHPdoc)
	 * @param input_form_interface $Request
	 */
	function validateData($Request){
		$options=$this->getOptions();
		//Нет вариантов
		if (!count($options)) return true;
		//Есть вари
		if (count($options)==1){
			$ids=array_keys($options);
			$this->obj=$ids[0];
			return true;
		}
		if ($id=$Request->requestInt($this->name,0)){
			if (!isset($m2m[$id])) return $this->ThrowError('i');
			if ($this->obj=$id)
			
		}
	}
	var $options=null;
	/**
	 * Получтиь m2m для объекта
	 * @return array
	 */
	protected function getOptions(){
		if ($this->options===null) $this->options=$this->element->getM2M($this->getInfo('m2m'));
	}
}
?>