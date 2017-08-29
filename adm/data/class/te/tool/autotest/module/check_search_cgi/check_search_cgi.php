<?php
/**
 * Mnogosearch: тест на выполнение поиска
 * 
 * @package		RBC_Contents_5_0
 * @subpackage module
 * @copyright	Copyright (c) 2007 RBC SOFT 
 */
class check_search_cgi extends autotest_test
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
		
		$sites = db::sql_select( 'select * from SITE' );
		
		foreach ( $sites as $site )
		{
			$q_string = 's_result_area_id_1=1&ul=' . urlencode( 'http://' . $site['HOST'] . '/' );
			$cgi_http = params::$params['common_cgi_http']['value'] . '/mnogosearch/runsearch.cgi';
			
			$result = @file_get_contents( $cgi_http . '?' . $q_string );
			
			if ( $result == '' || strstr( $result, 'Unable to open template' ) )
			{
				$this -> report[] = array( 'descr' => metadata::$lang['lang_autotest_check_search_cgi_error_request'] . ': ' . $cgi_http .
					( $result != '' ? ( '<br/>'. metadata::$lang['lang_autotest_check_search_cgi_may_be'] . ': ' . $result ) : '' ) );
			}
		}
	}
}
?>