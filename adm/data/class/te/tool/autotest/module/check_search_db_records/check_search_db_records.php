<?php
/**
 * Mnogosearch: тест состояния БД
 * 
 * @package		RBC_Contents_5_0
 * @subpackage module
 * @copyright	Copyright (c) 2007 RBC SOFT 
 */
class check_search_db_records extends autotest_test
{
	/**
	 * Максимально допустимое количество запсией с таблице dict
	 */
	const MAXWORDS = 10000000;
	
	/**
	 * Максимально допустимое количество запсией с таблице url
	 */
	const MAXURLS = 50000;
	
	/**
	 * Тест
	 */
	public function do_test()
	{
		if ( !$this -> is_module_exists( 'MNOGOSEARCH' ) )
		{
			$this -> report[] = array( 'descr' => metadata::$lang['lang_autotest_no_mnogosearch'] ); return false;
		}
		
		// Проверка таблицы bdict
		$dict_words = db::sql_select( 'select count(*) as WORDS from dict' );
		$bdict_words = db::sql_select( 'select count(*) as WORDS from bdict' );
		
		if ( $dict_words[0]['WORDS'] == 0 && $bdict_words[0]['WORDS'] == 0 )
			$this -> report[] = array( 'descr' => metadata::$lang['lang_autotest_check_search_db_index_empty_dict'] );
		elseif ( ( $dict_words[0]['WORDS'] + $bdict_words[0]['WORDS'] ) > self::MAXWORDS )
			$this -> report[] = array( 'descr' => metadata::$lang['lang_autotest_check_search_db_index_overflow_dict'] . ': ' . ( $dict_words[0]['WORDS'] + $bdict_words[0]['WORDS'] ) );
		
		// Проверка таблицы url
		$urls = db::sql_select( 'select count(*) as URLS from url' );
		
		if ( $urls[0]['URLS'] == 0 )
			$this -> report[] = array( 'descr' => metadata::$lang['lang_autotest_check_search_db_index_empty_url'] );
		elseif ( $urls[0]['URLS'] > self::MAXURLS )
			$this -> report[] = array( 'descr' => metadata::$lang['lang_autotest_check_search_db_index_overflow_url'] . ': ' . $urls[0]['URLS'] );
	}
}
?>