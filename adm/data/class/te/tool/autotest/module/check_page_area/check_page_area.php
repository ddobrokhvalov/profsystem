<?php
/**
* Класс автотеста - проверка областей разделов
* @package    RBC_Contents_5_0
* @subpackage cms
* @copyright  Copyright (c) 2007 RBC SOFT 
*/
class check_page_area extends autotest_test{
	
	/**
	* Предусмотрено глобальное исправление
	*/
	protected $is_for_global_fix=true;
	
	/**
	* Тестируем
	*/
	public function do_test(){
		$error_types[]=array("table"=>"PAGE_AREA","error_name"=>"pa_no_ta","error_descr"=>metadata::$lang["lang_autotest_test_page_area_absent_template_areas"]);
		$error_types[]=array("table"=>"PAGE_AREA","error_name"=>"pa_no_pv","error_descr"=>metadata::$lang["lang_autotest_test_page_area_absent_pages"]);
		$error_types[]=array("table"=>"PAGE_AREA","error_name"=>"pa_no_ib","error_descr"=>metadata::$lang["lang_autotest_test_page_area_absent_blocks"]);
		$error_types[]=array("table"=>"PAGE_AREA","error_name"=>"pa_no_tt","error_descr"=>metadata::$lang["lang_autotest_test_page_area_pages_invalid_template_type"]);
	
		$error_types[]=array("table"=>"PAGE_AREA_PARAM","error_name"=>"pap_no_ta","error_descr"=>metadata::$lang["lang_autotest_test_page_area_absent_template_areas"]);
		$error_types[]=array("table"=>"PAGE_AREA_PARAM","error_name"=>"pap_no_pv","error_descr"=>metadata::$lang["lang_autotest_test_page_area_absent_pages"]);
		$error_types[]=array("table"=>"PAGE_AREA_PARAM","error_name"=>"pap_no_mp","error_descr"=>metadata::$lang["lang_autotest_test_page_area_absent_module_parameters"]);
		$error_types[]=array("table"=>"PAGE_AREA_PARAM","error_name"=>"pap_no_tt","error_descr"=>metadata::$lang["lang_autotest_test_page_area_pages_invalid_template_type"]);
		$error_types[]=array("table"=>"PAGE_AREA_PARAM","error_name"=>"pap_no_pa","error_descr"=>metadata::$lang["lang_autotest_test_page_area_for_absent_page_areas"]);
		$error_types[]=array("table"=>"PAGE_AREA_PARAM","error_name"=>"pap_no_true_mp","error_descr"=>metadata::$lang["lang_autotest_test_page_area_for_module_parameters_of_invalid_blocks"]);
		$this->find_error();
		foreach($error_types as $error_type){
			$error_name=$error_type["error_name"];
			if($this->$error_name){
				$this->report[]=array("descr"=>metadata::$lang['lang_autotest_test_page_area_founded_records_in_table']." {$error_type["table"]} ".metadata::$lang['lang_autotest_test_page_area_tied_to']." {$error_type["error_descr"]}: {$this->$error_name} ","status"=>1);
			}
		}
		$this->global_fix_confirm = metadata::$lang['lang_autotest_test_content_map_bad_records_will_be_deleted'];
	}

	/**
	* Вывод в массив report вариантов исправления
	*/
	public function fix_index(){
		$this->report[]=array("descr"=>metadata::$lang['lang_autotest_test_content_map_bad_records_will_be_deleted'], 'link_descr'=>metadata::$lang['lang_delete']);
	}
	
	/**
	* Автоисправление
	*/
	public function fix_action(){
		$this->find_error();
		foreach($this->broken_pap as $b_pap){
			db::delete_record('PAGE_AREA_PARAM', array('PAGE_ID'=>$b_pap["PAGE_ID"], 'VERSION'=>$b_pap["VERSION"], 'TEMPLATE_AREA_ID'=>$b_pap["TEMPLATE_AREA_ID"], 'MODULE_PARAM_ID'=>$b_pap["MODULE_PARAM_ID"]));
		}
		foreach($this->broken_pa as $b_pa){
			db::delete_record('PAGE_AREA', array('PAGE_ID'=>$b_pa['PAGE_ID'], 'VERSION'=>$b_pa["VERSION"], 'TEMPLATE_AREA_ID'=>$b_pa["TEMPLATE_AREA_ID"]));
		}
		return metadata::$lang['lang_done'];
	}

	/**
	* Внутренняя ф-ия поиска ошибки
	*/

	private function find_error() {
		// PAGE_AREA
		
		$this->broken_pa=array();
				
		// проверка PAGE_AREAs на существование соотв. TEMPLATE_AREA
		$this->pa_no_ta = $this->set_error_data ("
			SELECT 
				PA.* 
			FROM 
				PAGE_AREA PA 
					LEFT JOIN
						TEMPLATE_AREA TA
					ON (TA.TEMPLATE_AREA_ID = PA.TEMPLATE_AREA_ID)
			WHERE 
				TA.TEMPLATE_AREA_ID IS NULL", $this->broken_pa);
		
		// проверка PAGE_AREAs на существование записи в PAGE с соотв. PAGE_ID и VERSIOn
		$this->pa_no_pv = $this->set_error_data ("
			SELECT 
				PA.* 
			FROM 
				PAGE_AREA PA 
					LEFT JOIN
						PAGE P
					ON (PA.PAGE_ID = P.PAGE_ID AND PA.VERSION = P.VERSION)
			WHERE 
				P.PAGE_ID IS NULL", $this->broken_pa);		
		
		// проверка PAGE_AREAs на существование информационного блока
		$this->pa_no_ib = $this->set_error_data ("
			SELECT 
				PA.* 
			FROM 
				PAGE_AREA PA 
					LEFT JOIN
						INF_BLOCK IB
					ON (PA.INF_BLOCK_ID = IB.INF_BLOCK_ID)
			WHERE 
				IB.INF_BLOCK_ID IS NULL", $this->broken_pa);		
	
		// проверка PAGE_AREAs на существование соотв. шаблона, карты и пространства
		$this->pa_no_tt = $this->set_error_data ("
 			SELECT 
 				PA.* 
 			FROM 
 				PAGE_AREA PA  
 			WHERE 
 				NOT EXISTS ( 
 					SELECT 
 						* 
 					FROM  
 						PAGE, 
 						TEMPLATE, 
 						TEMPLATE_AREA_MAP 
 					WHERE 
 						PAGE.TEMPLATE_ID=TEMPLATE.TEMPLATE_ID  
 							AND 
 								TEMPLATE_AREA_MAP.TEMPLATE_TYPE_ID=TEMPLATE.TEMPLATE_TYPE_ID 
 							AND 
 								PA.PAGE_ID = PAGE.PAGE_ID 
 							AND 
 								PA.VERSION = PAGE.VERSION 
 							AND 
 								PA.TEMPLATE_AREA_ID = TEMPLATE_AREA_MAP.TEMPLATE_AREA_ID
 				)", $this->broken_pa);
		
		
		// PAGE_AREA_PARAM
		$this->broken_pap=array();
		
		// проверка PAGE_AREA_PARAMs на существование соотв. TEMPLATE_AREA
		$this->pap_no_ta = $this->set_error_data ("
			SELECT 
				PAP.*
			FROM
				PAGE_AREA_PARAM PAP
					LEFT JOIN
						TEMPLATE_AREA TA
					ON
						(PAP.TEMPLATE_AREA_ID = TA.TEMPLATE_AREA_ID)		
			WHERE
				TA.TEMPLATE_AREA_ID IS NULL", $this->broken_pap);
		
		
		// проверка PAGE_AREA_PARAMs на существование записи в PAGE с соотв. PAGE_ID и VERSIOn
		$this->pap_no_pv = $this->set_error_data ("
			SELECT 
				PAP.* 
			FROM 
				PAGE_AREA_PARAM PAP
					LEFT JOIN
						PAGE P
					ON (PAP.PAGE_ID = P.PAGE_ID AND PAP.VERSION = P.VERSION)
			WHERE 
				P.PAGE_ID IS NULL", $this->broken_pap);
		
		// проверка PAGE_AREA_PARAMs на существование соотв. параметра модуля
		$this->pap_no_mp = $this->set_error_data ("
			SELECT 
				PAP.* 
			FROM 
				PAGE_AREA_PARAM PAP
					LEFT JOIN
						MODULE_PARAM MP
					ON (PAP.MODULE_PARAM_ID = MP.MODULE_PARAM_ID)
			WHERE 
				MP.MODULE_PARAM_ID IS NULL", $this->broken_pap);
		
		// проверка PAGE_AREA_PARAMs на существование соотв. шаблона, карты и пространства
		$this->pap_no_tt = $this->set_error_data ("
			SELECT 
				PAP.*
			FROM
				PAGE_AREA_PARAM PAP
			WHERE
				NOT EXISTS (
 					SELECT 
 						* 
 					FROM  
 						PAGE, 
 						TEMPLATE, 
 						TEMPLATE_AREA_MAP 
 					WHERE 
 						PAGE.TEMPLATE_ID=TEMPLATE.TEMPLATE_ID  
 							AND 
 								TEMPLATE_AREA_MAP.TEMPLATE_TYPE_ID=TEMPLATE.TEMPLATE_TYPE_ID 
 							AND 
 								PAP.PAGE_ID = PAGE.PAGE_ID 
 							AND 
 								PAP.VERSION = PAGE.VERSION 
 							AND 
 								PAP.TEMPLATE_AREA_ID = TEMPLATE_AREA_MAP.TEMPLATE_AREA_ID				
				)", $this->broken_pap
		);
		
		// проверка PAGE_AREA_PARAMs на существование соотв. записи в PAGE_AREA
		$this->pap_no_pa = $this->set_error_data ("
			SELECT 
				PAP.*
			FROM
				PAGE_AREA_PARAM PAP
					LEFT JOIN
						PAGE_AREA PA
					ON (PAP.PAGE_ID = PA.PAGE_ID AND PAP.VERSION = PA.VERSION AND PAP.TEMPLATE_AREA_ID=PA.TEMPLATE_AREA_ID)
			WHERE
				PA.PAGE_ID IS NULL", $this->broken_pap
		);
		
		// проверка PAGE_AREA_PARAMs на существование соотв блока и параметра модуля
		$this->pap_no_true_mp = $this->set_error_data("
			SELECT 
				PAP.*
			FROM
				PAGE_AREA_PARAM PAP
					INNER JOIN
						PAGE_AREA PA
					ON (PAP.PAGE_ID = PA.PAGE_ID AND PAP.VERSION = PA.VERSION AND PAP.TEMPLATE_AREA_ID=PA.TEMPLATE_AREA_ID)
			WHERE NOT EXISTS (
				SELECT 
					* 
				FROM
					MODULE_PARAM,
					INF_BLOCK
				WHERE 
					INF_BLOCK.PRG_MODULE_ID=MODULE_PARAM.PRG_MODULE_ID
						AND
							PA.INF_BLOCK_ID = INF_BLOCK.INF_BLOCK_ID
						AND
							PAP.MODULE_PARAM_ID=MODULE_PARAM.MODULE_PARAM_ID
			)", $this->broken_pap);
	}
	
	private function set_error_data ($sql, &$res_arr) {
		if (!is_array($res_arr))
			$res_arr = array();
			
		$cur_res = db::sql_select($sql);
		$res_arr = array_merge($res_arr, $cur_res);	
		
		return sizeof($cur_res);
	}
}
?>