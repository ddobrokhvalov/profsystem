<?php
class form_textarea extends form_input{
	function validate(){
		if (!$this->obj=str::HTML($this->form->requestValue($this->name))){
			return $this->throwEmpryField();
		}
		if (strlen($this->commit())>$this->getMaxLength()){
			return $this->throwError('invalid');
		}
		return true;
	}
	
	/**
	 * Максимальная длина:
	 * @return int
	 */
	function getMaxLength(){
		return $this->getSetting('maxLength',2000);
	}
	
	function printForm(){		
		echo '<textarea name="',$this->form->getFieldName($this->name),'" id="',$this->form->getFieldID($this->name),'">',str::formString($this->obj),'</textarea>';
		if ($this->isRequired()){
			echo '<script>op.form.nonempty(\'',$this->form->getFieldID($this->name),'\',null,\'',$this->getLastError(),'\');</script>';
		}
	}
}
?>