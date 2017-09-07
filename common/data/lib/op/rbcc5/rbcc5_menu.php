<?php
class rbcc5_menu extends DataStore {
	
	
	var $linkParams=array();
	/**
	 * 
	 * @param array $tables
	 * @param array $query
	 * @return rbcc5_menu
	 */
	static function multitableOrderable($tables, $query){
		$stack=array();
		
		/**
		 * Ищем явно переданный ID:
		 */
		foreach ($tables as $table){
			if (isset($query[str::lower($table)])){
				$id=$query[str::lower($table)];
				
				if (!preg_match('/^[1-9]\d*$/', $id)){
					//Invalid ID:
					return false;
				}
				if ($obj=rbcc5_object::loadByID($id, $table)){
					$ret->Info['link']=array(str::lower($table),$id);
					break;
				}
				else {
					//404:
					return false;
				}
			}
			else {
				array_push($stack, $table);
			}
		}
		
		$ret=new rbcc5_menu();
		/**
		 * Строим путь:
		 */
		if ($obj){
			$path=array();
			$path[$obj->table]=$obj;
			while ($table=array_pop($stack)){
				$obj=$obj->getObject($table);
				$path[$obj->table]=$obj;
			}			
		}
		$root=null;
		/**
		 * Сортируем путь:
		 */
		foreach ($tables as $table){
			$sel= rbcc5::select($table, true);
			if ($root){
				$sel->Where($root->table.'_ID',eq,$root->getID());				
			}
			/**
			 * Получаем уровень меню:
			 */
			$ret->Info['menu'][$table]=rbcc5_object::fetchList($sel);
			if (isset($path[$table])){
				/**
				 * Явно есть в запросе:
				 */
				$root=$path[$table];
			}
			else {
				/**
				 * Получаем первый:
				 */
				foreach ($ret->Info['menu'][$table] as $root) break;
			}
			$ret->Info['path'][$table]=$root;
		}
		
		return $ret;
	}
}
?>