<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Системные URL"
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class system_url extends table_lang {
	/**
	 * Добавляем колонку "Значение" и удаляем колонку "Язык"
	 *
	 * @see table::ext_index_header()
	 */
	public function ext_index_header($mode)
	{
		$headers = parent::ext_index_header( $mode ); unset( $headers['lang_names'] );
		return array_merge( $headers, array( "lang_values" => array( "title" => metadata::$lang["lang_Value"], "type" => "_list" ) ) );
	}
	
	/**
	 * Заполняем колонку "Значение" данными о языках и значениях системных слов
	 *
	 * @see table::get_index_records()
	 */
	public function get_index_records( &$request, $mode, $list_mode, $include = 0, $exclude = array() )
	{
		$records = parent::get_index_records( $request, $mode, $list_mode, $include, $exclude );
		
		if( count( $records ) > 0 )
		{
			foreach( $records as $record )
				$ids[] = $record[$this->autoinc_name];
			$in = join( ", ", $ids );
			
			$lang_obj = object::factory( 'LANG' );
			list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
				$lang_obj -> ext_field_selection( 'TITLE', 1 );
			$lang_obj -> __destruct();
			
			$system_words = db::sql_select("
				SELECT SYSTEM_URL.SYSTEM_URL_ID, SYSTEM_URL.LANG_ID,
					SYSTEM_URL.URL, " . $dec_field . " as TITLE
				FROM SYSTEM_URL, LANG " . $dec_join[0] . "
				WHERE SYSTEM_URL.SYSTEM_URL_ID IN ({$in})
					AND SYSTEM_URL.LANG_ID = LANG.LANG_ID
				ORDER BY LANG.TITLE", $dec_binds);
			
			foreach ( $system_words as $word )
				$values[$word[$this->autoinc_name]][$word['LANG_ID']] = array( 'title' => $word['TITLE'].': '.$word['URL'] );
			foreach( $records as $id => $record )
				$records[$id]['lang_values'] = $values[$records[$id][$this->autoinc_name]];
		}
		return $records;
	}
}
?>