<?php
/**
 * ��������� ��� rbcc5
 * @author atukmanov
 *
 */
class dbselect_provider_rbcc5 implements dbselect_provider {
	/**
	 * ������
	 * @see lib/dbselect_provider#query($sql)
	 */
	function query($sql){	
		try {	
			return db::sql_query($sql, array(), array(), true);
		}
		catch (Exception $e){			
			throw new dbselect_exception($sql, $e->getMessage());		
		}
	}
	
	function escape($str){
		return substr(db::db_quote($str),1,-1);
	}
	
	function fetchResult($res){		
		return $res->fetchAll(PDO::FETCH_NUM);
	}
	
	function lastInsertID(){
		return db::last_insert_id('');
	}
}
?>