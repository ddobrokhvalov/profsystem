<?php
/**
 * Словарь тегов команды
 * @author atukmanov
 *
 */
class sport_team_tags_dict {
	/**
	 * Получить теги:
	 * @return array
	 */
	function getOptions(){
		$sel= rbcc5_select::getInstance('PLAYER', 0, 'SURNAME','ASC');
		
		$contract= rbcc5_select::getInstance('PLAYER_CONTRACT');
		$contract->Where('START',smaller,date('YmdHis'));
		$contract->Where('END',grater,date('YmdHis'));
		$sel->Join($contract,$sel->primary_key,$sel->primary_key,'CONTRACT','INNER');
		$sel->Select('PLAYER_ID','SURNAME');
		$ret=array();

		foreach ($sel as $obj){
			$ret['PLAYER.'.$obj['PLAYER_ID']]=$obj['SURNAME'];
		}

		return $ret;
	}
	
	function checkOption($player){
		
		list($table, $id)=explode('.',$player);
		return rbcc5_object::loadByID($id,'PLAYER');
	}
}
?>