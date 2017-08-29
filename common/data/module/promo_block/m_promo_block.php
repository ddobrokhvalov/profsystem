<?php
/**
 * Модуль "Рекламный блок"
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class m_promo_block extends module
{
	/**
	* Объект шаблонизатора модуля
	*/
	protected $tpl;
	
	/**
	* Список ссылок, для которых не производится замена
	*/
	protected $link_exclude = array( '#', 'mailto', 'javascript' );
	
	/**
	* Диспетчер модуля
	*/
	protected function content_init()
	{
		// Инициализация шаблонизатора
		$this -> tpl = new smarty_ee_module( $this );
		
		$today_date = date( 'YmdHis', time() );
		
		// Получаем список рекламных блоков, готовых к показу
		$promo_block_query = $this -> get_module_sql(
			$this -> module_table.'.*, CONTENT_MAP.CONTENT_ID',
			'and START_DATE <= :start_date and END_DATE >= :end_date
				and ( MAX_NUM_LOADS > NUM_LOADS or MAX_NUM_LOADS = 0 or MAX_NUM_LOADS is null )' );
		$promo_block_items = db::sql_select( $promo_block_query, $this -> get_module_binds() +
			array( 'start_date' => $today_date, 'end_date' => $today_date ) );
		
		if ( count( $promo_block_items ) )
		{
			// Подсчитываем сумму приоритетов
			$priority_sum = 0;
			foreach ( $promo_block_items as $promo_block_item )
				$priority_sum += $promo_block_item['PRIORITY'];

			// Выбираем случайное число в интервале от 1 до $priority_sum
			$random_number = rand( 1, $priority_sum );
			
			// Выбираем рекламный блок
			$max_value = $min_value = 0;
			foreach ( $promo_block_items as $promo_block_item )
			{
				$max_value = $min_value + $promo_block_item['PRIORITY'];
				if ( $min_value < $random_number && $random_number <= $max_value ) 
				{
					$random_block_item = $promo_block_item; break;
				}
				$min_value = $max_value;
			}
			
			// Выбираем все ссылки в теле блока
			preg_match_all( '/href=(?:(?:\'([^\']+)\')|(?:\"([^\"]+)\")|([^\s>]+))/i', $random_block_item['BODY'], $matches );
			
			// Проходимся по всем найденным ссылкам и заменяем все, кроме тех, что в массиве $link_exclude
			for ( $i = 0; $i < count( $matches[0] ); $i++ )
			{
				$link = trim( $matches[1][$i] . $matches[2][$i] . $matches[3][$i] );
				$pattern_exclude = '/^(' . implode( '|', $this -> link_exclude ) . ').*/i';
				if ( !preg_match( $pattern_exclude, $link ) )
				{
					$replace = 'href="/common/tool/promo_redirect.php?content_id=' . $random_block_item['CONTENT_ID'] .
						'&lang_id=' . $random_block_item['LANG_ID'] . '&version=' . $random_block_item['VERSION'] . '&url=' . base64_encode( $link ) . '"';
					$random_block_item['BODY'] = str_replace( $matches[0][$i], $replace, $random_block_item['BODY'] );
				}
			}
			
			// Для рабочей версии увеличиваем счетчик показов
			if ( $this -> env['version'] == 0 )
				db::sql_query( 'update PROMO_BLOCK set NUM_LOADS = NUM_LOADS + 1 where PROMO_BLOCK_ID = :CONTENT_ID and LANG_ID = :LANG_ID',
					array( 'CONTENT_ID' => $random_block_item['CONTENT_ID'], 'LANG_ID' => $random_block_item['LANG_ID'] ) );
			
			$this -> tpl -> assign( 'body', $random_block_item['BODY'] );
		}
		
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir . 'promo_block.tpl' );
	}
	
	/**
	 * Оключаем кэширование
	 */
	protected function get_hash_code()
	{
		return false;
	}
}
?>
