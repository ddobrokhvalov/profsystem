<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Параметры модулей"
 *
 * @package    RBC_Contents_5_0
 * @subpackage cms
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class module_param extends table_translate
{
	/**
	 * Перед выводом карточки редактирования полю "Значение по умолчанию" выставляются
	 * дополнительные ограничения в зависимости от занчения поля "Тип параметра модуля"
	 *
	 * @see table::action_change()
	 */
	public function action_change()
	{
		$record = $this -> full_object -> get_change_record( $this -> primary_key -> get_from_request() );
		
		if ( $record['PARAM_TYPE'] == 'int' )
			metadata::$objects[$this->obj]['fields']['DEFAULT_VALUE']['errors'] |= _int_;
		if ( $record['PARAM_TYPE'] == 'float' )
			metadata::$objects[$this->obj]['fields']['DEFAULT_VALUE']['errors'] |= _float_;
		if ( $record['PARAM_TYPE'] == 'table' )
		{
			metadata::$objects[$this->obj]['fields']['TABLE_NAME']['errors'] |= _nonempty_;
			metadata::$objects[$this->obj]['fields']['FIELD_NAME']['errors'] |= _nonempty_;
		}
		parent::action_change();
	}
	
	/**
	 * Перед вызовом метода базового класса метаданные таблицы видоизменяются с целью
	 * установки дополнительных проверок на некоторые поля таблицы
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		$param_type = $this -> field -> get_prepared( $raw_fields[$prefix.'PARAM_TYPE'], metadata::$objects[$this->obj]['fields']['PARAM_TYPE'] );
		
		$this -> patch_metadata_errors( $param_type );
		
		return parent::exec_add( $raw_fields, $prefix );
	}
	
	/**
	 * Перед вызовом метода базового класса метаданные таблицы видоизменяются с целью
	 * установки дополнительных проверок на некоторые поля таблицы
	 *
	 * @see table::exec_change()
	 */
	public function exec_change( $raw_fields, $prefix, $pk )
	{
		$record = $this -> full_object -> get_change_record( $pk );
		
		$this -> patch_metadata_errors( $record['PARAM_TYPE'] );
		
		parent::exec_change( $raw_fields, $prefix, $pk );
	}
	
	/**
	 * Метод изменяет метаданные некоторый полей в зависимости от типа параметра модуля
	 *
	 * @param string $param_type	Типа параметра модуля
	 * @todo Для красоты можно применить метод {@link object::add_field_error()} для прикладывания _nonempty_
	 */
	public function patch_metadata_errors( $param_type )
	{
		// В зависимости от типа параметра изменяем метаданные поля "Значение по умолчанию" 
		if ( $param_type == 'int' )
			metadata::$objects[$this->obj]['fields']['DEFAULT_VALUE']['type'] = 'int';
		if ( $param_type == 'float' )
			metadata::$objects[$this->obj]['fields']['DEFAULT_VALUE']['type'] = 'float';
		
		// В зависимости от типа параметра изменяем метаданные полей "Название таблицы" и "Поле-название" на обязательные или нет
		if ( $param_type == 'table' ){
			metadata::$objects[$this->obj]['fields']['TABLE_NAME']['errors'] |= _nonempty_;
			metadata::$objects[$this->obj]['fields']['FIELD_NAME']['errors'] |= _nonempty_;
		}else{
			metadata::$objects[$this->obj]['fields']['TABLE_NAME']['errors'] &= ~_nonempty_;
			metadata::$objects[$this->obj]['fields']['FIELD_NAME']['errors'] &= ~_nonempty_;
		}
	}
	
	/**
	 * Ограничение списка параметров модулей только теми, что имеют тип "select" или "template", если $list_mode["select_only"]
	 */
	public function ext_index_by_list_mode( $mode, $list_mode )
	{
		list( $where, $binds ) = $this -> call_parent( 'ext_index_by_list_mode', array( $mode, $list_mode ) );
		if ( $list_mode["select_only"] )
			$where .= " AND MODULE_PARAM.PARAM_TYPE IN ('template', 'select') ";
		return array( $where, $binds );
	}
	
	/**
	 * Добавляем в конец таблицы скрипт вызова метода для изменения набора полей
	 *
	 * @see table::html_card()
	 */
	public function html_card($mode, &$request)
	{
		list( $title, $html ) = $this -> call_parent( 'html_card', array( $mode, &$request ) );
		$form_name = html_element::get_form_name();
		
		$html .=  <<<HTML
<script type="text/javascript">
	var oSelect = document.{$form_name}['_form_PARAM_TYPE'];
	if ( oSelect )
	{
		addListener( oSelect, 'change', checkModuleParam ); checkModuleParam();
	}
	
	function checkModuleParam()
	{
		var sParamType = oSelect.options[ oSelect.selectedIndex ].value;
		
		var oDefault = document.getElementById( '_form_DEFAULT_VALUE' );
			var oDefaultInput = document.{$form_name}['_form_DEFAULT_VALUE'];
		var oTableName = document.getElementById( '_form_TABLE_NAME' );
			var oTableNameInput = document.{$form_name}['_form_TABLE_NAME'];
		var oFieldName = document.getElementById( '_form_FIELD_NAME' );
			var oFieldNameInput = document.{$form_name}['_form_FIELD_NAME'];
		var oIsLang = document.getElementById( '_form_IS_LANG' );
		
		oDefault.style.display = ( sParamType == 'int' || sParamType == 'float' || sParamType == 'varchar' || sParamType == 'textarea' ) ? '' : 'none';
		oTableName.style.display = oFieldName.style.display = oIsLang.style.display = ( sParamType == 'table' ) ? '' : 'none';
		
		switch ( sParamType )
		{
			case 'int': case 'float':
				oDefaultInput.setAttribute( 'lang', '_' + sParamType + '_' );
				oTableNameInput.setAttribute( 'lang', '' );
				oFieldNameInput.setAttribute( 'lang', '' );
				break;
			case 'table':
				oDefaultInput.setAttribute( 'lang', '' );
				oTableNameInput.setAttribute( 'lang', '_nonempty_' );
				oFieldNameInput.setAttribute( 'lang', '_nonempty_' );
				break;
			default:
				oDefaultInput.setAttribute( 'lang', '' );
				oTableNameInput.setAttribute( 'lang', '' );
				oFieldNameInput.setAttribute( 'lang', '' );
		}
	}
</script>
HTML;
		return array( $title, $html );
	}
}
?>