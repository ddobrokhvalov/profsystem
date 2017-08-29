<?PHP
/**
 * Класс для реализации нестандартного поведения таблицы "Варианты ответов" модуля "Форма обратной связи"
 *
 * @package    RBC_Contents_5_0
 * @subpackage app
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
 	
	class form_options extends table_rights_inheritance {
		
		/**
		* Удаление из полей для экспорта FORM_QUESTION_ID, потому что данные этой таблицы 
		* вгенеряются в общий тег модуля Форма обратной связи и данная информация не имеет значения
		*/
		
		public function get_fields_for_export() {
			$fields=$this->call_parent('get_fields_for_export');
			unset($fields['FORM_QUESTION_ID']);
			return $fields;
		}
	}
?>