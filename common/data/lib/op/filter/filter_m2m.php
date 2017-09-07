<?php
class filter_m2m extends filter_select2{
	/**
	 * Получить список:
	 * @return dbselect
	 */
	function getSelect(){
		return new rbcc5_select($this->secondary_table);		
	}
	/**
	 * Выполнить:
	 * @param rbcc5_select $sel
	 * @param rbcc5_module $module
	 * @return 
	 */
	function execute(&$sel, &$module){		
		if ($this->isExecutable($module)){
			$m2m= new rbcc5_select($this->getSetting('m2m_table',$this->key));
			$m2m->Where($this->getSetting('secondary_m2m_field', $this->secondary_table.'_ID'),eq,$this->obj->getID());
			$this->handler=$sel->Join($m2m, $sel->primary_key, $this->getSetting('primary_m2m_field',$sel->primary_key),$this->key,'INNER');		
		}		
	}
	/**
	 * (non-PHPdoc)
	 * @see filter/filter_select2#getMenuSelect()
	 */
	function getMenuSelect($sel1, $module){
		$menu=$this->getSelect();
		$menu->applyEnv($module->env, rbcc5_select::skipBlocks);
		//Поле меню:
		$sel= clone $sel1;
		/*@var $sel dbselect*/
		if ($this->handler!==null){				
			$sel->dropJoin($this->handler);
		}
		$m2m= new rbcc5_select($this->getSetting('m2m_table',$this->key));
		$m2m->GroupBy($menu->primary_key);
		$m2m->Select('count(*) AS total');
		$sel->Select($sel->primary_key);
		$m2m->Join($sel, $this->getSetting('primary_m2m_field',$sel->primary_key), $sel->primary_key, 'pk_'.$this->key, 'INNER');
		$menu->Join($m2m, $menu->primary_key, $this->getSetting('secondary_m2m_field',$menu->primary_key),'total','INNER');
		return $menu;
	}
}
?>