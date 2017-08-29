<?PHP
/**
 * Класс для реализации нестандартного поведения таблицы языков
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @author Alexandr Vladykin <avladykin@rbc.ru>
 * @copyright  Copyright (c) 2006 RBC SOFT
 */

	
	class lang extends table_translate {
		/**
	 	* Ограничиваем список для select
	 	*/
		
		public function ext_index_by_list_mode($mode, $list_mode){
			list($where, $binds)=$this->call_parent('ext_index_by_list_mode',array($mode, $list_mode));
			if($list_mode["by_in_admin"]){
				$where.=" AND IN_ADMIN=:in_admin";
				$binds=array_merge($binds, array("in_admin"=>$list_mode["by_in_admin"]));
			}
			if($list_mode["by_in_content"]){
				$where.=" AND IN_CONTENT=:in_content";
				$binds=array_merge($binds, array("in_content"=>$list_mode["by_in_content"]));
			}
			return array($where, $binds);
		}
		
		
		/**
		* Дополнительно к стандартной процедуре проверяем можем ли снять с языка IN_ADMIN 
		* (он не должен использоваться тогда никем)
		* @see table::exec_change
		*/
		
		public function exec_change($raw_fields, $prefix, $pk){

			if (!$raw_fields[$prefix.'IN_ADMIN'] && $this->is_used_in_admin($raw_fields[$prefix.'ROOT_DIR'])) 
				throw new Exception(metadata::$lang['lang_lang_can_not_uncheck_in_admin_language_used']);

			$this->call_parent('exec_change', array($raw_fields, $prefix, $pk));
		}
		
		/**
		* Дополнительно к стандартной процедуре проверяем используется ли кем-нить язык, который пытаемся удалить
		* @see table::exec_change
		*/
		
		public function exec_delete($pk, $partial=false){
			$lang_data=$this->get_change_record($pk);
			if ($this->is_used_in_admin($lang_data['ROOT_DIR'])) 
				throw new Exception(metadata::$lang['lang_lang_can_not_uncheck_in_admin_language_used']);
			$this->call_parent('exec_delete', array($pk, $partial));
		}
		
		/**
		* Проверяет, используется ли на данный момент данный язык каким-либо пользователем в админской части
		* @param $lang_id ID языка
		* @return boolean
		*/
		
		private function is_used_in_admin ($lang_dir) {
			if (
				(params::$params['default_interface_lang']['value'] == $lang_dir) 
					|| 
				(params::$params['default_interface_lang']['default_value'] == $lang_dir) 
			)
				return true;
			
			
			$res=db::sql_select(
				'SELECT * FROM SYSTEM_AUTH_USER_PARAMS WHERE SYSTEM_NAME=:lang AND VALUE=:lang_dir', 
					array(
						'lang'=>'default_interface_lang', 
						'lang_dir'=>$lang_dir
					)
			);
			
			if (sizeof($res)) return true;
		}
		
		
		/**
		* Дополнительно проверяем, есть ли необходимые директория на диске и файл метаданных для языка, который пользователь
		* хочет использовать в админке
		* @see table::get_prepared_fields
		*/
		
		public function get_prepared_fields($raw_fields, $prefix, $mode){
			if ($raw_fields[$prefix.'IN_ADMIN']) {
				if (!file_exists(params::$params["adm_data_server"]["value"]."lang/".$raw_fields[$prefix.'ROOT_DIR'])) 
					throw new Exception(metadata::$lang['lang_lang_can_not_do_lang_in_admin_without_lang_dir']);
				
				if (!file_exists(params::$params["adm_data_server"]["value"]."prebuild/metadata_".$raw_fields[$prefix.'ROOT_DIR'].".php"))
					throw new Exception(metadata::$lang['lang_lang_can_not_do_lang_in_admin_without_metadata_file']);
			}
			return $this->call_parent('get_prepared_fields', array($raw_fields, $prefix, $mode));
		}
			
		
	}
?>