<?
/**
 * Выпадающий:
 * @author atukmanov
 *
 */
class form_select2 extends form_input {
	
	function validate(){
		$sel=$this->getSelect();
		$count=$sel->selectCount();		
		if ($id=$this->form->requestInt($this->name)){
			$sel->Where($sel->primary_key,eq,$id);
			return ($this->obj=$sel->selectString())?true:$this->throwError('invalid');
		}
		else {
			return false;
		}
	}
	/**
	 * Выборка
	 * @return rbcc5_select
	 */
	function getSelect(){
		$ret= new rbcc5_select($this->fk_table);
		
		$ret->select($ret->primary_key,'TITLE');
		$ret->OrderBy($this->getSetting('orderBy', $ret->getOrderField()),$this->getSetting('orderDir','ASC'));
		return $ret; 
	}
	/**
	 * Форма:
	 * @return unknown_type
	 */
	function printForm(){
		
		$value=($this->obj)?$this->obj:$this->getInfo('default');
		$sel=$this->getSelect();
		
		echo '<select name="',$this->form->getFieldName($this->name),'" id="',$this->form->getFieldID($this->name),'">';
		echo '<option></option>';
		foreach ($sel as $obj){
			
			echo '<option value="',$obj[$sel->table.'_ID'],'" ',($value==$obj[$sel->table.'_ID'])?' selected="selected"':'','>',$obj['TITLE'],'</option>';
		}
		echo '</select>';
	}
}
?>