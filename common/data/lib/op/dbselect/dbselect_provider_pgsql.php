<?php
class dbselect_provider_pgsql {
	
	var $db=null;
	
	function __construct($db){
		$this->db=$db;
	}
	
	static function factory($connectionString){
		if (preg_match('/(\w+):\/\/(\w+):(.*)@(.*?)\/(\w+)/',$connectionString, $m)){
			$conn_str = 'host='.$m[4].' dbname='.$m[5].' user='.$m[2].' password='.$m[3];
			if (!$db= pg_connect($conn_str)){
				throw new Exception('Invalid database connection params');
			}
			return new dbselect_provider_pgsql($db);
		}
		else {
			throw new Exception('Invalid connection string');
		}
	}
	/**
	 * Выполнить запрос:
	 * @param $sql
	 * @return handler
	 */
	function query($sql){
		if (!$ret=pg_query($this->db, $sql)){
			throw new dbselect_exception($sql, pg_last_error(),$this);
		}
		return $ret;
	}
		
	/**
	 * Разобрать результат
	 * @param handler $res
	 * @return array
	 */
	function fetchResult($res){
		$ret=array();			
		while ($row=pg_fetch_row($res)){
			$ret[]=$row;
		}
		return $ret;
	}
	/**
	 * Последний вставленный
	 * @return int
	 */
	function lastInsertID(){
		return pg_insert_oid($this->db);// хз
	}
	
	function escape($str){
		return pg_escape_string(null, $str);
	}
}
?>