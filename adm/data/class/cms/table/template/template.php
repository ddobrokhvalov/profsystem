<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Шаблоны"
 *
 * @package    RBC_Contents_5_0
 * @subpackage cms
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class template extends table_translate
{
	
	/**
	* Путь к директории шаблонов
	* @var string
	*/
	
	public $template_root_dir = "{adm_data_server}page_tpl/";
	
	/**
	* Конструктор
	*/
	
	function __construct(&$full_object, $decorators){
		parent::__construct($full_object, $decorators);
		system_params::parse_template_param_for_object ($this, array('template_root_dir'));
	}
	
	/**
	 * В отличие от базового метода происходит попытка создать каталог с шаблонами
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		$template_dir = $this -> field -> get_prepared( $raw_fields[$prefix.'TEMPLATE_DIR'], metadata::$objects[$this->obj]['fields']['TEMPLATE_DIR'] );
		
		template::create_template_dir( $this, $this->template_root_dir, $template_dir );
		
		try
		{
			$last_id = parent::exec_add( $raw_fields, $prefix );
		}
		catch ( Exception $e )
		{
			template::delete_template_dir( $this, $this->template_root_dir, $template_dir );
			throw new Exception( $e -> getMessage() );
		}
		
		return $last_id;
	}
	
	/**
	 * В отличие от базового метода происходит попытка переименовать каталог с шаблонами
	 *
	 * @see table::exec_change()
	 */
	public function exec_change( $raw_fields, $prefix, $pk )
	{
		$template_dir = $this -> field -> get_prepared( $raw_fields[$prefix.'TEMPLATE_DIR'], metadata::$objects[$this->obj]['fields']['TEMPLATE_DIR'] );
		$template_dir_old = db::sql_select('select TEMPLATE_DIR from TEMPLATE where TEMPLATE_ID = :template_id', array( 'template_id' => $pk['TEMPLATE_ID'] ) );
		$template_dir_old = $template_dir_old[0]['TEMPLATE_DIR'];
		
		// Если изменился каталог с шаблонами, пытаемся его переименовать
		if ( $template_dir != $template_dir_old )
			template::rename_template_dir( $this, $this->template_root_dir, $template_dir_old, $template_dir );
		
		try
		{
			parent::exec_change( $raw_fields, $prefix, $pk );
		}
		catch ( Exception $e )
		{
			if ( $template_dir != $template_dir_old )
				template::rename_template_dir( $this, $this->template_root_dir, $template_dir, $template_dir_old );
			throw new Exception( $e -> getMessage() );
		}
	}
	
	/**
	 * В отличие от базового метода происходит попытка скопировать каталог с шаблонами
	 *
	 * @see table::exec_copy()
	 */
	public function exec_copy( $raw_fields, $prefix, $pk )
	{
		$template_dir = $this -> field -> get_prepared( $raw_fields[$prefix.'TEMPLATE_DIR'], metadata::$objects[$this->obj]['fields']['TEMPLATE_DIR'] );
		$template_dir_old = db::sql_select('select TEMPLATE_DIR from TEMPLATE where TEMPLATE_ID = :template_id', array( 'template_id' => $pk['TEMPLATE_ID'] ) );
		$template_dir_old = $template_dir_old[0]['TEMPLATE_DIR'];
		
		$last_id = parent::exec_copy( $raw_fields, $prefix, $pk );
		
		template::copy_template_dir( $this, $this->template_root_dir, $template_dir_old, $template_dir );
		
		return $last_id;
	}
	
	/**
	 * Добавляем в заголовок таблицы колонки "Файлы шаблонов" и обновление разделов
	 *
	 * @see table::ext_index_header()
	 */
	public function ext_index_header( $mode )
	{
		$header = array();
		
		$header['link_to_file_manager'] = array( 'title' => metadata::$lang['lang_template_files'], 'type' => '_link' );
		$header['mass_generate'] = array( 'title' => metadata::$lang['lang_refresh'], 'type' => '_link' );
		
		if ( $this -> auth -> is_main_admin )
			$header['page'] = array( 'title' => metadata::$lang['lang_page_table'], 'type' => '_link' );
		
		return $header;
	}
	
	/**
	 * Финализация удаления
	 *
	 * Происходит попытка удалить каталог с шаблонами
	 *
	 * @see table::ext_finalize_delete()
	 */
	public function ext_finalize_delete( $pk, $partial = false )
	{
		$template_dir_old = db::sql_select( '
			select TEMPLATE_DIR from TEMPLATE where TEMPLATE_ID = :template_id',
			array( 'template_id' => $pk['TEMPLATE_ID'] ) );
		$template_dir_old = $template_dir_old[0]['TEMPLATE_DIR'];
		
		template::delete_template_dir( $this, $this->template_root_dir, $template_dir_old );
		
		parent::ext_finalize_delete( $pk, $partial );
	}
	
	/**
	 * Добавляем в таблицу колонки "Файлы шаблонов" и обновление разделов
	 *
	 * @see table::get_index_ops()
	 */
	public function get_index_ops( $record )
	{
		$ops = $this -> call_parent( 'get_index_ops', array( $record ) );
		
		$ops = array_merge( array( 'link_to_file_manager' => array( 'url' => "index.php?obj=FM&path=".urlencode( "/page_tpl/{$record['TEMPLATE_DIR']}" ) ) ), $ops );
		$ops = array_merge( array( 'mass_generate' => array( 'url' => lib::make_request_uri(array( 'obj' => $this -> obj ) ) . '&action=distributed&do_op=mass_generate&do_status=' . base64_encode( serialize( array( 'TEMPLATE_ID' => $record['TEMPLATE_ID'] ) ) ) ) ), $ops );
		if ( $this -> auth -> is_main_admin )
			$ops = array_merge( array( 'page' => array( 'url' => $this -> url -> get_url( 'page', array( 'pk' => array( 'TEMPLATE_ID' => $record['TEMPLATE_ID'] ) ) ) ) ), $ops );
		
		return $ops;
	}
	
	/**
	 * Карточка формы разнесения шаблонов
	 */
	public function action_page()
	{
		$pk = $this -> primary_key -> get_from_request( true );
		if ( !$this->auth->is_main_admin )
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_operation_not_permitted_page"].": (".$this->primary_key->pk_to_string($pk).")");
		
		// Получаем список шаблонов
		$template_obj = object::factory( 'TEMPLATE' );
		$template_list = $template_obj -> get_index_records( $none, 'select2', '' );
		$template_obj -> __destruct();
		$template_list = lib::array_reindex( $template_list, 'TEMPLATE_ID' );
		
		// Получаем список типов шаблонов
		$template_type_obj = object::factory( 'TEMPLATE_TYPE' );
		$template_type_list = $template_type_obj -> get_index_records( $none, 'select2', '' );
		$template_type_obj -> __destruct();
		$template_type_list = lib::array_reindex( $template_type_list, 'TEMPLATE_TYPE_ID' );
		
		// Получаем информацию о текущем шаблоне
		$cur_template = $this -> full_object -> get_change_record( $pk );
		
		$cur_template['TEMPLATE_TITLE'] = $template_list[$cur_template['TEMPLATE_ID']]['_TITLE'];
		$cur_template['TEMPLATE_TYPE_TITLE'] = $template_type_list[$cur_template['TEMPLATE_TYPE_ID']]['_TITLE'];
		
		// Получаем дополнительные ограничения по версиям разделов
		$page_obj = object::factory( 'PAGE' );
		$version_where_clause = $page_obj -> version_where_clause();
		$version_where_clause_binds = $page_obj -> version_where_clause_binds();
		$page_obj -> __destruct();
		
		// Получаем список всех разделов (кроме ссылок) на выбранном языке
		$page_list = db::sql_select( '
			select * from PAGE where LANG_ID = :lang_id and PAGE_TYPE <> :page_type ' . $version_where_clause,
			array( 'lang_id' => $cur_template['LANG_ID'], 'page_type' => 'link' ) + $version_where_clause_binds );
		
		// Получаем массив разделов, сгруппированный по номеру раздела
		$page_list = lib::array_reindex( $page_list, 'PAGE_ID' );
		
		// Получаем список разделов, сгруппированный по родителю и номеру раздела
		$parent_list = lib::array_group( $page_list, 'PARENT_ID' );
		
		// Строим дерево разделов, используя библиотечную функцию
		$page_tree = get_tree::get( $page_list, 'PAGE_ID', 'PARENT_ID', 'PAGE_ORDER' );
		
		// Получаем информацию о шаблонах и областях
		list( $tmpl_info, $area_info ) = self::get_areas_info();
		
		// Бежим по полученному дереву, добавляя необходимую информацию
		$counter = 1;
		foreach ( $page_tree as &$record )
		{
			// Счетчик разделов
			$record['_number'] = $counter++;
			
			if ( $record['TEMPLATE_ID'] == $pk['TEMPLATE_ID'] )
			{
				$record['class'] = 'block_not_area'; // Для текущего шаблона стиль остается дефолтным
			}
			elseif ( $tmpl_info[$record['TEMPLATE_ID']] == $tmpl_info[$pk['TEMPLATE_ID']] )
			{
				$record['class'] = 'block_this_module'; // Если тип шаблона такой же, то зеленый
			}
			else if ( count( $area_info[$tmpl_info[$record['TEMPLATE_ID']]] ) == count( array_intersect( $area_info[$tmpl_info[$pk['TEMPLATE_ID']]], $area_info[$tmpl_info[$record['TEMPLATE_ID']]] ) ) )
			{
				$record['class'] = 'block_this_area'; // Если в типе шаблона нового шаблона области те же самые (или те же самые плюс еще какие-то), то желтый
			}
			else
			{
				$record['class'] = 'block_other_module'; // Если в типе шаблона нового шаблона нет хотя бы одной области исходного шаблона, то красный
			}
			
			if ( $record['TEMPLATE_ID'] != $pk['TEMPLATE_ID'] )
				$record['_group'] = array( 'id' => $record['PAGE_ID'] . '_' . $record['VERSION'] );
			
			// Иконки разделов выбираются в зависимости от типа раздела и наличия у него потомков
			if ( $record['PAGE_TYPE'] == 'link' )
				$record['_icon'] = 'link';
			elseif ( $record['PAGE_TYPE'] == 'folder' )
				$record['_icon'] = isset( $parent_list[$record['PAGE_ID']] ) ? 'folder' : 'leaf_folder';
			elseif ( $record['PAGE_TYPE'] == 'page' )
				$record['_icon'] = isset( $parent_list[$record['PAGE_ID']] ) ? 'page' : 'leaf_page';
			
			// Выводится в шаблон информация о шаблоне и типе шаблона
			$template = $template_list[$record['TEMPLATE_ID']]['_TITLE'];
			$template_type = $template_type_list[$tmpl_info[$record['TEMPLATE_ID']]]['_TITLE'];
			
			$record['TEMPLATE'] = $template . ' (' . $template_type . ')';
		}
		
		$headers = array(
			'_number' => array ( 'title' => 'N' ),
			'TITLE' => array ( 'title' => metadata::$lang['lang_name'], 'is_main' => '1' ),
			'TEMPLATE' => array ( 'title' => metadata::$lang['lang_template'] . ' (' . metadata::$lang['lang_template_type_link_name'] . ')' ),
			'_group' => array( 'title' => '', 'type' => '_group' ) );
		
		$page_header_tpl = new smarty_ee( metadata::$lang );
		$page_header_tpl -> assign( $cur_template );
		$html_page_header = $page_header_tpl -> fetch( $this -> tpl_dir . 'cms/template/page.tpl' );
		
		$html_table_body = html_element::html_table( array( 'header' => $headers, 'list' => $page_tree, 'counter' => $counter - 1, 'html_hidden' => $this -> url -> get_hidden( 'paged', array( 'pk' => array( 'for_subtrees' => '0', 'all_version' => '1' ) ) ) ), $this -> tpl_dir . 'core/html_element/html_table.tpl', $this -> field );
		
		$operations = array();
		if (!$this->is_record_blocked($pk, false)) 
			$operations[] = array( 'name' => 'apply', 'alt' => metadata::$lang['lang_apply'], "onClick"=>"javascript:if ( CheckFill() ) {remove_unblock_record (); document.forms['checkbox_form'].submit() };  return false" );
		
		$operations = array_merge( $operations, $this -> get_record_operations() );
		
		$title = metadata::$lang['lang_page_table'];
		$card_title = $this -> full_object -> get_record_title( $pk );
		
		$this -> path_id_array[] = array( 'TITLE' => $card_title );
		
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'title', $card_title );
		$tpl -> assign( 'tabs', $this -> get_header_tabs( $pk, 'page' ) );
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir.'core/html_element/html_operation.tpl') );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir.'core/html_element/html_operation.tpl') );
		$tpl -> assign( 'form', $html_page_header . $html_table_body );
		$tpl -> assign( 'form_name', 'checkbox_form' );
		
		$this -> set_blocked_tpl_params( $tpl, $pk );
		
		$this -> title = $title;
		$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' );
	}
	
	/**
	 * Действие - разнесение блока
	 */
	public function action_paged()
	{
		$pk = $this -> primary_key -> get_from_request( true );
		if ( !$this->auth->is_main_admin )
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_operation_not_permitted_page"].": (".$this->primary_key->pk_to_string($pk).")");
		
		// Получаем информацию о текущем шаблоне
		$cur_template = $this -> full_object -> get_change_record( $pk );
		
		// Получаем список всех разделов на выбранном языке
		$pages_all = db::sql_select( '
			select * from PAGE where LANG_ID = :lang_id and PAGE_TYPE <> :page_type',
			array( 'lang_id' => $cur_template['LANG_ID'], 'page_type' => 'link' ) );
		
		$page_obj = object::factory( 'PAGE' );
		
		// Получаем массив разделов, сгруппированный по номеру и версии раздела
		$page_obj -> page_id_array = lib::array_reindex( $pages_all, 'PAGE_ID', 'VERSION' );
		// Получаем список разделов, сгруппированный по родителю и номеру раздела
		$page_obj -> parent_id_array = lib::array_reindex( $pages_all, 'PARENT_ID', 'PAGE_ID' );
		
		// Собираем массив разделов, область которых нужно вставить выбранный блок
		$page_id_version_array = array();
		
		// За основу берем список идентификаторов разделов и версий, переданных в запросе
		foreach ( $page_obj -> primary_key -> get_group_from_request() as $group_pk )
		{
			list( $page_id, $version ) = split( '_', $group_pk['pk']['PAGE_ID'] );
			
			$page_id_version_array[$page_id][$version] = 1;
			
			// В случае установленного флажка "Все версии" по возможности добавляем в массив рабочую версию раздела
			if ( $_REQUEST['all_version'] && $version == 1 && isset( $page_obj -> page_id_array[$page_id][0] ) )
				$page_id_version_array[$page_id][0] = 1;
			
			// В случае установленного флажка "Поддеревья" добавляем в массив информацию о потомках
			if ( $_REQUEST['for_subtrees'] )
				$page_id_version_array += $page_obj -> get_subtrees( $page_id, $_REQUEST['all_version'] );
		}
		
		// Определяем права на изменение и публикацию разделов
		$page_ids = array_keys( $page_id_version_array );
		
		// Получаем информацию о шаблонах и областях
		list( $tmpl_info, $area_info ) = self::get_areas_info();
		
		$errors = array();
		foreach ( $page_id_version_array as $page_id => $versions )
		{
			foreach ( $versions as $version_id => $version_value )
			{
				// Определяем области, которые окажутся лишними при смене шаблона
				$area_diff = array_diff( $area_info[$tmpl_info[$page_obj -> page_id_array[$page_id][$version_id]['TEMPLATE_ID']]], $area_info[$tmpl_info[$pk['TEMPLATE_ID']]] );
				
				// Очищаем лишние области
				foreach ( $area_diff as $area_id )
					$page_obj -> delete_page_params( $page_id, $version_id, $area_id );
				
				// Обновление шаблона и времени изменения раздела
				db::update_record( 'PAGE', array( 'TEMPLATE_ID' => $pk['TEMPLATE_ID'], 'TIMESTAMP' => time() ), '',
					 array( 'PAGE_ID' => $page_id, 'VERSION' => $version_id ) );
			}
		}
		
		$page_obj -> __destruct();
		
		$this -> url -> redirect( 'page', array( 'pk' => $pk ) );
	}
	
	/**
	 * Метод возвращает массив навигационных вкладок для карточки записи
	 * 
	 * @see table::get_header_tabs()
	 */
	public function get_header_tabs( $pk, $mark_select = 'change' )
	{
		$header_tabs = $this -> call_parent( 'get_header_tabs', array( $pk, $mark_select ) );
		
		if ( $this -> auth -> is_main_admin )
			$header_tabs[] = array( 'title' => metadata::$lang['lang_page_table'], 'url' => $this->url->get_url( 'page', array( 'pk' => $pk ) ), 'active' => $mark_select == 'page' );
		
		return $header_tabs;
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Статический метод для создания каталога шаблонов. При неудаче вызывает исключение
	 *
	 * @param string $template_root_dir	Корневой каталог
	 * @param string $template_dir		Каталог для создания
	 */
	public static function create_template_dir( $object, $template_root_dir, $template_dir )
	{
		// Вылетаем, произошла ошибка при создании несуществующего корневого каталога
		if ( !file_exists( $template_root_dir ) )
			if ( !( @mkdir( $template_root_dir ) && @chmod( $template_root_dir, 0777 ) ) )
				throw new Exception( $object -> te_object_name.': "'.metadata::$lang['lang_can_not_create_dir'].': '.$template_root_dir.'"' );
		// Вылетаем, если создаваемый каталог уже существует
		if ( file_exists( $template_root_dir.$template_dir ) )
			throw new Exception( $object -> te_object_name.': "'.metadata::$lang['lang_catalog_exists'].': '.$template_root_dir.$template_dir.'"' );
		// Вылетаем, если произошла ошибка при создании каталога
		if ( !( @mkdir( $template_root_dir.$template_dir ) && @chmod( $template_root_dir.$template_dir, 0777 ) ) )
			throw new Exception( $object -> te_object_name.': "'.metadata::$lang['lang_can_not_create_dir'].': '.$template_root_dir.$template_dir );
	}
	
	/**
	 * Статический метод для переименования каталога шаблонов. При неудаче вызывает исключение
	 *
	 * @param string $template_root_dir	Корневой каталог
	 * @param string $template_dir_old	Каталог-источник
	 * @param string $template_dir		Каталог-назначение
	 */
	public static function rename_template_dir( $object, $template_root_dir, $template_dir_old, $template_dir )
	{
		// Вылетаем, если каталог-назначение уже существует
		if ( file_exists( $template_root_dir.$template_dir ) )
			throw new Exception( $object -> te_object_name.': '.metadata::$lang['lang_catalog_exists'].': "'.$template_root_dir.$template_dir.'"' );
		// Вылетаем, если каталог-источник не существует
		if ( !file_exists( $template_root_dir.$template_dir_old ) )
			throw new Exception( $object -> te_object_name.': '.metadata::$lang['lang_catalog_not_exists'].': "'.$template_root_dir.$template_dir_old.'"' );
		// Вылетаем, если произошла ошибка при переименования каталога
		if ( !@rename( $template_root_dir.$template_dir_old, $template_root_dir.$template_dir ) )
			throw new Exception( $object -> te_object_name.': '.metadata::$lang['lang_catalog_can_not_rename'].': "'.$template_root_dir.$template_dir_old.'"' );
	}
	
	/**
	 * Статический метод для удаления каталога шаблонов
	 *
	 * @param string $template_root_dir	Корневой каталог
	 * @param string $template_dir		Каталог для удаления
	 */
	public static function delete_template_dir( $object, $template_root_dir, $template_dir )
	{
		// Если каталог существует, пытаемся его удалить
		if ( file_exists( $template_root_dir.$template_dir ) )
		{
			filesystem::rm_r( $template_root_dir.$template_dir, false, false );
			
			// Проверяем, удален ли каталог
			if ( file_exists( $template_root_dir.$template_dir ) )
				throw new Exception( $object -> te_object_name.': '.metadata::$lang['lang_catalog_delete_error'].': "'.$template_root_dir.$template_dir.'"' );
		}
	}
	
	/**
	 * Статический метод для копирования каталога шаблонов
	 *
	 * @param string $template_root_dir	Корневой каталог
	 * @param string $template_dir_old	Каталог-источник
	 * @param string $template_dir		Каталог-назначение
	 */
	public static function copy_template_dir( $object, $template_root_dir, $template_dir_old, $template_dir )
	{
		// Если каталог существует, пытаемся скопировать его содержимое.
		// Метод filesystem::cp_r используется с пареметром $without_root = true,
		// так как предполагается, что каталог-назначение уже создан на предыдущем этапе
		if ( file_exists( $template_root_dir.$template_dir_old ) )
		{
			$old_dir_count = filesystem::cp_r( $template_root_dir.$template_dir_old, $template_root_dir.$template_dir, false, true );
			$new_dir_count = count( filesystem::ls_r( $template_root_dir.$template_dir, false, true ) );
			
			// Сравниваем число файлов в исходном и в созданом каталоге
			if ( $new_dir_count != $old_dir_count )
				throw new Exception( $object -> te_object_name.': '.metadata::$lang['lang_catalog_copy_error'].': "'.$template_root_dir.$template_dir.'"' );
		}
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Если требуется подсветка шаблонов в зависимости от наборов областей типов шаблонов, то делаем это
	 *
	 * @see table::get_index_records()
	 */
	public function get_index_records(&$request, $mode, $list_mode, $include=0, $exclude=array()){
		$records=$this -> call_parent( 'get_index_records' , array(&$request, $mode, $list_mode, $include, $exclude) );
		if($list_mode["TEMPLATE_ID"]){
			// Получаем исходные данные
			list($tmpl_info, $area_info)=self::get_areas_info();
			$cur_tmpl_areas=$area_info[$tmpl_info[$list_mode["TEMPLATE_ID"]]];
			// Пробегаем по шаблонам и настраиваем их вид
			foreach($records as &$record){
				if($record["TEMPLATE_ID"]==$list_mode["TEMPLATE_ID"]){
					$class=""; // Для текущего шаблона стиль остается дефолтным
				}elseif($tmpl_info[$record["TEMPLATE_ID"]]==$tmpl_info[$list_mode["TEMPLATE_ID"]]){
					$class="status_green"; // Если тип шаблона такой же, то зеленый
				}else{
					// Если в типе шаблона нового шаблона области те же самые (или те же самые плюс еще какие-то), то желтый
					if(count(array_diff($cur_tmpl_areas, $area_info[$tmpl_info[$record["TEMPLATE_ID"]]]))==0) {
						$class="status_yellow";
					}else{
						$class="status_red"; // Если в типе шаблона нового шаблона нет хотя бы одной области исходного шаблона, то красный
					}
				}
				$record["_class"]=$class;
			}
		}
		return $records;
	}
	
	/**
	 * Подготовка списка операций над записями
	 * 
	 * @return array
	 */
	public function get_index_operations()
	{
		$operations = $this -> call_parent( 'get_index_operations' );
		
		$operations['mass_generate'] = array( 'name' => 'mass_generate', 'alt' => metadata::$lang['lang_refresh_all_pages'],
			'url' => lib::make_request_uri( array( 'obj' => $this -> obj ) ) . '&action=distributed&do_op=mass_generate' );
		
		return $operations;
	}
	
	/**
	 * Получить информацию о шаблонах и областях для понимания, что делать при смене шаблона у раздела
	 *
	 * Информация о шаблонах - список идентификаторов типов шаблонов индексированных идентификаторами шаблонов<br>
	 * Информация об областях - списки идентификаторов областей, сгруппированные в общем списке по типам шаблонов, к которым они привязаны
	 *
	 * @see table::get_index_records()
	 * @return array
	 */
	public static function get_areas_info(){
		// О шаблонах
		$templates=db::sql_select("SELECT * FROM TEMPLATE");
		foreach($templates as $template){
			$tmpl_info[$template["TEMPLATE_ID"]]=$template["TEMPLATE_TYPE_ID"];
		}
		// Об областях
		$areas=db::sql_select("SELECT TEMPLATE_AREA.*, TEMPLATE_AREA_MAP.TEMPLATE_TYPE_ID FROM TEMPLATE_AREA, TEMPLATE_AREA_MAP WHERE TEMPLATE_AREA.TEMPLATE_AREA_ID=TEMPLATE_AREA_MAP.TEMPLATE_AREA_ID");
		$area_info=lib::array_group($areas, "TEMPLATE_TYPE_ID");
		foreach($area_info as $tt_id=>$area){
			$area_info[$tt_id]=array_keys(lib::array_reindex($area, "TEMPLATE_AREA_ID"));
		}
		// Прочекиваем пустым массивом те типы шаблонов, к которым еще не привязана ни одна область
		$template_types=array_unique($tmpl_info);
		foreach($template_types as $tt){
			if(!is_array($area_info[$tt])){
				$area_info[$tt]=array();
			}
		}
		// Прочекиваем пустым массивом TEMPLATE_TYPE_ID="", чтобы ссылки и папки успешно проходили через этот метод
		$area_info[""]=array();
		return array($tmpl_info, $area_info);
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Информация о распределенной операции "Массовая генерация разделов"
	 *
	 * @param array $status		информация об операции, включая "TEMPLATE_ID", если нужно генерировать не все разделы, а только разделы определенного шаблона
	 * @return array
	 */
	public function mass_generate_info($status)
	{
		list($t_id_clause, $t_id_bind)=$this->full_object->mass_generate_get_t_id_clause($status["TEMPLATE_ID"]);
		$total=db::sql_select("SELECT COUNT(*) AS COUNTER FROM PAGE WHERE PAGE_TYPE<>'link' {$t_id_clause}", $t_id_bind);
		return array("title"=>metadata::$lang["lang_page_refresh"], "total"=>$total[0]["COUNTER"], "for_once"=>15, "success_message" => metadata::$lang["lang_pages_refresh_success"]);
	}

	/**
	 * Список записей для текущей итерации распределенной операции "Массовая генерация разделов"
	 *
	 * @param array $status		информация об операции, включая "TEMPLATE_ID", если нужно генерировать не все разделы, а только разделы определенного шаблона
	 * @param int $from			первая запись в текущей итерации
	 * @param int $offset		сколько записей вынимать в текущей итерации
	 * @return array
	 */
	public function mass_generate_list($status, $from, $offset)
	{
		list($t_id_clause, $t_id_bind)=$this->full_object->mass_generate_get_t_id_clause($status["TEMPLATE_ID"]);
		$records=db::sql_select("SELECT * FROM PAGE WHERE PAGE_TYPE<>'link' {$t_id_clause} ORDER BY PAGE_ID, VERSION DESC LIMIT {$from}, {$offset}", $t_id_bind);
		return $records;
	}

	/**
	 * Генерация конкретного раздела в распределенной операции "Массовая генерация разделов"
	 *
	 * @param array $page		запись раздела, которого нужно сгенерировать в данной элементарной операции
	 */
	public function mass_generate_item($page, $status)
	{
		$page_obj = object::factory("PAGE");
		
		$page_obj->exec_gen_page($page["PAGE_ID"], $page["VERSION"]);
		$message=$page_obj->get_generate_page_message( $page );
		$page_obj->__destruct();
		
		return array( "message" => $message );
	}

	/**
	 * Вспомогательный метод для массовой генерации разделов, который позволяет получить кляузы для ограничения выборки разделов по шаблону
	 *
	 * @param int $template_id	идентификатор шаблона или ноль (или пустая строка), как признак того, что разделы не нужно ограничивать по шаблону
	 * @return array
	 */
	private function mass_generate_get_t_id_clause($template_id)
	{
		if($template_id!=0){
			$t_id_clause=" AND TEMPLATE_ID=:template_id ";
			$t_id_bind=array("template_id"=>$template_id);
		}else{
			$t_id_bind=array();
		}
		return array($t_id_clause, $t_id_bind);
	}
	
	/**
	* Дополняем данные для экспорта содержимым файлов шаблонов
	*/
	
	public function get_export_add_data_xml($pk) {
		$xml = $this -> call_parent( 'get_export_add_data_xml', array( $pk ) );
		
		$record = $this->get_change_record($pk);
		
		$xml .= self::get_files_xml_for_export($this->template_root_dir.$record['TEMPLATE_DIR']);
		return $xml;
	}
	
	/**
	* Возвращает все содержимое файлов директории $dir в формате XML для экспорта
	*
	* @todo Кажется этот метод нужно перенести куда-нить в более хорошее место
	*
	* @param string $dir Путь к директории
	* @return string XML
	*/
	
	public static function get_files_xml_for_export($dir) {
		$xml='';
		$temp_files = filesystem::ls_r($dir, true, true);
		if (sizeof($temp_files)) 
			foreach ($temp_files as $file) {
				if ($file['is_dir'] || !is_file($file['name'])) continue;
				
				$xml .= "<FILE FILE_NAME=\"{$file['pure_name']}\"><![CDATA[".base64_encode(file_get_contents($file['name']))."]]></FILE>\n";
			}
		return $xml;
	}
	
	/**
	* Метод импорта данных из XML - унаследованный метод от table
	* Дополняем данные созданием файлов шаблонов
	* @param array $xml_arr массив данных одной записи, возвращаемый ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return array карта соответствия между id записей из файла импорта и реальными
	*/
	
	public function import_from_xml ($xml_arr, &$import_data) {
		$id_map = $this->call_parent('import_from_xml', array($xml_arr, &$import_data));

		$record = $this->get_change_record(array('TEMPLATE_ID'=>current($id_map)));
		
		$this->create_files_from_import($xml_arr['children'], $this->template_root_dir.$record['TEMPLATE_DIR'], $import_data);
		return $id_map;
	}

	/**
	* Возвращает поля для вставки в таблицу в процессе импорта - унаследованный метод от table
	* Дополняет функционал подменой языка на язык раздела под который данный шаблон импортируется
	* @param array $main_children данные обо всех потомках массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return array Подготовленные данные для вставки в таблицу
	*/
	
	public function get_import_field_values($main_children, &$import_data) {
		$field_values = $this->call_parent('get_import_field_values', array($main_children, &$import_data));
		$field_values['LANG_ID']=$import_data['root_page_info']['LANG_ID'];
		return $field_values;
	}


	/**
	* Возвращает значение для конкретного поля для вставки в таблицу - унаследованный метод от table
	* Дополняет функционал генерацией уникального имени директории шаблона модуля,
	* Кроме этого подменяются данные о ID типа шаблона в случае если был указан параметр импортировать типы шаблонов
	* А также применяется префикс, указанный пользователем для всех названий шаблонов
	* @param string $field_name Название поля
	* @param array $field_children Данные обо всех потомках данного поля массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return mixed Значение, которое вставляется в БД (еще возможно подменить, @see get_import_field_values)
	* @todo Позволил импорту вставить пустые названия шаблонов при закоментированном коде (баг)
	*/

	public function get_import_field_value($field_name, $field_children, &$import_data) {
		$value = $this->call_parent('get_import_field_value', array($field_name, $field_children, &$import_data));
		
		if ($field_name=='TEMPLATE_DIR') 
			$value = upload::get_unique_file_name($this->template_root_dir, $import_data['_f_prefix_for_dirs'].$value);
		elseif ($field_name=='TEMPLATE_TYPE_ID') {
			if ($import_data['info_data']['TEMPLATE_TYPE'])
				$value = $this->full_object->get_import_new_id($value, 'TEMPLATE_TYPE', $import_data);
		}
		elseif ($field_name=='TITLE') {
			foreach ($value as $lang_id=>$title) 
				$value[$lang_id]=$import_data['_f_prefix_for_templates'][$lang_id].$title;
			//foreach ($value as $lang_id=>$value) 
				//$value[$lang_id]=$import_data['_f_prefix_for_templates'][$lang_id].$value[$lang_id];
		}
		return $value;
	}

	
	/**
	* Создает файлы по данным, указанным в файле экспорта
	* @param array $main_children данные обо всех потомках массива записи, возвращаемой ExpatXMLParser
	* @param string $file_path путь на файловой системе, куда необходимо записывать файлы
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	*/
	
	public static function create_files_from_import ($main_children, $file_path, &$import_data) {
		for ($i=0, $n=sizeof($main_children); $i<$n; $i++) 
			if ($main_children[$i]['tag']=='FILE') {
				$file_name = $file_path.'/'.$main_children[$i]['attributes']['FILE_NAME'];
				$file_contents = base64_decode($main_children[$i]['value']);
				if ($import_data['info_data']['BASE64_CONTENT_ENCODING']!=params::$params['encoding']['value'])
					$value=iconv($import_data['info_data']['BASE64_CONTENT_ENCODING'], params::$params['encoding']['value'], $file_contents);
				
				// в случае если экспортировались типы шаблонов - подменяем данные об областях на новые имена
				if ($import_data['info_data']['TEMPLATE_TYPE']) {
					// подгружаем template_type
					$tmp=object::factory('TEMPLATE_TYPE');
					$tmp->__destruct();

					$file_contents=preg_replace_callback('/(\$areas\.)([a-z0-9_]+)/i', array('template_type', '_callback_set_template_area_import_system_names'), $file_contents);
				}
				
				if (!@file_put_contents($file_name, $file_contents)) 
					throw new Exception(metadata::$lang['lang_can_not_create_file'].': '.$file_name);
			}
	}
}
?>