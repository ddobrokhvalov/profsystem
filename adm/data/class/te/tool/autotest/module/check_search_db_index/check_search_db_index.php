<?php
/**
 * Mnogosearch: тест на периодическую индексацию
 * 
 * @package		RBC_Contents_5_0
 * @subpackage module
 * @copyright	Copyright (c) 2007 RBC SOFT 
 */
class check_search_db_index extends autotest_test
{
	/**
	 * Тест
	 */
	public function do_test()
	{
		if ( !$this -> is_module_exists( 'MNOGOSEARCH' ) )
		{
			$this -> report[] = array( 'descr' => metadata::$lang['lang_autotest_no_mnogosearch'] ); return false;
		}
		
		// Поиск устаревших ссылок
		$old_urls = db::sql_select( 'select count( next_index_time ) as OLD_URL_COUNT from url where next_index_time - ' . time() . ' < 0' );
		
		if ( $old_urls[0]['OLD_URL_COUNT'] )
			$this -> report[] = array( 'descr' => metadata::$lang['lang_autotest_check_search_db_index_old_urls'] . ': ' . $old_urls[0]['OLD_URL_COUNT'] );
	}
}
?>