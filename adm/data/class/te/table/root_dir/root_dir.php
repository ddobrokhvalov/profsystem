<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Корневые каталоги"
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class root_dir extends table_translate
{
	/**
	 * Проверка существования каталога при добавлении записи
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		$this -> check_path_exists( $raw_fields[$prefix . 'ROOT_DIR_TYPE'], $raw_fields[$prefix . 'ROOT_DIR_VALUE'] );
		
		return parent::exec_add( $raw_fields, $prefix );
	}
	
	/**
	 * Проверка существования каталога при изменении записи
	 *
	 * @see table::exec_change()
	 */
	public function exec_change( $raw_fields, $prefix, $pk )
	{
		$this -> check_path_exists( $raw_fields[$prefix . 'ROOT_DIR_TYPE'], $raw_fields[$prefix . 'ROOT_DIR_VALUE'] );
		
		parent::exec_change( $raw_fields, $prefix, $pk );
	}
	
	/**
	 * Метод проверки существования каталога
	 *
	 * @param int $root_dir_type		Тип каталога
	 * @param string $root_dir_value	Название каталога
	 */
	public function check_path_exists( $root_dir_type, $root_dir_value )
	{
		$root_dir_value = $this->get_real_path($root_dir_type, $root_dir_value);
		
		if ( !file_exists( $root_dir_value ) || !is_dir( $root_dir_value ) )
			throw new Exception($this->te_object_name . ': ' . metadata::$lang['lang_fm_not_exists'] . ' "' . htmlspecialchars( $root_dir_value ) . '"' );
	}
	
	/**
	 * Функция выдает полный путь к каталогу
	 *
	 * @param int $root_dir_type		Тип каталога
	 * @param string $root_dir_value	Название каталога
	 */
	public function get_real_path( $root_dir_type, $root_dir_value )
	{
		switch ( $root_dir_type )
		{
			case 1: $root_path = realpath( params::$params['adm_data_server']['value'] ); break; 
			case 2: $root_path = realpath( params::$params['common_data_server']['value'] ); break;
			case 3: $root_path = realpath( params::$params['adm_htdocs_server']['value'] ); break;
			case 4: $root_path = realpath( params::$params['common_htdocs_server']['value'] ); break;
			default: $root_path = '';
		}
		
		return $root_path . '/' . $root_dir_value;
	}
}
?>