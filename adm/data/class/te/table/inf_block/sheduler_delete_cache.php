<?php
include_once( params::$params['common_data_server']['value'] . 'interface/sheduler_task_interface.php' );

/**
 * Задание утилите-плаинровщику. Очистка кэша модулей.
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2008 RBC SOFT
 */
class sheduler_delete_cache implements sheduler_task
{
	/**
	 * Очистка кэша модулей
	 */
	public function exec_sheduler_task()
	{
		include_once( params::$params['adm_data_server']['value'] . 'class/te/table/inf_block/inf_block.php' );
		
		inf_block::delete_all_cache();
	}
}
?>
