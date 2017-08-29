<?php
/**
 * Mnogosearch: тест на переполнение БД
 * 
 * @package		RBC_Contents_5_0
 * @subpackage module 
 * @copyright	Copyright (c) 2007 RBC SOFT 
 */
class check_search_db_space extends autotest_test
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
		
		// Число попыток записи в таблицу dict
		$maxwords = 10;
		
		$uids = db::sql_select( 'select max( url_id ) as MAXUID from dict' ); $uid = (integer) $uids[0]['MAXUID'];
		
		// Пытаемся добавить несколько записей в таблицу dict
		for ( $i = 0; $i < $maxwords; $i++ )
		{
			$result = db::sql_query( 'insert into dict ( url_id, word ) values ( :uid, :wrd )',
				array( 'uid' => $uid, 'wrd' => $this -> i_to_w( $i ) ) );
			
			if ( !$result )
			{
				$this -> report[] = array( 'descr' => metadata::$lang['lang_autotest_check_search_db_space_overflow_db_insert'] );
				$maxwords = $i;
				break;
			}
		}
		
		// Пытаемся прочитать добавленные записи из таблицы dict
		for ( $i = 0; $i < $maxwords; $i++ )
		{
			$result = db::sql_query( 'select * from dict where url_id = :uid and word = :wrd',
				array( 'uid' => $uid, 'wrd' => $this -> i_to_w( $i ) ) );
			
			if ( empty( $result ) )
			{
				$this -> report[] = array( 'descr' => metadata::$lang['lang_autotest_check_search_db_space_overflow_db_select'] );
				break;
			}
		}

		// Удаляем добавленные записи из таблицы dict
		for ( $i = 0; $i < $maxwords; $i++ )
		{
			$result = db::sql_query( 'delete from dict where url_id = :uid and word = :wrd',
				array( 'uid' => $uid, 'wrd' => $this -> i_to_w( $i ) ) );
		}
	}
	
	/**
	 * Вспомагательный метод для создания тестового контента
	 */
	private function i_to_w( $i )
	{
		return substr( md5( $i ), 0, 20 );
	}
}
?>