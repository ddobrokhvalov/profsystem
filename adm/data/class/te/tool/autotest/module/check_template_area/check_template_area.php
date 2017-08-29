<?php
/**
* Класс для автотеста областей шаблонов
* @package    RBC_Contents_5_0
* @subpackage cms
* @copyright  Copyright (c) 2007 RBC SOFT 
*/


class check_template_area extends autotest_test{

	/**
	* тест
	*/
	function do_test(){
		
		$template_types=db::sql_select("SELECT * FROM TEMPLATE_TYPE");
		foreach($template_types as $tt){
			$tareas=db::sql_select("
							SELECT 
								TEMPLATE_AREA.* 
							FROM 
								TEMPLATE_AREA,
									TEMPLATE_AREA_MAP 
							WHERE 
								TEMPLATE_AREA_MAP.TEMPLATE_TYPE_ID=:template_type_id 
									AND 
										TEMPLATE_AREA_MAP.TEMPLATE_AREA_ID=TEMPLATE_AREA.TEMPLATE_AREA_ID
			", array ('template_type_id'=>$tt["TEMPLATE_TYPE_ID"]));

			foreach($tareas as $ta){
				$r_ta[$tt["TEMPLATE_TYPE_ID"]][strtolower($ta["SYSTEM_NAME"])]=1;
			}
		}
		list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
			object::factory( 'TEMPLATE' ) -> ext_field_selection( 'TITLE', 1 );
		
		$templates=db::replace_field(db::sql_select("SELECT TEMPLATE.*, " . $dec_field . " AS \"_TITLE\" FROM TEMPLATE " . $dec_join[0], $dec_binds ), 'TITLE', '_TITLE');
		foreach($templates as $template){
			$tmpl_dir=params::$params['adm_data_server']['value']."page_tpl/".$template["TEMPLATE_DIR"];
			$files = filesystem::ls_r($tmpl_dir, false, true);
			if (sizeof($files))
				foreach ($files as $file) {
					$tmpl_file=file_get_contents($file['name']);
					preg_match_all('/{\$areas.([a-z0-9_]+)}/i', $tmpl_file, $matches, PREG_SET_ORDER);
					foreach($matches as $m){
						if($r_ta[$template["TEMPLATE_TYPE_ID"]][strtolower($m[1])]!=1){
							$this->report[]=array("descr"=>metadata::$lang['lang_At_file']." {$tmpl} ".metadata::$lang['lang_of_template']." '{$template["TITLE"]}' ".metadata::$lang['lang_defined_area']." '{$m[1]}' ".metadata::$lang['lang_is_not_tied_with_template_type'],"status"=>1);
						}
					}
				}
		}
	}
}
?>