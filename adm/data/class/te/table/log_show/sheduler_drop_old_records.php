<?php
include_once( params::$params['common_data_server']['value'] . 'interface/sheduler_task_interface.php' );

/**
 * Задание утилите-планировщику. Очистка старых журналов.
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2008 RBC SOFT
 */
class sheduler_drop_old_records implements sheduler_task
{
	/**
	 * Очистка старых журналов
	 */
	public function exec_sheduler_task()
	{
		include_once( params::$params['adm_data_server']['value'] . 'class/te/table/log_show/log_show.php' );
		
		log_show::drop_all_old_records();
	}
}
?>
