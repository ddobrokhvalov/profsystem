<?php
/**
 * Модуль "Таксономия"
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class m_taxonomy extends module
{
	/**
	* Объект шаблонизатора модуля
	* 
	* @todo Не перенести ли его в module.php? И не создавать ли его в init()?
	*/
	protected $tpl;
	
	/**
	* Параметр представления "Модуль"
	*/
	protected $view_module;
	
	/**
	* Список модулей, участвующих в таксономии
	*/
	protected $view_module_list;
	
	/**
	* Диспетчер модуля
	*/
	protected function content_init()
	{
		// Инициализация шаблонизатора
		$this -> tpl = new smarty_ee_module( $this );
		
		// Вычисляем вариант использования модуля
		$view_mode = in_array( $this -> q_param['view_mode'], array( 'tag_cloud', 'tag_search' ) ) ?
			$this -> q_param['view_mode'] : $this -> view_param['view_mode'];
		
		// Вычисляем список модулей, участвующих в таксономии
		$this -> view_module_list = lib::array_reindex( $this -> get_module_list(), 'VALUE' );
		
		// Вычисляем параметр представления модуля
		$this -> view_module = in_array( $this -> q_param['view_module'], array_keys( $this -> view_module_list ) ) ?
			$this -> q_param['view_module'] : $this -> view_param['view_module'];
		
		foreach ( $this -> view_module_list as $view_module_index => $view_module_item )
			$this -> view_module_list[$view_module_index]['selected'] =
				$this -> view_module == $view_module_item['VALUE'];
		
		if ( $view_mode == 'tag_cloud' )
			$this -> mode_tag_cloud();
		else
			$this -> mode_tag_search();
	}
	
	/**
	* Облако тегов
	*/
	protected function mode_tag_cloud()
	{
		$level_count = max( intval( $this -> view_param['level_count'] ), 1 );
		$tag_cloud_count = max( intval( $this -> view_param['tag_cloud_count'] ), 1 );
		$min_font_size = max( intval( $this -> view_param['min_font_size'] ), 1 );
		
		unset( $this -> view_module_list['anywhere'] );
		
		if ( $this -> view_module == 'anywhere' )
			$module_list = array_keys( $this -> view_module_list );
		else
			$module_list = array( $this -> view_module );
		
		$module_list = array_map( 'strtoupper', $module_list );
		
		$module_sql = array(); $module_binds = array();
		foreach ( $module_list as $module_name )
		{
			$module_sql[] = "( TE_OBJECT.SYSTEM_NAME = '{$module_name}' and TAG_OBJECT.OBJECT_ID in ( " .
				$this -> call_module( $module_name, 'get_module_sql', array( $module_name . '_ID as OBJECT_ID' ), array( 'any_block' => true ) ) . ' ) )';
			$module_binds += $this -> call_module( $module_name, 'get_module_binds', array(), array( 'any_block' => true ) );
		}
		
		$module_sql = 'and ( ' . join( ' or ', $module_sql ) . ' )';
		
		// Получаем список тегов в зависимости от параметра представления
		$tag_list = db::sql_select( '
				select TAG.TITLE, TAG.SYSTEM_NAME,
					count( TAG_OBJECT.OBJECT_ID ) as NUM_LINKS
				from TAG, TAG_OBJECT, TE_OBJECT
				where
					TAG_OBJECT.TAG_ID = TAG.TAG_ID and
					TAG_OBJECT.TE_OBJECT_ID = TE_OBJECT.TE_OBJECT_ID
					' . $module_sql . '
				group by TAG.TITLE, TAG.SYSTEM_NAME
				order by NUM_LINKS desc
				limit ' . $tag_cloud_count, $module_binds );
		
		if ( count( $tag_list ) )
		{
			// Высисляем путь до ближайщего блока с модулем "Таксономия" в главное области
			$path_and_area = $this -> get_url_by_module_name( 'TAXONOMY', '', true );
			if ( !$path_and_area['PATH'] && $this -> env['is_main'] )
				$path_and_area['PATH'] = 'index.php';
			
			$num_links_max = $tag_list[0]['NUM_LINKS'];
			$num_links_min = $tag_list[count( $tag_list ) - 1]['NUM_LINKS'];
			$level_step = ( $num_links_max - $num_links_min ) / $level_count;
			
			foreach ( $tag_list as $tag_index => $tag_item )
			{
				// Вычисляем размер шрифта тега
				if ( $level_step > 0 )
					$tag_list[$tag_index]['FONT_SIZE'] = round( $min_font_size + ( $tag_item['NUM_LINKS'] - $num_links_min ) / $level_step );
				else
					$tag_list[$tag_index]['FONT_SIZE'] = round( $min_font_size + $level_count / 2 );
				
				// Формируем ссылку на страницу с поиском по тегам
				if ( $path_and_area['PATH'] )
					$tag_list[$tag_index]['URL'] = $path_and_area['PATH'] . '?' . join( '&', array(
						'search_' . $path_and_area['AREA'] . '=' . urlencode( $tag_item['SYSTEM_NAME'] ),
						'view_mode_' . $path_and_area['AREA'] . '=' . 'tag_search',
						'view_module_' . $path_and_area['AREA'] . '=' . $this -> view_module ) );
			}
			
			usort( $tag_list, array( $this, 'tag_sort' ) );
		}
		
		$this -> tpl -> assign( 'tag_list', $tag_list );
		
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir . 'tag_cloud.tpl' );
	}
	
	/**
	* Поиск по тегам
	*/
	protected function mode_tag_search()
	{
		$this -> tpl -> assign( 'view_module_list', $this -> view_module_list );
		
		unset( $this -> view_module_list['anywhere'] );
		
		if ( $this -> view_module == 'anywhere' )
			$module_list = array_keys( $this -> view_module_list );
		else
			$module_list = array( $this -> view_module );
		
		$search = mb_strtolower( $this -> q_param['search'], params::$params["encoding"]["value"] );
		
		$result_list = array(); $anchor_list = array();
		
		if ( $search )
		{
			// Формируем строку поиска и переменные привязки
			$filter_str = $search ? 'and TAG.SYSTEM_NAME = :search' : '';
			$filter_binds = $search ? array( 'search' => $search ) : array();
			
			$items_per_page = max( intval( $this -> view_param['items_per_page'] ), 1 );
			
			foreach ( $module_list as $module_name )
			{
				$from = ( max( intval( $this -> q_param['from'] ), 1 ) - 1 ) * $items_per_page;
				$limit_str = 'limit ' . $from . ', ' . $items_per_page;
				
				// Для получения списка записей используем класс самого модуля
				$module_result_list = $this -> call_module( $module_name, 'get_tag_records',
						array( '', '', $limit_str, $filter_str, $filter_binds ), array( 'any_block' => true ) );
				
				if ( count( $module_result_list ) )
				{
					// Формируем список якорей для быстрого перехода к результатам поиска по модулям
					$anchor_list[$module_name] = $this -> view_module_list[$module_name]['TITLE'];
					
					// Формируем ссылки на элементы контента найденных записей
					foreach( $module_result_list as $result_index => $result_item )
						$module_result_list[$result_index]['URL'] =
							$this -> call_module( $module_name, 'get_tag_content_url', array( $result_item['CONTENT_ID'] ) );
					
					// Для каждого модуля используется отдельный шаблон списка записей
					$content_tpl = new smarty_ee_module( $this );
					$content_tpl -> assign( 'result_list', $module_result_list );
					$result_list[$module_name]['result'] =
						$content_tpl -> fetch( $this -> tpl_dir . $module_name . '.tpl' );

					$count = $this -> call_module( $module_name, 'get_tag_records',
						array( 'count(*) as COUNT', '', '', $filter_str, $filter_binds ), array( 'any_block' => true ) );
					
					$result_list[$module_name]['result_count'] = $count[0]['COUNT'];
					$result_list[$module_name]['module_title'] = $this -> view_module_list[$module_name]['TITLE'];
					
					// В зависимости от варианта представления выводим в шаблон либо строку навигации
					// либо ссылку на индивидуальный список записей конкретного модуля
					if ( $count[0]['COUNT'] > $items_per_page )
					{
						if ( $this -> view_module != 'anywhere' )
							$result_list[$module_name]['navigation'] = lib::page_navigation( $items_per_page, $count[0]['COUNT'],
								'from_' . $this -> env['area_id'], $this -> tpl_dir . 'navigation.tpl' );
						else
							$result_list[$module_name]['search_url'] = lib::make_request_uri( array(
								'search_' . $this -> env['area_id'] => urlencode( $search ),
								'view_mode_' . $this -> env['area_id'] => 'tag_search',
								'view_module_' . $this -> env['area_id'] => $module_name ) );
					}
				}
			}
		}
		
		// Выводим в шаблон результаты поиска
		$this -> tpl -> assign( 'search', htmlspecialchars( $search, ENT_QUOTES ) );
		if ( count( $result_list ) )
			$this -> tpl -> assign( 'result_list', $result_list );
		if ( count( $anchor_list ) > 1 )
			$this -> tpl -> assign( 'anchor_list', $anchor_list );
		
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir . 'tag_search.tpl' );
	}
	
	/**
	* Список модулей, участвующих в таксономии
	*/
	protected function get_module_list()
	{
		return db::sql_select( "
			select
				lower( PARAM_VALUE.VALUE ) as VALUE, TT.VALUE as TITLE
			from
				PRG_MODULE, MODULE_PARAM, PARAM_VALUE
				left join TABLE_TRANSLATE TT on
					TT.CONTENT_ID = PARAM_VALUE.PARAM_VALUE_ID and
					TT.LANG_ID = :lang_id and
					TT.FIELD_NAME = 'TITLE' and
					TT.TE_OBJECT_ID = (
						select TE_OBJECT_ID from TE_OBJECT where TE_OBJECT.SYSTEM_NAME = 'PARAM_VALUE'
					)
			where
				PRG_MODULE.SYSTEM_NAME = 'TAXONOMY' and
				MODULE_PARAM.PRG_MODULE_ID = PRG_MODULE.PRG_MODULE_ID and
				MODULE_PARAM.SYSTEM_NAME = 'view_module' and
				PARAM_VALUE.MODULE_PARAM_ID = MODULE_PARAM.MODULE_PARAM_ID and
				( lower( PARAM_VALUE.VALUE ) = 'anywhere' or lower( PARAM_VALUE.VALUE ) in (
					select lower( SYSTEM_NAME ) as SYSTEM_NAME from TE_OBJECT where TAXONOMY_ALLOWED = 1
				) )
			order by
				TT.VALUE", array( 'lang_id' => $this -> env['lang_id'] ) );
	}
	
	/**
	* Сортировка облака тегов в алфавитном порядке
	*/
	protected function tag_sort( $a, $b ) 
	{
		return strcmp( $a['SYSTEM_NAME'], $b['SYSTEM_NAME'] );
	}
}
?>
