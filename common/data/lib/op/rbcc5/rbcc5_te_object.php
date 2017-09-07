<?php
/**
 * Табличные объекты
 * @author atukmanov
 *
 */
class rbcc5_te_object extends rbcc5_object {
	/**
	 * Конструктор:
	 * @param $Info	 
	 */
	function __construct($Info, $table='TE_OBJECT'){
		parent::__construct($Info, $table);
		//Запоминаем в кэше:
		self::$_systemNameCache[$Info['SYSTEM_NAME']]=$Info[$table.'_ID'];
	}
	/**
	 * Кэш элементов
	 * @var unknown_type
	 */
	protected static $_systemNameCache=array();
	/**
	 * Загрузить по системному объекту
	 * @param $systemName
	 * @return rbcc5_te_object
	 */
	static function loadBySystemName($systemName){
		if (isset(self::$_systemNameCache[$systemName])){
			//Выбираем из кэша:
			return rbcc5_object::loadByID(self::$_systemNameCache[$systemName],'TE_OBJECT');
		}
		else {
			//Выбираем из базы:
			$sel= new rbcc5_select('TE_OBJECT');
			$sel->Where('SYSTEM_NAME',eq,$systemName);
			
			$ret= rbcc5_object::fetchObject($sel);
			
			return $ret;
		}
	} 
	/**
	 * Получить информационные блоки:
	 * @return rbcc5_select
	 */
	function selectInfBlocks(){
		$sel= new rbcc5_select('INF_BLOCK');
		$sel->Where('TE_OBJECT_ID',eq,$this->getID());
		return $sel;
	}
}
?>