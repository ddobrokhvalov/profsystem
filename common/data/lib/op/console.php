<?php
/**
 * Ком
 * @author 1
 *
 */
class console {
	/**
	 * Аргументы командной строки:
	 * 
	 * @param $argv
	 * @param $argvSeq
	 * @param $argReq
	 * @return datastore
	 */
	static function args($argv, $argvSeq, $argReq=array()){
		$ret=new DataStore();
		$counter=0;
		for ($i=1; $i<count($argv);$i++){
			if (substr($argv[$i],0,1)=='-'){
				$key=trim($argv[$i], '-');
				if (substr($argv[$i+1],0,1)=='-'&&!in_array($key, $argReq)){
					$ret->Info[$key]=true;
				}
				else {
					$ret->Info[$key]=$argv[$i+1];
					$i++;
				}
			}
			else {
				if (isset($argvSeq[$counter])){
					$ret->Info[$argvSeq[$counter]]=$argv[$i];
					$counter++;
				}
			}
		}
		return $ret;
	}
	/**
	 * Написать:
	 * @return void
	 */
	static function write(){
		$a= func_get_args();
		if (is_array($a[0])) $a=$a[0];
		echo implode(PHP_EOL, $a),PHP_EOL;
	}
	/**
	 * Ошибка:
	 * @return void
	 */
	static function error(){
		$a= func_get_args();
		self::write($a);
		die();
	}
	
	static function debug($obj){
		if (is_object($obj)) $obj=$obj->Info;
		print_r($obj);
		echo PHP_EOL;
	}
}
?>