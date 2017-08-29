<?php
/**
 * Скрипт-редирект модуля "Рекламный блок"
 * @package		RBC_Contents_5_0
 * @subpackage module
  */
include_once( '../../data/config/params.php' );

$version = intval( $_REQUEST['version'] );
$lang_id = intval( $_REQUEST['lang_id'] );
$content_id = intval( $_REQUEST['content_id'] );

// Для рабочей версии увеличиваем счетчик переходов
if ( $version == 0 )
	db::sql_query( 'update PROMO_BLOCK set NUM_CLICKS = NUM_CLICKS + 1 where PROMO_BLOCK_ID = :CONTENT_ID and LANG_ID = :LANG_ID',
		array( 'CONTENT_ID' => $content_id, 'LANG_ID' => $lang_id ) );

// Перенаправляем пользователя на указанную страницу
$url = ( $_REQUEST['url'] != '' ) ? base64_decode( $_REQUEST['url'] ) :
	( ( $_SERVER['HTTP_REFERER'] == '' ) ? ( 'http://' . $_SERVER['SERVER_NAME'] ) : $_SERVER['HTTP_REFERER'] );

header( 'Location: ' . $url );
?>