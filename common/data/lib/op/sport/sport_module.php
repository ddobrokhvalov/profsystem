<?php
/**
 * Базовый функционал модулей:
 * @author atukmanov
 *
 */
class sport_module extends rbcc5_module {
	/**
	 * 
	 * @param int $tourtamentID	id турнира, переданный в запросе
	 * @param dbSelect $join	индексная таблица
	 * @param string $on		ключ для прикрепления
	 * @return int	id турнира
	 */
	function initTourtament($tourtamentID, $join=null, $on=null){
		//Получаем турниры:		
		$t=rbcc5_select::getInstance('TOURTAMENT');
		if ($join){
			if (!$on) $on=$t->primary_key;
			$join->Select($on);
			$t->Join($join, $t->primary_key, ($on)?$on:$t->primary_key,'i','INNER');
		}
		//Получаем список турниров:			
		$this->info['tourtament']=null;
		//
		$seasonsID=array();
		foreach ($t as $tourtament){
			//Проверяем совпадает ли турнир с переданным в запросе:
			if ($tourtament['TOURTAMENT_ID']==$tourtamentID) $this->info['tourtament']=$tourtament;
			$seasonID=$tourtament['SEASON_ID'];
			if (!isset($this->info['tourtaments'][$seasonID])) $this->info['tourtaments'][$seasonID]=array();
			$this->info['tourtaments'][$seasonID][]=$tourtament;
		}		
		//Проверяем передан ли верный id:
		if ($tourtamentID && !$this->info['tourtament']) return false;
		//Нет турниров удовлетворяющих условию:
		if (!count($this->info['tourtaments'])) return false;
		//Получаем список сезонов:
		$seasons=rbcc5_select::getInstance('SEASON');
		$seasons->Where('SEASON_ID',eq,array_keys($this->info['tourtaments']));
		$this->info['seasons']=$seasons->ToArray('SEASON_ID',true);
		
		if (!$tourtamentID){
			//Получаем турнир по-умолчанию:
			foreach ($this->info['seasons'] as $seasonID=>$void) break;
			
			$this->info['tourtament']=$this->info['tourtaments'][$seasonID][0];			
		}
		$this->setLinkParam('tourtament',$this->info['tourtament']['TOURTAMENT_ID']);	
		return $this->info['tourtament']['TOURTAMENT_ID'];
	}
	
}
?>