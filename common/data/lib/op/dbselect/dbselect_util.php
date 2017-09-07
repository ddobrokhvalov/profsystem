<?php
class dbselect_util {
	/**
	 * Создать временную таблицу:
	 *
	 * @param dbSelect $sel
	 * @return dbSelect
	 */
	static function createTemporaryTable($sel, $copyData=true){
		$tmpTable=$sel->table.'_tmp';
		dbselect::$DB->query('DROP TABLE IF EXISTS '.$tmpTable);
		dbselect::$DB->query('CREATE TABLE `'.$tmpTable.'` LIKE `'.$sel->table.'`');
		if ($copyData) dbselect::$DB->query('INSERT INTO '.$tmpTable.' SELECT * FROM '.$sel->table);
		
		$sel->table.='_tmp';
		return $sel;
	}
	/**
	 * Принять временную таблицу:
	 *
	 * @param dbSelect $sel
	 * @return dbSelect
	 */
	static function commitTemporaryTable($sel){
		if (!preg_match('/_tmp$/',$sel->table)) throw new Exception('Invalid tmp table');
		$table=substr($sel->table,0,-4);
		dbselect::$DB->query('DROP TABLE IF EXISTS '.$table.'_bac;');
		dbselect::$DB->query('RENAME TABLE `'.$table.'` TO `'.$table.'_bac`, `'.$table.'_tmp` TO `'.$table.'`');
		$sel->table=$table;
		return $table;
	}
	
	static function buildIndex($src, $dst){
		$dst=self::createTemporaryTable($dst, false);		
		$dst->Insert($src);
		self::commitTemporaryTable($dst);
	}
}
?>