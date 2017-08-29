<?php
/**
 * Стартовые действия
 * @package    RBC_Contents_5_0
 * @subpackage lib
 * @copyright  Copyright (c) 2006 RBC SOFT
 */

@header('Content-Type: text/html; charset='.params::$params['encoding']['value']);
umask(000);

error_reporting(E_ALL & ~E_NOTICE & !E_DEPRECATED);

if (params::$params['encoding']['value']=='utf-8')
	setlocale(LC_ALL, 'ru_RU.utf8');
else
	setlocale(LC_ALL, 'ru_RU.CP1251');


if ( get_magic_quotes_gpc() == 1 ) {
	$_GET = exec_gpc_stripslashes($_GET);
	$_POST = exec_gpc_stripslashes($_POST);
	$_COOKIE = exec_gpc_stripslashes($_COOKIE);
	$_REQUEST = exec_gpc_stripslashes($_REQUEST);
}

$_SERVER["REQUEST_URI"] = $_SERVER["SCRIPT_NAME"]."?".$_SERVER["QUERY_STRING"];

////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Рекурсивная функция снятия слэшей. Слеши снимаются как со значений так и с ключей массива. 
 *
 * @param array &$arr    ссылка на массив, с которого надо снять слэши
 * @return array
 */
function exec_gpc_stripslashes(&$arr){
	if (is_array($arr)){
		foreach($arr as $_arrykey => $_arryval){
			$_arrykey_temp = stripslashes($_arrykey);
			if ( $_arrykey_temp != $_arrykey )
				unset( $arr[$_arrykey] );
			$arr[$_arrykey_temp] = is_array($_arryval) ?
				exec_gpc_stripslashes($_arryval) : stripslashes($_arryval);
		}
	}
	return $arr;
}

?>
