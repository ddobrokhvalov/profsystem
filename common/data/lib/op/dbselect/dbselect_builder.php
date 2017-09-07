<?php
class dbselect_builder{
	static function generate_select_class($prefix){
		
		$plen=strlen($prefix)+1;
		
		$ret='<?'.PHP_EOL.'class '.$prefix.'_select{'.PHP_EOL;
		$sql="SHOW TABLES LIKE '".$prefix."_%';";		
		$res=dbselect::getDBProvider()->query($sql);
		
		if (DB::isError($res)) die('Error');
		while($obj=$res->fetchRow(DB_FETCHMODE_ORDERED)){
			list($fields,$pk)=self::fields($obj[0]);
			$ret.="
/**
 * ".$obj[0]." full
 * 
 * @return dbselect
 */	
static function ".substr($obj[0],$plen).'(){$a=func_get_args(); return dbselect::factory(array('.$fields.'),\''.$obj[0].'\',null,$a);}'.PHP_EOL.
"/**
 * ".$obj[0]." id
 * 
 * @return dbselect
 */
static function _".substr($obj[0],$plen).'(){$a=func_get_args(); return dbselect::factory(array(\''.$pk.'\'),\''.$obj[0].'\',null,$a);}'.PHP_EOL.PHP_EOL					
			;			
		}
		$ret.=PHP_EOL.'}?>';
		return $ret;
	}
		
	/**
	 * Получить список полей:
	 *
	 * @param string $table
	 * @return array
	 */
	static function fields($table){
		$sql="DESCRIBE ".$table;
		$res=dbselect::getDBProvider()->query($sql);
		$select='';
		$pk=null;
		while($obj=$res->fetchRow(DB_FETCHMODE_ORDERED)){
		
			str::Add($select,"'".$obj[0]."'",',');
			if (!$pk) $pk=$obj[0];
		}
		return array($select,$pk);		
	}
}
?>