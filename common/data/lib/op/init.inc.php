<?php
if (!defined('STORAGE_PATH')){
	define('STORAGE_PATH', $_SERVER['DOCUMENT_ROOT'].'/_data_/');
}
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).'/export/');
//error_reporting(E_ALL);
if (!defined('DEBUG')) define('DEBUG',false);
setlocale(LC_ALL, "ru_RU.CP1251");
//
// Allow Guest Voting - End Code Addition
//------------------------------------------------------------------------------
/*
 	autoload (cloud: 05-03-2008)
	libpath: /lib/ by default or AutoloadPath if constant defined
	ex:

	AnyClassWithoutNamespace -> /lib/anyclasswithoutnamespace.php
	SomeNamespace_SomeClass  -> /lib/somenamespace/somenamespace_someclass.php
	Another_Class_UseOnlyFirst_Symbol -> /lib/another/another_class_useonlyfirst_symbol.php
	
*/
function __autoload($ClassName){
	
	static $FirstCall=true;
	$ClassName=strtolower($ClassName);
	if (class_exists($ClassName)) return true;
	if (false!==$pos=strpos($ClassName, '_')){
		$Path=substr($ClassName,0,$pos).'/'.$ClassName.'.php';
	}
	else {
		$Path=$ClassName.'.php';
	}
	
	if (defined('AutoloadPath')){
		$Path=constant('AutoloadPath').$Path;
	}	
	else {
		$Path=dirname(__FILE__).'/'.$Path;		
	}
	
	if ($FirstCall){
		$FirstCall=false;
		set_include_path(get_include_path().PATH_SEPARATOR.$Path.'export/');
	}
	
	if (file_exists($Path)){	
		
		include_once($Path);		
		return __class_init($ClassName);
	}
	else {

		if (false!==$pos=strrpos($ClassName,'_')){

			__autoload(substr($ClassName,0,$pos));
			return __class_init($ClassName);
		}
		return false;
	}
}


function __class_init($ClassName){
	if (!class_exists($ClassName)) return false;
	if (is_callable(array($ClassName,'__init'))) call_user_func(array($ClassName,'__init'));
	return true;
}

function on_unload($function=null, $args=null){
	static $on_unload=null;
	if (!$function){
		while($on_unload_function=array_pop($on_unload)){
			call_user_func($on_unload_function[0],$on_unload_function[1]);
		}		
	}
	else {
		if (!$on_unload){

			$on_unload=array();
			register_shutdown_function('on_unload');
		}
		array_push($on_unload, array($function,$args));
	}	
}
//Регистрируем нашу ф-ю автозагрузки:
spl_autoload_register('__autoload');

$connection='pgsql://'.params::$params['db_user']['value'].':'.params::$params['db_password']['value'].'@'.params::$params['db_server']['value'].'/'.params::$params['db_name']['value'].'/';
dbselect::$DB= dbselect_provider_pgsql::factory($connection);
dbselect::$DB->query("SET NAMES 'utf8'");
?>
