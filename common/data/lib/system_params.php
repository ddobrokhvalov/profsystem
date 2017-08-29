<?PHP
/**
 * Класс для работы с параметрами системы
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @author Alexandr Vladykin <avladykin@rbc.ru>
 * @copyright  Copyright (c) 2007 RBC SOFT
 * @todo Наверно стоит придумать какое-нибудь ограничение параметров - от и до, а то сейчас можно выставить 10000 записей на страницу и система будет ложиться по памяти или класть сервер по нагрузке
 */
	class system_params extends lib_abstract {
  		/**
  		 * Присвоение глобальных параметров системы в params::$params в зависимости от значений в БД, 
  		 * которые пользователь может изменять
  		 * вызывать после подключения ф-ий БД
  		 */
  		public static function apply_global_params_from_db() {
  		  $db_params=db::sql_select("
  		        SELECT SYSTEM_GLOBAL_PARAMS_ID AS ID, SYSTEM_NAME, VALUE, 'GLOBAL' AS PARAM_TYPE 
  		        FROM SYSTEM_GLOBAL_PARAMS"
  		    );
  		    
  		  self::apply_params_from_db($db_params);
  		}
  		
  		/**
  		 * Присвоение локальных параметров системы (используемых только для конкретного пользователя
  		 * в админке) в params::$params в зависимости от значений в БД.
  		 * Вызывать после старта сессии
  		 */
  		public static function apply_auth_user_params_from_db() {
  		  if (!$_SESSION['AUTH_USER_ID']) return false;
  		  $db_params=db::sql_select("
  		        SELECT SYSTEM_AUTH_USER_PARAMS_ID AS ID, SYSTEM_NAME, VALUE, 'LOCAL' AS PARAM_TYPE
  		        FROM SYSTEM_AUTH_USER_PARAMS
  		        WHERE AUTH_USER_ID=".$_SESSION['AUTH_USER_ID']
  		  );
  		  self::apply_params_from_db($db_params);
  		}
  		
  		/**
  		 * Присвоение значения параметра
  		 * @param string $system_name  название параметра
  		 * @param string $new_value значение параметра
  		 */ 
  		public static function set_parameter_value($system_name, $new_value) {
  		  params::$params[$system_name]=$new_value;
  		}		
		
		/**
		* устанавливает необходимые константы для объекта, заданные через шаблоны
		* @param object $obj Объект
		* @param array $const_arr Массив названий констант
		*/

		public static function parse_template_param_for_object ($obj, $const_arr) {
			for ($i=0, $n=sizeof($const_arr); $i<$n; $i++)
				if (is_array($obj->$const_arr[$i]))
					$obj->$const_arr[$i] = array_map(array(self, 'parse_template_param'), $obj->$const_arr[$i]);
				else
					$obj->$const_arr[$i] = self::parse_template_param($obj->$const_arr[$i]);
		}
		
		
		public static function parse_template_param ($value) {
			return preg_replace_callback('/{(.+?)}/', array(self, 'callback_get_param_value'), $value);
		}
		
		private function callback_get_param_value($matches) {
			return params::$params[$matches[1]]['value'];
		}
		
		public static function templatize_path ($path) {
			$path_params = array (
				"common_htdocs_server",
				// "common_htdocs_http",
				"common_data_server",
			
				"adm_htdocs_server",
				"adm_htdocs_http",
				"adm_data_server",
			);
		
			foreach ($path_params as $param) {
				if (preg_match('{'.params::$params[$param]['value'].'(.+)}', $path, $m)) {
					return '{'.$param.'}'.$m[1];
				}
			}
			
			return false;			
		}		
		
		
		/**
  		 * Внутренний метод для присвоения значений параметрам из $db_params
  		 */
  		private function apply_params_from_db($db_params) {
  		  foreach ($db_params as $db_param) {
  		    if (
  		          in_array($db_param['SYSTEM_NAME'],array_keys(params::$params)) 
  		            && in_array($db_param['PARAM_TYPE'], explode('+', params::$params[$db_param['SYSTEM_NAME']]['param_type']))
  		        )  {
  		          params::$params[$db_param['SYSTEM_NAME']]['default_value']=params::$params[$db_param['SYSTEM_NAME']]['value'];
  		          params::$params[$db_param['SYSTEM_NAME']]['value']=$db_param['VALUE'];
  		          
  		    }
  		  }
  		}   		
	}
?>