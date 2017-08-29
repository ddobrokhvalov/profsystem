<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Содержимое анкет"
 *
 * @package    RBC_Contents_5_0
 * @subpackage app
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class form_values extends table {
	/**
	 * Добавляем колонку "Значение" и удаляем колонку "Язык"
	 *
	 * @see table::ext_index_header()
	 */
	public function ext_index_header($mode)
	{
		return array( "VALUES" => array( "title" => metadata::$lang['lang_form_values_answers'], "type" => "_list" ) );
	}
	
	/**
	 * Вырезаем колонку "Варианты ответов"
	 *
	 * @see table::get_index_header()
	 */
	public function get_index_header($mode)
	{
		$headers = parent::get_index_header( $mode );
		unset( $headers['FORM_OPTIONS_ID'] );
		unset( $headers['ANSWER_VALUE'] );
		return $headers;
	}
	
	/**
	 * Для списковых типов полей заполняем колонку "Ответ" значениями этих полей,
	 *
	 * @see table::get_index_records()
	 */
	public function get_index_records( &$request, $mode, $list_mode, $include = 0, $exclude = array() )
	{
		$fa_obj = object::factory( 'FORM_ANSWER' );
		$fa_obj -> primary_key -> is_record_exists( array( 'FORM_ANSWER_ID' => $request['_f_FORM_ANSWER_ID'] ), true );
		$fa_obj -> is_permitted_to( 'change', array( 'FORM_ANSWER_ID' => $request['_f_FORM_ANSWER_ID'] ), true );
		$fa_obj -> __destruct();
		
		$records = parent::get_index_records( $request, $mode, $list_mode, $include, $exclude );
		
		foreach( $records as & $record )
			if ( !$record['ANSWER_VALUE'] && $record['FORM_OPTIONS_ID'] )
				$record['ANSWER_VALUE'] = $record['FORM_OPTIONS_ID'];
		
		$group_records = array();
		foreach( $records as & $record )
			$group_records[ $record['_FORM_QUESTION_ID'] ][] = $record;
		
		$return_records = array();
		foreach( $group_records as & $group_record )
		{
			$values = array();
			foreach( $group_record as $record )
				if ( strval( $record['ANSWER_VALUE'] ) != '' )
					$values[] = array( 'title' => $record['ANSWER_VALUE'] );
			$record['VALUES'] = $values;
			$return_records[] = $record;
		}
		
		return $return_records;
	}
	
	/**
	 * Право на вход в раздел дается всем. Право на просмотр ответов конкретной анкеты проверяется в get_index_records()
	 *
	 * @see object::is_permitted_to()
	 */
	public function is_permitted_to( $ep_type, $pk = '', $throw_exception = false )
	{
		return true;
	}
}
?>