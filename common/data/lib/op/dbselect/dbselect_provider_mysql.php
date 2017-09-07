<?php
class dbselect_provider_mysql {
	
	var $db=null;
	
	function __construct($db){
		$this->db=$db;
	}
	
	static function factory($connectionString){
		if (preg_match('/(\w+):\/\/(\w+):(.*)@(.*?)\/(\w+)/',$connectionString, $m)){
			if (!$db= mysql_connect($m[4], $m[2], $m[3])){
				throw new Exception('Invalid database connection params');
			}
			if (!mysql_selectdb($m[5])){
				throw new Exception('Invalid database conncetion name');
			}
			return new dbselect_provider_mysql($db);
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
		if (!$ret=mysql_query($sql, $this->db)){
			throw new dbselect_exception($sql, mysql_error(),$this);
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
		while ($row=mysql_fetch_row($res)){
			$ret[]=$row;
		}
		return $ret;
	}
	/**
	 * Последний вставленный
	 * @return int
	 */
	function lastInsertID(){
		return mysql_insert_id($this->db);
	}
	
	function escape($str){
		return mysql_real_escape_string($str);
	}
}
?>