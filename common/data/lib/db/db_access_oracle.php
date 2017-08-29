<?php
/**
 * Класс доступа к Oracle
 * В связи с ограничениями библиотеки PDO для Oracle и нестабильным ее состоянием внедрена работа с oci8 на основе
 * аналогичного PDO Statement класса db_oracle_oci_statement_to_pdo
 *
 * @package    RBC_Contents_5_0
 * @subpackage lib
 * @copyright  Copyright (c) 2008 RBC SOFT
 * 
 */
 
include_once(dirname(__FILE__)."/db_oracle_oci_statement_to_pdo.php"); 
 
class db_access_oracle extends db_access{

	/**
	 * Кэш выборки lob-полей из БД
	 * @var array
	 * @see db_access_oracle::get_lobs()
	 */
	 
	protected $lob_cache;

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Конструктор. Осуществляет соединение с БД
	 */
	 
	function __construct($db_type, $db_server, $db_name, $db_user, $db_password){
		$this->dbh = oci_pconnect($db_user, $db_password, $db_name, (params::$params['encoding']['value']=='utf-8')?'UTF8':'CL8MSWIN1251');
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////


	/*
	* Выборка данных из БД
	* Для Оракле добавлена работа с LIMIT
	* @see db_access::sql_select
	*/
	
	public function sql_select($query, $fields=array(), $special=array()) {
		$parsed_query=$this->get_limit_from_query($query);
		// Если LIMIT есть, то модифицируем запрос
		if($parsed_query["is_limited"]){
			// Если выбираем не с нуля
			if($parsed_query["offset"]){
				$query="
					SELECT TABLE_LIMIT.* FROM(
						SELECT TABLE_LIMIT.*, ROWNUM AS ROWNUM_LIMIT FROM(
							{$parsed_query["pure_query"]}
					    )TABLE_LIMIT WHERE ROWNUM<=:lim_row_count_plus_offset
					)TABLE_LIMIT WHERE ROWNUM_LIMIT>:lim_offset
				";
				$fields+=array("lim_row_count_plus_offset"=>$parsed_query["row_count"]+$parsed_query["offset"], "lim_offset"=>$parsed_query["offset"]);
			// Если выбираем с нуля (запрос проще, поэтому более производительный)
			}else{
				$query="
					SELECT TABLE_LIMIT.*, ROWNUM FROM(
						{$parsed_query["pure_query"]}
					)TABLE_LIMIT WHERE ROWNUM<=:lim_row_count
				";
				$fields+=array("lim_row_count"=>$parsed_query["row_count"]);
			}
		}
		
		
		return parent::sql_select($query, $fields, $special);
	}
	
	/**
	* Подготовка запроса, возвращает объект класса db_oracle_oci_statement_to_pdo, аналога PDO Statement, работающего с библиотекой oci
	* @see db_access::prepare_query
	*/
	
	protected function prepare_query($query) {
		$sth = @oci_parse($this->dbh, $query);
		if (!$sth) {
			$e = oci_error($this->dbh);
			throw new DBDebugException($e['message'], "\n{$e['sqltext']}\n");
		}

		return new db_oracle_oci_statement_to_pdo($this->dbh, $sth);
	}
	
	/**
	*	Экранирование данных против SQL-инъекций
	* В связи с тем, что класс не использует PDO, приходится делать вручную
	* @see db_access::db_quote
	*/
	
	public function db_quote($content){
		return "'".preg_replace("/'/", "''", $content)."'";
	}
	
	

	/**
	 * Базонезависимая конкатенация произвольного количества полей для Oracle
	 *
	 * @see db_access::concat_clause()
	 */

	public function concat_clause($fields, $delimiter){
		if(count($fields)>1){
			foreach($fields as $key=>$field){
				if($key==0){
					$full_fields[]="NVL(".$field.", '')";
				}else{
					$full_fields[]="NVL2({$field}, '{$delimiter}' || {$field}, '')";
				}
			}
			return join(' || ', $full_fields);
		}else{
			return $fields[0];
		}
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Выборка информации о наличии lob-полей в таблице для использования в insert_record(), update_record()
	 *
	 * Работает как для таблиц из собственной схемы, так и для таблиц из других схем, для которых есть синонимы в текущей схеме.
	 * Формат: array("BODY"=>"BLOB", "ANOTHER_BODY"=>"CLOB"). Если lob-полей нет, то вернет false
	 *
	 * @param string $table		название таблицы, для которой собирается информация
	 * @return mixed
	 */
	 
	protected function get_lobs($table){
		// Используется кэширование, чтобы не тормозить серии инсертов или апдейтов
		if(!isset($this->lob_cache[$table])){
			// Первая половина запроса для таблиц из собственной схемы, вторая - для синонимов из других схем
			$lobs=parent::sql_select("
				SELECT USER_TAB_COLUMNS.COLUMN_NAME, USER_TAB_COLUMNS.DATA_TYPE FROM USER_TAB_COLUMNS
					WHERE USER_TAB_COLUMNS.TABLE_NAME=:table1
						AND (USER_TAB_COLUMNS.DATA_TYPE IN ('BLOB', 'CLOB', 'NCLOB', 'BFILE', 'LONG_RAW'))
			", array("table1"=>$table));
			// Формируем запись для кэша
			foreach($lobs as $lob){
				$done_lobs[$lob["COLUMN_NAME"]]=$lob["DATA_TYPE"];
			}
			$this->lob_cache[$table]=(is_array($done_lobs) ? $done_lobs : array());
		}
		return $this->lob_cache[$table];
	}
	
	
	/**
	 * Помещение записи в таблицу
	 *
	 * Добавлена работа с LOB-полями. Функция переписана, не вызывает родительский метод
	 * @see  db_access::insert_record
	 */
	 
	public function insert_record($table, $fields=array(), $special=array()){
		if (!is_array($special)) $special=array();
		// получаем blob-поля
		$lob_fields = $this->get_lobs($table);

		$columns = array();
		$values = array();
		$lobs = array();
		
		foreach($fields as $key=>$value){
			$columns[]=$key;
			
			if (array_key_exists($key, $lob_fields)) {
				if ($lob_fields[$key]=='BLOB') {
					// если поле является блобом, то вставляем вместо значения-параметра ф-ию EMPTY_BLOB, а затем внедряем данные через RETURNING INTO
					$lobs[] = $key;
					$values[] = 'EMPTY_BLOB()';
					$special[$key] =  OCI_B_BLOB;
				}
				elseif ($lob_fields[$key]=='CLOB') {
					// то же самое для CLOB
					$lobs[] = $key;
					$values[] = 'EMPTY_CLOB()';
					$special[$key] =  OCI_B_CLOB;
				}
			}
			else 
				$values[]=$this->set_parameter_colon($key);
			
		}
		$columns=join(", ",$columns);
		$values=join(", ",$values);

		// добавляем вставку лобов
		$query=$this->set_lobs("INSERT INTO {$table} ({$columns}) VALUES ({$values})", $lobs);
		
		$sth=$this->execute_query($query, $fields, $special);
	}
	
	
	/**
	 * Изменение записи или записей в таблице с ограничением по значениям некоторых полей
	 * Добавлена работа с LOB-полями. Функция переписана, не вызывает родительский метод
	 * @see db_access::update_record
	 */
	 
	public function update_record($table, $fields=array(), $special=array(), $where=array()) {	
		if ( !is_array( $fields ) || !count( $fields ) ||
			!is_array( $where ) || !count( $where ) ) return;
	
		if (!is_array($special)) $special=array();
		
		$lob_fields = $this->get_lobs($table);

		$lobs = array();
				
		foreach($fields as $key=>$value){
			if (array_key_exists($key, $lob_fields)) {

				if ($lob_fields[$key]=='BLOB') {
					// если поле является блобом, то вставляем вместо значения-параметра ф-ию EMPTY_BLOB, а затем внедряем данные через RETURNING INTO
					$pairs[] = "{$key}=EMPTY_BLOB()";
					$special[$key] =  OCI_B_BLOB;
					$lobs[] = $key;
				}
				elseif ($lob_fields[$key]=='CLOB') {
					// то же самое для CLOB
					$pairs[] = "{$key}=EMPTY_CLOB()";
					$special[$key] =  OCI_B_CLOB;
					$lobs[] = $key;
				}					
			}
			else 
				$pairs[]="{$key}=".$this->set_parameter_colon($key);
		}
		
		$pairs=join(", ",$pairs);

		foreach($where as $key=>$value){
			$ands[]="{$key}=:ands_{$key}";
			$fields["ands_".$key]=$value;
		}
		
		$ands=join(" AND ",$ands);

		$query=$this->set_lobs("UPDATE {$table} SET {$pairs} WHERE {$ands}", $lobs);
		
		$sth=$this->execute_query($query, $fields, $special);
		
		return $sth->rowCount();
	}
	

	/**
	* Функция модифицирует запросы INSERT и UPDATE для работы с блобами
	* @param string $query Запрос
	* @param array $blobs массив названий полей с блобами
	*
	* @return string Оператор
	*/
	
	private function set_lobs ($query, $lobs) {
		if (sizeof($lobs)) 
			$query .= ' RETURNING '.implode(', ',$lobs).' INTO '.implode(', ', array_map(array($this, 'set_parameter_colon'), $lobs));
		return $query;
	}
	

	
	/**
	 * Получение последнего автоинкрементного идентификатора
	 *
	 * @param string $sequence_name		название сиквенса (учитывается только теми СУБД, где есть сиквенсы)
	 * @return int
	 */
	
	public function last_insert_id($sequence_name){
		$res = parent::sql_select('SELECT '.$sequence_name.'.CURRVAL AS CURRVAL FROM DUAL');
		return $res[0]['CURRVAL'];
	}
	
	
	/**
	* Возвращает поля, входящие в первичный ключ таблицы
	* @param string $obj	название таблицы
	* @return array
	*/
	
	public function get_primary_key_fields ($obj) {
		return lib::array_reindex($this->sql_select('
								SELECT 
									UCC.COLUMN_NAME 
								FROM 
									USER_CONSTRAINTS UC
										INNER JOIN
											USER_CONS_COLUMNS UCC
												USING (CONSTRAINT_NAME)
								WHERE
									UC.CONSTRAINT_TYPE=\'P\'
										AND
											UC.TABLE_NAME=:obj
		', array('obj'=>$obj)), 'COLUMN_NAME'
		);
	}
	
	
	/**
	* Возвращает поля, входящие в автоинкремент
	* @param string $obj	название таблицы
	* @return array
	*/
	
	public function get_autoincrement_fields($obj) {
		return lib::array_reindex($this->sql_select('
								SELECT
									COLUMN_NAME
								FROM
									USER_TRIGGER_COLS
								WHERE 
									TRIGGER_NAME = :trigger_name
										AND
											TABLE_NAME = :obj
		', array('trigger_name'=>$obj.'_BI', 'obj'=>$obj)), 'COLUMN_NAME');
	}
}



?>