<?php
/**
 * Модуль "Статьи"
 */
class m_articles extends module
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
		
		// Инициализация шаблонизатора
		$this -> tpl = new smarty_ee_module( $this );
		
		if ( $this -> q_param['id'] )
		{
			// Вывод содержимого статьи
			$tpl_file = 'item.tpl'; $this -> mode_item();
		}
		else
		{
			// Вывод ленты статей
			$tpl_file = 'list.tpl'; $this -> mode_list();
		}
		
		
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir.$tpl_file );
	}
	
	/**
	* Вывод содержимого статьи
	*/
	protected function mode_item()
	{
		// Получаем содержимое статьи
		$query_item = $this -> get_module_sql( $this -> module_table.'.*, IF2.INF_BLOCK_ID', 'and '.$this -> module_table_pk.' = :id' );
		$content_item = db::sql_select( $query_item, $this -> get_module_binds() + array( 'id' => intval( $this -> q_param['id'] ) ) );
		
		if ( !count( $content_item ) ) return;
		
		$this -> tpl -> assign( $this -> view_param['view_mode'] == 'archives' ? 'arch_url': 'back_url',
			lib::make_request_uri( array( 'id_'.$this -> env['area_id'] => '' ) ) );
		
		if ( $this -> view_param['view_mode'] == 'list' )
		{
			$path_and_area = $this -> get_url_by_module_param( 'ARTICLES', 'view_mode', 'archives', $content_item[0]['INF_BLOCK_ID'] );
			$this -> tpl -> assign( 'arch_url', $path_and_area['PATH'] );
		}
		
		$tag_list = $this -> get_tag_list( $content_item[0]['ARTICLES_ID'] );
		$content_item[0]['TAG_LIST'] = $tag_list[$content_item[0]['ARTICLES_ID']];
		/*if($this->env["version"] == 1){
			print_r("<pre>");
			print_r($content_item);
			print_r("</pre>");
		}*/
		$this -> tpl -> assign( $content_item[0] );

		$this -> title = $content_item[0]['TITLE'];
	}
	
	/**
	* Вывод ленты статей
	*/
	protected function mode_list()
	{
		$filter_str = ''; $filter_arr = array();
		
		
		// Получаем обшее число статей
		$query_count = $this -> get_module_sql( 'count(*) as ARTICLES_COUNT', $filter_str );
		$content_count = db::sql_select( $query_count, $this -> get_module_binds() + $filter_arr );
		$ARTICLES_count = $content_count[0]['ARTICLES_COUNT'];
		
		if ( !$ARTICLES_count ) return;
		
		$limit_str = ''; $items_per_page = max( intval( $this -> view_param['items_per_page'] ), 1 );
		
		// Подготавливаем навигационную стороку и строку ограничения числа записей
		if ( $ARTICLES_count > $items_per_page )
		{
			$from = ( max( intval( $this -> q_param['from'] ), 1 ) - 1 ) * $items_per_page;
			if ( $this -> view_param['navigation'] == 'yes' && !$this -> env['is_print'] )
				$this -> tpl -> assign( 'navigation',
					lib::page_navigation( $items_per_page, $ARTICLES_count, 'from_'.$this -> env['area_id'], $this -> tpl_dir.'navigation.tpl' ) );
			$limit_str = "limit $from, $items_per_page";
		}
		
		// Получаем содержимое ленты статей
		$query_ARTICLES = $this -> get_module_sql(
			$this -> module_table.'.*, IF2.INF_BLOCK_ID', $filter_str,
			'order by ARTICLES_ID '.( $this -> view_param['sort_order'] == 'asc' ? 'asc' : 'desc' ),
			$limit_str );
		$content_ARTICLES = db::sql_select( $query_ARTICLES, $this -> get_module_binds() + $filter_arr );
		
		// Дополнительно выводим в шаблон информацию о ссылках и тегах
		foreach ( $content_ARTICLES as & $item )
		{
			$path_and_area = $this -> get_url_by_module_param( 'ARTICLES', 'view_mode', $this -> view_param['view_mode'], $item['INF_BLOCK_ID'] );
			$item['URL'] = lib::make_request_uri( array( 'id_' . $path_and_area['AREA'] => $item['ARTICLES_ID'] ), $path_and_area['PATH'] );
			$item['ANNOUNCE'] = mb_substr($item['ANNOUNCE'], 0, 80, "utf-8");
		}
		/*print_r("<pre>");
		print_r($content_ARTICLES);
		print_r("</pre>");*/
		$this -> tpl -> assign( 'content', $content_ARTICLES );
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
		return parent::get_tag_records( $select_str ? $select_str : 'ARTICLES.*, ARTICLES.ARTICLES_ID as CONTENT_ID ',
			'order by ARTICLES.ARTICLES_ID desc',$limit_str, $filter_str, $filter_binds );
	}
	
	/**
	 * Метод возвращает ссылку на элемент контента
	 */
	protected function get_tag_content_url( $content_id )
	{
		$path_and_area = $this -> get_url_by_module_content( 'ARTICLES', $content_id );
		
		if ( $path_and_area['PATH'] )
			return $path_and_area['PATH'] . '?id_' . $path_and_area['AREA'] . '=' . $content_id;
	}
	
	
}
?>
