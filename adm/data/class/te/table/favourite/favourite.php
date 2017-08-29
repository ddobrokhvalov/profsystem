<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Избранное"
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class favourite extends table
{
	/**
	 * Заполянем поля карточки добавления записи соответствующим значениями из REQUEST
	 * 
	 * @see table::action_add()
	 */
	public function action_add()
	{
		metadata::$objects[$this->obj]['fields']['URL']['value'] = $_REQUEST['url'];
		metadata::$objects[$this->obj]['fields']['TITLE']['value'] = $_REQUEST['title'];
		
		$_SESSION['back_url'] = $_SERVER['HTTP_REFERER'];
		
		parent::action_add();
	}
	
	/**
	 * После добавления записи возвращаемся на исходную страницу
	 * 
	 * @see table::action_added()
	 */
	public function action_added()
	{
		if ( !metadata::$objects[$this -> obj]['no_add']) {
			$_REQUEST['_form_AUTH_USER_ID'] = intval( $_SESSION['AUTH_USER_ID'] );
			$this -> full_object -> exec_add( $_REQUEST, '_form_' );
		}
		
		if ( preg_match( '/obj=FAVOURITE/i', $_SESSION['back_url'] ) )
			$this -> url -> redirect();
		
		header( 'Location: ' . $_REQUEST['_form_URL'] );
		
		exit;
	}
	
	/**
	 * Подготовка списка операций
	 */
	public function get_record_operations( $form_name = '', $apply_action = '' )
	{
		$operations = parent::get_record_operations( $form_name, $apply_action );
		
		if ( $_REQUEST['url'] )
			$operations['cancel']['url'] = $_REQUEST['url'];
		
		return $operations;
	}
	
	/**
	 * Перед вызовом метода базового класса метаданные таблицы видоизменяются.
	 * Добаляется новое поле AUTH_USER_ID, которое заполняется идентификатором пользователя
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		unset( metadata::$objects[$this->obj]['fields']['AUTH_USER_ID']['no_add'] );
		
		return parent::exec_add( $raw_fields, $prefix );
	}
	
	/**
	 * Из массива, возвращаемого методом базового класс удаляется колонка "URL"
	 *
	 * @see table::get_index_header()
	 */
	public function get_index_header( $mode )
	{
		$header = parent::get_index_header( $mode );
		unset( $header['URL'] );
		return $header;
	}
	
	/**
	 * Делаем колонку "Название" ссылкой на указанный раздел
	 *
	 * @see table::get_index_records()
	 */
	public function get_index_records( &$request, $mode, $list_mode, $include = 0, $exclude = array() )
	{
		$records = parent::get_index_records( $request, $mode, $list_mode, $include, $exclude );
		foreach ( $records as & $record )
			$record['TITLE'] = "<a href=\"{$record['URL']}\">" . htmlspecialchars( $record['TITLE'], ENT_QUOTES ) . "</a>";
		return $records;
	}
	
	/**
	 * Дополняем условие фильтрации ограничением по идентификатору пользователя
	 *
	 * @return string
	 */
	public function ext_index_query()
	{
		return ' and AUTH_USER_ID = :auth_user_id ';
	}
	
	/**
	 * Дополняем набор переменных привязки идентификатором пользователя
	 *
	 * @return array
	 */
	public function ext_index_query_binds()
	{
		return array( 'auth_user_id' => intval( $_SESSION['AUTH_USER_ID'] ) );
	}
	
	/**
	 * Дополняем $_REQUEST фильтром по пользователю, чтобы правильно группировка поля "Порядок"
	 *
	 * @see table::html_card()
	 */
	public function html_card($mode, &$request)
	{
		$request['_f_AUTH_USER_ID'] = $_SESSION['AUTH_USER_ID'];
		return parent::html_card($mode, $request);
	}
	
	/**
	 * Доступ к "Избранному" должны иметь все пользователи
	 *
	 * @see	object::is_permitted_to
	 */
	 public function is_permitted_to($ep_type, $pk="", $throw_exception=false){
		if ( params::$params["client_mode"]["value"] )
			return false;
		
		return true;
	}
}
?>
