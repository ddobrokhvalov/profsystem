<?php
class rbcc5_filter extends DataStore {
	
	protected $key=null;
	
	function __construct($key, $info){
		parent::__construct($info);
		$this->key=$key;	
	}
	/**
	 * hanler фильтрации
	 * @var int
	 */
	protected $handler=null;
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
	 * Выполнить:
	 * @param rbcc5_select $sel
	 * @param rbcc5_module $module
	 * @return 
	 */
	function execute(&$sel, &$module){
		if (!$this->obj) return;
		if ($this->type=='select2'){
			$this->handler=$sel->Where($this->key, eq, $this->obj->getID());			
		}
		elseif ($this->type=='m2m'){
			$m2m= new rbcc5_select($this->getSetting('m2m_table',$this->key));
			$m2m->Where($this->getSetting('secondary_m2m_field', $this->secondary_table.'_ID'),eq,$this->obj->getID());
			$this->handler=$sel->Join($m2m, $sel->primary_key, $this->getSetting('primary_m2m_field',$sel->primary_key),$this->key,'INNER');			
		}
		$module->setLinkParam($this->key, $this->obj->getID());
	}
	/**
	 * Получить меню:
	 * @param rbcc5_select $sel
	 * @param rbcc5_module $module
	 * @return array
	 */
	function getMenu($sel1, $module){
		$menu=$this->getSelect();
		$menu->OrderBy($menu->getOrderField(),'ASC');
		if ($this->type=='select2'){	
			//Поле связи:	
			$sel=clone $sel1;		
			if ($this->handler!==null){			
				$sel->dropWhere($this->handler);
			}
			$sel->GroupBy($this->key);
			$sel->Select($sel->primary_key,'count(*) AS total');		
			$menu->Join($sel,$menu->primary_key,$this->key,'total','INNER');
			
			$ret=array();			
		}
		elseif ($this->type=='m2m') {
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
		}
		else return array();
		
		foreach ($menu as $obj){
			$obj['href']=$module->buildLink(array($this->key=>$obj[$menu->primary_key]));
			$obj['total']=$obj['total']['total'];
			$obj['selected']=($this->obj&&$obj[$sel->primary_key]==$this->obj->getID())?true:false;
			$ret[]=rbcc5_object::instance($obj,$menu->table);
		}
		return $ret;
	}
	/**
	 * Выборка по таблице:
	 * @return rbcc5_select
	 */
	function getSelect(){
		if ($this->type=='select2'){			
			return new rbcc5_select($this->fk_table);
		}	
		elseif ($this->type=='m2m'){
			return new rbcc5_select($this->secondary_table);
		}	
	}
}
?>