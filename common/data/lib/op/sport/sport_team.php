<?php
/**
 * Класс доступа к составу команд:
 * @author atukmanov
 *
 */
class sport_team {
	/**
	 * Получить игроков:
	 * 
	 * @param string  $team				состав
	 * @param boolean $groupByAmplua	группировать по амплуа
	 * @param int 	  $time				состав на момент (если не задано- текущий)
	 * @return array
	 */
	static function getPlayers($team, $groupByAmplua=false, $time=null){
		$players=self::selectPlayers($team, $time);
		return $players->ToArray(($groupByAmplua)?'AMPLUA':null);
	}
	/**
	 * Выбрать игроков команды:
	 * @param $team
	 * @param $time
	 * @return rbcc5_select
	 */
	static function selectPlayers($team, $time=null){
		$players=new rbcc5_select('PLAYER');
		$players->setMode(rbcc5_select::listMode);
		$players->applyEnv();
		
		$players->Join(self::selectTeamContracts($team, $time),'PLAYER_ID','PLAYER_ID','CONTRACT','INNER');
		return $players;
	}
	/**
	 * Выборка контрактов по команде и времени
	 * 
	 * @param $team
	 * @param $time
	 * @return rbcc5_select
	 */
	static function selectTeamContracts($team, $time=null, $orderBy='NUMBER'){
		$c= new rbcc5_select('PLAYER_CONTRACT');
		$c->applyEnv();
		if (is_array($time)){			
			$start=dbselect::Escape($time[0]);
			$end=dbselect::Escape($time[1]);			
			/**
			 * Контракт оканчивается до конца сезона $end и оканчивается после начала:			 
			 */			
			$c->Where('START',smaller_or_eq,$end);
			$c->Where('END',grater_or_eq, $start);
			$c->GroupBy('PLAYER_ID');
		}
		else{
			$date=($time)?date('YmdHis',$time):date('YmdHis');
			$c->Where('START',smaller,$date);
			$c->Where('END',grater,$date);
		}
		if ($orderBy){
			$c->OrderBy('NUMBER','ASC');
		}
		
		//Получаем состав:
		if ($team) $c->Where('TEAM',eq,$team);		
		return $c;
	}
	
}
?>