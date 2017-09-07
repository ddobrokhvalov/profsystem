<?php
/**
 * Обсчет статистики игрока:
 * @author atukmanov
 *
 */
class sport_player_stat extends rbcc5_object {
	const matchLength=90;
	
	var $table='PLAYER';
	/**
	 * Загрузить игрока
	 * @param $id
	 * @return sport_player_stat
	 */
	static function loadByID($id){
		$sel= new rbcc5_select('PLAYER');
		$sel->applyEnv();
		$sel->Where($sel->primary_key, eq, $id);
		return $sel->selectObject(__CLASS__);
	}
	/**
	 * Получить статистику по турниру
	 * @return unknown_type
	 * 	 	
	 */
	function getTourtamentMatchesStat($tourtamentID, $joinTeams=false){
		$players=$this->getMatchPlayers($tourtamentID, $joinTeams);
		
		if (!count($players)) return array();
		/**
		 * Получаем события:
		 */		
		foreach ($this->getMatchPlayerEvents(array_keys($players)) as $event){
			//Привязываем события к матчу:
			$players[$event->MATCH_PLAYER_ID]['events'][$event->TYPE][]=$event;
			/**
			 * Вычисляем периоды, когда игрок был на поле:
			 */
			switch ($event->TYPE){
				case 'PlayerIn':
					//Вышел на поле, добавляем период до конца матча
					//Так как события сортируются по времени, событие ухода если оно есть, перезапишет окончание
					$players[$event->MATCH_PLAYER_ID]['ingame'][]=array($event->FIRETIME, self::matchLength);
				break;
				case 'PlayerOut':
					//Запоминаем окончание периода:
					$players[$event->MATCH_PLAYER_ID]['ingame'][count($players[$event->MATCH_PLAYER_ID]['ingame'])-1][1]=$event->FIRETIME;
				break;				
			}
		}
		/**
		 * Считаем статистику по каждому матчу:
		 */
		foreach ($players as $id=>&$player){
			$player['stat']=array(
				'GAMES'=>		(count($player['ingame']))?1:0,//считаем только игры, когда игрок выходил на поле
				'GAMES_MAIN'=>	($player['STOCK'])?0:1,	 //считаем только игры, где игрок выступал в основном составе
				'TIME'=>0,//инициализируем время на поле
				//количество разных событий:
				'GOAL'=>		count($player['events']['Goal'])+count($player['events']['GoalP']),//голы и голы с пенальти
				'GOAL_PASS'=>	count($player['events']['GoalPass']),
				'GOAL_P'=>		count($player['events']['GoalP']),
				'UNREALIZED_P'=>count($player['events']['UnrealizedP']),
				'YC'=>			count($player['events']['YC']),
				'RC'=>			count($player['events']['RC']),
				'CHANGE_IN'=>	count($player['events']['PlayerIn']),
				'CHANGE_OUT'=>	count($player['events']['PlayerOut']),
				
							
			);
			//Считаем время на поле, как сумму всех выходов:
			foreach ($player['ingame'] as $period) $player['stat']['TIME']+=$period[1]-$period[0];
			if ($this->AMPLUA=='goalkeeper' && $player['stat']['TIME']){
				//Если игрок вратарь и выходил на поле- считаем пропущенные голы:
				$player['missed']=$this->_getMissedGoals($player);
				$player['stat']['GOAL']-=count($player['missed']['GOAL']);
				$player['stat']['GOAL_P']-=count($player['missed']['GOAL_P']);
				$player['stat']['UNREALIZED_P']+=count($player['missed']['UNREALIZED_P']);
			}
		}
		
		return $players;
	}
	/**
	 * Пропущенные голы:
	 * @param $player
	 * @return array
	 */
	protected function _getMissedGoals($player){
		$ret=array(
			'GOAL'=>0,
			'GOAL_P'=>0,
			'UNREALIZED_P'=>0,
			'goals'=>array(),
		);		
		foreach ($player['ingame'] as $period){
			/**
			 * Голы за период:
			 */
			$sel=rbcc5::select('MATCH_EVENT','FIRETIME');
			//Получаем голы:
			$sel->Where('TYPE',eq,array('Goal','GoalP','UnrealizedP'));
			$sel->Where('MATCH_ID',eq,$player['MATCH_ID']);
			//Забитые, пока игрок был на поле:
			$sel->Where('FIRETIME',between,$period);
			//Для игроков команды противника:			
			$players=rbcc5::select('MATCH_PLAYER');
			$players->Where('MASTER',eq,1-$player['MASTER']);
			
			$sel->Join($players,$players->primary_key,$players->primary_key,$players->TABLE,'INNER');
			foreach ($sel as $goal){
				$ret['goals'][]=$goal;
				switch ($goal['TYPE']){
					case 'UnrealizedP':
						$ret['UNREALIZED_P']++;
					case 'GoalP':
						$ret['GOAL_P']++;
					default:
						$ret['GOAL']++;
						$ret['goals'][]=$goal;
					break;
				}
			}
			
			/**
			 * Автоголы за период:
			 */
			$sel=rbcc5::select('MATCH_EVENT','FIRETIME');
			//Получаем автоголы:
			$sel->Where('TYPE',eq,array('AutoGoal'));
			$sel->Where('MATCH_ID',eq,$player['MATCH_ID']);
			//Забитые, пока игрок был на поле:
			$sel->Where('FIRETIME',between,$period);
			//Для игроков его команды:			
			$players=rbcc5::select('MATCH_PLAYER');
			$players->Where('MASTER',eq,$player['MASTER']);
			
			$sel->Join($players,$players->primary_key,$players->primary_key,$players->TABLE,'INNER');
			foreach ($sel as $goal){
				$ret['goals'][]=$goal;
				$ret['GOAL']+=1;
			}
		}
		return $ret;
	}
	/**
	 * Получить список матчей, в которых участвовал игрок:
	 * @param $tourtamentID
	 * @return array
	 */
	protected function getMatchPlayers($tourtamentID, $joinTeams=false){
		$mp= rbcc5::select('MATCH_PLAYER',false);
		$mp->Where('PLAYER_ID',eq,$this->getID());
		
		$match_calendar=rbcc5::select('MATCH_CALENDAR','DATE','ASC');
		$match_calendar->Where('TOURTAMENT_ID',eq,$tourtamentID);
		$match_calendar->Where('MATCH_ID',noteq,0);
		if ($joinTeams){
			$match_calendar->autojoin('TEAM1_ID');
			$match_calendar->autojoin('TEAM2_ID');
			//$match_calendar->autojoin('MATCH_ID');
		}
		$mp->Join($match_calendar, 'MATCH_ID', 'MATCH_ID', $match_calendar->table, 'INNER');
		
		foreach ($mp as $obj){
			$obj['events']=array();
			$obj['ingame']=array();
			if (!$obj['STOCK']){
				//Основной состав, предполагаем, что игрок был на поле все время:
				$obj['ingame'][0]=array(0, self::matchLength);
			}
			$ret[$obj['MATCH_PLAYER_ID']]=$obj;
		}
		return $ret; 
	} 
	/**
	 * Получить события матчей:
	 * @return array
	 */
	protected function getMatchPlayerEvents($matchPlayerID){
		$sel=rbcc5::select('MATCH_EVENT','FIRETIME','ASC');
		$sel->Where('MATCH_PLAYER_ID',eq,$matchPlayerID);
		return rbcc5_object::fetchList($sel);
	}
	/**
	 * Обновить статистику по сезону и турниру
	 * @param $season
	 * @param $tournir
	 * @return boolean
	 */
	function update($tourtament){
		$sel= new rbcc5_select('PLAYER_STAT');
		//Инициируем статистику:
		foreach (array_keys($sel->getProperty('fields')) as $f) $stat[$f]=0;		
		$stat['PLAYER_ID']=$this->getID();
		$stat['TOURTAMENT_ID']=$tourtament;		
		$stat['HAS_DETAILS']=1;
		
		//Получаем события по матчам:
		foreach ($this->getTourtamentMatchesStat($tourtament) as $match){
			
			foreach ($match['stat'] as $k=>$v){
				$stat[$k]+=$v;
			}
		}
		//Сохраняем статистику:				
		$sel->Insert($stat,'REPLACE');
		$tourtament=rbcc5_object::loadByID($tourtament,'TOURTAMENT');
		if ($tourtament['SEASON_ID']){
			$this->updateGoalStat($tourtament['SEASON_ID']);
		}
	}	
	/**
	 * Обновить статистику по голам
	 * @param int $season
	 * 	 
	 */
	function updateGoalStat($season){
		//Выбираем голы:
		$sel= new rbcc5_select('MATCH_EVENT');
		$sel->Where('TYPE',eq,'Goal');
		//Забитые игроком:
		$players= new rbcc5_select('MATCH_PLAYER');
		$players->Where('PLAYER_ID',eq,$this->getID());
		
		$calendar=new rbcc5_select('MATCH_CALENDAR');		
		//В данном сезоне:
			//Получаем турниры сезона:
			$tourtaments= rbcc5_select::getInstance('TOURTAMENT', 0, false);
			$tourtaments->Where('(SEASON_ID='.$season.' OR SEASON1_ID='.$season.')');
			$tourtaments->Where('GOAL_STAT',eq,1);
			$tourtaments->Select('TOURTAMENT_ID');
			$tourtamentsID=array();			
			foreach ($tourtaments as $t) $tourtamentsID[]=$t[$tourtaments->primary_key];
			
			
		$calendar->Where('TOURTAMENT_ID',eq,$tourtamentsID);
		$players->Join($calendar,'MATCH_ID','MATCH_ID','c','INNER');
		
		$sel->Join($players,$players->primary_key,$players->primary_key,'PLAYER');
		
		$ins= new rbcc5_select('PLAYER_GOAL_STAT');
		$ins->Where('SEASON_ID',eq,$season);
		$ins->Where('PLAYER_ID',eq,$this->getID());
				
		if ($sel->numRows()){
			$periods=array();
			foreach ($ins->getProperty('fields') as $k=>$field){
				if ($field['type']!='int') continue;
				if (preg_match('@^GOAL_(\d+)_@',$k,$m)){
					
					$periods[$k]=$m[1];
				}
				
				$stat[$k]=0;//Инициализируем пустой массив
			}			
			
			//Смотрим события:
			foreach ($sel as $e){
				if ($e['META']){				
					$stat[$e['META']]+=1;
				}
				/**
				 * Вычисляем переиод в который был забит гол:
				 */
				foreach ($periods as $k=>$start){
					if ($e['FIRETIME']<$start) break;
					$periodKey=$k;	
				}
				
				$stat[$periodKey]+=1;
			}
			$stat['SEASON_ID']=$season;
			$stat['PLAYER_ID']=$this->getID();
			
			$ins->Insert($stat,'REPLACE');	
		}
		else {
			
			$ins->Delete();		
		}	
		
	}
}
?>