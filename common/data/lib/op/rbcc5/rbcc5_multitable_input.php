<?php
class rbcc5_multitable_input extends DataStore{
	/**
	 * 
	 * @see lib/input#printForm()
	 */
	function printForm(){
		echo '<ul id="',$this->form->getFieldID($this->name),'"/>';
		$i=0;
		foreach ($this->getOptions() as $id=>$value){
			echo '<li rel="',str::formString(str::lower($value)),'"><input type="checkbox" name="',$this->form->getFieldName($this->name, $i),'" id="',$this->form->getFieldID($this->name,$id),'" value="',$id,'"/><label for="',$this->form->getFieldID($this->name,$id),'">',$value,'</label></li>';
			$i++;
		}
		echo '</ul>';
	}
	
	var $options=null;
	/**	 
	 * Выставить опции:
	 * @return array
	 */
	function getOptions(){
		if ($this->options) return $this->options;
		foreach ($this->Info['tables'] as $table){
			
			$sel= new rbcc5_select($table);			
			if ($title=$sel->getProperty('elementTitle', null)){
				if (preg_match_all('@{\$(\w+?)}@',$title,$m)){
					$select=array($sel->primary_key);
					foreach ($m[1] as $field) $select[]=$field;					
				}
				$sel->Select($select);
			}
			else {
				$sel->Select($sel->primary_key, 'TITLE');
			}
			foreach ($sel as $obj){
				if ($title){
					$find=array(); $replace=array();
					foreach ($obj as $k=>$v){
						$find[]='{$'.$k.'}';
						$replace[]=$v;
					}
					$option=str_replace($find,$replace,$title);
				}
				else {
					$option=$obj['TITLE'];
				}
				$this->options[$sel->table.'.'.$obj[$sel->primary_key]]=$option;
			}
		}
		asort($this->options, SORT_STRING);
		return $this->options;
	}
	
	function checkOption($option){
		$this->getOptions();
		return (isset($this->options[$option]))?true:false;
	}
}
?>