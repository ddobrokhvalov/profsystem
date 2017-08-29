<?php
// Подключаем файл с классом template, содержащим статические методы для работы с каталогами шаблонов
include_once( params::$params['adm_data_server']['value'].'class/cms/table/template/template.php' );

/**
 * Класс для реализации нестандартного поведения таблицы "Значения параметров модулей"
 *
 * @package    RBC_Contents_5_0
 * @subpackage cms
 * @copyright  Copyright (c) 2006 RBC SOFT
 *
 * @todo $template_root_dir перенести в параметры класса
 */
 
class param_value extends table_translate
{
	/**
	 * В отличие от базового метода осуществляется динамическая проверка поля "Значение"
	 * в зависимости от типа параметра модуля. Если параметр типа TEMPLATE, 
	 * происходит попытка создать каталог с шаблонами
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		$module_param_id = $raw_fields[$prefix.'MODULE_PARAM_ID'];
		lib::is_record_exists( 'MODULE_PARAM', array( 'MODULE_PARAM_ID' => $module_param_id ), true );
		
		list( $param_type, $system_name ) = $this -> get_param_info_by_module_param_id( $module_param_id );
		
		$param_value = $this -> get_prepared_value( $raw_fields[$prefix.'VALUE'], $param_type );
		
		if ( $param_type == 'template' )
		{
			$template_root_dir = params::$params['common_data_server']['value'].'module_tpl/'.$system_name.'/';	
			template::create_template_dir( $this, $template_root_dir, $param_value );
		}
		
		try
		{
			$last_id = parent::exec_add( $raw_fields, $prefix );
		}
		catch ( Exception $e )
		{
			if ( $param_type == 'template' )
				template::delete_template_dir( $this, $template_root_dir, $param_value );
			throw new Exception( $e -> getMessage() );
		}
		
		return $last_id;
	}
	
	/**
	 * В отличие от базового метода осуществляется динамическая проверка поля "Значение"
	 * в зависимости от типа параметра модуля. Если параметр типа "template", 
	 * происходит попытка переименовать каталог с шаблонами
	 *
	 * @see table::exec_change()
	 */
	public function exec_change( $raw_fields, $prefix, $pk )
	{
		// С учетом того, что при редактировании значения параметра нельзя изменять сам параметр, получаем этот параметр из БД
		$source_record=$this->full_object->get_change_record($pk);
		$module_param_id = $source_record['MODULE_PARAM_ID'];
		lib::is_record_exists( 'MODULE_PARAM', array( 'MODULE_PARAM_ID' => $module_param_id ), true );
		
		list( $param_type, $system_name ) = $this -> get_param_info_by_module_param_id( $module_param_id );
		
		$param_value = $this -> get_prepared_value( $raw_fields[$prefix.'VALUE'], $param_type );
		
		if ( $param_type == 'template' )
		{
			$template_root_dir = params::$params['common_data_server']['value'].'module_tpl/'.$system_name.'/';
			
			$template_dir_old = db::sql_select('select VALUE from PARAM_VALUE where PARAM_VALUE_ID = :param_value_id', array( 'param_value_id' => $pk['PARAM_VALUE_ID'] ) );
			$template_dir_old = $template_dir_old[0]['VALUE'];
			
			// Если изменился каталог с шаблонами, пытаемся его переименовать
			if ( $param_value != $template_dir_old )
				template::rename_template_dir( $this, $template_root_dir, $template_dir_old, $param_value );
		}
		
		try
		{
			parent::exec_change( $raw_fields, $prefix, $pk );
		}
		catch ( Exception $e )
		{
			if ( $param_type == 'template' && $param_value != $template_dir_old )
				template::rename_template_dir( $this, $template_root_dir, $param_value, $template_dir_old );
			throw new Exception( $e -> getMessage() );
		}
	}
	
	/**
	 * В отличие от базового метода, если параметр типа TEMPLATE (4), происходит попытка скопировать каталог с шаблонами
	 *
	 * @see table::exec_copy()
	 */
	public function exec_copy( $raw_fields, $prefix, $pk )
	{
		$module_param_id = $this -> field -> get_prepared( $raw_fields[$prefix.'MODULE_PARAM_ID'], metadata::$objects[$this->obj]['fields']['MODULE_PARAM_ID'] );
		list( $param_type, $system_name ) = $this -> get_param_info_by_module_param_id( $module_param_id );
		
		$param_value = $this -> get_prepared_value( $raw_fields[$prefix.'VALUE'], $param_type );
		
		if ( $param_type == 'template' )
		{
			$template_root_dir = params::$params['common_data_server']['value'].'module_tpl/'.$system_name.'/';
			
			$template_dir_old = db::sql_select('select VALUE from PARAM_VALUE where PARAM_VALUE_ID = :param_value_id', array( 'param_value_id' => $pk['PARAM_VALUE_ID'] ) );
			$template_dir_old = $template_dir_old[0]['VALUE'];
			
			$last_id = parent::exec_copy( $raw_fields, $prefix, $pk );
			
			template::copy_template_dir( $this, $template_root_dir, $template_dir_old, $param_value );
			
			return $last_id;
		}
		else
			return parent::exec_copy( $raw_fields, $prefix, $pk );
	}
	
	/**
	 * Добавляем в заголовок таблицы колонку "Файлы шаблонов"
	 *
	 * @see table::ext_index_header()
	 */
	public function ext_index_header( $mode )
	{
		return array( 'link_to_file_manager' => array( 'title' => metadata::$lang['lang_template_files'], 'type' => '_link' ) );
	}
	
	/**
	 * Финализация удаления
	 *
	 * Если параметр типа TEMPLATE (4), происходит попытка удалить каталог с шаблонами
	 *
	 * @see table::ext_finalize_delete()
	 */
	public function ext_finalize_delete( $pk, $partial = false )
	{
		list( $param_type, $system_name ) = $this -> get_param_info_by_param_value_id( $pk['PARAM_VALUE_ID'] );
		
		if ( $param_type == 'template' )
		{
			$template_root_dir = params::$params['common_data_server']['value'].'module_tpl/'.$system_name.'/';
			
			$template_dir_old = db::sql_select( '
				select VALUE from PARAM_VALUE where PARAM_VALUE_ID = :param_value_id',
				array( 'param_value_id' => $pk['PARAM_VALUE_ID'] ) );
			$template_dir_old = $template_dir_old[0]['VALUE'];
			
			template::delete_template_dir( $this, $template_root_dir, $template_dir_old );
		}
		
		parent::ext_finalize_delete( $pk, $partial );
	}
	
	/**
	 * Добавляем в таблицу колонку "Файлы шаблонов". Только для параметров типа TEMPLATE (4)
	 *
	 * @see table::get_index_ops()
	 */
	public function get_index_ops( $record )
	{
		list( $param_type, $system_name ) = $this -> get_param_info_by_param_value_id( $record['PARAM_VALUE_ID'] );
		
		$ops = $this -> call_parent( 'get_index_ops', array( $record ) );
		if ( $param_type == 'template' )
			$ops = array_merge( array( 'link_to_file_manager' => array( 'url' => "index.php?obj=FM&path=".urlencode( "/module_tpl/".$system_name."/{$record['VALUE']}" ) ) ), $ops );
		else
			$ops = array_merge( array( 'link_to_file_manager' => '' ), $ops );
		return $ops;
	}
	
	/**
	 * Если передан параметр $list_mode, то в список попадают только те записи,
	 * которые относятся к пареметру модуля с id равным $list_mode["MODULE_PARAM_ID"]
	 */
	public function ext_index_by_list_mode($mode, $list_mode){
		list($where, $binds)=$this -> call_parent( 'ext_index_by_list_mode', array( $mode, $list_mode ) );
		if($list_mode["MODULE_PARAM_ID"]){
			$where.=' and MODULE_PARAM_ID = :mpid';
			$binds=array_merge($binds, array( 'mpid' => $list_mode["MODULE_PARAM_ID"] ) );
		}
		return array($where, $binds);
	}
	
	/**
	 * Метод возвращает типа параметра и системное название модуля по идентификатору значения параметра
	 *
	 * @param int $param_value_id	Идентификатор значения параметра модуля
	 * @return array
	 */	
	function get_param_info_by_param_value_id( $param_value_id = '' )
	{
		$param_info = db::sql_select('
			select MODULE_PARAM.PARAM_TYPE, PRG_MODULE.SYSTEM_NAME
			from MODULE_PARAM inner join PRG_MODULE on MODULE_PARAM.PRG_MODULE_ID = PRG_MODULE.PRG_MODULE_ID
							  inner join PARAM_VALUE on PARAM_VALUE.MODULE_PARAM_ID = MODULE_PARAM.MODULE_PARAM_ID
			where PARAM_VALUE.PARAM_VALUE_ID = :param_value_id', array( 'param_value_id' => $param_value_id ) );
		return array( $param_info[0]['PARAM_TYPE'], strtolower( $param_info[0]['SYSTEM_NAME'] ) );
	}
	
	/**
	 * Метод возвращает типа параметра и системное название модуля по идентификатору параметра модуля
	 *
	 * @param int $module_param_id	Идентификатор параметра модуля
	 * @return array
	 */	
	function get_param_info_by_module_param_id( $module_param_id = '' )
	{
		$param_info = db::sql_select('
			select MODULE_PARAM.PARAM_TYPE, PRG_MODULE.SYSTEM_NAME
			from MODULE_PARAM inner join PRG_MODULE on MODULE_PARAM.PRG_MODULE_ID = PRG_MODULE.PRG_MODULE_ID
			where MODULE_PARAM.MODULE_PARAM_ID = :module_param_id', array( 'module_param_id' => $module_param_id ) );
		return array( $param_info[0]['PARAM_TYPE'], strtolower( $param_info[0]['SYSTEM_NAME'] ) );
	}
	
	/**
	 * Метод осуществляет динамическую проверку значения параметра в зависимости от его типа
	 *
	 * @param string $param_value	Значение параметра модуля
	 * @param int $param_type		Тип параметра модуля
	 * @return string
	 */
	function get_prepared_value( $param_value, $param_type )
	{
		$field_metadata = metadata::$objects[$this->obj]['fields']['VALUE'];
		
		switch ( $param_type )
		{
			case 'template': $field_metadata['errors'] |= _dirname_; break;
		}
		
		return $this -> field -> get_prepared( $param_value, $field_metadata );
	}
	

	/**
	* В случае если параметр является шаблоном заменяем ID модуля на его системное имя
	* @todo Красивее назвать, что это такое ID - а в нем не ID, а имя??? см INF_BLOCK
	*/
	
	public function get_export_field_values($pk) {
		$fields = $this->call_parent('get_export_field_values', array($pk));
		
		list($param_type, $system_name)=$this->get_param_info_by_param_value_id($pk[$this->autoinc_name]);
		if ($param_type=='template') 
			$fields['MODULE_PARAM_ID'][0]['value']=$system_name;
		
		return $fields;
	}
	
	/**
	* Дополняем информацию об экспорте содержимым файлов шаблонов модулей
	*/
	
	public function get_export_add_data_xml($pk) {
		$xml = $this->call_parent('get_export_add_data_xml', array($pk));
		
		list($param_type, $system_name)=$this->get_param_info_by_param_value_id($pk[$this->autoinc_name]);
		
		if ($param_type=='template') {
			$record = $this->get_change_record($pk);
			
			$template_root_dir = params::$params['common_data_server']['value'].'module_tpl/'.$system_name.'/';	
			
			$xml .= template::get_files_xml_for_export($template_root_dir.$record['VALUE']);
		}
		
		return $xml;
	}
	
	/**
	* Метод импорта данных из XML - унаследованный метод от table
	* Дополняем данные созданием файлов шаблонов модулей
	* @param array $xml_arr массив данных одной записи, возвращаемый ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return array карта соответствия между id записей из файла импорта и реальными
	* @todo оптимизировать по скорости, 2 ненужных запроса к БД
	*/
	
	public function import_from_xml ($xml_arr, &$import_data) {
		$id_map = $this->call_parent('import_from_xml', array($xml_arr, &$import_data));
		$record = $this->get_change_record(array('PARAM_VALUE_ID'=>current($id_map)));
		list( $param_type, $system_name ) = $this -> get_param_info_by_module_param_id( $record['MODULE_PARAM_ID'] );

		$template_root_dir = params::$params['common_data_server']['value'].'module_tpl/'.$system_name.'/';	
		
		template::create_files_from_import($xml_arr['children'], $template_root_dir.$record['VALUE'], $import_data);
		return $id_map;
	}


	/**
	* Возвращает поля для вставки в таблицу в процессе импорта - унаследованный метод от table
	* Дополняет функционал генерацией уникального имени директории шаблона модуля
	* @param array $main_children данные обо всех потомках массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return array Подготовленные данные для вставки в таблицу
	*/
		
	public function get_import_field_values($main_children, &$import_data) {
		$fields = $this->call_parent('get_import_field_values', array($main_children, &$import_data));
		list( $param_type, $system_name ) = $this -> get_param_info_by_module_param_id( $fields['MODULE_PARAM_ID'] );
		$template_root_dir = params::$params['common_data_server']['value'].'module_tpl/'.$system_name.'/';	
		$fields['VALUE']=upload::get_unique_file_name($template_root_dir, $import_data['_f_prefix_for_dirs'].$fields['VALUE']);
		
		foreach ($fields['TITLE'] as $lang_id=>&$title) 
			$title=$import_data['_f_prefix_for_templates'][$lang_id].$title;
		
		// если у данного модуля уже есть шаблон по умолчанию то необходимо его оставить таковым
		if ($fields['IS_DEFAULT'] && $this->is_default_template_exists($fields['MODULE_PARAM_ID']))
			$fields['IS_DEFAULT']=0;
		
		return $fields;
	}
	
	/**
	* Проверяет, существует ли дефолтный шаблон на данный момент
	* @param int $module_param_id ID параметра номера
	* @return boolean TRUE - существует
	*/
	
	private function is_default_template_exists($module_param_id) {
		$is_def=db::sql_select('
			SELECT 
				MAX(IS_DEFAULT) AS DEF_EXISTS 
			FROM 
				PARAM_VALUE 
			WHERE 
				MODULE_PARAM_ID=:module_param_id
			', array('module_param_id'=>$module_param_id)
		);
		return ($is_def[0] && $is_def[0]['DEF_EXISTS'])?true:false;
	}

	/**
	* Возвращает значение для конкретного поля для вставки в таблицу - унаследованный метод от table
	* В связи с тем что в процессе экспорта в поле MODULE_PARAM_ID записывается системное имя модуля, здесь подменяем его на ID модуля
	* @param string $field_name Название поля
	* @param array $field_children Данные обо всех потомках данного поля массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return mixed Значение, которое вставляется в БД (еще возможно подменить, @see get_import_field_values)
	*/
	
	public function get_import_field_value($field_name, $field_children, &$import_data) {
		$value = $this->call_parent('get_import_field_value', array($field_name, $field_children, &$import_data));
		
		// поскольку экспорт шел только параметров-шаблонов модулей, импорт выполняем тоже только для них

		if ($field_name=='MODULE_PARAM_ID') {
			$value = $this->get_template_module_param_id ($value);
		}
		return $value;
	}
	
	/**
	* Возвращает ID параметра модуля, который является шаблонов для модуля с системным именем $module_system_name
	* @param int $module_system_name Системное название модуля
	* @return int ID параметра модуля
	*/
	
	private function get_template_module_param_id($module_system_name) {
		$res=db::sql_select(
			'SELECT 
				MP.MODULE_PARAM_ID 
			FROM 
				MODULE_PARAM MP
					INNER JOIN 
						PRG_MODULE M
							ON (M.PRG_MODULE_ID=MP.PRG_MODULE_ID)
			WHERE 
				MP.PARAM_TYPE=:template AND M.SYSTEM_NAME=:module_system_name',
			array ('template'=>'template', 'module_system_name'=>$module_system_name)
		);
		return $res[0]['MODULE_PARAM_ID'];
	}
}
?>