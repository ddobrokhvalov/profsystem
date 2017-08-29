<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Форма обратной связи"
 *
 * @package    RBC_Contents_5_0
 * @subpackage app
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class form_question extends table_workflow
{
	/**
	 * Перед выводом карточки редактирования полю "Значение по умолчанию" выставляются
	 * дополнительные ограничения в зависимости от занчения поля "Тип вопроса"
	 *
	 * @see table::action_change()
	 */
	public function action_change()
	{
		$record = $this -> full_object -> get_change_record( $this -> primary_key -> get_from_request() );
		
		if ( $record['QUESTION_TYPE'] == 'email' )
			metadata::$objects[$this->obj]['fields']['DEFAULT_VALUE']['errors'] |= _email_;
		if ( $record['QUESTION_TYPE'] == 'date' )
			metadata::$objects[$this->obj]['fields']['DEFAULT_VALUE']['errors'] |= _date_;
		if ( $record['QUESTION_TYPE'] == 'int' )
			metadata::$objects[$this->obj]['fields']['DEFAULT_VALUE']['errors'] |= _int_;
		if ( $record['QUESTION_TYPE'] == 'float' )
			metadata::$objects[$this->obj]['fields']['DEFAULT_VALUE']['errors'] |= _float_;
		
		$this->call_parent('action_change');
	}
	
	/**
	 * Подготовка списка операций над записями
	 * 
	 * @return array
	 */
	public function get_index_operations()
	{
		$operations = $this -> call_parent( 'get_index_operations' );
		
		if ( $_REQUEST['_f_INF_BLOCK_ID'] )
			$operations['form_answer'] = array( 'name' => 'form_answer', 'alt' => metadata::$lang['lang_form_answer'],
				'url' => 'index.php?obj=FORM_ANSWER&_f_INF_BLOCK_ID=' . urlencode( $_REQUEST['_f_INF_BLOCK_ID'] ) );
		
		return $operations;
	}
		
	/**
	 * Перед вызовом метода базового класса метаданные таблицы видоизменяются с целью
	 * установки дополнительных проверок на некоторые поля таблицы
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		$question_type = $this -> field -> get_prepared( $raw_fields[$prefix.'QUESTION_TYPE'], metadata::$objects[$this->obj]['fields']['QUESTION_TYPE'] );
		// запоминаем описание поля до дополнения, потому что exec_add можно вызвать несколько раз подряд, например в момент экспорта
		$old_df = metadata::$objects[$this->obj]['fields']['DEFAULT_VALUE'];
		$this -> patch_metadata_errors( $question_type );
		$id=parent::exec_add( $raw_fields, $prefix );
		metadata::$objects[$this->obj]['fields']['DEFAULT_VALUE'] = $old_df;
		return $id;
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
		
		$this -> patch_metadata_errors( $record['QUESTION_TYPE'] );
		
		parent::exec_change( $raw_fields, $prefix, $pk );
	}
	
	
	/**
	 * Не позволяем удалять ответ, если он фигурирует в заполненных анкетах
	 *
	 * @see table::exec_delete()
	 */
	public function exec_delete( $pk, $partial = false )
	{
		$values_count = db::sql_select( '
				select count(*) as VALUES_COUNT from FORM_VALUES where FORM_QUESTION_ID = :form_question_id',
			array( 'form_question_id' => $pk['FORM_QUESTION_ID'] ) );
		
		if ( $values_count[0]['VALUES_COUNT'] )
			throw new Exception( $this -> te_object_name . ": " . metadata::$lang["lang_no_delete_with_children_table"] . " " .
				"\"" . metadata::$objects["FORM_VALUES"]["title"] . "\": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
		
		return $this -> call_parent( 'exec_delete', array( $pk, $partial ) );
	}
	
	/**
	 * Метод изменяет метаданные некоторый полей в зависимости от типа вопроса
	 *
	 * @param string $param_type	Тип вопроса
	 */
	public function patch_metadata_errors( $question_type )
	{
		// В зависимости от типа параметра изменяем метаданные поля "Значение по умолчанию"
		if ( $question_type == 'email' )
			metadata::$objects[$this->obj]['fields']['DEFAULT_VALUE']['errors'] |= _email_;
		if ( $question_type == 'date' )
			metadata::$objects[$this->obj]['fields']['DEFAULT_VALUE']['errors'] |= _date_;
		
		if ( in_array( $question_type, array( 'int', 'float' ) ) )
		{
			metadata::$objects[$this->obj]['fields']['DEFAULT_VALUE']['type'] = $question_type;
			metadata::$objects[$this->obj]['fields']['DEFAULT_VALUE']['is_null'] = true;
		}
	}
	
	/**
	 * Ограничение списка вопросов, имеющими перечисляемый тип, если $list_mode['select_only']
	 */
	public function ext_index_by_list_mode( $mode, $list_mode )
	{
		list( $where, $binds ) = $this->call_parent('ext_index_by_list_mode', array($mode, $list_mode));
		if ( $list_mode['select_only'] )
			$where .= " AND FORM_QUESTION.QUESTION_TYPE IN ('select', 'radio_group', 'radio_group_alt', 'checkbox_group', 'checkbox_group_alt' ) ";
		return array( $where, $binds );
	}
	
	/**
	 * Добавляем в конец таблицы скрипт вызова метода для изменения набора полей
	 *
	 * @see table::html_card()
	 */
	public function html_card($mode, &$request){
		list( $title, $html ) = parent::html_card($mode, $request);
		$form_name = html_element::get_form_name();
		
		$html .= <<<HTM
<script type="text/javascript" language="JavaScript">
	var oSelect = document.{$form_name}['_form_QUESTION_TYPE'];
	if ( oSelect )
	{
		addListener( oSelect, 'change', checkFormQuestion ); checkFormQuestion();
	}
	
	function checkFormQuestion()
	{
		var oDefault = document.getElementById('_form_DEFAULT_VALUE');
		var oDefaultInput = document.{$form_name}['_form_DEFAULT_VALUE'];
		
		if ( oDefault && oDefaultInput )
		{
			var sType = oSelect.options[ oSelect.selectedIndex ].value;
			switch ( sType )
			{
				case 'email': case 'int': case 'float': case 'date':
					oDefaultInput.setAttribute( 'lang', '_' + sType + '_' ); sDisplay = ''; break;
				case 'string': case 'textarea': case 'checkbox':
					oDefaultInput.setAttribute( 'lang', '' ); sDisplay = ''; break;
				default:
					oDefaultInput.setAttribute( 'lang', '' ); sDisplay = 'none';
			}
			oDefault.style.display = sDisplay;
		}
	}
</script>
HTM;
		return array( $title, $html );
	}
	
	/**
	* Дополняем данные для экспорта информацией о вариантах ответа
	*/
	
	public function get_export_add_data_xml($pk) {
		$xml=$this->call_parent('get_export_add_data_xml', array($pk));
		
		$form_options_obj = object::factory('FORM_OPTIONS');
		$options=db::sql_select('
			SELECT '.
				$form_options_obj->primary_key->select_clause().' 
			FROM 
				FORM_OPTIONS
			WHERE 
				FORM_QUESTION_ID=:form_question_id', 
			array('form_question_id'=>$pk['FORM_QUESTION_ID'])
		);
		if (sizeof($options)) {
			$xml .= "<LINKS>\n";
			
			for ($i=0, $n=sizeof($options); $i<$n; $i++) 
				$xml .= preg_replace('/^/m', '  ', $form_options_obj->get_export_xml($options[$i]));
			
			$xml .= "</LINKS>\n";
		}
		
		$form_options_obj->__destruct();
		return $xml;
	}
	
}
?>
