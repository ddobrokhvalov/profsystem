<?php
class form_text extends form_input{
	/**
	 * Проверить:
	 * @see common/data/lib/op/form/form_input#validate()
	 */
	function validate(){
		$this->obj= str::HTML($this->form->requestValue($this->name),str::StripTags);
		//Пустое поле:
		if (!$this->obj){
			return $this->throwEmpryField();
		}
		//Проверка регекспом:
		if ($preg=$this->getPreg()){
			if (!preg_match($preg, $this->obj)){
				return $this->throwError('invalid');
			}
		}
		//Длина строки:
		if (strlen($this->obj)>$this->getMaxLength()){
			return $this->throwError('invalid');
		}
		return true;
	}
	
	const email=2;
	
	function getPreg($default=null){
		if ($preg=$this->getInfo('preg')) return $preg;		
		if ($this->Info['errors']&self::email){			
			return '/^[\.\-_A-Za-z0-9]+?@[\.\-A-Za-z0-9]+?\.[A-Za-z0-9]{2,6}$/';;
			//return email::preg;
		}
		if ($this->Info['errors']&self::dirname){
			return '/^[a-z|0-9|_|-]$/';
		}
		return $default;
	}
	
	function getMaxLength(){
		return $this->getSetting('maxLength',250);
	}
	
	function printForm(){		
		echo '<input type="text" id="',$this->form->getFieldID($this->name),'" name="',$this->form->getFieldName($this->name),'" value="',str::formString($this->obj),'"/>';		
		echo '<script>op.form.nonempty(\'',$this->form->getFieldID($this->name),'\',',$this->getPreg('null'),',\''.$this->getLastError().'\');</script>';
	}
}
?>