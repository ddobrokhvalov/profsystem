<?php
/**
 * Модуль "Новости"
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class m_news extends module
{
	/**
	* Объект шаблонизатора модуля
	* 
	* @todo Не перенести ли его в module.php? И не создавать ли его в init()?
	*/
	protected $tpl;
	
	/**
	* Модуль "Новости" обладает свойством мультиблочности
	*/
	protected $multiple_block = true;
	
	/**
	* Диспетчер модуля
	*/
	protected function content_init()
	{
		// Вывод ленты новостей в формате RSS
		if ( $this -> view_param['view_mode'] == 'rss' )
			return $this -> export_content( 'rss' );
		
		// Инициализация шаблонизатора
		$this -> tpl = new smarty_ee_module( $this );
		
		if ( $this -> q_param['id'] )
		{
			// Вывод содержимого новости
			$tpl_file = 'item_news.tpl'; $this -> mode_item();
		}
		else
		{
			// Вывод ленты новостей
			$tpl_file = 'list_news.tpl'; $this -> mode_list();
		}
		
		// Выводим в случае необходимости ссылку на страницу с версией для печати
		if ( $this -> view_param['show_print_url'] == 'yes' && !$this -> env['is_print'] )
			$this -> tpl -> assign( 'print_url', lib::make_request_uri( array( 'print' => 1 ), 'printable.php' ) );
		
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir.$tpl_file );
	}
	
	/**
	* Вывод содержимого новости
	*/
	protected function mode_item()
	{
		// Получаем содержимое новости
		$query_item = $this -> get_module_sql( $this -> module_table.'.*, IF2.INF_BLOCK_ID', 'and '.$this -> module_table_pk.' = :id' );
		$content_item = db::sql_select( $query_item, $this -> get_module_binds() + array( 'id' => intval( $this -> q_param['id'] ) ) );
		
		if ( !count( $content_item ) ) return;
		
		// Выводим в шаблон ссылки на ленту и архив новостей
		$this -> tpl -> assign( $this -> view_param['view_mode'] == 'archives' ? 'arch_url': 'back_url',
			lib::make_request_uri( array( 'id_'.$this -> env['area_id'] => '' ) ) );
		
		if ( $this -> view_param['view_mode'] == 'list' )
		{
			$path_and_area = $this -> get_url_by_module_param( 'NEWS', 'view_mode', 'archives', $content_item[0]['INF_BLOCK_ID'] );
			$this -> tpl -> assign( 'arch_url', $path_and_area['PATH'] );
		}
		
		$tag_list = $this -> get_tag_list( $content_item[0]['NEWS_ID'] );
		$content_item[0]['TAG_LIST'] = $tag_list[$content_item[0]['NEWS_ID']];
		
		// Выводим в шаблон содержимое новости
		$content_item[0]['NEWS_DATE'] = $this -> format_date( $content_item[0]['NEWS_DATE'] );
		$this -> tpl -> assign( $content_item[0] );

		$this -> title = $content_item[0]['TITLE'];
	}
	
	/**
	* Вывод ленты новостей
	*/
	protected function mode_list()
	{
		$filter_str = ''; $filter_arr = array();
		
		// Для варианта использования "Архив новостей" подготавливаем данные для формы и строку фильтрации
		if ( $this -> view_param['view_mode'] == 'archives' )
		{
			$afrom = lib::pack_date( $this -> q_param['afrom'].' 00:00', 'long' );
			$ato = lib::pack_date( $this -> q_param['ato'].' 25:00', 'long' );
			
			if ( !$afrom ) $afrom = date( 'Ymd', mktime( 0, 0, 0, date('m') - 1, date('d'), date('Y') ) ).'000000';
			if ( !$ato ) $ato = date( 'Ymd', mktime( 0, 0, 0, date('m'), date('d'), date('Y') ) ).'250000';
			
			$archives_tpl = new smarty_ee_module( $this );
			$archives_tpl -> assign( 'afrom', lib::unpack_date( $afrom, 'short' ) );
			$archives_tpl -> assign( 'ato', lib::unpack_date( $ato, 'short' ) );
			$this -> tpl -> assign( 'archives_form', $archives_tpl -> fetch( $this -> tpl_dir.'archives_form.tpl' ) );
			
			$filter_str = 'and NEWS_DATE >= :afrom and NEWS_DATE <= :ato';
			$filter_arr = array( 'afrom' => $afrom, 'ato' => $ato );
		}
		
		// Получаем обшее число новостей
		$query_count = $this -> get_module_sql( 'count(*) as NEWS_COUNT', $filter_str );
		$content_count = db::sql_select( $query_count, $this -> get_module_binds() + $filter_arr );
		$news_count = $content_count[0]['NEWS_COUNT'];
		
		if ( !$news_count ) return;
		
		$limit_str = ''; $items_per_page = max( intval( $this -> view_param['items_per_page'] ), 1 );
		
		// Подготавливаем навигационную стороку и строку ограничения числа записей
		if ( $news_count > $items_per_page )
		{
			$from = ( max( intval( $this -> q_param['from'] ), 1 ) - 1 ) * $items_per_page;
			if ( $this -> view_param['navigation'] == 'yes' && !$this -> env['is_print'] )
				$this -> tpl -> assign( 'navigation',
					lib::page_navigation( $items_per_page, $news_count, 'from_'.$this -> env['area_id'], $this -> tpl_dir.'navigation.tpl' ) );
			$limit_str = "limit $from, $items_per_page";
		}
		
		// Получаем содержимое ленты новостей
		$query_news = $this -> get_module_sql(
			$this -> module_table.'.*, IF2.INF_BLOCK_ID', $filter_str,
			'order by NEWS_DATE '.( $this -> view_param['sort_order'] == 'asc' ? 'asc' : 'desc' ),
			$limit_str );
		$content_news = db::sql_select( $query_news, $this -> get_module_binds() + $filter_arr );
		
		$tag_list = $this -> get_tag_list( lib::array_make_in( $content_news, 'NEWS_ID' ) );
		
		// Дополнительно выводим в шаблон информацию о ссылках и тегах
		foreach ( $content_news as & $item )
		{
			$item['NEWS_DATE'] = $this -> format_date( $item['NEWS_DATE'] );
			
			$path_and_area = $this -> get_url_by_module_param( 'NEWS', 'view_mode', $this -> view_param['view_mode'], $item['INF_BLOCK_ID'] );
			$item['URL'] = lib::make_request_uri( array( 'id_' . $path_and_area['AREA'] => $item['NEWS_ID'] ), $path_and_area['PATH'] );
			
			$item['TAG_LIST'] = $tag_list[$item['NEWS_ID']];
		}
		
		if ( $this -> view_param['view_mode'] == 'list' )
		{
			$path_and_area = $this -> get_url_by_module_param( 'NEWS', 'view_mode', 'archives', $this -> env['block_id'] );
			$this -> tpl -> assign( 'arch_url', $path_and_area['PATH'] );
			
			$path_and_area = $this -> get_url_by_module_param( 'NEWS', 'view_mode', 'rss', $this -> env['block_id'] );
			$this -> tpl -> assign( 'rss_url', $path_and_area['PATH'] );
		}
		
		$this -> tpl -> assign( 'content', $content_news );
	}
	
	/**
	* Форматирование даты в зависимости от значения параметра модуля
	*/
	protected function format_date( $date )
	{
		$months = array("01"=>"янв.","02"=>"фев.","03"=>"мар.","04"=>"апр.","05"=>"мая","06"=>"июн.","07"=>"июл.","08"=>"авг.","09"=>"сен.","10"=>"окт.","11"=>"ноя.","12"=>"дек.");
		switch ( $this -> view_param['date_format'] )
		{
			case 'dd.mm': $format = 'd.m'; break;
			case 'dd.mes': $format = 'd.m'; break;
			case 'hh:ii': $format = 'H:i'; break;
			case 'dd.mm.yyyy': $format = 'd.m.Y'; break;
			case 'dd.mm hh:ii': $format = 'd.m H:i'; break;
			case 'dd.mm.yyyy hh:ii': $format = 'd.m.Y H:i'; break;
			default: $format = 'd.m.y';
		}
		
		if ( preg_match( '/(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)/', $date, $match ) )
			if($this -> view_param['date_format'] == "dd.mes"){
				$day = date( "d", mktime( $match[4], $match[5], $match[6], $match[2], $match[3], $match[1] ) );
				$mes = date( "m", mktime( $match[4], $match[5], $match[6], $match[2], $match[3], $match[1] ) );
				$mes = $months[$mes];
				return '<div class="number">'.$day.'</div>
						<div class="month">'.$mes.'</div>';
			}else{
				return date( $format, mktime( $match[4], $match[5], $match[6], $match[2], $match[3], $match[1] ) );
			}
		else
			return '';
	}
	
	////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Метод возвращает список записей модуля при поиске по тегу
	 */
	protected function get_tag_records( $select_str = '', $order_str = '', $limit_str = '', $filter_str = '', $filter_binds = array() )
	{
		return parent::get_tag_records( $select_str ? $select_str : 'NEWS.*, NEWS.NEWS_ID as CONTENT_ID ',
			'order by NEWS.NEWS_DATE desc',$limit_str, $filter_str, $filter_binds );
	}
	
	/**
	 * Метод возвращает ссылку на элемент контента
	 */
	protected function get_tag_content_url( $content_id )
	{
		$path_and_area = $this -> get_url_by_module_content( 'NEWS', $content_id );
		
		if ( $path_and_area['PATH'] )
			return $path_and_area['PATH'] . '?id_' . $path_and_area['AREA'] . '=' . $content_id;
	}
	
	//////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Заголовок экспорта списка записей
	 * 
	 * @return string
	 */
	protected function get_export_title()
	{
		return htmlspecialchars( $this -> view_param['export_title'], ENT_QUOTES );
	}
	
	/**
	 * Описание экспорта списка записей
	 * 
	 * @return string
	 */
	protected function get_export_description()
	{
		return htmlspecialchars( $this -> view_param['export_description'], ENT_QUOTES );
	}
	
	/**
	 * Список экспортируемых записей
	 * 
	 * @return array
	 */
	protected function get_export_list()
	{
		// Получаем содержимое ленты новостей
		$export_query = $this -> get_module_sql(
			$this -> module_table.'.*, IF2.INF_BLOCK_ID', '',
			'order by NEWS_DATE '.( $this -> view_param['sort_order'] == 'asc' ? 'asc' : 'desc' ),
			'limit ' . max( intval( $this -> view_param['items_per_page'] ), 1 ) );
		$export_list = db::sql_select( $export_query, $this -> get_module_binds() );
		
		// Подготавливаем информацию для вывода в шаблон
		foreach ( $export_list as & $item )
		{
			$item['title'] = htmlspecialchars( $item['TITLE'], ENT_QUOTES );
			$item['description'] = $item['ANNOUNCE'];
			$item['pub_date'] = lib::unpack_date( $item['NEWS_DATE'], 'rfc' );
			
			$path_and_area = $this -> get_url_by_module_param( 'NEWS', 'view_mode', 'list', $item['INF_BLOCK_ID'] );
			$item['link'] = lib::make_request_uri( array( 'id_' . $path_and_area['AREA'] => $item['NEWS_ID'] ), $path_and_area['PATH'] );
			
			if ( !preg_match( '/^https?\:\/\//', $item['link'] ) && preg_match( '/^\//', $item['link'] ) )
				$item['link'] = 'http' . ( $_SERVER['HTTPS'] == 'on' ? 's' : '' ) . '://' . $_SERVER['HTTP_HOST'] . $item['link'];
		}
		
		return $export_list;
	}
	
	/**
	 * Завершение инициализации модуля. Используется для вывода ленты новостей в формате RSS
	 */
	function complete_init()
	{
		if ( $this -> view_param['view_mode'] == 'rss' )
		{
			header( 'Content-Type: text/xml; charset=' . params::$params['encoding']['value'] );
			print $this -> body; exit;
		}
	}
}
?>
