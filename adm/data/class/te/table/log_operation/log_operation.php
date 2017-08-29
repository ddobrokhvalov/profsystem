<?PHP
/**
 * Класс для реализации нестандартного поведения таблицы операций журналов системы журналирования
 *
 * @package		RBC_Contents_5_0
 * @subpackage te
 * @copyright	Copyright (c) 2007 RBC SOFT
 * @author Alexandr Vladykin		
 */

	class log_operation extends table_translate {
		 
		 /**
		 * @param int $log_type Если указан, выборка выполняется только для указанного типа журнала. Используется в фильтре
		 */
		 
		 private $log_type;
		 
		 /**
		 * В list_mode передается ID типа журнала если нужно вывести только некоторые значения журнала
		 */
		 
		public function get_index_records(&$request, $mode, $list_mode, $include=0, $exclude=array()){
				if ($list_mode) {
					$this->log_type=$list_mode;
				}
				return $this -> call_parent ( 'get_index_records' , array( $request, $mode, $list_mode, $include, $exclude ) );
		}
		 
		/**
		* Дополняем типом журнала если нужно
		*/
		
		public function ext_index_query(){
			$ret = $this -> call_parent ( 'ext_index_query' );
			
			if ($this->log_type)
				$ret.=' AND LOG_TYPE_ID=:log_type_id ';
			
			return $ret;
		}
		 
		 /**
		 * Дополняем типом журнала если нужно
		 */
		 public function ext_index_query_binds(){
			$ret = $this -> call_parent ( 'ext_index_query_binds' );
			
			if ($this->log_type) 
				$ret['log_type_id']=$this->log_type;
			
			return $ret;
		}
	}
?>