<?php
/**
 * Утилита отображения системных разделов
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class auth_system_section_tool extends tool
{
	/**
	 * Объект шаблонизатора
	 */
	protected $tpl;
	
	/**
	 * Число колонок в списке сисметных разделов
	 * 
	 * @var int
	 */
	protected $columns_count = 2;
	
	/**
	 * Убираем дерево
	 */
	public $is_menu = false;
	
	/**
	 * Конструктор
	 *
	 * @see tool::__constructor()
	 */
	function __construct( $obj, $full_object = '' )
	{
		parent::__construct( $obj, $full_object );
	}
	
	/**
	 * Действие по умолчанию - список файлов
	 */
	protected function action_index()
	{
		$system_section_type = $_REQUEST['SECTION_TYPE'];
		
		// Получаем список системных разделов, на который данный пользователь имеет права
		$auth_system_section_obj = object::factory( 'AUTH_SYSTEM_SECTION' );
		$system_sections = $auth_system_section_obj -> get_allow_system_sections( $system_section_type );
 		$auth_system_section_obj -> __destruct();
 		
 		if ( !count( $system_sections ) && $system_section_type != 'content' )
 		{
			$this->title=metadata::$lang["lang_permission_denied"];
			$this->body=metadata::$lang["lang_permission_denied"];
			return;
 		}
		
		foreach ( $system_sections as &$section )
			if ( $section['SYSTEM_NAME'] )
				$section['URL'] = 'index.php?obj=' . $section['SYSTEM_NAME'] . ( $section['FILTER_PRESET'] ? '&' . $section['FILTER_PRESET'] : '' ) . ( $section['OBJECT_PARAM'] ? '&' . $section['OBJECT_PARAM'] : '' );
		
		$section_tree = get_tree::get( $system_sections, 'AUTH_SYSTEM_SECTION_ID', 'PARENT_ID', 'SECTION_ORDER' );
		
		// Удаляем из дерева записи ниже второго уровня. Для красоты.
		foreach ( $section_tree as $section_tree_index => $section_tree_item )
			if ( $section_tree_item['TREE_DEEP'] > 1 )
				unset( $section_tree[$section_tree_index] );
		
		// Предворяем таблицу справочников списком программных модулей
		if ( $system_section_type == 'content' )
		{
			$module_tree = array( array( 'TITLE' => metadata::$lang['lang_inf_block_table'], 'TREE_DEEP' => 0, 'URL' => 'index.php?obj=INF_BLOCK' ) );
			
			if (metadata::$objects['PRG_MODULE']) {
				$prg_module_obj = object::factory( 'PRG_MODULE' );
				list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
					$prg_module_obj -> ext_field_selection( 'TITLE', 1 );
				$prg_module_obj -> __destruct();
				
				$prg_modules = db::replace_field(db::sql_select( "
					select PRG_MODULE.*, " . $dec_field . " as \"_TITLE\"
					from PRG_MODULE " . $dec_join[0] . "
					order by " . $dec_field, $dec_binds ), 'TITLE', '_TITLE');
							
				foreach ( $prg_modules as $module )
					if ( $module['IS_ELEMENTS'] )
						$module_tree[] = array( 'TITLE' => $module['TITLE'], 'TREE_DEEP' => 1, 'URL' => 'index.php?obj=' . $module['SYSTEM_NAME'] );
			}			
			
			$section_tree = array_merge( $module_tree, $section_tree );
		}
		
		// Строим массив разделов сгруппированный по корневым разделам
		$section_tree_by_parent = array(); $parent_section_index = 0;
		foreach ( $section_tree as $section_tree_item )
		{
			if ( $section_tree_item['TREE_DEEP'] == 0 ) $parent_section_index++;
			$section_tree_by_parent[$parent_section_index][] = $section_tree_item;
		}
		
		// Для нужд верстки выделяем последний элемент в группе
		foreach ( $section_tree_by_parent as $section_tree_id => $section_tree_item )
			$section_tree_by_parent[$section_tree_id][count( $section_tree_item ) - 1]['LAST_NODE'] = 1;
		
		// Инициализируем массив колонок пустыми массивами
		$section_columns = array();
		for ( $j = 0; $j < $this -> columns_count; $j++ )
			$section_columns[$j]['COLUMN'] = array();
		
		// Вычисляем примерное число разделов в каждой колонке
		$sections_per_column = count( $section_tree ) / $this -> columns_count;
		
		// Раскидываем разделы по колонкам, так что бы в каждой колонке
		// число разделов превышало допустимое на минимальную величину
		foreach ( $section_tree_by_parent as $section_tree_item )
		{
			for ( $j = 0; $j < $this -> columns_count; $j++ )
			{
				if ( count( $section_columns[$j]['COLUMN'] ) < $sections_per_column )
				{
					$section_columns[$j]['COLUMN'] = array_merge( $section_columns[$j]['COLUMN'], $section_tree_item ); break;
				}
			}
		}
		
		$this -> title = metadata::$lang["lang_{$system_section_type}"];
		
		$this -> tpl = new smarty_ee( metadata::$lang );
		
		$this -> tpl -> assign( 'section_columns', $section_columns );
		$this -> tpl -> assign( 'column_width', round( 100 / $this -> columns_count ) );
		$this -> tpl -> assign( 'title', $this -> title );
		$this -> tpl -> assign( 'icon', $system_section_type . '.gif' );
		
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir.'te/auth_system_section/auth_system_section.tpl' );
	}
	
	/**
	 * Доступ к утилите должны иметь все пользователи
	 *
	 * @see	object::is_permitted_to
	 */
	 public function is_permitted_to($ep_type, $pk="", $throw_exception=false){
		return true;
	}
}
?>
