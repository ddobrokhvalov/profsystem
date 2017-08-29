<?php
/**
 * Утилита-планировщик предназначена для автоматического выполнения сервисных операций администраторской части
 * 
 * Утилиту рекомендуется запускать с увеличенным максимальным временем выполнения скрипта, например
 * /usr/local/bin/php -d max_execution_time=1800 /www/project/adm/scripts/sheduler_tool.php > /dev/null 2>&1
 */
include_once( dirname( __FILE__ ) . '/../data/config/params.php' );
include_once( params::$params['adm_data_server']['value'] . 'prebuild/metadata_' . params::$params['default_interface_lang']['value'] . '.php' );

$tasks = db::sql_select( '
	select SHEDULER.*, TE_OBJECT.SYSTEM_NAME
	from SHEDULER
		inner join TE_OBJECT on SHEDULER.TE_OBJECT_ID = TE_OBJECT.TE_OBJECT_ID
	where IS_ACTIVE = 1' );

foreach ( $tasks as $task )
{
	$class_name = $task['CLASS_NAME']; $system_name = $task['SYSTEM_NAME'];
	$class_dir = metadata::$objects[$system_name]['class'] ?
		metadata::$objects[$system_name]['class'] : strtolower( $system_name );
	$object_type = metadata::$objects[$system_name]['type'];
	$object_level = metadata::$objects[$system_name]['object_level'];
	
	include_once(params::$params['adm_data_server']['value'] . 'class/core/object/object.php' );
	include_once(params::$params['adm_data_server']['value'] . 'class/core/object/' . $object_type . '.php' );
	include_once( params::$params['adm_data_server']['value'] . 'class/' .
		$object_level . '/' . $object_type . '/' . $class_dir . '/' . $class_name . '.php' );
	
	$object = new $class_name();
	
	$object -> exec_sheduler_task();
}
?>
