<?php
/**
* Класс для автотеста таблицы TEMPLATE_AREA_MAP
* @package    RBC_Contents_5_0
* @subpackage cms
* @copyright  Copyright (c) 2007 RBC SOFT 
*/

class check_template_area_map extends autotest_test{

	/**
	* Предусмотрено глобальное исправление
	*/
	protected $is_for_global_fix=true;

	/**
	* тест
	*/
	function do_test() {
		$this->find_error();
		$c=count($this->no_tt);
		if($c>0){
			$this->report[]=array("descr"=>metadata::$lang['lang_autotest_test_template_area_map_founded_records_tied_to_absent_template_types'].": {$c} ","status"=>1);
		}
		$c=count($this->no_ta);
		if($c>0){
			$this->report[]=array("descr"=>metadata::$lang['lang_autotest_test_template_area_map_founded_records_tied_to_absent_areas'].": {$c} ","status"=>1);
		}
		
		$this->global_fix_confirm = metadata::$lang['lang_autotest_test_content_map_bad_records_will_be_deleted'];
	}


	/**
	* исправление
	*/
	function fix_action(){
		$this->find_error();
		foreach($this->no_tt as $tam){
			db::delete_record('TEMPLATE_AREA_MAP', array('TEMPLATE_TYPE_ID'=>$tam["TEMPLATE_TYPE_ID"], 'TEMPLATE_AREA_ID'=>$tam["TEMPLATE_AREA_ID"]));
		}
		foreach($this->no_ta as $tam){
			db::delete_record('TEMPLATE_AREA_MAP', array('TEMPLATE_TYPE_ID'=>$tam["TEMPLATE_TYPE_ID"], 'TEMPLATE_AREA_ID'=>$tam["TEMPLATE_AREA_ID"]));
		}
		return metadata::$lang['lang_done'];		
	}
	
	
	/**
	* Внутренняя ф-ия поиска ошибки
	*/

	function find_error(){
		$this->no_tt=array();
		$this->no_ta=array();
		
		$tarea_map=db::sql_select("SELECT * FROM TEMPLATE_AREA_MAP");
		$template_types=db::sql_select("SELECT * FROM TEMPLATE_TYPE");
		foreach($template_types as $tt){
			$r_tt[$tt["TEMPLATE_TYPE_ID"]]=$tt;
		}
		$template_areas=db::sql_select("SELECT * FROM TEMPLATE_AREA");
		foreach($template_areas as $ta){
			$r_ta[$ta["TEMPLATE_AREA_ID"]]=$ta;
		}
	
		foreach($tarea_map as $tam){
			if(!isset($r_tt[$tam["TEMPLATE_TYPE_ID"]])){
				$this->no_tt[]=$tam;
			}
			elseif(!isset($r_ta[$tam["TEMPLATE_AREA_ID"]])){
				$this->no_ta[]=$tam;
			}
		}
	}
}
?>