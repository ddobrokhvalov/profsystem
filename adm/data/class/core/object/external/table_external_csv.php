<?php
/**
 * Адаптер декоратора "Внешние данные" для работы с csv-файлами
 *
 * @package		RBC_Contents_5_0
 * @subpackage	core
 * @copyright	Copyright (c) 2007 RBC SOFT
 */
abstract class table_external_csv extends table_external
{
	/**
	 * Путь к файлу-источнику данных
	 */
	protected $import_file = '';
	
	/**
	 * Путь к файлу-приемнику данных
	 */
	protected $export_file = '';
	
	/**
	 * Дескриптор открытого файла данных
	 */
	protected $resource_handle = null;
	
	/**
	 * Разделитель поля csv-файла
	 */
	protected $csv_delimiter = ',';
	
	/**
	 * Ограничителя поля csv-файла
	 */
	protected $csv_enclosure = '"';
	
	/**
	 * Открытие внешнего источника данных
	 */
	public function external_open( $mode = 'r' )
	{
		$this -> resource_handle = fopen( $mode == 'r' ? $this -> import_file : $this -> export_file, $mode );
	}
	
	/**
	 * Чтение строки из внешнего источника данных
	 */
	public function external_read()
	{
		return fgetcsv( $this -> resource_handle, 512, $this -> csv_delimiter, $this -> csv_enclosure );
	}
	
	/**
	 * Запись строки во внешний источник данных
	 */
	public function external_write( $row )
	{
		fputcsv( $this -> resource_handle, $row, $this -> csv_delimiter, $this -> csv_enclosure );
	}
	
	/**
	 * Закрытие внешнего источника данных
	 */
	public function external_close()
	{
		fclose( $this -> resource_handle );
		unset( $this -> resource_handle );
	}
}
?>
