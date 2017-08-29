<?php
/**
 * Класс доступа к MySQL
 *
 * @package    RBC_Contents_5_0
 * @subpackage lib
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class db_access_mysql extends db_access{

	/**
	 * Конструктор. Осуществляет соединение с БД
	 *
	 * Выставляет кодировку для MySQL
	 */
	function __construct($db_type, $db_server, $db_name, $db_user, $db_password){
		parent::__construct($db_type, $db_server, $db_name, $db_user, $db_password);
		// Выставляем кодировку клиента
		if(params::$params["encoding"]["value"]=="windows-1251"){
			$charset="cp1251";
		}elseif(params::$params["encoding"]["value"]=="utf-8"){
			$charset="utf8";
		}
		$sth=$this->dbh->prepare("SET CHARACTER SET {$charset}");
		$sth->execute();
		$sth=$this->dbh->prepare("SET NAMES '{$charset}'");
		$sth->execute();
	}

	/**
	 * Базонезависимая конкатенация произвольного количества полей для MySQL
	 *
	 * @see db_access::concat_clause()
	 */
	public function concat_clause($fields, $delimiter){
		if(count($fields)>1){
			foreach($fields as $key=>$field){
				if($key==0){
					$full_fields[]="IFNULL(".$field.", '')";
				}else{
					$full_fields[]="IF({$field} IS NULL, '', CONCAT('{$delimiter}', {$field}))";
				}
			}
			return "CONCAT(".join(', ', $full_fields).")";
		}else{
			return $fields[0];
		}
	}
}
?>