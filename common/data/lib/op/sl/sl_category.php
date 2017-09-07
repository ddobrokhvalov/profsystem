<?php
class sl_category extends rbcc5_admin{
	
	protected $id=0;
	protected $city=0;
	/**
	 * Конструктор:
	 * @param int $id	id категории
	 * @param int $city id города
	 * 	 
	 */
	function __construct($id, $city){
		$this->id=$id;
		$this->city=$city;	
	} 
	
	protected $_groups=null;
	/**
	 * Получить группы:
	 * @return array
	 */
	function getGroups(){
		if ($this->_groups) return $this->_groups;
		$sel= new rbcc5_select('SL_SERVICE_GROUP');
		$sel->Where('SL_SERVICE_CATEGORY_ID',eq,$this->id);
		$sel->OrderBy($sel->getOrderField(),$sel->getOrderDir());
		$gc= new rbcc5_select('SL_SERVICE_GROUP_CITY');
		$gc->Where('SL_CITY_ID',eq,array(0, $this->city));
		$sel->Join($gc, $sel->primary_key, $sel->primary_key, $gc->table, 'INNER');
		
		//Получаем список груп:
		$this->_groups=array();
		foreach ($sel as $obj){
			$this->_groups[$obj['SL_SERVICE_GROUP_ID']]=$obj;
		}
		
		return $this->_groups;
	}
	/**
	 * 
	 * @return array
	 */
	function getDashboard(){
		
	}
	/**
	 * Получить список сервисов:
	 * @return array
	 */
	static function getServices($groupID, $city){
		$sel= new rbcc5_select('SL_SERVICE');
		$sel->Where('SL_SERVICE_GROUP_ID',eq,$groupID);
		$sel->OrderBy($sel->getDefaultOrderBy(), $sel->getOrderDir());
		
		$sc= new rbcc5_select('SL_SERVICE_CITY');
		$sc->Where('CITY_ID',eq,array(0,$city));		
		$sel->Join($sc, $sel->primary_key, $sel->primary_key, $sc->table, 'INNER');
		
		foreach ($sel as $obj){
			$ret[$obj['SL_SERVICE_ID']]=new sl_service($obj);
		}
		
		return $ret;
	}
}
?>