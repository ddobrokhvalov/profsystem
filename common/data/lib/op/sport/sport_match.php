<?php
/**
 * Класс доступа к данным матчей:
 * @author atukmanov
 *
 */
class sport_match extends rbcc5_object{
	
	var $table='MATCH_INFO';
	/**
	 * Загрузить по ID
	 * @param $id
	 * @return sport_match
	 */
	static function loadByID($id){
		$sel= new rbcc5_select('MATCH_INFO');
		$sel->applyEnv(self::$env);		
		$sel->Where($sel->primary_key, eq, $id);
		return $sel->selectObject(__CLASS__);
	}		
	/**
	 * id 
	 * @var unknown_type
	 */
	static $mainTeamID=0;
	/**
	 * 
	 * @return unknown_type
	 */
	static function getMainTeamID(){
		return 1;//params::$params['main_team_id']['value'];
	}
	/**
	 * Массив игроков:
	 * @var unknown_type
	 */
	protected $_players=null;
	/**
	 * 
	 * Список игроков:
	 * 
	 * @return array
	 */
	function getPlayers(){		
		if ($this->_players) return $this->_players;
		
		$sel= rbcc5::select('MATCH_PLAYER', 'MATCH_PLAYER_ID','ASC');
		
		//$sel->applyEnv(self::$env);
		$sel->Where('MATCH_ID',eq,$this->getID());
		
		$this->_players= $sel->ToArray($sel->primary_key, true);
		uasort($this->_players, 'sport_match::cmp');
		return $this->_players;
	}
	
	static function cmp($p1, $p2){		
		static $ampluaOrder=null;
		if (!$ampluaOrder){
			$ampluaOrder=array();
			$i=1;
			foreach (rbcc5_metadata::query('MATCH_PLAYER.fields.AMPLUA.value_list') as $vl){
				$ampluaOrder[$vl['value']]=$i;
				$i++;
			}
		}
		$a=(isset($ampluaOrder[$p1['AMPLUA']]))?$ampluaOrder[$p1['AMPLUA']]:$i;
		$b=(isset($ampluaOrder[$p2['AMPLUA']]))?$ampluaOrder[$p2['AMPLUA']]:$i;
		
		if ($a == $b) {
			if ($p1['NUMBER']==$p2['NUMBER']) return 0;
			return ($p1['NUMBER']<$p2['NUMBER'])?-1:1;
        	return 0;
    	}
    	return ($a < $b) ? -1 : 1;
	}
	/**
	 * Игроки в виде ассоциативного массива:
	 * 
	 * Структура:
	 * + MASTER - хозяева
	 * 		+ STOCK 	- основной состав
	 * 			+ MATCH_PLAYER_ID - список игроков по id
	 * 				PLAYER_ID	- id игрока
	 * 				PLAYER_NAME - Имя игрока
	 * 				NUMBER		- Номер игрока
	 * 				EVENTS		- Ч
	 * 		
	 * 		+ RESERVE	- запасные
	 * + GUESTS - гости
	 * 		+ STOCK 	- основной состав
	 * 		+ RESERVE	- запасные
	 * 	 
	 * @return array
	 */
	function getPlayersTable($params=0){
		$ret=array(
			'MASTER'=>array('STOCK'=>array(),'MAIN'=>array()),
			'GUESTS'=>array('STOCK'=>array(),'MAIN'=>array()),
		);		
		$players=$this->getPlayers();
		
		/*Прикрепляем события:*/		
		if ($params&self::attachEvents){			
			foreach ($this->getEvents() as $id=>$event){
				$playerID=$event['MATCH_PLAYER_ID'];
				if (!isset($players[$playerID])) continue;//shit happend
				if (!isset($players[$playerID]['EVENTS'])) $players[$playerID]['EVENTS']=array();//Инициализируем события, во избежание php_warning
				//Прикрепляем события к пользователю:
				$players[$playerID]['EVENTS'][$id]= $event;
			}
		}
		foreach ($players as $id=>$player){
			$ret[($player['MASTER'])?'MASTER':'GUESTS'][($player['STOCK'])?'STOCK':'MAIN'][$id]=$player;
		}
		return $ret;		
	}
	
	const Goal='Goal';
	const Penalty='GoalP';
	const AutoGoal='AutoGoal';
	/**
	 * Получить голы:
	 * @return array
	 */
	function getGoals(){
		$players=$this->getPlayers();
		$ret=array(0=>array(),1=>array());
		foreach ($this->getEvents() as $e){
			$e['PLAYER']=$players[$e['MATCH_PLAYER_ID']];
			
			if ($e['TYPE']==self::Goal||$e['TYPE']==self::Penalty){
				$ret[$e['PLAYER']['MASTER']][]=$e;
			}
			elseif ($e['TYPE']==self::AutoGoal){
				//Автогол- ставится команде противника:
				$ret[1-$e['PLAYER']['MASTER']][]=$e;
			}
			
		}
		
		return array(
			'MASTER'=>$ret[1],
			'GUESTS'=>$ret[0],
		);
	}
	/**
	 * События:
	 * @var boolean
	 */
	protected $_events=null;
	/**
	 * Получить события:
	 * @return boolean
	 */
	function getEvents(){
		if ($this->_events) return $this->_events;		
		$sel= new rbcc5_select('MATCH_EVENT');
		$sel->Where('MATCH_ID',eq,$this->getID());
		$sel->applyEnv(self::$env);		
		$sel->OrderBy('INJURYTIME','ASC');		
		$sel->OrderBy('FIRETIME','ASC');
		
		return $this->_events=rbcc5_object::fetchList($sel);		
	}
	/**
	 * Таблица с событиями:
	 * @return array
	 */
	function getEventsTable(){
		$players=$this->getPlayers();
		$ret=array('MASTER'=>array(),'GUESTS'=>array());
		foreach ($this->getEvents() as $e){
			$e['MATCH_PLAYER']=$players[$e['MATCH_PLAYER_ID']];
			$ret[($e['MATCH_PLAYER']['MASTER'])?'MASTER':'GUESTS'][$e['MATCH_EVENT_ID']]=$e;
		}
		return $ret;
	}
	/**
	 * Типы событий
	 * @return array
	 */
	static function getTypeTitles(){
		$typeField=rbcc5_metadata::getFieldData('MATCH_EVENT','TYPE');
		foreach ($typeField['value_list'] as $v){
			$ret[$v['value']]=$v['title'];
		}
		return $ret;
	}
	/**
	 * Прикрепить события
	 * @var int
	 */
	const attachEvents=1;
	/**
	 * Получить дашборд матча
	 * @return array
	 */
	function getDashboard(){
		return $this->getPlayersTable(self::attachEvents);
	}
	
	protected $_calendar=null;
	/**
	 * Матч в календаре:
	 * @return rbcc5_object
	 */
	function getCalendar(){
		if ($this->_calendar) return $this->_calendar;
		$sel= new rbcc5_select('MATCH_CALENDAR');
		$sel->applyEnv(self::$env);
		$sel->Where('MATCH_ID',eq,$this->getID());
		return $this->_calendar=rbcc5_object::fetchObject($sel);
	}
		
	function _getInfo($path){
		switch ($path[0]){
			case 'EVENTS':
				return $this->getEvents();				
			break;
			case 'PLAYERS':
				return $this->getPlayers();
			break;
			case 'EVENTS_TABLE':
				return $this->getEventsTable();
			break;		
			case 'PLAYERS_TABLE':
				return $this->getPlayersTable();
			break;
			case 'DASHBOARD':
				return $this->getDashboard();
			break;
			case 'CALENDAR':
				return $this->getCalendar();
			break;
			case 'GOALS':
				return $this->getGoals();
			break;
		}
		return parent::_getInfo($path);	
	}
}
?>