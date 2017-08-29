<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Архив писем"
 *
 * @package    RBC_Contents_5_0
 * @subpackage app
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class subscribe_archive extends table
{
	/**
	 * Добавляем ссылку на просмотр
	 * 
	 * @param array $record Запись
	 * @return array список операций
	 */
	public function get_index_ops ( $record ) 
	{
		$ops = parent::get_index_ops( $record );
		$pk = $this -> primary_key -> get_from_record( $record );
		
		$ops['_ops'][] = array( 'name'=>'view', 'alt' => metadata::$lang['lang_view'],
			'url' => $this -> url -> get_url( 'view', array( 'pk' => $pk ) ) );
		
		return $ops;
	}
}
?>
