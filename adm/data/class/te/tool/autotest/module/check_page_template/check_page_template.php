<?php
/**
* Класс для автотеста корректности шаблонов страниц
* @package    RBC_Contents_5_0
* @subpackage cms
* @copyright  Copyright (c) 2007 RBC SOFT 
*/

class check_page_template extends autotest_test{

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
		$page=db::sql_select("SELECT * FROM PAGE WHERE PAGE_ID=:page_id AND VERSION=:version", array('page_id'=>$_GET["PAGE_ID"], 'version'=>$_GET['VERSION']));

		$fields=array(
			'TEMPLATE_ID'=>array(
				'title' => metadata::$lang["lang_version"].': '.($page[0]['VERSION']?metadata::$lang["lang_test"] : metadata::$lang["lang_work"]),
				'type' => 'select2',
				"fk_table"=>"TEMPLATE",
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
														"PAGE_ID" => $page[0]['PAGE_ID'],
														"VERSION" => $page[0]['VERSION'],
														"SYSTEM_NAME" => $this->get_property('SYSTEM_NAME'),
													)
												)
										), $this -> template_path . '../../core/html_element/html_form.tpl', true );

		$operations = array (
			'save' => array("name"=>"save", "alt"=>metadata::$lang["lang_action_save"], "onClick"=>"if ( CheckForm.validate( document.forms['{$form_name}'])) {document.forms['{$form_name}'].submit()}; return false;", url=>"#"),
			'cancel' => array("name"=>"cancel", "alt"=>metadata::$lang["lang_cancel"], "url"=>$this->url->get_url( "", array( "restore_params" => 1 ) ))
		);
		
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'title', metadata::$lang['lang_autotest_test_page_template_template_for_page'].' '.$page[0]['TITLE'] );
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> template_path . '../../core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> template_path . '../../core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'form', $form );
		
		return $tpl -> fetch( $this -> template_path . '../../core/html_element/html_card.tpl' );		
	}
	
	
	/**
	* исправление
	*/
	public function fix_action(){
		db::update_record('PAGE', array('TEMPLATE_ID'=>$_REQUEST['TEMPLATE_ID']), array(), array('PAGE_ID'=>$_REQUEST['PAGE_ID'], 'VERSION'=>$_REQUEST['VERSION']));
		return metadata::$lang["lang_autotest_test_page_template_template_assigned"];
	}
	
	/**
	* функция проверки
	*/
	private function find_error(){
		$templates=db::sql_select("SELECT * FROM TEMPLATE");
		foreach($templates as $template){
			$r_templates[$template["TEMPLATE_ID"]]=$template;
		}
		$pages=db::sql_select("SELECT * FROM PAGE WHERE PAGE_TYPE=:page_type ORDER BY TITLE", array('page_type'=>'page'));
		foreach($pages as $page){
			if(!isset($r_templates[$page["TEMPLATE_ID"]])){
				$this->report[]=array("descr"=>"{$page["TITLE"]}: ".metadata::$lang['lang_autotest_test_page_template_page_have_non_existent_template']." PAGE_ID: {$page["PAGE_ID"]}, ".metadata::$lang['lang_version'].": ".($page["VERSION"] ? metadata::$lang["lang_test"] : metadata::$lang["lang_work"]),"status"=>0,"fix_link"=>"PAGE_ID={$page["PAGE_ID"]}&VERSION={$page["VERSION"]}");
			}
		}
	}
}
?>