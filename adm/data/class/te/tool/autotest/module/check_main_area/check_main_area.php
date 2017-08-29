<?php
/**
* Класс для автотеста главной области
* @package    RBC_Contents_5_0
* @subpackage cms
* @copyright  Copyright (c) 2007 RBC SOFT 
*/

class check_main_area extends autotest_test{
	/**
	* тест
	*/
	public function do_test(){
		$template_types=db::replace_field(db::sql_select("
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
					TT.FIELD_NAME='TITLE'",
			array( 'root_dir' => params::$params['default_interface_lang']['value'] ) ), 'TITLE', '_TITLE') ;
		
		foreach($template_types as $tt){
			$main_area=db::sql_select("SELECT COUNT(*) AS COUNTER FROM TEMPLATE_AREA,TEMPLATE_AREA_MAP WHERE TEMPLATE_AREA.TEMPLATE_AREA_ID=TEMPLATE_AREA_MAP.TEMPLATE_AREA_ID AND TEMPLATE_AREA.IS_MAIN=:is_main AND TEMPLATE_AREA_MAP.TEMPLATE_TYPE_ID=:template_type_id", array('is_main'=>1, 'template_type_id'=>$tt["TEMPLATE_TYPE_ID"]));
			if($main_area[0]["COUNTER"]==0){
				$this->report[]=array("descr"=>"'{$tt['TITLE']}'. ".metadata::$lang['lang_autotest_test_main_area_template_type_has_no_main_area'],"status"=>1);
			}elseif($main_area[0]["COUNTER"]>1){
				$this->report[]=array("descr"=> metadata::$lang['lang_autotest_test_main_area_count_of_main_areas_of_template_type']."'{$tt["TITLE"]}': {$main_area[0]["COUNTER"]}","status"=>1);
			}
		}
	}
}
?>