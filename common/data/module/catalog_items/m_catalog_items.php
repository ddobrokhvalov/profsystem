<?php
/**
 * Модуль "Каталог"
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2017 Dmitry Dobrokhvalov
 */
class m_catalog_items extends module
{
	/**
	* Объект шаблонизатора модуля
	* 
	* @todo Не перенести ли его в module.php? И не создавать ли его в init()?
	*/
	protected $tpl;
	
	/**
	* Модуль "Каталог" обладает свойством мультиблочности
	*/
	protected $multiple_block = true;
	
	/**
	* Диспетчер модуля
	*/
	protected function content_init()
	{
		// Инициализация шаблонизатора
		$this -> tpl = new smarty_ee_module( $this );
		/*$this->tpl->clear_all_cache();
		$this->tpl->cache_lifetime = 0;
		$this->tpl->compile_check = true;*/
		/*print_r("<pre>");
		print_r($this->tpl);
		print_r("</pre>");*/
		
		if ( $this -> q_param['id'] )
		{
			$tpl_file = 'item.tpl'; $this -> mode_item();
		}
		else
		{
			$tpl_file = 'list.tpl'; $this -> mode_list();
		}
		/*print_r("<pre>");
		print_r($this);
		print_r("</pre>");*/
		
		// Выводим в случае необходимости ссылку на страницу с версией для печати
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir.$tpl_file );
	}
	
	/**
	* Вывод содержимого новости
	*/
	protected function mode_item()
	{
		/*// Получаем содержимое новости
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

		$this -> title = $content_item[0]['TITLE'];*/
	}
	
	/**
	* Вывод ленты новостей
	*/
	protected function mode_list()
	{
		$filter_str = ''; $filter_arr = array();
		
		// Получаем обшее число товаров
		$query_count = $this -> get_module_sql( 'count(*) as CATALOG_ITEMS_COUNT', $filter_str );
		$binds_count = $this -> get_module_binds() + $filter_arr;
		
		if($this->view_param["view_mode"] == "from_all"){
			$query_count = "select count(*) as CATALOG_ITEMS_COUNT
							from CATALOG_ITEMS
							where CATALOG_ITEMS.VERSION = :CATALOG_ITEMS_version 
							and CATALOG_ITEMS.LANG_ID = :CATALOG_ITEMS_lang_id";
			$binds_count = array("CATALOG_ITEMS_version"=>$this->env["version"], "CATALOG_ITEMS_lang_id"=>$this->env["lang_id"]);
		}
		
		$content_count = db::sql_select( $query_count, $binds_count );
		$catalog_items_count = $content_count[0]['CATALOG_ITEMS_COUNT'];
		
		if ( !$catalog_items_count ) return;
		
		$limit_str = ''; $items_per_page = max( intval( $this -> view_param['items_per_page'] ), 1 );
		
		
		if ( $catalog_items_count > $items_per_page )
		{
			$from = ( max( intval( $this -> q_param['from'] ), 1 ) - 1 ) * $items_per_page;
			if ( $this -> view_param['navigation'] == 'yes' && !$this -> env['is_print'] )
				$this -> tpl -> assign( 'navigation',
					lib::page_navigation( $items_per_page, $catalog_items_count, 'from_'.$this -> env['area_id'], $this -> tpl_dir.'navigation.tpl' ) );
			$limit_str = "limit $from, $items_per_page";
		}
		
		
		$query_catalog_items = $this -> get_module_sql(
			$this -> module_table.'.*, IF2.INF_BLOCK_ID', $filter_str, "order by CATALOG_ITEMS_ID asc",	$limit_str );
		$binds_catalog_items = $this -> get_module_binds() + $filter_arr;
		
		if($this->view_param["view_mode"] == "from_all"){
			$query_catalog_items = "select *
							from CATALOG_ITEMS
							where CATALOG_ITEMS.VERSION = :CATALOG_ITEMS_version 
							and CATALOG_ITEMS.LANG_ID = :CATALOG_ITEMS_lang_id";
			if($this->view_param["template"] == "main"){
				$query_catalog_items .= "  and SHOW_ON_MAIN = 1";
			}
			$query_catalog_items .= "  order by CATALOG_ITEMS_ID asc";
			$binds_catalog_items = array("CATALOG_ITEMS_version"=>$this->env["version"], "CATALOG_ITEMS_lang_id"=>$this->env["lang_id"]);
		}
		
		
		$content_catalog_items = db::sql_select( $query_catalog_items, $binds_catalog_items );
		
		if ($this->view_param["view_mode"] == "from_all"){
			shuffle($content_catalog_items);
			if($catalog_items_count > $items_per_page){
				$content_catalog_items = array_slice($content_catalog_items, 0, $items_per_page);
			}
		}
		$tag_list = $this -> get_tag_list( lib::array_make_in( $content_news, 'CATALOG_ITEMS_ID' ) );
		
		foreach ( $content_catalog_items as & $item )
		{
			$path_and_area = $this -> get_url_by_module_content('CATALOG_ITEMS', $item["CATALOG_ITEMS_ID"]);
			$item['URL'] = lib::make_request_uri( array( 'id_' . $path_and_area['AREA'] => $item['CATALOG_ITEMS_ID'] ), $path_and_area['PATH'] );
			$item['TAG_LIST'] = $tag_list[$item['CATALOG_ITEMS_ID']];
		}
		/*print_r("<pre>");
		print_r($content_catalog_items);
		print_r("</pre>");*/
		$this -> tpl -> assign( 'content', $content_catalog_items );
	}
	
	
	////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Метод возвращает список записей модуля при поиске по тегу
	 */
	protected function get_tag_records( $select_str = '', $order_str = '', $limit_str = '', $filter_str = '', $filter_binds = array() )
	{
		return parent::get_tag_records( $select_str ? $select_str : 'CATALOG_ITEMS.*, CATALOG_ITEMS.CATALOG_ITEMS_ID as CONTENT_ID ',
			'order by CATALOG_ITEMS.CATALOG_ITEMS_ID asc',$limit_str, $filter_str, $filter_binds );
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
