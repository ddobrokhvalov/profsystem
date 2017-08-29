<?php
/**
 * Класс с разными полезными статическими методами, относящиеся к аплоаду файлов
 *
 * @package    RBC_Contents_5_0
 * @subpackage lib
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class upload extends lib_abstract {
	/**
	 * Метод для закачки файлов на сервер
	 *
	 * @param array $file_descr			структура-описание файла
	 * @param string $upload_path		каталог для закачки
	 * @param bool $create_upload_dir	флаг, нужно ли создавать каталог, если его не существует
	 * @param bool $rewrite_file		флаг, нужно ли затирать файл, если он существует
	 */
	public static function upload_file( $file_descr, $upload_path, $create_upload_dir = true, $rewrite_file = true )
	{
		$tmp_name = $file_descr['tmp_name']; $name = $file_descr['name'];
		
		$name = self::get_translit_file_name( $name );
		
		if ( !self::is_valid_name( $name ) )
			throw new Exception( metadata::$lang['lang_fm_name_not_valid'] );
		
		if ( !self::is_valid_ext( $name ) )
			throw new Exception( metadata::$lang['lang_fm_ext_not_valid'] );
		
		if( $create_upload_dir && !file_exists( $upload_path ) )
			if ( !( @mkdir( $upload_path , 0777, true) ) )
				throw new Exception( metadata::$lang['lang_can_not_create_dir'] );
		
		if( !$rewrite_file )
			$name = self::get_unique_file_name( $upload_path, $name );
		
		if ( !( @move_uploaded_file( $tmp_name, $upload_path.'/'.$name ) &&
				@chmod( $upload_path.'/'.$name, 0777 ) ) )
			throw new Exception( metadata::$lang['lang_lib_can_not_upload_file'] );
		
		return realpath( $upload_path.'/'.$name );
	}
	
	/**
	 * Проверка имени файла/каталога на недопустимые символы
	 *
	 * @param string $name	имя файла
	 */
	public static function is_valid_name( $name )
	{
		return !preg_match( '/[^a-z0-9_\.\[\]-]/i', $name );
	}
	
	/**
	 * Проверка имени файла/каталога на запрещенное расширение
	 *
	 * @param string $name	имя файла
	 */
	public static function is_valid_ext( $name )
	{
		$name_parts = pathinfo( $name );
		$ext = $name_parts['extension'];
		
		return !in_array( $ext, self::$forbidden_extensions );
	}
	
	/**
	 * Создание уникального имени файла путем его последовательной нумерации
	 *
	 * @param string $path	путь к файлу
	 * @param string $name	имя файла
	 */
	public static function get_unique_file_name( $path, $name )
	{
		$point_index = strrpos( $name, '.' );
		$base = ( $point_index !== false ) ? substr( $name, 0, $point_index ) : $name;
		$ext = ( $point_index !== false ) ? substr( $name, $point_index, strlen( $name ) ) : '';
		
		$new_name = $name; $n = 0;
		while ( file_exists( $path . '/' . $new_name ) )
			$new_name = $base . '[' . ( ++$n ) . ']' . $ext;
		return $new_name;
	}
	
	/**
	 * Перевод имени файла на транслит
	 *
	 * @param string $name	имя файла
	 */
	public static function get_translit_file_name( $name )
	{
		return preg_replace( '/[^a-z0-9_\.\[\]-]/i', '', strtr( $name, self::$translit ) );
	}
	
	/**
	 * Массив запрещенных к закачке расширений файлов
	 *
	 * @var array()
	 */
	public static $forbidden_extensions = array( 'htaccess' );
	
	/**
	 * Таблица транслитерации
	 *
	 * @var array()
	 */
	public static $translit = array(
		' ' => '_', 'Ё' => 'YO', 'ё' => 'yo', 'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
		'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
		'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'KH', 'Ц' => 'TS', 'Ч' => 'CH',
		'Ш' => 'SH', 'Щ' => 'SHCH', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'U', 'Я' => 'YA', 'а' => 'a',
		'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y',
		'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
		'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'y',
		'ь' => '', 'э' => 'e', 'ю' => 'u', 'я' => 'ya', '№' => 'N' );
}
?>