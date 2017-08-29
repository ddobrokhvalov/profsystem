<?PHP
/**
* Класс журналов
*
* @package		RBC_Contents_5_0
* @subpackage core
* @copyright	Copyright (c) 2007 RBC SOFT
* @author Alexandr Vladykin 
*
*/
	class log {
		/**
		* @var string $strftime_format Формат хранения даты в БД
		*/
		public static $strftime_format = "%Y%m%d%H%M%S";
		
		/**
		* @var array $log_types Записываются все возможные типы журналов в хеш с ключом SYSTEM_NAME
		*/
		
		public static $log_types=array();

		/**
		* @var array $log_types_by_id Записываются все возможные типы журналов в хеш с ключом SYSTEM_NAME
		*/
		
		public static $log_types_by_id=array();
		
		/**
		* @var array $log_operations Записываются все возможные операции
		*/
		
		public static $log_operations=array();
		
		/*
		* ф-ия инициализации класса, вызывается при загруке файла класса
		*/
		
		public static function __init () {
			$res=db::replace_field(db::sql_select("
				SELECT LOG_TYPE.*, TT.VALUE as \"_TITLE\"
				FROM LOG_TYPE
					LEFT JOIN LANG ON
						LANG.ROOT_DIR = :root_dir
					LEFT JOIN TE_OBJECT ON
						TE_OBJECT.SYSTEM_NAME = 'LOG_TYPE'
					LEFT JOIN TABLE_TRANSLATE TT ON
						TT.TE_OBJECT_ID=TE_OBJECT.TE_OBJECT_ID AND
						TT.LANG_ID=LANG.LANG_ID AND
						TT.CONTENT_ID=LOG_TYPE.LOG_TYPE_ID AND
						TT.FIELD_NAME='TITLE'",
				array( 'root_dir' => params::$params['default_interface_lang']['value'] ) ), 'TITLE', '_TITLE');
			
			self::$log_types = lib::array_reindex($res, 'SYSTEM_NAME');
			self::$log_types_by_id = lib::array_reindex($res, 'LOG_TYPE_ID');
			
			$res=db::replace_field(db::sql_select("
				SELECT LOG_OPERATION.*, TT.VALUE as \"_TITLE\"
				FROM LOG_OPERATION
					LEFT JOIN LANG ON
						LANG.ROOT_DIR = :root_dir
					LEFT JOIN TE_OBJECT ON
						TE_OBJECT.SYSTEM_NAME = 'LOG_OPERATION'
					LEFT JOIN TABLE_TRANSLATE TT ON
						TT.TE_OBJECT_ID=TE_OBJECT.TE_OBJECT_ID AND
						TT.LANG_ID=LANG.LANG_ID AND
						TT.CONTENT_ID=LOG_OPERATION.LOG_OPERATION_ID AND
						TT.FIELD_NAME='TITLE'",
				array( 'root_dir' => params::$params['default_interface_lang']['value'] ) ), 'TITLE', '_TITLE');
			
			self::$log_operations=lib::array_reindex($res, 'LOG_TYPE_ID', 'SYSTEM_NAME');
		}

		/**
		* Проверяет, включен ли журнал $log_type
		* @param string $log_type Тип журнала
		* @param boolean $exception Тип журнала
		* @TODO Добавить тип операции
		*/
		
		public static function is_enabled ($log_type) {
			return self::$log_types[$log_type]['IS_ENABLED'];
		}
		
		/**
		* регистрирует запись в журнале
		* @param string $log_type Тип журнала
		* @param string $operation_name Название операции
		* @param string $log_info Дополнительная информация об операции
		* @param int $te_object_id ID TE_OBJECT
		* @param int $object_id ID OBJECT
		* @param int $lang_id ID LANG языка
		* @param int $version Версия
		* @param int $extended_info Дополнительная информация
		*
		* @return int Уникальный ключ LOG_RECORD_ID вставленной записи
		*/
		public static function register ($log_type, $operation_name, $log_info="", $te_object_id=0, $object_id=0, $lang_id=0, $version="", $extended_info="") {
			if (!$operation = self::get_operation_info($log_type, $operation_name, $te_object_id, $object_id, $lang_id))  
				return false;
			
			if (is_array($log_info))
				$log_info=self::prepare_complex_field($log_info);
			
			$insert_array = array (
				'LOG_OPERATION_ID' => $operation['operation']['LOG_OPERATION_ID'],
				'AUTH_USER_ID' => $_SESSION['AUTH_USER_ID']?$_SESSION['AUTH_USER_ID']:0,
				'TE_OBJECT_ID' => $te_object_id,
				'LANG_ID' => $lang_id,
				'IP' => $_SERVER['REMOTE_ADDR']?$_SERVER['REMOTE_ADDR']:'127.0.0.1',
				'OPERATION_DATE' => strftime(self::$strftime_format),
				'OBJECT_ID' => $object_id,
				'VERSION' => $version,
				'LOG_INFO' => $log_info,
				'IS_ERASABLE' => $operation['log_type']['IS_ERASABLE'],
				'DENORMALIZED_INFO' => self::prepare_complex_field($operation)
			);
			
			db::insert_record ('LOG_RECORD', $insert_array);
			
			$log_record_id=db::last_insert_id('LOG_RECORD_SEQ');
			
			if ($extended_info) {
				if (is_array($extended_info)) 
					$extended_info = self::prepare_complex_field($extended_info);
					
				db::insert_record (
					'LOG_EXTENDED_INFO', 
						array(
							'LOG_RECORD_ID' => $log_record_id,
							'EXTENDED_INFO' => $extended_info
						)
				);
			}
			return $log_record_id;
		}
		
		/**
		* проверяет переданные значения в лог, и получает необходимые данные
		* @param string $log_name тип журнала
		* @param string $operation_name название операции
		* @return array Массив для вставки в DENORMALIZED_INFO с ключами log_type, operation, te_object, lang, user
		*/
		
		private static function get_operation_info ($log_name, $operation_name, $te_object_id, $object_id, $lang_id) {
			if (!self::$log_types[$log_name])
				throw new Exception('LOG: '.metadata::$lang['lang_can_not_find_log_type']."'$log_name'");
			else 
				$log_type = array_intersect_key(self::$log_types[$log_name], array('LOG_TYPE_ID'=>1, 'TITLE'=>1, "SYSTEM_NAME"=>1, 'IS_ERASABLE'=>1));

			if (!self::is_enabled($log_name)) 
				return false;

			if (!self::$log_operations[$log_type['LOG_TYPE_ID']][$operation_name])
				throw new Exception(metadata::$lang['lang_can_not_find_operation_name']." '$operation_name' ".metadata::$lang['lang_for_log_type']." '".$log_type['TITLE']."'");
			else 
				$operation = array_intersect_key(
					self::$log_operations[$log_type['LOG_TYPE_ID']][$operation_name], 
					array('LOG_OPERATION_ID'=>1, 'LOG_TYPE_ID'=>1, 'TITLE'=>1, 'SYSTEM_NAME'=>1)
				);

			$te_object=FALSE;
			if ($te_object_id) {
				$te_object=object_name::$te_object_names[$te_object_id];
				if (!$te_object) 
					throw new Exception('LOG: TE_OBJECT_ID='.$te_object_id.' '.metadata::$lang['lang_is_not_found_in_the_system']);
			}

			$lang=FALSE;
			if ($lang_id) {
				$lang=db::replace_field(db::sql_select('
					SELECT LANG.*, TT.VALUE as "_TITLE"
					FROM LANG
						LEFT JOIN TE_OBJECT ON
							TE_OBJECT.SYSTEM_NAME = \'LANG\'
						LEFT JOIN TABLE_TRANSLATE TT ON
							TT.TE_OBJECT_ID=TE_OBJECT.TE_OBJECT_ID AND
							TT.LANG_ID=LANG.LANG_ID AND
							TT.CONTENT_ID=LANG.LANG_ID AND
							TT.FIELD_NAME=\'TITLE\'
					WHERE LANG.LANG_ID=:lang', array('lang'=>$lang_id)), 'TITLE', '_TITLE');
				
				if (!$lang) 
					throw new Exception('LOG: LANG_ID='.$lang_id.' '.metadata::$lang['lang_is_not_found_in_the_system']);
			}

			$user=FALSE;
			if ($_SESSION['AUTH_USER_ID']) {
				$user=array_intersect_key(auth::singleton()->user_info, array('AUTH_USER_ID'=>1, 'SURNAME'=>1, 'LOGIN'=>1));
				if (!$user) 
					throw new Exception('LOG: AUTH_USER_ID='.$_SESSION['AUTH_USER_ID'].' '.metadata::$lang['lang_is_not_found_in_the_system']);
			}

			return array('log_type'=>$log_type, 'operation'=>$operation, 'te_object'=>$te_object, 'lang'=>$lang[0], 'user'=>$user);
		}
		
		
		/**
		* Добавляет данные в EXTENDED_INFO для записи log_record_id
		* @param int $log_record_id ID записи
		* @param string $data данные, добавляемые в конец.
		*/
		public static function add_data_to_extended_info($log_record_id, $data) {
			$ext_info = db::sql_select('SELECT EXTENDED_INFO FROM LOG_EXTENDED_INFO WHERE LOG_RECORD_ID=:log_record_id', array('log_record_id'=>$log_record_id));
			$ext_info=$ext_info[0]['EXTENDED_INFO'];
			$ext_info.=$data;
			db::sql_query('UPDATE LOG_EXTENDED_INFO SET EXTENDED_INFO=:ext_info WHERE LOG_RECORD_ID=:log_record_id', array('ext_info'=>$ext_info, 'log_record_id'=>$log_record_id));
		}

		/**
		* Обновляет любые поля
		* @param int $log_record_id ID записи
		* @param string $data данные.
		*/
		public static function change_data ($log_record_id, $data) {
			db::update_record('LOG_RECORD', $data, null, array('LOG_RECORD_ID'=>$log_record_id));
		}
		
		private static function set_complex_field ($field, $key, $value) {
			
		}
		
		/**
		* Преобразует комплексное поле к формату, пригодному для хранения в БД
		* @param $arr массив
		* @return string
		*/
		
		public static function prepare_complex_field ($arr) {
			return serialize($arr);
		}

		/**
		* Преобразует комплексное поле в формате БД к изначальному формату
		* @param string $val Данные из комплексного поля в БД
		* @return array
		*/		
		public static function get_complex_field($val) {
			return unserialize($val);
		}
		
	}
	
	log::__init();
	
?>