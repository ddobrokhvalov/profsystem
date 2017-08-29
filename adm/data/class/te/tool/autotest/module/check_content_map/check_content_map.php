<?php
/**
* Класс автотеста - проверка таблицы CONTENT_MAP
* @package    RBC_Contents_5_0
* @subpackage cms
* @copyright  Copyright (c) 2007 RBC SOFT 
*/

class check_content_map extends autotest_test{

	/**
	* Предусмотрено глобальное исправление
	*/
	protected $is_for_global_fix=true;
	
	/**
	* Вывод в массив report всех проблем, найденных с CONTENT_MAP
	*/
	public function do_test(){
		$this->find_error();
		$c=count($this->no_block);
		if($c>0){
			$this->report[]=array("descr"=>metadata::$lang['lang_autotest_test_content_map_records_tied_to_absent_blocks'].": {$c} ","status"=>1);
		}
		$c=count($this->no_content);
		if($c>0){
			$this->report[]=array("descr"=>metadata::$lang['lang_autotest_test_content_map_records_with_no_contents'].": {$c} ","status"=>1);
		}
		
		$this->global_fix_confirm = metadata::$lang['lang_autotest_test_content_map_bad_records_will_be_deleted'];
	}
	
	/**
	* Автоисправление
	*/
	public function fix_action(){
		$this->find_error();
		
		foreach($this->no_block as $cm){
			db::delete_record('CONTENT_MAP', array('CONTENT_ID'=>$cm["CONTENT_ID"], 'INF_BLOCK_ID'=>$cm["INF_BLOCK_ID"]));
		}
		foreach($this->no_content as $cm){
			db::delete_record('CONTENT_MAP', array('CONTENT_ID'=>$cm["CONTENT_ID"], 'INF_BLOCK_ID'=>$cm["INF_BLOCK_ID"]));
		}
		return metadata::$lang['lang_done'];		
	}
	
	
	/**
	* Внутренняя ф-ия поиска ошибки
	*/
	private function find_error(){
		$this->no_block=array();
		$this->no_content=array();
		
		$inf_block=db::sql_select("SELECT IB.*, TOBJ.SYSTEM_NAME AS TO_SYSNAME FROM INF_BLOCK IB INNER JOIN TE_OBJECT TOBJ ON (IB.TE_OBJECT_ID=TOBJ.TE_OBJECT_ID)");
		foreach($inf_block as $ib){
			$r_ib[$ib["INF_BLOCK_ID"]]=$ib;
		}
	
		foreach (metadata::$objects as $key=>$object) {
			if ($object['decorators']['block']) {
				$content[$key]=db::sql_select("SELECT {$key}_ID AS CONTENT_ID FROM {$key}");
				foreach ($content[$key] as $c_id)
					$r_content[$key][$c_id['CONTENT_ID']]=1;
			}
		}
		
		$content_map=db::sql_select("SELECT * FROM CONTENT_MAP");
		foreach($content_map as $cm){
			if(!isset($r_ib[$cm["INF_BLOCK_ID"]])){
				$this->no_block[]=$cm;
			}
			elseif($r_content[$r_ib[$cm["INF_BLOCK_ID"]]["TO_SYSNAME"]][$cm["CONTENT_ID"]]!=1){
				$this->no_content[]=$cm;
			}
		}
	}
}
?>