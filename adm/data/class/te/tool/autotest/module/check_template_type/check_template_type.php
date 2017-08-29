<?php
/**
* Класс для автотеста корректности поля "тип шаблона" у шаблонов раздела
* @package    RBC_Contents_5_0
* @subpackage cms
* @copyright  Copyright (c) 2007 RBC SOFT 
*/

class check_template_type extends autotest_test{

	/**
	* тест
	*/

	public function do_test(){
		$this->find_error();
	}
	
	
	/**
	* возможности исправления
	*/
	
	public function fix_index(){
		$page=db::sql_select("SELECT * FROM TEMPLATE WHERE TEMPLATE_ID=:template_id", array('template_id'=>$_GET["TEMPLATE_ID"]));


		$fields=array(
			'TEMPLATE_TYPE_ID'=>array(
				'title' => $page[0]['TITLE'],
				'type' => 'select2',
				"fk_table"=>"TEMPLATE_TYPE",
				"errors"=>_nonempty_
			)
		);
		
		$test_obj = object::factory('AUTOTEST');
		$form_fields = $test_obj->get_form_fields('add', '', '', '', $fields);
		
		$form_name = html_element::get_next_form_name();
		
		$html_fields = html_element::html_fields( $form_fields, $this -> template_path . '../../core/html_element/html_fields.tpl', $test_obj->field );
		$form = html_element::html_form( $html_fields, $this->url->get_hidden (
												'fix_action', 
												array(
													pk=>array(
														"TEMPLATE_ID" => $page[0]['TEMPLATE_ID'],
														"SYSTEM_NAME" => $this->get_property('SYSTEM_NAME'),
													)
												)
										), $this -> template_path . '../../core/html_element/html_form.tpl', true );

		$operations = array (
			'save' => array("name"=>"save", "alt"=>metadata::$lang["lang_action_save"], "onClick"=>"if ( CheckForm.validate( document.forms['{$form_name}'])) {document.forms['{$form_name}'].submit()}; return false;", url=>"#"),
			'cancel' => array("name"=>"cancel", "alt"=>metadata::$lang["lang_cancel"], "url"=>$this->url->get_url( "", array( "restore_params" => 1 ) ))
		);
		
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'title', metadata::$lang['lang_autotest_test_template_type_type_for_template'] );
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> template_path . '../../core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> template_path . '../../core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'form', $form );
		
		return $tpl -> fetch( $this -> template_path . '../../core/html_element/html_card.tpl' );		
	}


	/**
	* исправление
	*/
	
	public function fix_action(){
		db::update_record('TEMPLATE', array('TEMPLATE_TYPE_ID'=>$_REQUEST['TEMPLATE_TYPE_ID']), array(), array('TEMPLATE_ID'=>$_REQUEST['TEMPLATE_ID']));
		return metadata::$lang["lang_autotest_test_template_type_template_type_set"];
	}

	/**
	* функция проверки
	*/
	private function find_error(){
		$template_types=db::sql_select("SELECT * FROM TEMPLATE_TYPE");
		foreach($template_types as $template_type){
			$r_template_types[$template_type["TEMPLATE_TYPE_ID"]]=$template_type;
		}
		
		list( $dec_field0, $dec_where_search0, $dec_join0, $dec_binds0 ) =
			object::factory( 'TEMPLATE' ) -> ext_field_selection( 'TITLE', 1 );
		
		$templates=db::replace_field(db::sql_select("SELECT TEMPLATE.*, " . $dec_field0 . " AS \"_TITLE\" FROM TEMPLATE " . $dec_join0[0] . " ORDER BY " . $dec_field0, $dec_binds0 ), 'TITLE', '_TITLE');
		foreach($templates as $template){
			if(!isset($r_template_types[$template["TEMPLATE_TYPE_ID"]])){
				$this->report[]=array("descr"=>"\"".$template["TITLE"]."\": ".metadata::$lang['lang_autotest_test_template_type_template_have_non_existent_type'].". TEMPLATE_ID: {$template["TEMPLATE_ID"]}","status"=>0,"fix_link"=>"TEMPLATE_ID={$template["TEMPLATE_ID"]}");
			}
		}
	}
}
?>