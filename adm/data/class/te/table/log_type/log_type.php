<?PHP
/**
 * Класс для реализации нестандартного поведения таблицы Типов журналов системы журналирования
 *
 * @package		RBC_Contents_5_0
 * @subpackage te
 * @copyright	Copyright (c) 2007 RBC SOFT
 * @author Alexandr Vladykin		
 */
 
 class log_type extends table_translate {
	
	/**
	* Переопределенная ф-ция вывода операций с добавлением ссылки с переходом на просмотр журнала
	*/
	
	public function get_index_ops($record) {
		$res = $this -> call_parent( 'get_index_ops', array( $record ) );
		array_unshift($res['_ops'], array("name"=>"view", "alt"=>metadata::$lang["lang_view"], "url"=>$this->url->get_url("link", array("id"=>$record[$this->autoinc_name], "autoinc_name"=>$this->autoinc_name, "link"=>array("secondary_table"=>"LOG_RECORD")))));
		return $res;
	}
}
?>