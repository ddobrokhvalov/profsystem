<?php
/**
 * Общие места рассылки
 *
 * @package    RBC_Contents_5_0
 * @subpackage app
 * @copyright  Copyright (c) 2006 RBC SOFT
 */

include_once(params::$params["common_data_server"]["value"]."module/module/module.php");

class subscribe_common extends module
{
	public function __construct()
	{
		//
	}
	
	public function content_init()
	{
		//
	}
	
	public function __call( $method, $vars )
	{
		return call_user_func_array( array( parent, $method ), $vars);
	}
	
	/**
	 * Отправка письма 
	 */
	public static function post_mail( $to_address = '', $to_name = '', $from_address = '', $from_name = '', $subject = '', $message = '', $to_encoding = '', $attach_files = array() )
	{
		$email = new vlibMimeMail();
		
		$from_encoding = strtolower( params::$params['encoding']['value'] );
		$to_encoding = strtolower( $to_encoding === '' ? params::$params['encoding']['value'] : $to_encoding );
		
		$email -> to( $to_address, $to_name ? '=?'.$to_encoding.'?B?'.base64_encode( iconv( $from_encoding, $to_encoding, $to_name ) ).'?=' : null );
		$email -> from( $from_address, $from_name ? '=?'.$to_encoding.'?B?'.base64_encode( iconv( $from_encoding, $to_encoding, $from_name ) ).'?=' : null );
		$email -> subject( $subject ? '=?'.$to_encoding.'?B?'.base64_encode( iconv( $from_encoding, $to_encoding, $subject ) ).'?=' : null );
		$email -> body( iconv( $from_encoding, $to_encoding, strip_tags( $message ) ), $to_encoding );
		$email -> htmlBody( iconv( $from_encoding, $to_encoding, $message ), $to_encoding );
		
		foreach ( $attach_files as $file_name )
			$email -> attach( $file_name, 'attachment', 'application/octet-stream' );
		
		return $email -> send();
	}
	
	/**
	 * Возвращает подпись к письму
	 * 
	 * @param int $lang_id				Язык
	 * @param int $site_id				Сайт
	 * @param int $subscribe_list_id	Лист рассылки
	 * @return	string
	 */
	public static function get_signature( $lang_id, $site_id = '', $subscribe_list_id = '' )
	{
		if ( $subscribe_list_id )
			$site_list = db::sql_select( 'select SITE.SITE_ID, SITE.HOST from SITE, SUBSCRIBE_LIST_SITE
				where SUBSCRIBE_LIST_SITE.SUBSCRIBE_LIST_ID = :SUBSCRIBE_LIST_ID and
					SUBSCRIBE_LIST_SITE.SITE_ID = SITE.SITE_ID order by SITE.HOST',
				array( 'SUBSCRIBE_LIST_ID' => $subscribe_list_id ) );
		else
			$site_list = db::sql_select( 'select SITE.SITE_ID, SITE.HOST from SITE where SITE_ID = :SITE_ID',
				array( 'SITE_ID' => $site_id ) );
		
		$module = new subscribe_common();
		
		$module -> env['lang_id'] = $lang_id;
		$module -> env['version'] = $module -> env['page_id'] = $module -> env['area_id'] = 0;
		
		foreach ( $site_list as $site_index => $site_item )
		{
			$module -> env['site_id'] = $site_item['SITE_ID'];
			$path_and_area = $module -> __call( 'get_url_by_module_name', array( 'SUBSCRIPTION' ) );
			
			if ( $path_and_area['PATH'] && $path_and_area['PATH'] != 'index.php' )
				$site_list[$site_index]['URL'] = 'http' . ( $_SERVER['HTTPS'] == 'on' ? 's' : '' ) . '://' .
					$site_item['HOST'] . $path_and_area['PATH'] . '?action_' . $path_and_area['AREA'] . '=subscribe_change&email_' .
						$path_and_area['AREA'] . '=<EMAIL>&pwd_' . $path_and_area['AREA'] . '=<PASSWORD>';
			else
				unset( $site_list[$site_index] );
		}
		
		$root_dir = db::sql_select( 'SELECT ROOT_DIR FROM LANG WHERE LANG_ID = :LANG_ID', array( 'LANG_ID' => $lang_id ) );
		
		$module_syswords_var = 'module_syswords_' . $root_dir[0]['ROOT_DIR'];
		include( params::$params["common_data_server"]["value"] . "prebuild/{$module_syswords_var}.php" );
		
		$tpl = new smarty_ee( $$module_syswords_var );
		$tpl -> current_lang = $root_dir[0]['ROOT_DIR'];
		
		$tpl -> assign( 'site_list', $site_list );
		
		return $tpl -> fetch( params::$params['common_data_server']['value'] . 'module_tpl/subscription/signature.tpl' );
	}
}
?>
