<?php
/**
 * Модуль "Список"
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class m_content_list extends module
{
	/**
	* Объект шаблонизатора модуля
	* 
	* @todo Не перенести ли его в module.php? И не создавать ли его в init()?
	*/
	protected $tpl;
	
	/**
	* Модуль "Список" обладает свойством мультиблочности
	*/
	protected $multiple_block = true;
	
	/**
	* Диспетчер модуля
	*/
	protected function content_init()
	{
		// Инициализация шаблонизатора
		$this -> tpl = new smarty_ee_module( $this );
		
		if ( $this -> q_param['id'] && $this -> view_param['view_mode'] == 'short_list' )
		{
			// Вывод элемента списка
			$tpl_file = 'item_short_list.tpl'; $this -> mode_item();
		}
		else
		{
			// Вывод оглавления списка
			switch ( $this -> view_param['view_mode'] )
			{
				case 'full_list': $tpl_file = 'full_list.tpl'; break;
				case 'one_full_list': $tpl_file = 'one_full_list.tpl'; break;
				default: $tpl_file = 'short_list.tpl';
			}
			$this -> mode_list();
		}
		
		// Выводим в случае необходимости ссылку на страницу с версией для печати
		if ( $this -> view_param['show_print_url'] == 'yes' && !$this -> env['is_print'] )
			$this -> tpl -> assign( 'print_url', lib::make_request_uri( array( 'print' => 1 ), 'printable.php' ) );
		
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir.$tpl_file );
	}
	
	/**
	* Вывод элемента списка
	*/
	protected function mode_item()
	{
		// Получаем содержимое элемента списка
		$query_item = $this -> get_module_sql( $this -> module_table.'.*', 'and '.$this -> module_table_pk.' = :id' );
		$content_item = db::sql_select( $query_item, $this -> get_module_binds() + array( 'id' => intval( $this -> q_param['id'] ) ) );
		
		if ( !count( $content_item ) ) return;
		
		$tag_list = $this -> get_tag_list( $content_item[0]['CONTENT_LIST_ID'] );
		$content_item[0]['TAG_LIST'] = $tag_list[$content_item[0]['CONTENT_LIST_ID']];
		
		// Выводим в шаблон содержимое элемента списка
		$this -> tpl -> assign( $content_item[0] );
		
		// Выводим в шаблон ссылку на оглавление списка
		$this -> tpl -> assign( 'back_url', lib::make_request_uri( array( 'id_'.$this -> env['area_id'] => '' ) ) );
		
		$this -> title = $content_item[0]['TITLE'];
	}
	
	/**
	* Вывод оглавления списка
	*/
	protected function mode_list()
	{
		// Получаем обшее число элементов списка
		$query_count = $this -> get_module_sql( 'count(*) as LIST_COUNT' );
		$content_count = db::sql_select( $query_count, $this -> get_module_binds() );
		$list_count = $content_count[0]['LIST_COUNT'];
		
		if ( !$list_count ) return;
		
		$limit_str = ''; $from = 1;
		if ( $this -> view_param['view_mode'] != 'one_full_list' && !$this -> env['is_print'] )
		{
			$items_per_page = max( intval( $this -> view_param['items_per_page'] ), 1 );
			
			// Подготавливаем навигационную стороку и строку ограничения числа записей
			if ( $list_count > $items_per_page )
			{
				$from = ( max( intval( $this -> q_param['from'] ), 1 ) - 1 ) * $items_per_page;
				
				if ( $this -> view_param['navigation'] == 'yes' && !$this -> env['is_print'] )
					$this -> tpl -> assign( 'navigation',
						lib::page_navigation( $items_per_page, $list_count, 'from_'.$this -> env['area_id'], $this -> tpl_dir.'navigation.tpl' ) );
				$limit_str = "limit $from, $items_per_page"; $from++;
			}
		}
		
		// Получаем содержимое элементов списка
		$query_list = $this -> get_module_sql(
			$this -> module_table.'.*, IF2.INF_BLOCK_ID', '',
			'order by LIST_ORDER '.( $this -> view_param['sort_order'] == 'desc' ? 'desc' : 'asc' ),
			$limit_str );
		$content_list = db::sql_select( $query_list, $this -> get_module_binds() );
		
		$tag_list = $this -> get_tag_list( lib::array_make_in( $content_list, 'CONTENT_LIST_ID' ) );
		
		// Дополнительно выводим в шаблон информацию о ссылках и нумерации элементов
		foreach ( $content_list as & $item )
		{
			if ( $this -> view_param['numeration'] == 'yes' )
				$item['ID'] = $from++;
			
			$item['TAG_LIST'] = $tag_list[$item['CONTENT_LIST_ID']];
			
			if ( $this -> view_param['view_mode'] == 'short_list' )
			{
				$path_and_area = $this -> get_url_by_module_param( 'CONTENT_LIST', 'view_mode', $this -> view_param['view_mode'], $item['INF_BLOCK_ID'] );
				$item['URL'] = lib::make_request_uri( array( 'id_' . $path_and_area['AREA'] => $item['CONTENT_LIST_ID'] ), $path_and_area['PATH'] );
			}
		}
		
		$this -> tpl -> assign( 'content', $content_list );
	}
	
	////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Метод возвращает список записей модуля при поиске по тегу
	 */
	protected function get_tag_records( $select_str = '', $order_str = '', $limit_str = '', $filter_str = '', $filter_binds = array() )
	{
		return parent::get_tag_records( $select_str ? $select_str : 'CONTENT_LIST.*, CONTENT_LIST.CONTENT_LIST_ID as CONTENT_ID',
			'order by CONTENT_LIST.CONTENT_LIST_ID desc',$limit_str, $filter_str, $filter_binds );
	}
	
	/**
	 * Метод возвращает ссылку на элемент контента
	 */
	protected function get_tag_content_url( $content_id )
	{
		$path_and_area = $this -> get_url_by_module_content( 'CONTENT_LIST', $content_id );
		
		if ( $path_and_area['PATH'] )
			return $path_and_area['PATH'] . '?id_' . $path_and_area['AREA'] . '=' . $content_id;
	}
}
?>
