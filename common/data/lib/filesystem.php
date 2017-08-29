<?php
/**
 * Класс содержащий методы для выполнения массовых операций над файлами и директориями
 *
 * Для простоты использования класс специально не инстанцируется. Инстанцирование происходит в самих методах класса
 *
 * Примеры:<br>
 * filesystem::chmod_r("/home/some_user/test_mv", 0777, 1);<br>
 * filesystem::rm_r("/home/some_user/test_mv");<br>
 * $files_and_dirs=filesystem::ls_r("/home/some_user/test_mv");
 *
 * @package    RBC_Contents_5_0
 * @subpackage lib
 * @copyright  Copyright (c) 2006 RBC SOFT
 * @todo Подумать над тем, чтобы вместо собак класс мог ловить ошибки и как-то сообщать о них
 * @todo Сделать поддержку атрибутов файлов для Windows наряду с UNIX-правами
 */
class filesystem extends lib_abstract{

	/**
	 * Список найденных объектов файловой системы. Набор полей см. в {@link filesystem::get_stat()}
	 * @var array
	 */
	private $files=array();

	/**
	 * Указатель на текущий элемент $this->files. Нужен для более удобной сборки данных об иерархии, если это потребуется
	 * @var int
	 */
	private $pointer=1;

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Конструктор. Собирает список директорий и файлов для последующего использования
	 *
	 * @param string $path				путь, для которого выполняется операция
	 * @param boolean $do_not_recurse	класс не будет производить обход поддиректорий
	 * @param boolean $without_root		класс будет выполнять операцию только для содержимого директории, на которую указывает $path, но не для ее самой
	 */
	private function __construct($path, $do_not_recurse, $without_root){
		$path=preg_replace("|/$|","",$path);
		$root_stat=$this->get_stat($path, $this->pointer, 0);
		if(!$without_root){
			$this->files[$this->pointer]=$root_stat;
		}
		if($root_stat["is_dir"] && is_readable($path)){
			$this->recursive_engine($path, $do_not_recurse, (!$without_root ? 1 : 0), $this->pointer);
		}
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Метод возвращает список файлов и директорий с их свойствами
	 *
	 * Параметры - см. {@link filesystem::__construct()}
	 *
	 * @return int
	 */
	public static function ls_r($path, $do_not_recurse=false, $without_root=false){
		$fs=new filesystem($path, $do_not_recurse, $without_root);
		return $fs->files;
	}

	/**
	 * Метод пытается поменять права, на все, что попадется ему на пути
	 *
	 * В случае неуспеха никаких сообщений выведено не будет.
	 * Возвращает число файлов и директорий, на которые пытался поменять права.
	 * Параметры (кроме $mode) - см. {@link filesystem::__construct()}
	 *
	 * @param int $mode		права, которые нужно выставить
	 * @return int
	 */
	public static function chmod_r($path, $mode, $do_not_recurse=false, $without_root=false){
		$fs=new filesystem($path, $do_not_recurse, $without_root);
		foreach($fs->files as $file){
			@chmod($file["name"], $mode);
		}
		return count($fs->files);
	}

	/**
	 * Метод пытается удалить все, что попадется ему на пути
	 *
	 * В случае неуспеха никаких сообщений выведено не будет.
	 * Возвращает число файлов и директорий, которые пытался удалить.
	 * Параметры - см. {@link filesystem::__construct()}
	 *
	 * @return int
	 */
	public static function rm_r($path, $do_not_recurse=false, $without_root=false){
		$fs=new filesystem($path, $do_not_recurse, $without_root);
		$fs->files=array_reverse($fs->files); // Разворот списка объектов для того, чтобы вложенные объекты удалялись раньше их директорий
		foreach($fs->files as $file){
			if($file["is_dir"]){
				if(!$do_not_recurse || $file["name"]==preg_replace("|/+$|", "", $path))// Вторая половинка условия для того, чтобы делалась попытка удалить целевую директорию, даже если нельзя удалять ее поддиректории
					@rmdir($file["name"]);
			}else{
				@unlink($file["name"]);
			}
		}
		return count($fs->files);
	}

	/**
	 * Метод пытается скопировать все, что попадется ему на пути
	 *
	 * В случае неуспеха никаких сообщений выведено не будет.
	 * Возвращает число файлов и директорий, которые пытался скопировать.
	 *
	 * @param string $path_from			директория-источник
	 * @param string $path_to			директория-назначение
	 * @param boolean $do_not_recurse	из директории-источника копируются только файлы
	 * @param boolean $without_root		директория-назначение не создается, предполагается ее наличие
	 *
	 * @todo Поконкретнее описать тонкости использования параметра $without_root
	 *
	 * @return int
	 */
	public static function cp_r( $path_from, $path_to, $do_not_recurse = false, $without_root = false )
	{
		$fs = new filesystem( $path_from, $do_not_recurse, $without_root );
		foreach( $fs -> files as $file )
		{
			$file_name_to = str_replace( $path_from, $path_to, $file['name'] );
			if ( $file['is_dir'] ) {
				if ( !$do_not_recurse || ( !$without_root && $file['id'] == 1 ) )
					@mkdir( $file_name_to );
			} else {
				@copy( $file['name'], $file_name_to );
			}
			@chmod( $file_name_to, octdec( $file['perms'] ) );
		}
		return count($fs->files);
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Рекурсивный движок
	 *
	 * Производит обход файловой системы и собирает информацио о файлах и директориях.
	 * Параметры (кроме $parent_id) - см. {@link filesystem::__construct()}
	 *
	 * @param int $parent_id	идентификатор (исключительно в рамках этого класса) родительской директории
	 */
	private function recursive_engine($path, $do_not_recurse, $parent_id){
		$dir_id=@opendir($path);
		while(($file=@readdir($dir_id))!=false){
			if($file!="." && $file!=".."){
				$pathfile=$path."/".$file;
				$this->pointer++;
				$stat=$this->get_stat($pathfile, $this->pointer, $parent_id);
				$this->files[$this->pointer]=$stat;
				if($stat["is_dir"] && is_readable($path) && !$do_not_recurse){
					$this->recursive_engine($pathfile, $do_not_recurse, $this->pointer);
				}
			}
		}
		@closedir($dir_id);
	}

	/**
	 * Метод, возвращающий хэш с информацией о файле или директории
	 *
	 * Набор полей:<br>
	 * "name"		полный путь<br>
	 * "pure_name"	название объекта без пути<br>
	 * "perms"		права на объект (строка)<br>
	 * "size"		размер в байтах<br>
	 * "mtime"		время модификации<br>
	 * "is_dir"		является директорией или нет<br>
	 * "id"			идентификатор (виртуальный - нужен для построения иерархии)<br>
	 * "parent"		идентификатор директории (также виртуальный)
	 *
	 * @param string $pathfile	путь
	 * @param int $id	идентификатор (исключительно в рамках этого класса) объекта
	 * @param int $parent_id	идентификатор (исключительно в рамках этого класса) родительской директории
	 * @return array
	 */
	private function get_stat($pathfile, $id, $parent_id){
		$perms=base_convert(@fileperms($pathfile), 10, 8);
		$stat=array(
			"name"=>$pathfile,
			"pure_name" => str_replace( dirname( $pathfile ).'/', '', $pathfile ),
			"perms"=>substr($perms, strlen($perms)-3, 3),
			"size"=>@filesize($pathfile),
			"mtime"=>@filemtime($pathfile),
			"is_dir"=>@is_dir($pathfile),
			"id"=>$id,	
			"parent"=>$parent_id,
		);
		return $stat;
	}
}
?>