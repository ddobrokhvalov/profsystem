<?
class rbcc5 {
	/**
	 * Выборка:
	 * @return rbcc5_select
	 */
	static function select($table, $orderBy=false, $orderDir='ASC'){
		return rbcc5_select::getInstance($table, rbcc5_select::skipBlocks, $orderBy, $orderDir);
	}
	
	static function getObject($table, $query, $orderBy=false, $orderDir='ASC'){
		$sel= self::select($table, $orderBy, $orderDir);
		foreach ($query as $k=>$v){
			if (is_array($v)){
				$sel->Where($k,$v[0],$v[1]);		
			}
			else {
				$sel->Where($k,eq,$v);
			}
		}
		return rbcc5_object::fetchObject($sel);
	}

}
?>