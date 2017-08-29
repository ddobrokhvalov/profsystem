<?php
/**
 * Модуль "Альтернативное меню"
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class m_alt_menu extends module
{
	/**
	 * "Ключевой" хэш декораторов. Исключен декоратор 'lang'
	 * @var array
	 */
	protected $decorators = array( 'version' => 1, 'block' => 1 );
	
	/**
	* Объект шаблонизатора модуля
	* 
	* @todo Не перенести ли его в module.php? И не создавать ли его в init()?
	*/
	protected $tpl;
	
	/**
	* Вспомогательный массив для пробега по дереву от листьев к корню
	*/
	protected $alt_menu_id_array = array();
	
	/**
	* Вспомогательный массив для пробега по дереву от корня к листьям
	*/
	protected $alt_parent_id_array = array();
	
	/**
	* Диспетчер модуля
	*/
	protected function content_init()
	{
		// Инициализация шаблонизатора
		$this -> tpl = new smarty_ee_module( $this );
		
		// Заполнение вспомогательных массивов
		$this -> make_arrays();
		
		$depth = min( max( intval( $this -> view_param['depth'] ), 1 ), 10 );
		
		// Построение дерева меню
		$menu_array = $this -> get_level_array( 0, 1, $depth );
		
		// Вывод меню в шаблон
		$this -> tpl -> assign( 'menu_array', $menu_array );
		
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir.'menu.tpl' );
	}
	
	/**
	* Постоение вспомогательных массивов
	*/
	protected function make_arrays()
	{
		$query_alt_pages = $this -> get_module_sql( $this -> module_table.'.*', '', 'order by MENU_ORDER asc' );
		$alt_pages = db::sql_select( $query_alt_pages, $this -> get_module_binds() );
		
		$current_page = 0;
		foreach ( $alt_pages as $page )
		{
			// Помечаем выделенную страницу, запоминаем ее номер
			if ( $page['PAGE_ID'] == $this -> env['page_id'] )
				$current_page = $page['ALT_MENU_ID'];
			
			// Заполняем вспомогательные массивы
			$this -> alt_menu_id_array[$page['ALT_MENU_ID']] = $page;
			$this -> alt_parent_id_array[$page['PARENT_ID']][] = $page;
		}
		
		if ( $current_page )
		{
			// Двигаемся по дереву от текущей ветки до корня, помечая пройденные ветки как "открытые"
			$path_page = $this -> alt_menu_id_array[$current_page];
			while ( $parent_id = $path_page['PARENT_ID'] )
			{
				$this -> alt_menu_id_array[$parent_id]['SELECTED'] = 1;
				$path_page = $this -> alt_menu_id_array[$parent_id];
			}
		}
	}
	
	/**
	* Построение дерева меню (рекурсия)
	*/
	protected function get_level_array( $page_id, $level, $depth )
	{
		$level_array = array();
		
		if( is_array( $this -> alt_parent_id_array[$page_id] ) )
		{
			foreach( $this -> alt_parent_id_array[$page_id] as $item )
			{
				// Для ссылок типа "раздел" формируем ссылку на выбраннуй страницу
				if ( intval( $item['PAGE_ID'] ) > 0 )
					$item['URL'] = $this -> get_url_by_page( $item['PAGE_ID'] );
				
				// Помечаем текущую страницу
				if ( $item['PAGE_ID'] == $this -> env['page_id'] )
					$item['CURRENT'] = 1;
				
				// Переносим информацию о "раскрытости" ветви в выходной массив
				if ( $this -> alt_menu_id_array[$item['ALT_MENU_ID']]['SELECTED'] )
					$item['SELECTED'] = 1;
				
				// Если не превышен параметр "глубина", поднимаемся дальше по дереву
				if ( $level < $depth && is_array( $this -> alt_parent_id_array[$item['ALT_MENU_ID']] ) )
					$item['CHILDREN'] = $this -> get_level_array( $item['ALT_MENU_ID'], $level + 1, $depth );
				
				array_push( $level_array, $item );
			}
		}
		return $level_array;
	}
	
	/**
	 * Добавляем в уникальное имя кэша идентификаотр раздела
	 */
	protected function ext_get_hash_code(){
		return $this -> env['page_id'];
	}
}
?>