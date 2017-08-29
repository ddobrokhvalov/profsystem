<?php
/**
* Класс для автотеста неиспользуемых шаблонов
* @package    RBC_Contents_5_0
* @subpackage cms
* @copyright  Copyright (c) 2007 RBC SOFT 
*/



class check_unusable_template extends autotest_test{

	/**
	* тестируем
	*/
	function do_test(){
	
		$rep_i=0; // счетчик репорта
	
		// type
		$r_used_t_templates=
				lib::array_reindex (
					db::sql_select(
						"SELECT 
							DISTINCT(TEMPLATE_TYPE_ID) AS TEMPLATE_TYPE_ID 
						FROM 
							TEMPLATE"),
					"TEMPLATE_TYPE_ID"
				);
		
		$t_templates=db::replace_field(db::sql_select("
			SELECT TEMPLATE_TYPE.*, TT.VALUE as \"_TITLE\"
			FROM TEMPLATE_TYPE
				LEFT JOIN LANG ON
					LANG.ROOT_DIR = :root_dir
				LEFT JOIN TE_OBJECT ON
					TE_OBJECT.SYSTEM_NAME = 'TEMPLATE_TYPE'
				LEFT JOIN TABLE_TRANSLATE TT ON
					TT.TE_OBJECT_ID=TE_OBJECT.TE_OBJECT_ID AND
					TT.LANG_ID=LANG.LANG_ID AND
					TT.CONTENT_ID=TEMPLATE_TYPE.TEMPLATE_TYPE_ID AND
					TT.FIELD_NAME='TITLE'
			ORDER BY TT.VALUE",
			array( 'root_dir' => params::$params['default_interface_lang']['value'] ) ), 'TITLE', '_TITLE');
		
		foreach($t_templates as $t_template){
			if(!$r_used_t_templates[$t_template["TEMPLATE_TYPE_ID"]]){
				$bad_t_template[]=$t_template;
			}
		}
		
		if(sizeof($bad_t_template)) {
			$this->report[$rep_i]['is_new_table']=true;
			$this->report[$rep_i]['caption']=metadata::$lang['lang_template_type_table'];
	
			$add_print_titles = array (
				'TITLE' => array('title'=>metadata::$lang['lang_name'], 'is_main'=>1),
				'TEMPLATE_TYPE_ID' => metadata::$lang['lang_identifier']
			);
			
			$this->report[$rep_i]['add_print_titles']=$add_print_titles;
			
			for ($i=0, $n=sizeof($bad_t_template); $i<$n; ++$i, ++$rep_i) {
				$this->report[$rep_i]['status']=1;
				$this->report[$rep_i]['descr']=metadata::$lang['lang_autotest_test_unusable_template_template_type_is_not_used'];
				$this->report[$rep_i]['add_print_fields']=array_intersect_key($bad_t_template[$i], $add_print_titles);
			}
		}
	
		// page
		$r_used_templates=lib::array_reindex(db::sql_select("SELECT DISTINCT(TEMPLATE_ID) AS TEMPLATE_ID FROM PAGE"), "TEMPLATE_ID");
		
		list( $dec_field0, $dec_where_search0, $dec_join0, $dec_binds0 ) =
			object::factory( 'TEMPLATE' ) -> ext_field_selection( 'TITLE', 1 );
		
		$templates=db::replace_field(db::sql_select("SELECT TEMPLATE.*, " . $dec_field0 . " AS \"_TITLE\" FROM TEMPLATE " . $dec_join0[0] . " ORDER BY " . $dec_field0, $dec_binds0 ), 'TITLE', '_TITLE');
		
		foreach($templates as $template){
			if(!$r_used_templates[$template["TEMPLATE_ID"]]){
				$bad_template[]=$template;
			}
		}
		
	
		if(sizeof($bad_template)){
			$this->report[$rep_i]['is_new_table']=true;
			$this->report[$rep_i]['caption']=metadata::$lang['lang_template_table'];
			
			$add_print_titles = array (
				'TITLE' => array('title'=>metadata::$lang['lang_name'], 'is_main'=>1),
				'TEMPLATE_ID' => metadata::$lang['lang_identifier'],
				'TEMPLATE_DIR' => metadata::$lang['lang_autotest_test_unusable_template_dir']
			);
	
			$this->report[$rep_i]['add_print_titles']=$add_print_titles;
			
			for ($i=0, $n=sizeof($bad_template); $i<$n; ++$i, ++$rep_i) {
				$this->report[$rep_i]['status']=1;
				$this->report[$rep_i]['descr']=metadata::$lang['lang_autotest_test_unusable_template_template_is_not_used'];
				$this->report[$rep_i]['add_print_fields']=array_intersect_key($bad_template[$i], $add_print_titles);
			}
		}
		
		// modules
		
		// выбираем все используемые в страницах шаблоны модулей
		$r_used_m_templates=lib::array_reindex(db::sql_select("
			SELECT 
				DISTINCT(PARAM_VALUE_ID) AS MODULE_TEMPLATE_ID
			FROM 
				PAGE_AREA_PARAM PAP
					INNER JOIN
						MODULE_PARAM MP
							ON (PAP.MODULE_PARAM_ID = MP.MODULE_PARAM_ID)
					INNER JOIN
						PARAM_VALUE PV
							ON (PAP.MODULE_PARAM_ID = PV.MODULE_PARAM_ID)
			WHERE 
					MP.PARAM_TYPE='template' AND PAP.VALUE=PV.PARAM_VALUE_ID"),
			"MODULE_TEMPLATE_ID");
		
		list( $dec_field1, $dec_where_search1, $dec_join1, $dec_binds1 ) =
			object::factory( 'PRG_MODULE' ) -> ext_field_selection( 'TITLE', 1 );
		list( $dec_field2, $dec_where_search2, $dec_join2, $dec_binds2 ) =
			object::factory( 'PARAM_VALUE' ) -> ext_field_selection( 'TITLE', 2 );
		
		// выбираем все шаблоны модулей
		$m_templates=db::replace_field(db::sql_select("
			SELECT
				PARAM_VALUE.PARAM_VALUE_ID AS MODULE_TEMPLATE_ID, 
				" . $dec_field2 . " AS \"_TITLE\",
				PARAM_VALUE.VALUE AS TEMPLATE_DIR,
				" . $dec_field1 . " AS PRG_MODULE_NAME
			FROM 
				PRG_MODULE
					INNER JOIN
						MODULE_PARAM
							ON (PRG_MODULE.PRG_MODULE_ID = MODULE_PARAM.PRG_MODULE_ID)
					INNER JOIN
						PARAM_VALUE
							ON (MODULE_PARAM.MODULE_PARAM_ID = PARAM_VALUE.MODULE_PARAM_ID)
				" . $dec_join1[0] . "
				" . $dec_join2[0] . "
			WHERE 
				 MODULE_PARAM.PARAM_TYPE='template'
		", $dec_binds1 + $dec_binds2 ), 'TITLE', '_TITLE');
		
		foreach($m_templates as $m_template){
			if(!$r_used_m_templates[$m_template["MODULE_TEMPLATE_ID"]]){
				$bad_m_template[]=$m_template;
			}
		}
		
		if(sizeof($bad_m_template)){
			$this->report[$rep_i]['is_new_table']=true;
			$this->report[$rep_i]['caption']=metadata::$lang['lang_autotest_test_unusable_module_types'];
	
			$add_print_titles = array (
				'TITLE' => array('title'=>metadata::$lang['lang_name'], 'is_main'=>1),
				'MODULE_TEMPLATE_ID' => metadata::$lang['lang_identifier'],
				'TEMPLATE_DIR' => metadata::$lang['lang_autotest_test_unusable_template_dir'],
				'PRG_MODULE_NAME' => array('title'=>metadata::$lang['lang_module'], 'is_main'=>1)
			);
			$this->report[$rep_i]['add_print_titles']=$add_print_titles;
			
			for ($i=0, $n=sizeof($bad_m_template); $i<$n; ++$i, ++$rep_i) {
				$this->report[$rep_i]['status']=1;
				$this->report[$rep_i]['descr']=metadata::$lang['lang_module_template_is_not_used'];
				$this->report[$rep_i]['add_print_fields']=array_intersect_key($bad_m_template[$i], $add_print_titles);
			}
		}
	}
}
?>