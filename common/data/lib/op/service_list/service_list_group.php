<?php
class service_list_group extends rbcc5_object{
	
	var $table='SL_SERVICE_GROUP';
	/**
	 * Получить список сервисов:
	 * @param int $groupID группа услуг
	 * @param int $cityID  город
	 * @return array
	 */
	static function getServices($groupID, $cityID){
		$sel= new rbcc5_select('SL_SERVICE');
		$sel->applyEnv(rbcc5_object::$env);
		$sel->OrderBy($sel->getOrderField(), $sel->getOrderDir());
		$sel->Where('SL_SERVICE_ID',eq,$groupID);
		
		$serviceCity= new rbcc5_select('SL_SERVICE_CITY');
		//Получаем сервисы привязанные либо к текущему городу, либо к произвольному (0) 
		$serviceCity->Where('CITY_ID',eq,array(0,$cityID));
		$sel->Join($serviceCity,$sel->primary_key,$sel->primary_key,'CITY','INNER');
		
		$ret= array();
		foreach ($sel as $obj){
			$ret[$obj['SL_SERVICE_ID']]=$obj;
		}
		
		return $ret;
	}
	/**
	 * Переиндексировать:
	 * @return boolean
	 */
	function reindex(){
		$target= new rbcc5_select('SL_GROUP_CITY');
		//Удаляем старый индекс:
		$target->Where('SL_GROUP_CITY_ID',eq,$this->getID());
		$target->Delete();
		
		$s= new rbcc5_select('SL_SERVICE');
		$i_fields= array('SL_SERVICE_GROUP_ID');		
		$s_fields= array('SL_SERVICE_GROUP_ID');
		if ($target->hasDecorator('version')){
			/*Учитываем версию:*/
			$s_fields[]='VERSION';
			$s->GroupBy('VERSION');
		}
		$s_city= new rbcc5_select('SL_SERVICE_CITY');
		$s_city->GroupBy('SL_CITY_ID');
		$s_city->Select('SL_CITY_ID');
		//Сортируем по 
		$s_city->OrderBy('SL_CITY_ID','ASC');
		$s->Join($s_citym, 'SL_SERVICE_ID','SL_SERVICE_ID','CITY','INNER');
		if ($target->hasDecorator('VERSION')){		
			/**
			 * Генерируем для разных версий:
			 */
			foreach ($s->ToArray('VERSION',false,'CITY') as $version=>$cities){
				$this->_buildIndex($cities, array('VERSION'=>$version));
			}
		}
		else {
			$this->_buildIndex($s->ToArray(''))
		}
		
	}
	
	protected function _buildIndex($cities, $insert=array()){
		$target= new rbcc5_select('SL_GROUP_CITY');
		$insert['SL_SERVICE_ID']=$this->getID();
		if ($cities[0]['SL_CITY_ID']==0){
			//Если хотя бы один объект связан со всеми городами группа тоже связана со всеми:
			$insert['CITY_ID']=0;
			$s->Insert($insert);
		}
		else {
			foreach ($cities as $city){
				/**
				 * Связываем со всеми городами:
				 */
				$insert['CITY_ID']=$city['CITY_ID'];
				$s->Insert($insert);
			}
		}
	} 
	/**
	 * Получить список групп категории
	 * @param $categoryID
	 * @param $cityID
	 * @return unknown_type
	 */
	static function getGroups($categoryID, $cityID){
		$sel= new rbcc5_select('SL_SERVICE_GROUP');
		$sel->applyEnv(rbcc5_select::$env);
		foreach ($sel as $obj){
			
		}
	}
}
?>