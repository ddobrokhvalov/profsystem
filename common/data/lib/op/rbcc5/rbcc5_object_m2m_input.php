<?php
/**
 * Поле для получения связи "многие-ко-многим"
 * @author atukmanov
 *
 */
class rbcc5_object_m2m_input extends form_input {
	
	protected $options=null;
	/**
	 * Получить опции:
	 * @return array
	 */
	protected function getOptions(){
		if (!$this->options) $this->options=$this->element->getM2M($this->m2m);
		return $this->options;
	}
	/**
	 * Проверка:
	 * @param form_interface $request
	 * @return boolean
	 */
	function validate(){		
		$options=$this->getOptions();	
		$count=count($options);		
		if ($count==0){
			//Выбирать не из чего вообще:
			$this->hide();
			return true;
		}
		elseif ($count==1){
			//1 вариант:
			$this->hide();
			$ids=array_keys($options);
			$this->obj=$ids[0];
			return true;
		}
		else {
			if ($id=$this->form->requestInt($this->name)){
				if (isset($options[$id])){
					$this->obj=$options[$id];
					return true;					
				}
				else {
					return $this->throwError();
				}
			}
			else {
				return $this->throwEmpryField();
			}
		}		
	}
	/**
	 * (non-PHPdoc)
	 * @see common/data/lib/op/form/form_input#printForm()
	 */
	function printForm(){
		if ($this->isHidden){
			return;
		}		
		echo '<select name="',$this->form->getFieldName($this->name),'" id="',$this->form->getFieldID($this->name),'">';
		echo '<option></option>';
		foreach ($this->getOptions() as $option){
			/*@var $option rbcc5_object*/
			echo '<option value="',$option->getID(),'"',($this->obj&&$option->getID()==$this->obj->getID())?' selected':'','>',$option->TITLE,'</option>';
		}
		echo '</select>';
		if ($this->isRequired()){
			echo '<script>op.form.nonempty(\''.$this->form->getFieldID($this->name).'\')</script>';
		}
	}
}
?>