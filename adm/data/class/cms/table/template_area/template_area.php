<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Области шаблонов"
 *
 * @package    RBC_Contents_5_0
 * @subpackage cms
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class template_area extends table_translate
{
	
	/**
	 * При смене значения поля "Главная версия" проверяется существование и
	 * уникальность области с главной версией у привязанных к данной области шаблонов
	 *
	 * @see table::exec_change()
	 */
	public function exec_change( $raw_fields, $prefix, $pk )
	{
		$record = $this -> full_object -> get_change_record( $pk );
		
		if ( $record['IS_MAIN'] != $raw_fields[$prefix . 'IS_MAIN'] )
		{
			$template_areas = db::sql_select( '
				select TEMPLATE_AREA_MAP.TEMPLATE_AREA_ID, TEMPLATE_AREA_MAP.TEMPLATE_TYPE_ID, TEMPLATE_AREA.IS_MAIN
				from TEMPLATE_AREA_MAP, TEMPLATE_AREA
				where TEMPLATE_AREA.TEMPLATE_AREA_ID = TEMPLATE_AREA_MAP.TEMPLATE_AREA_ID and
					TEMPLATE_AREA_MAP.TEMPLATE_TYPE_ID in
						( select TEMPLATE_TYPE_ID from TEMPLATE_AREA_MAP where TEMPLATE_AREA_ID = :template_area_id )',
				array( 'template_area_id' => $pk['TEMPLATE_AREA_ID'] ) );
			
			if ( count( $template_areas ) )
			{
				foreach ( $template_areas as $template_area_id => $template_area )
					if ( $template_area['TEMPLATE_AREA_ID'] == $pk['TEMPLATE_AREA_ID'] )
						$template_areas[$template_area_id]['IS_MAIN'] = $raw_fields[$prefix . 'IS_MAIN'];
				
				$template_types = lib::array_group( $template_areas, 'TEMPLATE_TYPE_ID' );
				
				foreach ( $template_types as $template_type_id => $template_type_areas )
				{
					$main_area_count = 0;
					foreach ( $template_type_areas as $template_area )
						$main_area_count += intval( $template_area['IS_MAIN'] );
					
					if ( $main_area_count != 1 )
						throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_single_main_area'] . ': "' .
							object::factory( 'TEMPLATE_TYPE' ) -> get_record_title( array( 'TEMPLATE_TYPE_ID' => $template_type_id ) ) . '" (' . $template_type_id . ')' );
				}
			}
		}
		
		$this -> call_parent( 'exec_change', array( $raw_fields, $prefix, $pk ) );
	}
	
	/**
	 * При удалении главной области проверяетсяее привязка к существующим шаблонам
	 *
	 * @see table::exec_change()
	 */
	public function exec_delete( $pk )
	{
		$template_types = db::sql_select( '
			select TEMPLATE_AREA_MAP.TEMPLATE_TYPE_ID
			from TEMPLATE_AREA_MAP, TEMPLATE_AREA
			where TEMPLATE_AREA.TEMPLATE_AREA_ID = TEMPLATE_AREA_MAP.TEMPLATE_AREA_ID and
				TEMPLATE_AREA_MAP.TEMPLATE_AREA_ID = :template_area_id and TEMPLATE_AREA.IS_MAIN = 1',
			array( 'template_area_id' => $pk['TEMPLATE_AREA_ID'] ) );
		
		if ( count( $template_types ) )
			throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_single_main_area'] . ': "' .
				object::factory( 'TEMPLATE_TYPE' ) -> get_record_title( array( 'TEMPLATE_TYPE_ID' => $template_types[0]['TEMPLATE_TYPE_ID'] ) ) . '" (' . $template_types[0]['TEMPLATE_TYPE_ID'] . ')' );
		
		$this -> call_parent( 'exec_delete', array( $pk ) );
	}
	
	/**
	 * Добавляем INNER JOIN по TEMPLATE_AREA_MAP, если в фильтре выбран тип шаблона
	 *
	 * @see table::get_index_query_components()
	 */
	public function get_index_query_components(&$request, $mode, $list_mode){
		// формат переменной $components - array($fields, $joins, $where, $binds)
		$components=$this->inner_object->get_index_query_components($request, $mode, $list_mode);
		if($request["_f_TEMPLATE_TYPE_ID"]){
			$components[1].="
				INNER JOIN TEMPLATE_AREA_MAP ON 
					TEMPLATE_AREA_MAP.TEMPLATE_AREA_ID=TEMPLATE_AREA.TEMPLATE_AREA_ID AND
					TEMPLATE_AREA_MAP.TEMPLATE_TYPE_ID=:template_type_id
				";
			// Добавляем переменную привязки с типом шаблона
			$components[3]["template_type_id"]=$request["_f_TEMPLATE_TYPE_ID"];
		}
		return $components;
	}
	
	/**
	* Возвращает значение для конкретного поля для вставки в таблицу - унаследованный метод от table
	* Дополняет функционал генерацией уникального имени системного имени области шаблона,
	* А также применяется префикс, указанный пользователем, для всех названий
	* @param string $field_name Название поля
	* @param array $field_children Данные обо всех потомках данного поля массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return mixed Значение, которое вставляется в БД (еще возможно подменить, @see get_import_field_values)
	*/
	public function get_import_field_value($field_name, $field_children, &$import_data) {
		$value = $this->call_parent('get_import_field_value', array($field_name, $field_children, &$import_data));
		
		if ($field_name=='TITLE') {
			foreach ($value as $lang_id=>$title)
				$value[$lang_id]=$import_data['_f_prefix_for_templates'][$lang_id].$title;
		}
		elseif ($field_name=='SYSTEM_NAME') {
			$value=$this->get_unique_system_name($import_data['_f_prefix_for_dirs'], $value);
		}
		
		return $value;
	}	
	
	/**
	* Сохраняет измененные системные имена при импорте
	* @var array
	*/
	
	public static $import_system_names;	

	/**
	* Возвращает уникальное системное имя на основе переданного $name
	* @param string $name Предполагаемое название системного имени
	* @return уникальное название
	* @todo Может стоит перенести выше?
	*/
	private function get_unique_system_name($prefix, $value) {
		$n = 0;
		$name = $prefix.$value;
		while ( sizeof(db::sql_select('SELECT * FROM '.$this->obj.' WHERE SYSTEM_NAME=:system_name LIMIT 1', array('system_name'=>$name))))
			$name = preg_replace('/((^_(\d)+_)|^)/', '_'.(++$n).'_', $name);
		
		self::$import_system_names[$value] = $name;
		
		return $name;		
	}
}
?>