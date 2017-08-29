<?PHP
/**
 * Корневой класс отображения журнальной информации для пользователя
 *
 * @package		RBC_Contents_5_0
 * @subpackage te
 * @copyright	Copyright (c) 2007 RBC SOFT
 * @author Alexandr Vladykin	 
 *
*/
	class log_show extends table {
	
		
		/**
		* @var array $common_fields	Поля, которые присутствуют во всех журналах
		*/
		
		private $common_fields = array (
				"LOG_RECORD_ID" => array ("title"=>"lang_identifier", "show"=>1),
				"LOG_INFO" => array ("title"=>"lang_log_info", "show"=>1),
				"DENORMALIZED_INFO" => array ("title" => "lang_denormalized_info", "show"=>1)
		);
		
		/**
		* @var array $common_fields_not_visible	Поля, которые не нужно показывать ни в одном журнале
		*/
		
		private $common_fields_not_visible = array (
				"LOG_INFO", "DENORMALIZED_INFO"
		);
		
		
		/**
		* @var array $fields для дочерних классов. Здесь хранятся поля, которые необходимо вывести пользователю 
		* в стандартном формате, кроме того, что добавился параметр subtype="extended", 
		* который создает виртуальное поле
		*/
		
		protected $fields = array ();

		/**
		* @var array $extended_fields Здесь хранятся дополнительные виртуальные поля, которые сюда попадают после преобразования
		* из $fields. Специально заполнять данное поле в подклассах не нужно.
		*/
		
		protected $extended_fields = array();
		
		
		/**
		* @var array $fields_absent_in_list	Список полей, которые не нужно показывать в общем списке, заполнять в дочерних классах
		*/
		
		protected $fields_absent_in_list = array();
		
		/**
		* Стандарный конструктор для табличного объекта, но с добавлением вызова prepare_fields и переопределенного класса url
		*/
		
		function __construct($obj, $full_object=""){
			parent::__construct($obj, $full_object);
			$this->prepare_fields();
		}
		
		/**
		* Подготовка полей к работе. Применяет языки и переносит $fields c subtype="extended" в $extended_fields,
		* А также выполняет дополнительные операции.
		*/
		
		private function prepare_fields() {
			$this->fields = array_merge($this->common_fields, $this->fields);
			if (sizeof($this->fields)) {
				foreach (array_keys($this->fields) as $field_name) {
					if ($this->fields[$field_name]['title'] && metadata::$lang[$this->fields[$field_name]['title']])
						$this->fields[$field_name]['title']=metadata::$lang[$this->fields[$field_name]['title']];
						
					if ($n=sizeof($this->fields[$field_name]['value_list'])) {
						for ($i=0; $i<$n; $i++) {
							$process = &$this->fields[$field_name]['value_list'][$i];
							if ($process['title'] && metadata::$lang[$process['title']]) {
								$process['title']=metadata::$lang[$process['title']];
							}
						}
					}
					
					if ($this->fields[$field_name]['subtype']=='extended') {
						$this->extended_fields[$field_name]=$this->fields[$field_name];
						unset ($this->fields[$field_name]);
					}
				}
				
			 // применяем фильтр если необходимо для LOG_OPERATION_ID
			 if ($this->fields['LOG_OPERATION_ID'] && $this->fields['LOG_OPERATION_ID']['filter'] && $_REQUEST["_f_LOG_TYPE_ID"]) {
					// если есть фильтр по данному полю, то в list_mode записываем текущий ID типа журнала
					$this->fields['LOG_OPERATION_ID']['list_mode']=$_REQUEST["_f_LOG_TYPE_ID"];
			 }
				
				metadata::$objects[$this->obj]["fields"] = sizeof($this->fields)?$this->fields:metadata::$objects[$this->obj]["fields"];
			}
		}
		
		/**
		* Ф-ия выбора подкласса, который будет работать в данном случае в зависимость от $_REQUEST["_f_LOG_TYPE_ID"]
		* @return string Название подкласса, который необходимо вызвать для показа соотв. журнала
		*/
		public static function subclass () {
			if ($_REQUEST["_f_LOG_TYPE_ID"]) {
				$log_type_obj = object::factory( 'LOG_TYPE' );
				metadata::$objects['LOG_RECORD']["title"] =
					$log_type_obj -> get_record_title( array( 'LOG_TYPE_ID' => $_REQUEST["_f_LOG_TYPE_ID"] ) );
				$log_type_obj -> __destruct();
				
				// подклассы должны называться так же как классы логов, но вместо log_ должно быть log_show_
				$ret = str_replace('log_', 'log_show_', log::$log_types_by_id[$_REQUEST["_f_LOG_TYPE_ID"]]['SYSTEM_NAME']);
				
				if (is_file(params::$params["adm_data_server"]["value"]."class/te/table/log_show/$ret.php")) {
					return $ret;
				}
			}
			
			if ($_REQUEST['action']!='service')
				throw new Exception(metadata::$lang['lang_unavailable_log']);
			
			return 'log_show';
		}

		/**
		* Переопределенная ф-ия, позволяющая добавить параметр текущего типа журнала
		" @return string Дополнение в формате SQL к запросу на выборку данных
		*/
		
		public function ext_index_query(){
			if ($_REQUEST["_f_LOG_TYPE_ID"]) 
				return " AND LOG_TYPE_ID=:log_type_id ";
			return parent::ext_index_query();
		}
		
		/**
		* Переопределенная ф-ия, позволяющая добавить параметр текущего типа журнала
		* @return array Массив параметров к запросу SQL на выборку данных
		*/
		public function ext_index_query_binds(){
			if ($_REQUEST["_f_LOG_TYPE_ID"]) 
				return array('log_type_id' => $_REQUEST["_f_LOG_TYPE_ID"]);
			return parent::ext_index_query_binds();
		}
		
		/**
		* Переопределенная ф-ия, позволяющая добавить в список дополнительные поля из $extended_fields
		* @param string $mode Режим списка записей. См. {@link table::get_index_modifiers()}
		* @return array Дополнительные поля
		*/
		
		public function ext_index_header($mode){
			return $this->extended_fields;
		}
		
		/**
		* Ф-ия удаления из интерфейса старых записей
		*/
		
		public function action_drop_old_records() {
			if (self::check_is_erasable($_REQUEST['_f_LOG_TYPE_ID'])) {
				$this->full_object->exec_drop_old_records($_REQUEST['_f_LOG_TYPE_ID']);
			}
			$this->url->redirect();
		}
		
		
		/**
		* Подставляем необходимые операции для каждой записи
		* @param array $record Запись
		* @return array список операций
		*/
		
		public function get_index_ops($record) {
			$ops=parent::get_index_ops($record);
			$pk=$this->primary_key->get_from_record($record);
			$ops['_ops'][]=array("name"=>"view", "alt"=>metadata::$lang["lang_view"], "url"=>$this->url->get_url("view" ,array("pk"=>$pk)));
			return $ops;
		}
		
		
		/**
		* Динамическая проверка, можно ли удалять записи в текущем журнале по старости
		* @param int log_type_id Тип журнала
		* @return boolean
		*/
		
		private static function check_is_erasable($log_type_id) {
			if (!$log_type_id || !log::$log_types_by_id[$log_type_id]) 
				return false;
				
			return (bool)log::$log_types_by_id[$log_type_id]['IS_ERASABLE'];
		}
		
		/**
		* Ф-ия удаления старых записей в объектном контекте
		* @param int log_type_id Тип журнала
		*/
		
		public function exec_drop_old_records($log_type) {
			self::drop_old_records($log_type, params::$params['drop_log_interval']['value']);
		}
		
		/**
		* Ф-ия удаления старых записей в статическом контекте
		*
		* @param $log_type_id - ID журнала
		* @param $interval - интервал удаления, задается в днях
		*/
		public static function drop_old_records ($log_type_id, $interval) {
			// кол-во элементов для IN в SQL
			
			if (self::check_is_erasable($log_type_id)) {
				
				$record_ids=db::sql_select ('
				
					SELECT 
						LR.LOG_RECORD_ID 
					FROM 
						LOG_RECORD LR 
							INNER JOIN
						LOG_OPERATION LO
							ON (LR.LOG_OPERATION_ID = LO.LOG_OPERATION_ID)
					WHERE 
						LO.LOG_TYPE_ID=:log_type_id
							AND
						LR.IS_ERASABLE=1
							AND
						LR.OPERATION_DATE <= :operation_date
				',
					array (
						'log_type_id'=>$log_type_id, 
						'operation_date'=>strftime(log::$strftime_format, strtotime('-'.$interval.' day'))
					)
				);
				
				for ($i=0, $cnt=sizeof($record_ids); $i<$cnt; $i++) {
					$bind=array('log_record_id'=>$record_ids[$i]['LOG_RECORD_ID']);
					db::sql_query('DELETE FROM LOG_EXTENDED_INFO WHERE LOG_RECORD_ID=:log_record_id', $bind);
					db::sql_query('DELETE FROM LOG_RECORD WHERE LOG_RECORD_ID=:log_record_id', $bind);
				}
			}
		}
		
		/**
		 * Ф-ия удаления старых записей из всех журналов в статическом контекте
		 */
		public static function drop_all_old_records()
		{
			$log_types = db::sql_select( 'select LOG_TYPE_ID from LOG_TYPE where IS_ERASABLE = 1' );
			
			foreach ( $log_types as $log_type )
				self::drop_old_records( $log_type['LOG_TYPE_ID'], params::$params['drop_log_interval']['value'] );
		}
		
		/**
		* Переопределенная ф-ия для заполнения дополнительных полей
		* @param array &$request		Ссылка на $_REQUEST или его эмуляцию
		* @param string $mode			Режим списка записей. См. get_index_modifiers()
		* @param string $list_mode		Модификация выборки данных. Используется не в этом базовом методе, а в его расширителях
	 	* @param int $include			Только для дерева. Идентификатор родителя, с которого нужно начининать строить дерево
	 	* @param array $exclude		Только для дерева. Массив идентификаторов записей, которые (и их дети) не должны попасть в дерево
	 	* @return array
		*
		*/

		public function get_index_records(&$request, $mode, $list_mode, $include=0, $exclude=array()) {
			$ret=parent::get_index_records($request, $mode, $list_mode, $include, $exclude);
			
			for ($i=0, $n=sizeof($ret); $i<$n; $i++ ) {
				$ret[$i]=$this->prepare_log_record($ret[$i]);
			}
			
			return $ret;
		}
		
		/**
		 * Подготовка списка операций над записями
		 * 
		 * @return array
		 */
		public function get_index_operations()
		{
			$operations = array();
			
			if (self::check_is_erasable($_REQUEST['_f_LOG_TYPE_ID']))
				$operations["drop_old_records"]=array("name"=>"drop_old_records", "alt"=>metadata::$lang["lang_drop_old_records"], "url"=>$this->url->get_url("drop_old_records") );
			
			return $operations;
		}
		
		/**
		* Готовит запись
		* @param array $rec Запись журнала
		* @return Сформированная запись
		*/
		
		protected function prepare_log_record($rec) {
			$rec=$this->apply_denormalized($rec);
			return $rec;
		}
		
		/**
		* Применяет к записи денормализованную инфу
		* @param array $rec	Запись
		* @return Запись с примененныой инфой
		*/
		
		protected function apply_denormalized($rec) {
			static $lang_obj, $user_obj;
			
			$denormalized=log::get_complex_field($rec['DENORMALIZED_INFO']);
			
			$rec['TE_OBJECT_ID']=object_name::$te_object_names[$rec['TE_OBJECT_ID']] ?
									object_name::$te_object_names[$rec['TE_OBJECT_ID']]:
									$denormalized['te_object']['TITLE'];
			
			if (isset($rec['_LANG_ID'])) {
				if (!$lang_obj)
					$lang_obj = object::factory('LANG');
				$lang_name = $lang_obj -> get_record_title(array('LANG_ID'=>$rec['_LANG_ID']));
				$rec['LANG_ID'] = $lang_name?$lang_name:$denormalized['lang']['TITLE'];
			}

			if (isset($rec['_AUTH_USER_ID'])) {
				if (!$user_obj) 
					$user_obj = object::factory('AUTH_USER');
				
				$user_info = $user_obj->get_record_title(array('AUTH_USER_ID'=>$rec['_AUTH_USER_ID']));
				
				$rec['AUTH_USER_ID']=$user_info?$user_info:$denormalized['user']['SURNAME'];
			}
			
			return $rec;
		}
		
		/**
		* Функция готовит запись для просмотра
		* @param array $rec	Запись журнала
		* @return array
		*/
		
		protected function prepare_view_log_record($rec) {
			// вставка для view, нужна для правильного отображения инфы
			if (sizeof($rec))
			  foreach ($rec as $key=>$val) {
				if (($key[0]!='_') && (!$rec['_'.$key])) 
					$rec['_'.$key]=$rec[$key];
			  }
			return $this->prepare_log_record($rec);
		}
		
		/**
		* Переписывает метод получения данных
		* Параметры см parent::get_form_fields
		*/
		
		public function get_form_fields($mode, $field_prefix, $record="", $record_prefix="", $fields="", $escape=true){
			$record=$this->prepare_view_log_record($record);
			$fields=parent::get_form_fields($mode, $field_prefix, $record, $record_prefix, $this->fields+$this->extended_fields, $escape);

			if ($mode != 'filter') 
				// удаляем пустые значения
				foreach ($fields as $k=>$v) 
					if (!isset($v['value']) || in_array($k, $this->common_fields_not_visible)) 
						unset ($fields[$k]);
				
			return $fields;
		}
		
		/**
		* Переопределенная ф-ия для того, чтобы выбросить поле LOG_INFO из результирующего набора 
		* полей для пользователя
		* @param string $mode Режим списка записей. См. {@link table::get_index_modifiers()}
		* @return array
		*/
		public function get_index_header($mode){
			$ret = parent::get_index_header($mode);
			foreach (array_merge($this->fields_absent_in_list, $this->common_fields_not_visible) as $key) 
				unset ($ret[$key]);
			
			return $ret;
		}	
	}
?>
