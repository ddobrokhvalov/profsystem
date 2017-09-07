<?
class medialib_select{
	/**
	 * Библиотеки
	 * @return dbSelect
	 */
	static function libs(){
		$a= func_get_args();
		return dbSelect::factory(
			array(
				'id',			//id по которому привязываются файлы
				'content_id',	//элемент контента
				'type',			//тип контента
				'version',		//версия
				'lang',			//язык
				'total',		//количество элементов
				'ts',			//время последнего изменения
				'stat'			//статистика библиотеки
			),'medialib_libs','stat',$a
		);
	}
	/**
	 * Файлы
	 * @return dbSelect
	 */
	static function files(){
		$a= func_get_args();
		return dbSelect::factory(
			array(
				'id',			//id по которому привязываются файлы
				'type',			//тип файла medialib_image, medialib_file etc
				'ext',			//расширения
				'fileName',		//имя файла
				'byteSize',		//размер в байтах
				'meta',			//метаданные (длина ролика, тип изображений и т.п.)
				'links_count',	//количество ссылок
				'ts',			//время загрузки
				'state',		//статус 0- в обработке, 1- готов	
			),'medialib_files','meta',$a
		);
	}
	/**
	 * Превью и другие порожденные файла
	 * @return dbSelect
	 */
	static function subfiles(){
		$a= func_get_args();
		return dbSelect::factory(
			array(
				'id',			//id по которому привязываются файлы
				'file',			//fk medialib_files.id
				'type',			//тип превью			
				'fileName',		//имя файла
				'ts'			//время создания			
			),'medialib_subfiles',null,$a
		);
	}
	/**
	 * Элементы контента
	 * @return dbSelect
	 */
	static function items(){
		$a= func_get_args();
		return dbSelect::factory(
			array(
				'id',
				'lib',			//fk medialib_libs.id
				'file',			//fk medialib_files.id
				'area',			//область к которой относиться файл
				'order',		//порядок сортировки
				'title',		//заголовок	
				'preview',		//fk. medialib_files.id
			),'medialib_items',null,$a
		);
	}
	/**
	 * Теги контента
	 * @return dbSelect
	 */
	static function tags(){
		
		$a= func_get_args();
		return dbSelect::factory(
			array(
				'id',
				'item',			//fk medialib_items.id
				'tag',			//текст тега
				'position',		//позиция (отрезок в ролике или область изображения)
			),'medialib_tags','position',$a
		);
	}
	
	static function crop(){
		$a= func_get_args();
		return dbSelect::factory(
			array(
				'file',			//fk medialib_files.id
				'crop',	//пропрорция
				'top',			
				'left',
				'width',
				'height',				
			),'medialib_crop',null,$a
		);
	}
}
?>
