<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Справочник системных слов"
 *
 * @package    RBC_Contents_5_0
 * @subpackage cms
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class system_word extends table_lang {
	/**
	 * В отличие от базового метода после вставки происходит пребилд системных слов
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		if ( !preg_match( '/^sysw_/i', $raw_fields[$prefix.'SYSTEM_NAME'] ) )
			$raw_fields[$prefix.'SYSTEM_NAME'] = 'sysw_' . $raw_fields[$prefix.'SYSTEM_NAME'];
		
		$last_id = $this -> call_parent('exec_add', array($raw_fields, $prefix));
		
		$this -> full_object -> exec_prebuild();
		
		return $last_id;
	}
	
	/**
	 * В отличие от базового метода после обновления происходит пребилд системных слов
	 *
	 * @see table::exec_change()
	 */
	public function exec_change( $raw_fields, $prefix, $pk )
	{
		$this -> call_parent('exec_change', array ($raw_fields, $prefix, $pk));
		$this -> full_object -> exec_prebuild();
	}
	
	/**
	 * В отличие от базового метода после удаления происходит пребилд системных слов
	 *
	 * @see table::exec_delete()
	 */
	public function exec_delete( $pk )
	{
		$this -> call_parent('exec_delete', array($pk));
		$this -> full_object -> exec_prebuild();
	}
	
	/**
	 * В отличие от базового метода после перевода происходит пребилд системных слов
	 * 
	 * @see table_lang::exec_translate()
	 */
	public function exec_translate( $pk, $lang_id )
	{
		parent::exec_translate( $pk, $lang_id );
		$this -> full_object -> exec_prebuild();
	}
	
	/**
	 * Метод предилда системных слов. Для каждого языка создается файлик
	 * с ассоциативным массивом системных слов ( например, syswords_ru.php )
	 * 
	 * @see table_lang::exec_translate()
	 */
	public function exec_prebuild(){
		// Очистка кеша компилированных шаблонов
		$tpl=new smarty_ee();
		$tpl->clear_compiled_tpl();
		// Проверка существования директории
		$syswords_root_dir = params::$params['common_data_server']['value'].'prebuild/';
		if ( !file_exists( $syswords_root_dir ) ){
			throw new Exception($this->te_object_name.": ". metadata::$lang['lang_catalog_not_exists'].': "'.$syswords_root_dir.'"' );
		}
		// Выборка данных и раскладка их по языкам
		$langs = db::sql_select("select * from LANG");
		$system_words = db::sql_select("select LANG_ID, SYSTEM_NAME, VALUE from SYSTEM_WORD");
		foreach ( $system_words as $word ){
			$system_values[$word['LANG_ID']][$word['SYSTEM_NAME']] = $word['VALUE'];
		}
		// Укладка системных слов в файлы
		foreach ( $langs as $lang ){
			$words_by_lang=($system_values[$lang['LANG_ID']] ? $system_values[$lang['LANG_ID']] : array());
			$output = "<?php\n\$module_syswords_{$lang['ROOT_DIR']} = ".var_export($words_by_lang, true).";\n?>";

			$file_name = $syswords_root_dir."module_syswords_{$lang['ROOT_DIR']}.php";
			if ( !( @file_put_contents( $file_name, $output ) ) ){
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_can_not_write_file'].': "'.$file_name.'"' );
			}
		}
	}
	
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
			$lang_obj = object::factory( 'LANG' );
			list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
				$lang_obj -> ext_field_selection( 'TITLE', 1 );
			$lang_obj -> __destruct();
			
			$system_words = db::sql_select("
				SELECT SYSTEM_WORD.SYSTEM_WORD_ID, SYSTEM_WORD.LANG_ID, SYSTEM_WORD.VALUE, " . $dec_field . " AS TITLE
				FROM SYSTEM_WORD, LANG " . $dec_join[0] . "
				WHERE SYSTEM_WORD.SYSTEM_WORD_ID IN ({$this->index_records_in})
					AND SYSTEM_WORD.LANG_ID = LANG.LANG_ID
				ORDER BY " . $dec_field, $dec_binds );
			
			foreach ( $system_words as $word )
				$values[$word[$this->autoinc_name]][$word['LANG_ID']] = array( 'title' => $word['TITLE'].': '.$word['VALUE'] );
			
			foreach( $records as $id => $record ) {
				$records[$id]['lang_values'] = $values[$records[$id][$this->autoinc_name]];
				unset( $records[$id]['lang_names'] );
			}
		}
		return $records;
	}
}
?>
