<?php
class filter_select2 extends filter_input{
	
	/**
	 * Проверить:
	 * @param rbcc5_module $module
	 * @return boolean
	 */
	function validate($module){
		if (!$value=$module->q_id($this->key)) return true;
		//Получаем таблицу:
		if ($sel=$this->getSelect()){
			$sel->applyEnv($module->env);			
			$sel->Where($sel->primary_key, eq, $value);			
			if ($this->Info['obj']=rbcc5_object::fetchObject($sel)){
				return true;
			}
			else {
				return false;
			}
		}
	}
	/**
	 * Получить список:
	 * @return dbselect
	 */
	function getSelect(){
		
		return new rbcc5_select($this->fk_table);
	}
	/**
	 * Получить выборку для меню:
	 * @param $sel1
	 * @param $module
	 * @return dbselect
	 */
	protected function getMenuSelect($sel1, $module){
		$menu= new rbcc5_select($this->fk_table);
		$menu->applyEnv($module->env,rbcc5_select::skipBlocks);
		$menu->OrderBy($menu->getOrderField(),'ASC');
		//Поле связи:	
		$sel=clone $sel1;		
		if ($this->handler!==null){			
			$sel->dropWhere($this->handler);
		}
		$sel->GroupBy($this->key);
		$sel->Select($sel->primary_key,'count(*) AS total');
				
		$menu->Join($sel,$menu->primary_key,$this->key,'total','INNER');
		return $menu;
	}
	/**
	 * 
	 * @see filter/filter_input#getMenu()
	 */
	function getMenu($sel1, $module){
		
		$menu=$this->getMenuSelect($sel1, $module);		
		$ret=array();
		foreach ($menu as $obj){
			$obj['href']=$module->buildLink(array($this->key=>$obj[$menu->primary_key]));
			$obj['total']=$obj['total']['total'];
			$obj['selected']=($this->obj&&$obj[$sel->primary_key]==$this->obj->getID())?true:false;
			$ret[]=rbcc5_object::instance($obj,$menu->table);
		}
		return $ret;
	}
}
?>