<?php
class rbcc5_template extends rbcc5_object {
	/**
	 * Загрузитб
	 * @param $id
	 * @return rbcc5_template
	 */
	static function loadById($id){
		$sel= new rbcc5_select('TEMPLATE');
		$sel->Where($sel->primary_key,eq,$id);
		return $sel->selectObject(__CLASS__);
	}
	/**
	 * Получить главную область:
	 * @return rbcc5_object
	 */
	function getMainArea(){
		
		$sel= new rbcc5_select('TEMPLATE_AREA');		
		$sel->Where('IS_MAIN',eq,1);
		$map= new rbcc5_select('TEMPLATE_AREA_MAP');
		$map->Where('TEMPLATE_TYPE_ID',eq,$this->TEMPLATE_TYPE_ID);
		$sel->Join($map, $map->primary_key, $map->primary_key,null,'INNER');
		
		return rbcc5_object::fetchObject($sel);
	}
}
?>