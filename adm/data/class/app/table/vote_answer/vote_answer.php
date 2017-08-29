<?php
	/**
	 * Класс для реализации нестандартного поведения таблицы "Варианты ответов" модуля "Опросы"
	 *
	 * @package    RBC_Contents_5_0
	 * @subpackage app
	 * @copyright  Copyright (c) 2007 RBC SOFT
	 */
	class vote_answer extends table_rights_inheritance
	{
		/**
		* Удаление из полей для экспорта VOTE_ID, потому что данные этой таблицы 
		* вгенеряются в общий тег модуля Опросы и данная информация не имеет значения
		*/
		public function get_fields_for_export () {
			$fields=$this->call_parent('get_fields_for_export');
			unset($fields['VOTE_ID']);
			return $fields;
		}
	}
?>