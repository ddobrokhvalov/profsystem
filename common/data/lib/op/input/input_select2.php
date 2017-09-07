<?php
/**
 * Выборка:
 * @author atukmanov
 *
 */
class input_select2 extends input_base{
	/**
	 * Проверить запрос
	 * 
	 * @return boolean	 
	 */
	abstract function validate(){
		if ($id=$this->form->requestInt($this->name)){			
			$sel=$this->getSelect();
			$sel->Select($sel->primary_key);			
			if ($obj=$sel->selectObject()){
				$this->value=$obj[$sel->primary_key];
				return true;
			}
			else {
				return $this->throwError('invalid');
			}
		}	
	}
	
	function getSelect(){
		$sel= new rbcc5_select($this->fk_table);
		$sel->applyEnv($this->form->env, rbcc5_select::skipBlocks);
		return $sel;
	}
	/**
	 * Принять изменения
	 * @param $ref
	 * @return mixed
	 */
	abstract function commit($ref=null){
		return $this->value;
	}
	/**
	 * Получить форму:
	 * @param $ref
	 * @return mixed
	 */
	abstract function printForm(){
		$sel= new rbcc5_select($this->fk_table);
		$sel->applyEnv($this->form->env);
		$sel->orderBy(($this->orderBy)?$this->orderBy:$sel->getOrderField(),'ASC');
		$sel->Select($sel->primary_key, $sel->title);
		if ($this->m2m){
			//Проверяем доступность эл-та справочника:
			$sel->Join($this->m2m, $sel->primary_key, $sel->primary_key, 'm2m', 'INNER');
		}
		echo '<select id="'.$this->form->getFieldID($this->name).' name="'.$this->getFieldName($this->name).'">';
		echo '<option value="0"></option>';
		foreach ($sel as $obj){
			echo '<option value="',$obj[$sel->primary_key],'"',($this->value==$obj[$sel->primary_key])?' selected="selected"':'','>',$obj['TITLE'],'</select>';
		}
		echo '</select>';
	}
}
?>