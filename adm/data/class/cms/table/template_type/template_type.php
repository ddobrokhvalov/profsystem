<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Типы шаблоны"
 *
 * @package    RBC_Contents_5_0
 * @subpackage cms
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class template_type extends table_translate
{
	/**
	 * В дополнение к базовому методу происходит копирование привязки областей типов шаблонов
	 *
	 * @see table::exec_copy()
	 */
	public function exec_copy( $raw_fields, $prefix, $pk )
	{
		$template_areas = db::sql_select( '
			select * from TEMPLATE_AREA_MAP where TEMPLATE_TYPE_ID = :template_type_id',
			array( 'template_type_id' => $pk['TEMPLATE_TYPE_ID'] ) );
		
		$last_id = parent::exec_copy( $raw_fields, $prefix, $pk );
		
		lib::inserter( 'TEMPLATE_AREA_MAP', $template_areas, array( 'TEMPLATE_TYPE_ID' => $last_id ) );
		
		return $last_id;
	}

	/**
	 * К типу шаблона может быть привязана только одна главная область
	 *
	 * @see table::exec_m2m()
	 */
	public function exec_m2m( $m2m_name, $values, $p_ids, $s_ids, $t_ids = array() )
	{
		if ( $m2m_name == 'AREA_MAP' && is_array( $values ) )
		{
			// Собираем массив областей данного типа шаблона
			$old_areas = db::sql_select( '
				select TEMPLATE_AREA.TEMPLATE_AREA_ID from TEMPLATE_AREA, TEMPLATE_AREA_MAP
				where TEMPLATE_AREA_MAP.TEMPLATE_AREA_ID = TEMPLATE_AREA.TEMPLATE_AREA_ID and
				TEMPLATE_AREA_MAP.TEMPLATE_TYPE_ID = :template_type_id',
				array( 'template_type_id' => $p_ids[0] ) );
			
			$old_areas = lib::array_reindex( $old_areas, 'TEMPLATE_AREA_ID' );
			
			// Собираем массивы выбранных и не выбранных областей, переданных в запросе
			$select_areas = array(); $unselect_areas = array();
			foreach ( $values as $id => $value )
				if ( $value )
					$select_areas[$s_ids[$id]] = 1;
				else
					$unselect_areas[$s_ids[$id]] = 1;
			
			// Объединяем полученные массивы областей
			$new_areas = array_keys( array_diff_key( $old_areas + $select_areas, $unselect_areas ) );
			
			// Подсчитываем итоговое число главных областей
			$new_main_areas = db::sql_select( '
				select TEMPLATE_AREA.TEMPLATE_AREA_ID from TEMPLATE_AREA
				where TEMPLATE_AREA.TEMPLATE_AREA_ID in (' . lib::array_make_in( $new_areas ).') and TEMPLATE_AREA.IS_MAIN = 1' );
			
			// Если итоговое число главных областей не равно 1, бросаем эксепшн
			if ( count( $new_main_areas ) != 1 )
				throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_single_main_area'] . ': "' .
					$this -> full_object -> get_record_title( array( 'TEMPLATE_TYPE_ID' => $p_ids[0] ) ) . '" (' .
					$this -> primary_key -> pk_to_string( array( 'TEMPLATE_TYPE_ID' => $p_ids[0] ) ) . ')' .
					( count( $new_main_areas ) ? ': ' . metadata::$lang['lang_template_area'] . ': ' .
						lib::array_make_in( $new_main_areas, 'TEMPLATE_AREA_ID' ) : '' ) );
		}
		
		return $this -> call_parent( 'exec_m2m', array( $m2m_name, $values, $p_ids, $s_ids, $t_ids ) );
	}
	
	
	
	/**
	* Дополняем информацию об экспорте связью с областями шаблонов
	* @todo Возможно перенести механизм экспорта m2m выше
	*/
	
	public function get_export_add_data_xml($pk) {
		$xml = $this->inner_object->get_export_add_data_xml($pk);
		
		foreach (metadata::$objects[$this->obj]['m2m'] as $m2m_name=>$m2m_data) {
			$recs=db::sql_select ("SELECT {$m2m_data['secondary_m2m_field']} AS ID FROM {$m2m_data['m2m_table']} WHERE ".$this->autoinc_name."=".$pk[$this->autoinc_name]);
			for ($i=0, $n=sizeof($recs); $i<$n; $i++) 
				$xml.="<M2M NAME=\"{$m2m_name}\" ID=\"{$recs[$i]['ID']}\" />\n";
		}
		
		return $xml;
	}
	
	/**
	* Метод импорта данных из XML - унаследованный метод от table
	* Дополняем данные формированием связи m2m
	* @param array $xml_arr массив данных одной записи, возвращаемый ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return array карта соответствия между id записей из файла импорта и реальными
	*/

	public function import_from_xml ($xml_arr, &$import_data) {
		$id_map = $this->inner_object->import_from_xml($xml_arr, $import_data);
		$this->import_m2m_from_xml($xml_arr['children'], $import_data, current($id_map));
		return $id_map;
	}	
	
	/**
	* Возвращает значение для конкретного поля для вставки в таблицу - унаследованный метод от table
	* Дополняет функционал применяется префикс, указанный пользователем для названий шаблонов
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
		elseif ($field_name=='HTML_MAP') {
			// патчим новыми названиями областей
			$value=preg_replace_callback('/(\$areas\.)([a-z0-9_]+)/i', array('self', '_callback_set_template_area_import_system_names'), $value);
		}
			
		
		return $value;
	}		

	/**
	* Функция обратного вызова для preg_replace_callback
	* Служит для замены названий областей в соответствии с их новыми названиями в связи с тем, что 
	*/
	
	public static function _callback_set_template_area_import_system_names ($matches) {
		// подгружаем template_area
		$tmp=object::factory('TEMPLATE_AREA');
		$tmp->__destruct();
		
		if (template_area::$import_system_names[$matches[2]]) 
			return $matches[1].template_area::$import_system_names[$matches[2]];
		return $matches[1].$matches[2];
	}


	/**
	* Импортирует данные m2m по данным из данных экспорта
	* @param array $main_children данные обо всех потомках массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @param int $inserted_id - id текущей записи
	* @todo Возможно перенести механизм импорта m2m выше
	*/
	
	public function import_m2m_from_xml($main_children, &$import_data, $inserted_id) {
		$m2ms = array();
		for ($i=0, $n=sizeof($main_children); $i<$n; $i++) 
			if ($main_children[$i]['tag']=='M2M') {
				$area_id=$main_children[$i]['attributes']['ID'];
				$secondary_table = metadata::$objects[$this->obj]['m2m'][ $main_children[$i]['attributes']['NAME'] ]['secondary_table'];
				$m2ms[$main_children[$i]['attributes']['NAME']][]=$this->full_object->get_import_new_id($area_id, $secondary_table, $import_data);
			}
		
		foreach ($m2ms as $m2m_name=>$m2m_ids) 
			$this->exec_m2m($m2m_name, array_fill(0, sizeof($m2m_ids), 1), array_fill(0, sizeof($m2m_ids), $inserted_id), $m2m_ids);
	}
	
	
}
?>
