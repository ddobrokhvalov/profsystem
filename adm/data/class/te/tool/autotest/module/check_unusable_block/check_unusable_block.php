<?php
/**
* Класс для автотеста неиспользуемых блоков
* @package    RBC_Contents_5_0
* @subpackage cms
* @copyright  Copyright (c) 2007 RBC SOFT 
*/

class check_unusable_block extends autotest_test{
	/**
	* тестируем
	*/
	function do_test() {
		global $bench;
		/*$r_blocks = db::sql_select("
				SELECT 
					IB.*,
					TOBJ.SYSTEM_NAME AS TO_SYSNAME,
					CNT.COUNTER AS COUNTER,
					CASE (CNT.COUNTER)
						WHEN CNT.COUNTER IS NULL OR CNT.COUNTER=0 THEN 1
						ELSE NULL
					END AS EMPTY,
					CASE 
						WHEN ASS.INF_BLOCK_ID IS NOT NULL THEN 1
						ELSE NULL
					END AS UNUSABLE
					
				FROM
					INF_BLOCK IB
						INNER JOIN
							TE_OBJECT TOBJ
						ON (IB.TE_OBJECT_ID = TOBJ.TE_OBJECT_ID)
							LEFT JOIN
								(
									SELECT 
										CONTENT_MAP.INF_BLOCK_ID,
										COUNT(*) AS COUNTER
									FROM
										CONTENT_MAP
									GROUP BY INF_BLOCK_ID
								) CNT
							ON (CNT.INF_BLOCK_ID=IB.INF_BLOCK_ID)
							
							LEFT JOIN 
							(
								SELECT   
									IB.INF_BLOCK_ID  
								FROM   
									INF_BLOCK IB  
								WHERE 
									INF_BLOCK_ID NOT IN 
										(SELECT * FROM 
											(  SELECT DISTINCT  PA.INF_BLOCK_ID   
											      FROM   
											      	PAGE_AREA PA  
											      		INNER JOIN  
											      			PAGE P  
											      		ON (PA.PAGE_ID = P.PAGE_ID)  
											    WHERE   
											    P.PAGE_TYPE = 'page'
											) a
										) 
							) ASS
							ON (ASS.INF_BLOCK_ID=IB.INF_BLOCK_ID)
				WHERE
					CNT.COUNTER IS NULL 
						OR 
							CNT.COUNTER=0 
								OR 
									ASS.INF_BLOCK_ID IS NOT NULL
		");
		$r_blocks = db::sql_select("
				SELECT 
					IB.*,
					TOBJ.SYSTEM_NAME AS TO_SYSNAME,
					CNT.COUNTER AS COUNTER,
					CASE (CNT.COUNTER)
						WHEN CNT.COUNTER IS NULL OR CNT.COUNTER=0 THEN 1
						ELSE NULL
					END AS EMPTY,
					CASE 
						WHEN ASS.INF_BLOCK_ID IS NOT NULL THEN 1
						ELSE NULL
					END AS UNUSABLE
					
				FROM
					INF_BLOCK IB
						INNER JOIN
							TE_OBJECT TOBJ
						ON (IB.TE_OBJECT_ID = TOBJ.TE_OBJECT_ID)
							LEFT JOIN
								(
									SELECT 
										CONTENT_MAP.INF_BLOCK_ID,
										COUNT(*) AS COUNTER
									FROM
										CONTENT_MAP
									GROUP BY INF_BLOCK_ID
								) CNT
							ON (CNT.INF_BLOCK_ID=IB.INF_BLOCK_ID)
							
							LEFT JOIN 
							(
								SELECT   
									IB.INF_BLOCK_ID  
								FROM   
									INF_BLOCK IB  
								WHERE 
									INF_BLOCK_ID NOT IN 
										(SELECT * FROM 
											(  SELECT DISTINCT  PA.INF_BLOCK_ID   
											      FROM   
											      	PAGE_AREA PA  
											      		INNER JOIN  
											      			PAGE P  
											      		ON (PA.PAGE_ID = P.PAGE_ID)  
											    WHERE   
											    P.PAGE_TYPE = 'page'
											) a
										) 
							) ASS
							ON (ASS.INF_BLOCK_ID=IB.INF_BLOCK_ID)
				WHERE
					CNT.COUNTER IS NULL 
						OR 
							CNT.COUNTER=0 
								OR 
									ASS.INF_BLOCK_ID IS NOT NULL
		");
		
		$bench->register(bench::bencher('all_parts'), "query");
		
		for ($i=0,$n=sizeof($r_blocks); $i<$n; $i++)
			if (!metadata::$objects[$r_blocks[$i]['TO_SYSNAME']]['decorators']['block'])
				unset ($r_blocks[$i]);*/
		
		$blocks=db::sql_select("
				SELECT 
					IB.*, 
					TOBJ.SYSTEM_NAME AS TO_SYSNAME
				FROM 
					INF_BLOCK IB
						INNER JOIN
							TE_OBJECT TOBJ
								ON (IB.TE_OBJECT_ID=TOBJ.TE_OBJECT_ID)
				ORDER BY IB.TITLE
		");
		
		$counters=db::sql_select("
				SELECT 
					CONTENT_MAP.INF_BLOCK_ID,
						COUNT(*) AS COUNTER 
				FROM 
					CONTENT_MAP 
				GROUP BY INF_BLOCK_ID
		");
		
		$assigned=db::sql_select("
				SELECT 
					DISTINCT PA.INF_BLOCK_ID 
				FROM 
					PAGE_AREA PA
						INNER JOIN
							PAGE P
								ON (PA.PAGE_ID = P.PAGE_ID)
				WHERE 
					P.PAGE_TYPE = 'page'
		");
		
		foreach($blocks as $block){
			$block["COUNTER"]="0";
			$r_blocks[$block["INF_BLOCK_ID"]]=$block;
		}
		
		foreach($counters as $block){
			if ($r_blocks[$block["INF_BLOCK_ID"]])
				$r_blocks[$block["INF_BLOCK_ID"]]["COUNTER"]=$block["COUNTER"];
		}
		
		foreach($assigned as $block){
			if ($r_blocks[$block["INF_BLOCK_ID"]])
				$r_blocks[$block["INF_BLOCK_ID"]]["ASSIGNED"]=1;
		}
	
		foreach($r_blocks as $id=>$block){
			if($block["ASSIGNED"]){
				if($block["COUNTER"] || !metadata::$objects[$block['TO_SYSNAME']]['decorators']['block']){
					unset($r_blocks[$id]);
				}else{
					$r_blocks[$id]["EMPTY"]=1;
				}
			}else{
				$r_blocks[$id]["UNUSABLE"]=1;
			}
		}

$bench->register(bench::bencher('all_parts'), "queries");
		
		if(count($r_blocks)>0){
			$add_print_titles=array (
				'INF_BLOCK_ID' => metadata::$lang['lang_id'],
				'TITLE' => array('title'=>metadata::$lang['lang_block_name'], 'is_main'=>1),
				'PRG_MODULE_TITLE' => array('title'=>metadata::$lang['lang_module'], 'is_main'=>1),
				'COUNTER' => metadata::$lang['lang_autotest_test_unusable_block_table_title_COUNTER']
			);
			
			$this->report[0]['add_print_titles']=$add_print_titles;
			$r_blocks=array_values($r_blocks);
			
			for ($i=0,$n=sizeof($r_blocks); $i<$n; $i++) {
				if ($r_blocks[$i]['UNUSABLE']) {
					$this->report[$i]['descr']=metadata::$lang['lang_autotest_test_unusable_block_block_not_tied_to_pages'];
				}
				else {
					$this->report[$i]['descr']=metadata::$lang['lang_autotest_test_unusable_block_block_does_not_have_any_content'];
				}
				
				$this->report[$i]['status']=1;
				$this->report[$i]['link_descr']=metadata::$lang['lang_delete'];
				$this->report[$i]['fix_link']='INF_BLOCK_ID='.$r_blocks[$i]['INF_BLOCK_ID'];
				$msg = $this->get_confirm_message($r_blocks[$i]['INF_BLOCK_ID'], $can_delete);
				if ($can_delete) {
					$this->report[$i]['confirm_message']=$msg;
				}
				else {
					$this->report[$i]['alert_message']=$msg;
				}

				$this->report[$i]['action'] = 'fix_action';
				$r_blocks[$i]['PRG_MODULE_TITLE']=metadata::$objects[$r_blocks[$i]['TO_SYSNAME']]['title'];
				
				if ($r_blocks[$i]['TO_SYSNAME']) {
					$r_blocks[$i]['TITLE']='<a href="?obj='.$r_blocks[$i]['TO_SYSNAME'].'&_f_INF_BLOCK_ID='.$r_blocks[$i]['INF_BLOCK_ID'].'">'.$r_blocks[$i]['TITLE'].'</a>';
				}
				$this->report[$i]['add_print_fields']=array_intersect_key($r_blocks[$i], $add_print_titles);
			}
		}
		unset($r_blocks);
$bench->register(bench::bencher('all_parts'), "report done");
	}
	
	/**
	* Возвращает подтверждающее сообщение
	*/
	function get_confirm_message($inf_block_id, &$can_delete){
		$can_delete = true;
		$map_content_map=db::sql_select("
			SELECT 
				IB_ALIEN.INF_BLOCK_ID, 
				IB_ALIEN.TITLE
			FROM 
				CONTENT_MAP CM_OUR, 
					CONTENT_MAP CM_ALIEN, 
						INF_BLOCK IB_OUR, 
							INF_BLOCK IB_ALIEN, 
								PAGE_AREA PA_ALIEN
				WHERE 
					CM_OUR.CONTENT_ID=CM_ALIEN.CONTENT_ID
						AND CM_OUR.INF_BLOCK_ID='{$inf_block_id}'
							AND CM_ALIEN.INF_BLOCK_ID<>'{$inf_block_id}'
								AND IB_OUR.INF_BLOCK_ID=CM_OUR.INF_BLOCK_ID
									AND IB_ALIEN.INF_BLOCK_ID=CM_ALIEN.INF_BLOCK_ID
										AND IB_OUR.TE_OBJECT_ID=IB_ALIEN.TE_OBJECT_ID
											AND PA_ALIEN.INF_BLOCK_ID=IB_ALIEN.INF_BLOCK_ID
				GROUP BY 
					IB_ALIEN.INF_BLOCK_ID, 
						IB_ALIEN.TITLE
			");
		
		$inf_block_obj = object::factory('INF_BLOCK');
		
		if(count($map_content_map)>0){
			$message.=metadata::$lang["lang_autotest_test_unusable_block_can_not_delete_due_blocks_tied"];
			foreach($map_content_map as $mcm){
				$message.=" {$mcm["TITLE"]} ({$mcm["INF_BLOCK_ID"]})";
			}
			$can_delete=false;
		}else{
			$counter=db::sql_select("SELECT COUNT(*) AS COUNTER FROM CONTENT_MAP WHERE INF_BLOCK_ID='{$inf_block_id}'");
			if($counter[0]["COUNTER"]>0){
				$message.=metadata::$lang['lang_autotest_test_unusable_block_block_has_content_will_be_deleted']." ";
				$message.=metadata::$lang['lang_autotest_test_unusable_block_operation_can_be_aborted_due_timeout'].". ";
			}
			$message.=metadata::$lang['lang_autotest_are_you_sure'];
		}
	
		return $message;
	}

	/**
	* исправляем
	*/
	function fix_action(){
		$module=db::sql_select("
			SELECT 
				TOBJ.SYSTEM_NAME AS TO_SYSNAME
			FROM 
				INF_BLOCK IB
					INNER JOIN
						TE_OBJECT TOBJ 
							ON (IB.TE_OBJECT_ID = TOBJ.TE_OBJECT_ID)
			WHERE 
					IB.INF_BLOCK_ID=:inf_block_id
						", array ('inf_block_id'=>$_GET["INF_BLOCK_ID"]));
		
		if($sysname=$module[0]["TO_SYSNAME"]){
			$obj=object::factory($sysname);
			$pk=array("C.{$sysname}_ID");
			if (metadata::$objects[$sysname]['decorators']['lang'])
				$pk[]="C.LANG_ID";

			if (metadata::$objects[$sysname]['decorators']['version'])
				$pk[]="C.VERSION";
			
			$contents = db::sql_select("
					SELECT 
						".implode(', ', $pk)." 
					FROM 
							{$sysname} C
								INNER JOIN
									CONTENT_MAP CM
										ON
											(C.{$sysname}_id=CM.content_id)
					WHERE 
						CM.INF_BLOCK_ID=:inf_block_id 
					ORDER BY 
						C.{$sysname}_id
			", array('inf_block_id'=>$_GET['INF_BLOCK_ID']));
			
			if(count($contents)>0){
				foreach($contents as $content){
					try {
						$obj->exec_delete($content);
					}
					catch (Exception $e) {
						return metadata::$lang['lang_insufficient_privileges'];
					}
				}
			}
		}

		$areas=db::sql_select("SELECT * FROM PAGE_AREA WHERE INF_BLOCK_ID=:inf_block_id", array('inf_block_id'=>$_GET["INF_BLOCK_ID"]));
		foreach($areas as $area){
				db::delete_record("PAGE_AREA_PARAM", array("PAGE_ID"=>$area["PAGE_ID"], "TEMPLATE_AREA_ID"=>$area["TEMPLATE_AREA_ID"]));
				db::delete_record("PAGE_AREA",array("PAGE_ID"=>$area["PAGE_ID"], "TEMPLATE_AREA_ID"=>$area["TEMPLATE_AREA_ID"]));
			}
			db::delete_record("INF_BLOCK",array(INF_BLOCK_ID=>$_GET["INF_BLOCK_ID"]));
			return metadata::$lang['lang_done'];
		}
}
?>