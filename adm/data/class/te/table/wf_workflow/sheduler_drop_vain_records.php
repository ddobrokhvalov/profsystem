<?php
include_once( params::$params['common_data_server']['value'] . 'interface/sheduler_task_interface.php' );

/**
 * Задание утилите-плинировщику. Удаление старых записей из таблиц WF_APPROVED и WF_NOTIFY
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2008 RBC SOFT
 */
class sheduler_drop_vain_records implements sheduler_task
{
	/**
	 * Удаление старых записей из таблиц WF_APPROVED и WF_NOTIFY
	 */
	public function exec_sheduler_task()
	{
		include_once( params::$params['adm_data_server']['value'] . 'class/te/table/log_show/log_show.php' );
		
		$operation_date = strftime( log::$strftime_format, strtotime( log_show::$drop_log_interval_default ) );
		
		db::sql_query( 'delete from WF_APPROVED where OPERATION_DATE <= :operation_date',
			array( 'operation_date' => $operation_date ) );
		
		db::sql_query( 'delete from WF_NOTIFY where OPERATION_DATE <= :operation_date',
			array( 'operation_date' => $operation_date ) );
	}
}
?>
