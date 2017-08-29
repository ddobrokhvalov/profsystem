<?PHP
/**
 * Класс эмуляции PDOStatement при помощи библиотеки oci8
 *
 * @author Alexandr Vladykin <avladykin@rbc.ru>
 * @package    RBC_Contents_5_0
 * @subpackage lib
 * @copyright  Copyright (c) 2009 RBC SOFT
 * 
*/

class db_oracle_oci_statement_to_pdo {
	
	/**
	* Идентификатор соединения с Oracle
	* @var resource
	*/
	
	private $oci_connection;

	/**
	* Идентификатор оператора, возвращенного oci_parse
	* @var resource
	*/

	private $oci_parsed_statement;
	
	/**
	* Массив LOB-дескрипторов, использующихся для вставки данных в LOB-поля
	* @var array
	*/
	
	public $lob_descriptors = array();

	/**
	* Массив значений, использующихся для вставки данных в LOB-поля
	* @var array
	* @see $lob_descriptors
	*/
	
	public $lob_values = array();
	
	/**
	* Конструктор
	* @param resource $oci_connection Идентификатор соединения с Oracle, возвращенный oci_connect
	* @param resource $oci_parsed_statement Идентификатор оператора, возвращенный oci_parse
	*/
	
	function __construct(&$oci_connection, &$oci_parsed_statement){
		$this->oci_connection =& $oci_connection;
		$this->oci_parsed_statement =& $oci_parsed_statement;
	}
	
	
	/**
	* Деструктор, очищает oci8 statement
	*/
	
	public function __destruct() {
		oci_free_statement($this->oci_parsed_statement);
	}

	/**
	* Связывает поле с переменной PHP
	* @see PDOStatement::bindColumn
	*/
	
	public function bindColumn ($column , &$param, $type ) {
		return oci_define_by_name($this->oci_parsed_statement, $column, $param);
	}


	/**
	* Связывает параметр с определенной переменной
	* @see PDOStatement::bindParam
	*/
	
	public function bindParam ( $parameter , &$value, $data_type=PDO::PARAM_STR ) {
		
		if (in_array($data_type, array(OCI_B_BLOB, OCI_B_CLOB))) {
			// Отдельно обрабатываем LOB-ы, для них заводим дескриптор в массив lob_descriptors и значение в lob_values, 
			// для последующей записи его в ф-ии save_lobs после выполнения операции
			
			$this->lob_descriptors[$parameter] = oci_new_descriptor($this->oci_connection, OCI_D_LOB);
			$ret = oci_bind_by_name($this->oci_parsed_statement, $parameter, $this->lob_descriptors[$parameter], -1, $data_type);
			$this->lob_values[$parameter] =& $value;
			return $ret;
		}
		
		return oci_bind_by_name($this->oci_parsed_statement, $parameter, &$value);
	}
	
	/**
	* Связывает значение с определенным параметром
	* @see PDOStatement::bindValue
	*/
	
	public function bindValue ( $parameter , $value,  $data_type=PDO::PARAM_STR) {
		return $this->bindParam($parameter , $value, $data_type );
	}
	

	/**
	* Выполняет подготовленый оператор
	* @see PDOStatement::execute
	* @param boolean $autocommit Вызывать ли commit после выполнения оператора
	*/
	
	public function execute ($input_parameters=array(), $autocommit=true) {
		if (sizeof($input_parameters)>0) 
			foreach ($input_parameters as $key=>$value) 
				$this->bindParam($key, $value);

		$r = @oci_execute($this->oci_parsed_statement, OCI_DEFAULT);
		if (!$r) {
			$e = oci_error($this->oci_parsed_statement);
			throw new DBDebugException ($e['message'], "\n".$e['sqltext']."\n ".preg_replace('/Array\s*\((.*)\)/s', '\1', print_r($params, 1)));
		}
		
		$this->save_lobs();

		if ($autocommit)
			oci_commit($this->oci_connection);
	}

	
	/**
	* Записывает LOB-значения в БД
	*/
	
	private function save_lobs () {
		if (!sizeof($this->lob_values)) return;
		
		foreach ($this->lob_values as $param_name=>$value) {
			$this->lob_descriptors[$param_name]->write($this->lob_values[$param_name]);
			$this->lob_descriptors[$param_name]->free();
		}
	
		$this->lob_descriptors=$this->lob_values=array();
	} 
	
	
	/**
	* Закрывает курсор
	* @see PDOStatement::closeCursor
	*/
	
	public function closeCursor() {
		return ocifreecursor($this->oci_parsed_statement);
	}
	
	/**
	* Возвращает кол-во колонок в результирующем множестве
	* @see PDOStatement::columnCount
	*/
	
	public function columnCount() {
		return oci_num_fields($this->oci_parsed_statement);
	}
	
	/**
	* Возвращает последний код ошибки
	* @see PDOStatement::errorCode
	*/
	
	public function errorCode() {
		$err = oci_error();
		if ($err)
			return $err['code'];
		return null;
	}
	
	/**
	* Возвращает расширенную информацию об последнем коде ошибки
	* @see PDOStatement::errorInfo
	*/
	
	public function errorInfo () {
		$err = oci_error();
		if ($err)
			return array (
				$err['code'],
				$err['code'],
				$err['message']
			);
		return null;
	}	
	
	/**
	* Возвращает следующую строку из результирующего набора
	* @see PDOStatement::fetch
	*/
	
	public function fetch ($fetch_style=PDO::FETCH_BOTH) {
		switch ($fetch_style) {
			case PDO::FETCH_BOTH : $fetch_style = OCI_BOTH+OCI_RETURN_LOBS; break;
			case PDO::FETCH_ASSOC : $fetch_style = OCI_ASSOC+OCI_RETURN_LOBS; break;
			case PDO::FETCH_NUM : $fetch_style = OCI_NUM; break;
			case PDO::FETCH_BOUND : return oci_fetch($this->oci_parsed_statement);
			case PDO::FETCH_OBJ : return oci_fetch_object($this->oci_parsed_statement);
		}
				
		return oci_fetch_array($this->oci_parsed_statement, $fetch_style);
	}
	
	/**
	* Возвращает массив, который содержит весь результирующий набор
	* @see PDOStatement::fetchAll
	*/
	
	public function fetchAll ($fetch_style=PDO::FETCH_BOTH, $column_index=0 ) {
		$res = array();

		if ($fetch_style != PDO::FETCH_COLUMN)
			while ($row=$this->fetch($fetch_style)) {$res[]=$row;}
		else
			oci_fetch_all($this->oci_parsed_statement, $res);
		
		return $res;
	}
	
	/**
	* Возвращает одну колонку из следующей строки результирующего набора
	* @see PDOStatement::fetchColumn
	*/
	
	public function fetchColumn ($column_number=0) {
		$res = $this->fetch(PDO::FETCH_NUM);
		return $res[$column_number];
	}
	
	
	/**
	* Возвращает кол-во строк, затронутых последним SQL-оператором
	* @see PDOStatement::rowCount
	*/
	
	public function rowCount () {
		return oci_num_rows($this->oci_parsed_statement);
	}
	
}
?>