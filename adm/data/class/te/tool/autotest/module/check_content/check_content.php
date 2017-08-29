<?php
/**
* Класс для автотеста элементов контента
* @package    RBC_Contents_5_0
* @subpackage cms
* @copyright  Copyright (c) 2007 RBC SOFT 
*/
class check_content extends autotest_test{
	/**
	* тест
	*/
	function do_test(){
		$content_map=db::sql_select("
			SELECT CM.*, TOBJ.SYSTEM_NAME AS TO_SYSNAME
			FROM 
				CONTENT_MAP CM
					INNER JOIN 
						INF_BLOCK IB
							ON (CM.INF_BLOCK_ID = IB.INF_BLOCK_ID)
					INNER JOIN
						TE_OBJECT TOBJ
							ON (IB.TE_OBJECT_ID=TOBJ.TE_OBJECT_ID)");
		foreach($content_map as $cm){
			$r_cm[$cm["TO_SYSNAME"]][$cm["CONTENT_ID"]]=$cm;
		}
		
		$lang_obj = object::factory( 'LANG' );
		list( $dec_field1, $dec_where_search1, $dec_join1, $dec_binds1 ) =
			 $lang_obj -> ext_field_selection( 'TITLE', 1 );
		$lang_obj -> __destruct();
		
		$langs=db::replace_field(db::sql_select("SELECT LANG.*, " . $dec_field1 . " as \"_TITLE\" FROM LANG " . $dec_join1[0], $dec_binds1 ), 'TITLE', '_TITLE');
		foreach($langs as $lang){
			$r_lang[$lang["LANG_ID"]]=$lang;
		}
		
		$state_obj = object::factory( 'WF_STATE' );
		list( $dec_field2, $dec_where_search2, $dec_join2, $dec_binds2 ) =
			$state_obj -> ext_field_selection( 'TITLE', 1 );
		$state_obj -> __destruct();
		
		$states=db::replace_field(db::sql_select("SELECT WF_STATE.*, " . $dec_field2 . " as \"_TITLE\" FROM WF_STATE " . $dec_join2[0], $dec_binds2 ), 'TITLE', '_TITLE');
		foreach($states as $state){
			$r_state[$state["WF_STATE_ID"]]=$state;
		}
		
		$te_objects = lib::array_reindex( db::sql_select( 'select SYSTEM_NAME, WF_WORKFLOW_ID from TE_OBJECT' ), 'SYSTEM_NAME' );
		$inf_blocks = lib::array_reindex( db::sql_select( 'select INF_BLOCK_ID, WF_WORKFLOW_ID from INF_BLOCK' ), 'INF_BLOCK_ID' );
		
		foreach (metadata::$objects as $key=>$object) {
			if ($object['decorators']['block']) {
				
				$tbl_obj = object::factory($key);
				list($gstc_field, $gstc_joins, $gstc_binds)=$tbl_obj->get_short_title_clause();// Не используем джойны и бинды, потому что переводимые таблицы не должны испоьзовать декораторы
				$tbl_obj -> __destruct();
				
				$sql_select = array($key.'_ID AS CONTENT_ID');
				$sql_select[] = $gstc_field.' AS TITLE';
	
				if ($object['decorators']['lang']) {
					$sql_select[] = 'LANG_ID';
				}
				
				if ($object['decorators']['workflow']) {
					$sql_select[] = 'WF_STATE_ID';
				}
				
				if ($object['decorators']['version']) {
					$sql_select[] = 'VERSION';
				}
				$contents=db::sql_select( '
					SELECT '.implode(', ', $sql_select).', (
						SELECT CONTENT_MAP.INF_BLOCK_ID
						FROM CONTENT_MAP, INF_BLOCK, TE_OBJECT
						WHERE
							CONTENT_MAP.CONTENT_ID='.$key.'_ID AND
							CONTENT_MAP.IS_MAIN=1 AND
							CONTENT_MAP.INF_BLOCK_ID=INF_BLOCK.INF_BLOCK_ID AND
							INF_BLOCK.TE_OBJECT_ID=TE_OBJECT.TE_OBJECT_ID AND
							TE_OBJECT.SYSTEM_NAME=\''.$key.'\' ) as MAIN_BLOCK_ID FROM '.$key );
				foreach ($contents as $content) {
					unset($errors);
					
					if(!isset($r_cm[$key][$content["CONTENT_ID"]])){
						$errors[]=metadata::$lang["lang_autotest_test_content_no_tie_to_any_block"];
					}
					
					if(isset($r_cm[$key][$content["CONTENT_ID"]]) && !$content["MAIN_BLOCK_ID"]){
						$errors[]=metadata::$lang["lang_autotest_test_content_no_tie_to_main_block"];
					}
					
					if($object['decorators']['workflow'] && !isset($r_state[$content["WF_STATE_ID"]])){
						$errors[]=metadata::$lang["lang_autotest_test_content_applied_bad_status"];
					}
					
					if($object['decorators']['workflow'] && isset($r_state[$content["WF_STATE_ID"]]) &&
						isset($r_cm[$key][$content["CONTENT_ID"]]) && $content["MAIN_BLOCK_ID"] && (
						metadata::$objects[$key]['workflow_scope'] == 'block' &&
							$r_state[$content["WF_STATE_ID"]]["WF_WORKFLOW_ID"] != $inf_blocks[$content["MAIN_BLOCK_ID"]]["WF_WORKFLOW_ID"] ||
						metadata::$objects[$key]['workflow_scope'] != 'block' &&
							$r_state[$content["WF_STATE_ID"]]["WF_WORKFLOW_ID"] != $te_objects[$key]["WF_WORKFLOW_ID"] )
					){
						$errors[]=metadata::$lang["lang_autotest_test_content_applied_status_from_other_workflow"];
					}
					
					if($object['decorators']['lang'] && !isset($r_lang[$content["LANG_ID"]])){
						$errors[]=metadata::$lang["lang_autotest_test_content_applied_bad_language"];
					}
					
					if($object['decorators']['version'] && !in_array($content["VERSION_ID"], array(0,1))){
						$errors[]=metadata::$lang["lang_autotest_test_content_applied_bad_version"];
					}

					if(is_array($errors)){
						// получаем необходимые поля для вывода
						
						$fields_for_print = array (
							'TABLE_NAME' => $key,
							'LANG_NAME'	 => $r_lang[$content["LANG_ID"]]["TITLE"],
							'STATE_NAME' => $r_state[$content["WF_STATE_ID"]]["TITLE"],
							'VERSION'	 => $content["VERSION"]?metadata::$lang["lang_test"] : metadata::$lang["lang_work"],
							'MODULE_NAME' => $object['title'],
							'errors' => $errors
						);
						
						$bad_content[]=array_merge($content,$fields_for_print);
					}
				}
			}
		}
		
		if(is_array($bad_content)){
			$add_print_titles=array (
				'MODULE_NAME' => array('title'=>metadata::$lang['lang_module'], 'is_main'=>1),
				'TITLE' => array('title'=>metadata::$lang['lang_prg_module_element_name'], 'is_main'=>1),
				'CONTENT_ID' => metadata::$lang['lang_identifier'],
				'VERSION' => metadata::$lang['lang_version'],
				'LANG_NAME' => metadata::$lang['lang_lang'],
				'STATE_NAME' => array('title'=>metadata::$lang['lang_state'])
			);
			$this->report[0]['add_print_titles']=$add_print_titles;
			
			
			for ($i=0, $n=sizeof($bad_content); $i<$n; ++$i) {
				$this->report[$i]['descr']=implode('<br>', $bad_content[$i]['errors']);
				$this->report[$i]['status']=1;
				$this->report[$i]['add_print_fields']=array_intersect_key($bad_content[$i], $add_print_titles);
			}
		}
	}
}
?>