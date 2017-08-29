<?php
/**
 * Класс для редактирования личных данных пользователя
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2007 RBC SOFT
 */
class personal_info_tool extends tool
{
	/**
	* Указывает методу html_card(), что данные для формы нужно брать из $_REQUEST
	*/
	protected $from_request = false;
	
	/**
	 * Список редактируемых полей
	 */
	protected $change_fields = array( 'SURNAME', 'NAME', 'PATRONYMIC', 'LOGIN', 'PASSWORD_MD5', 'EMAIL' );
	
	/**
	 * Изменение личных данных
	 */
	protected function exec_change( $raw_fields, $prefix, $pk )
	{
		$auth_user_obj = object::factory( 'AUTH_USER' );
		
		foreach( $auth_user_obj -> get_change_record( $pk, true ) as $field_name => $field_value )
			if ( !in_array( $field_name, $this -> change_fields ) || !isset( $raw_fields[$prefix . $field_name] ) )
				$raw_fields[$prefix . $field_name] = $field_value;
		
		$auth_user_obj -> exec_change( $raw_fields, $prefix, $pk );
		
		$auth_user_obj -> __destruct();
	}
	
	/**
	 * Действие - страница по умолчанию
	 * 
	 */
	protected function action_index()
	{
		list( $this -> title, $this -> body ) = $this -> full_object -> html_card( $_REQUEST );
	}
	
	/**
	 * Действие - изменяет существующую запись
	 */
	protected function action_changed()
	{
		try {
			$metadata = metadata::$objects['AUTH_USER'];
			$pk = array( 'AUTH_USER_ID' => $_SESSION['AUTH_USER_ID'] );
			$this -> full_object -> exec_change( $_REQUEST, '_form_', $pk );
			$this -> url -> redirect();
		} catch ( Exception $e ) {
			$_REQUEST['action'] = 'change';
			metadata::$objects['AUTH_USER'] = $metadata;
			$this -> from_request = true; $this -> full_object -> action_index();
			$this -> body = object::html_error( $e -> getMessage(), $e -> getFile(), $e -> getLine(), '', $e -> getTraceAsString(), false ) . $this -> body;
		}
	}
	
	/**
	 * Карточка редактирования личных данных
	*/
	protected function html_card( &$request )
	{
		$pk = array( 'AUTH_USER_ID' => $_SESSION['AUTH_USER_ID'] );
		
		$auth_user_obj = object::factory( 'AUTH_USER' );
		$record = $auth_user_obj -> get_change_record( $pk, true );
		
		if ( $this -> from_request )
			foreach ( metadata::$objects[$auth_user_obj -> obj]['fields'] as $field_name => $field_descr )
				if ( isset( $request['_form_' . $field_name] ) )
					$record[$field_name] = $request['_form_' . $field_name];
		
		$form_fields = $auth_user_obj -> get_form_fields( 'change', '_form_', $record );
		$auth_user_obj -> __destruct();
		
		foreach( $form_fields as $field_name => $field_desc )
			if ( !in_array( $field_name, $this -> change_fields ) )
				unset( $form_fields[$field_name] );
		
		$title = metadata::$lang['lang_change_record'];
		$form_name = html_element::get_next_form_name();
		
		$html_fields = html_element::html_fields( $form_fields, $this -> tpl_dir . 'core/html_element/html_fields.tpl', $this -> field, !$this->from_request );
		$form = html_element::html_form( $html_fields, $this -> url -> get_hidden( 'changed' ), $this -> tpl_dir . 'core/html_element/html_form.tpl', true );
		
		$operations = $this -> get_record_operations( $form_name );
		
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'title', metadata::$objects[$this -> obj]['title'] );
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'form', $form );
		
		return array( $title, $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' ) );		
	}
	
	/**
	 * Cписок операций
	 */
	protected function get_record_operations( $form_name )
	{
		return array( array( "name" => "apply", "alt" => metadata::$lang["lang_action_apply"], "url"=>"javascript:if ( CheckForm.validate( document.forms['{$form_name}'] ) ) { document.forms['{$form_name}'].submit() }" ) );
	}
	
	/**
	 * Доступ к личным данным должны иметь все пользователи
	 *
	 * @see	object::is_permitted_to
	 */
	 public function is_permitted_to($ep_type, $pk="", $throw_exception=false){
		return true;
	}
}
?>
