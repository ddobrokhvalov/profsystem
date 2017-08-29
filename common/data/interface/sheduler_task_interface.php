<?php
/**
 * Интерфейс задач утилмты-планировщика.
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
interface sheduler_task
{
	/**
	 * Выполняет задание
	 */
	public function exec_sheduler_task();
}
?>