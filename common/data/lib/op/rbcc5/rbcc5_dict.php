<?php
class rbcc5_dict extends rbcc5_select {
	/**
	 * Найти записи
	 * @return array 
	 */
	function findItems($str, $env, $flag=rbcc5_select::skipBlocks){
		$sel= new rbcc5_select($this->table);
		$sel->applyEnv($env, $flag);
		foreach ($this->getProperty('fields') as $k=>$field){
			if ($field['is_main']){
				$sel->Where($k,like,$str.'%');
				$sel->OrderBy($k,'ASC');
				break;
			}
		}
		return rbcc5_object::fetchList($sel);
	}
	/**
	 * "elementTitle"=>'{$SURNAME} {$NAME} {$PATRONYMIC}',
	 */
	
	function getItem($str, $env, $flag=rbcc5_select::skipBlocks){
		if (!$titleFields=$this->getProperty('titleFields')){
			foreach ($this->getProperty('fields') as $k=>$field){
				if ($field['is_main'])
			}
		}
	}
	
	 
}
?>