<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Планировщик"
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class sheduler extends table_translate
{
	/**
	 * Дополнительная провека на существование класса объекта
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		$this -> is_class_exists( $raw_fields[$prefix . 'CLASS_NAME'], $raw_fields[$prefix . 'TE_OBJECT_ID'] );
		
		return parent::exec_add( $raw_fields, $prefix );
	}
	
	/**
	 * Дополнительная провека на существование класса объекта
	 *
	 * @see table::exec_change()
	 */
	public function exec_change( $raw_fields, $prefix, $pk )
	{
		$this -> is_class_exists( $raw_fields[$prefix . 'CLASS_NAME'], $raw_fields[$prefix . 'TE_OBJECT_ID'] );
		
		parent::exec_change( $raw_fields, $prefix, $pk );
	}
	
	/**
	 * Проверяет существование указанного класса для указанного объекта
	 */
	private function is_class_exists( $class_name, $te_object_id )
	{
		$system_name = object::$te_object_names[$te_object_id]['SYSTEM_NAME'];
		$class_dir = metadata::$objects[$system_name]['class'] ?
			metadata::$objects[$system_name]['class'] : strtolower( $system_name );
		$object_type = metadata::$objects[$system_name]['type'];
		$object_level = metadata::$objects[$system_name]['object_level'];
		
		$class_path = params::$params['adm_data_server']['value'] . 'class/' .
			$object_level . '/' . $object_type . '/' . $class_dir . '/' . $class_name . '.php';
		
		if ( !is_file( $class_path ) )
			throw new Exception( $this -> te_object_name.': "' . metadata::$lang['lang_sheduler_class_not_found'] . ': ' . $class_path . '"' );
	}
}
?>