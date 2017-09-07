<?php
class filter_select1 extends filter_input {
	/**
	 * Проверить:
	 * @param rbcc5_module $module
	 * @return boolean
	 */
	function validate($module){
		if (null===$value=$module->requestValue($this->key)){
			//no filter:
			return true;
		}
		foreach ($this->Info['value_list'] as $obj){
			if ($obj['value']==$value){
				$this->obj=new DataStore(array('id'=>$obj['value'],'TITLE'=>$obj['title']));
				return true;
			}
		}
		return false;
	}
	/**
	 * Получить меню:
	 * @param rbcc5_select $sel
	 * @param rbcc5_module $module
	 * @return array
	 */
	function getMenu($sel1, $module){
		$sel= clone $sel1;
		if ($this->handler) $sel->dropWhere($this->handler);
		$sel->groupBy($this->key);
		$sel->select($this->key,'count(*) AS total');
		$total=$sel->toArray($this->key,true,'total');		
		$ret=array();
		foreach ($this->Info['value_list'] as $value){
			if (!isset($total[$value['value']])) continue;
			$ret[]=array(
				'id'=>$value['value'],
				'TITLE'=>$value['title'],
				'href'=>$module->buildLink(array($this->key=>$value['value'])),
				'selected'=>($this->obj&&$this->obj->getID()==$value['value'])?true:false,
				'total'=>$total[$value['value']],
			);
		}
		
		return $ret;
	}
}
?>