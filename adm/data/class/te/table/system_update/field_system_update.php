<?PHP
	/**
	* Класс обработки полей для системы обновлений RBC Contents
	*
	* @package		RBC_Contents_5_0
	* @subpackage te
	* @copyright	Copyright (c) 2007 RBC SOFT
	* @author Alexandr Vladykin
	*
	*/
	class field_system_update extends field {
		/**
		* Переписан метод обработки загруженного файла с обновлением
		*/
	public function prepare_file( $content, $field_descr, $field_name )
	{
		$su_obj=object::factory('SYSTEM_UPDATE');
		
		if ( isset( $_FILES[$field_name.'_file'] ) && ( $_FILES[$field_name.'_file']['error'] == UPLOAD_ERR_OK ) )
		{
			$upload_file = upload::upload_file( $_FILES[$field_name.'_file'], $su_obj->updates_path, true, false);
			return $upload_file;
		}
		else {
			$src_file_name=basename($content);
			$dest_file_name=upload::get_unique_file_name( $su_obj->updates_path, $src_file_name );
			$dest_path=$su_obj->updates_path.'/'.$dest_file_name;
			if (copy($content, $dest_path)) {
				return realpath( $dest_path );
			}
		}
		return false;
	}
	}
?>