<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Блоги"
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2008 RBC SOFT
 */
class blog extends table
{

	/**
	 * Дополнтиельная проверка корректности ввода пароля
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		$this -> check_password( $raw_fields, $prefix );
		
		$added_blog_id = parent::exec_add( $raw_fields, $prefix );

        include_once( params::$params["common_data_server"]["value"]."module/module/module.php" );
        $blog_site=module::factory('BLOGS');
        $blog_site->setTplDir(params::$params["common_data_server"]["value"]."module_tpl/blogs/default/");
        $blog_site->createBlogFolder($raw_fields[$prefix.'TITLE'], $added_blog_id);
        
        return $added_blog_id;
	}
	
	/**
	 * Обновление соответствующей записи в таблице "Администраторы"
	 *
	 * @see table::exec_change()
	 */
	public function exec_change( $raw_fields, $prefix, $pk )
	{
		// Дополнительная проверка корректности ввода пароля
		$this -> check_password( $raw_fields, $prefix, $pk );

		parent::exec_change( $raw_fields, $prefix, $pk );
	}
	
	/**
	 * Отвязка пользователя от соответствующего администратора
	 *
	 * @see table::ext_finalize_delete()
	 */
	public function ext_finalize_delete( $pk, $partial = false )
	{
        include_once( params::$params["common_data_server"]["value"]."module/module/module.php" );
        $blog_site=module::factory('BLOGS');
        $blog_site->removeBlogFolder($pk['BLOG_ID']);

        parent::ext_finalize_delete( $pk, $partial );
    }
	
	///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Действие - карточка добавления
	 *
	 * @metadatamod При добавлении делаем поле "Пароль" обязательным
	 */
	public function action_add(){
		metadata::$objects[$this->obj]["fields"]["PASSWORD_MD5"]["errors"]|=_nonempty_;
		parent::action_add();
	}
	
	/**
	 * Возвращает подготовленные к помещению в БД данные
	 *
	 * @metadatamod Если при редактировании поле "Пароль" не заполнено, то делаем это поле неизменяемым
	 */
	public function get_prepared_fields($raw_fields, $prefix, $mode){
		if(!$raw_fields[$prefix."PASSWORD_MD5"] && $mode=="change"){
			metadata::$objects[$this->obj]["fields"]["PASSWORD_MD5"]["no_change"]=1;
		}

		return parent::get_prepared_fields($raw_fields, $prefix, $mode);
	}
	
	/**
	 * Проверяет поле "Пароль" на корректность
	 */
	public function check_password( $raw_fields, $prefix, $pk = '' )
	{
		if ( params::$params["min_password_length"]["value"] && $raw_fields[$prefix . 'PASSWORD_MD5'] !== '' &&
				strlen( $raw_fields[$prefix . 'PASSWORD_MD5'] ) < params::$params["min_password_length"]["value"] )
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_min_password_length"]." (".params::$params["min_password_length"]["value"].")".
				($pk?": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")":""));
		
		if ( params::$params["password_letters_and_numbers"]["value"] && $raw_fields[$prefix . 'PASSWORD_MD5'] !== '' &&
				( !preg_match( '/[0-9]/', $raw_fields[$prefix . 'PASSWORD_MD5'] ) || !preg_match( '/[A-Za-z]/', $raw_fields[$prefix . 'PASSWORD_MD5'] ) ) )
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_password_letters_and_numbers"].
				($pk?": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")":""));
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
		
	/**
	 * Проверка корректности введенного пароля на стороне браузера
	 *
	 * @see table::html_card()
	 */
	public function html_card( $mode, &$request )
	{
		list( $title, $html ) = parent::html_card( $mode, &$request );
		
		if ( params::$params["min_password_length"]["value"] || params::$params["password_letters_and_numbers"]["value"] )
		{
			$html .= <<<HTM
			
<script type="text/javascript">
	CheckForm.validate_ext = function()
	{
		var oPassword = this.oForm['_form_PASSWORD_MD5'];
HTM;
			if ( $min_password_length = params::$params["min_password_length"]["value"] )
				$html .= <<<HTM
		
		if ( oPassword && oPassword.value.length > 0 && oPassword.value.length < {$min_password_length} )
		{
			alert( Dictionary.translate( 'lang_min_password_length' ) + ': ' + {$min_password_length} );
			try { oPassword.focus() } catch (e) {};
			return false;
		}
HTM;
			if ( params::$params["password_letters_and_numbers"]["value"] )
				$html .= <<<HTM
		
		if ( oPassword && oPassword.value.length > 0 &&
			( !( /[0-9]/.test( oPassword.value ) ) || !( /[A-Za-z]/.test( oPassword.value ) ) ) )
		{
			alert( Dictionary.translate( 'lang_password_letters_and_numbers' ) );
			try { oPassword.focus() } catch (e) {};
			return false;
		}
HTM;
			$html .= <<<HTM
		
		return true;
	}
</script>
HTM;
		}
		
		return array( $title, $html );
	}
}
?>
