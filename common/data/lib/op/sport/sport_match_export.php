<?php
class sport_match_export extends sport_match {
	/**
	 * Кодировка файла:
	 * @var string
	 */
	const csvEncoding='cp1251';
	/**
	 * Кодировка БД
	 * @var string
	 */
	const dbEncoding='utf8';
	/**
	 * Загрузить по id
	 * @param $id
	 * @return sport_match_export
	 */
	static function loadByID($id){
		$sel= new rbcc5_select('MATCH_INFO');
		$sel->applyEnv(self::$env);
		$sel->Where($sel->primary_key, eq, $id);
		
		if ($obj=$sel->selectObject()){				
			return new sport_match_export($obj);
		}
		return null;
	}
	
	
	/**
	 * Экспорт игроков из CSV файла
	 * @return boolean
	 */
	function exportPlayersCSV($fileName, $glue=';'){
		$fp= fopen($fileName,'r');
		//Читаем поле со свойствами:
		$valueList=array();
		
		foreach (rbcc5_metadata::query('MATCH_PLAYER.fields.AMPLUA.value_list') as $amplua){
			
			$ampluas[str::lower($amplua['title'])]=$amplua['value'];
			$ampluas[str::lower($amplua['value'])]=$amplua['value'];
			if ($amplua['export']){
				foreach ($amplua['export'] as $csv) $ampluas[str::lower($csv)]=$amplua['value'];
			}
		}
		
		//Читаем:
		$players=array();
		$stock=-1;
		while ($s=fgets($fp, 1000)){
			$line=explode($glue, iconv(self::csvEncoding, self::dbEncoding, trim($s)));
			if (count($line)==0 || substr($line[0],0,1)=='#'){
				//На пустой линии переключаем основной состав на дублирующий:
				if ($stock==0) $stock=1;				
				continue;
			}
			
			//По умолчанию основной состав:
			if ($stock==-1) $stock=0;
			
			//Хозяева:
			if ($line[1]){
				$players[]=array(
					'MASTER'		=>1,
					'STOCK'			=>$stock,
					'PLAYER_NAME'	=>$line[1],
					'NUMBER'		=>(int)$line[0],
					'AMPLUA'		=>$ampluas[str::lower($line[2])],
					'GUID'=>0,		
					'PLAYER_ID'=>0	
				);
			}			
			//Гости:
			
			if ($line[3]){
				$players[]=array(				
					'MASTER'		=>0,
					'STOCK'			=>$stock,
					'PLAYER_NAME'	=>$line[4],
					'NUMBER'		=>(int)$line[3],
					'AMPLUA'		=>$ampluas[str::lower($line[5])],
					'GUID'=>0,
					'PLAYER_ID'=>0
				);
			}
		}
		fclose($fp);		
		$this->_exportPlayers($players);
	}
	/**
	 * Экспортируем игроков:
	 * @return boolean
	 */
	function _exportPlayers($players){
		//Получаем тип команды в игре (гости или хозяева):
		$mainTeamMaster=($this->TEAM1_ID==self::getMainTeamID())?1:0;
		
		//Подготавливаем данные для вставки:
		$sel= new rbcc5_select('MATCH_PLAYER');
		$sel->Where('MATCH_ID',eq,$this->getID());
		$sel->applyEnv(self::$env);

		$insert=dbselect_insert::fromSelect($sel);
		$errorPlayers=array();
		foreach ($players as $player){
			
			if ($player['MASTER']==$mainTeamMaster){
				//Игрок относится к нашей команде и должен быть в списке игроков:
				if ($playerObject=$this->_getPlayerID($player)){
					$player['PLAYER_ID']=$playerObject['PLAYER_ID'];
					
					if (!$player['AMPLUA']||$player['AMPLUA']=='undefined'){
						
						$player['AMPLUA']=$playerObject['AMPLUA'];
					}
				}
				else {
					$errorPlayers[]=$player['PLAYER_NAME'];
				}				
			}
			
			$insert->Insert($player);
		}
		//Создаем исключение:
		if (count($errorPlayers)){
			throw new sport_match_export_exception('invalid players', $errorPlayers);
		}
		//Очищаем игроков:
		$sel->Delete();
		//Вставляем:
		$insert->Commit(20, 'INSERT');
		//Сбрасываем кэш:
		$this->_players=null;
		//Очищаем события:
		$sel= rbcc5_select::getInstance('MATCH_EVENT');
		$sel->Where('MATCH_ID',eq,$this->getID());
		$sel->Delete();
	}
	/**
	 * Получить id игрока
	 * @param $player
	 * @return int
	 */
	function _getPlayerID($player){
		/* Ищем по фамилии: */
		$sel= new rbcc5_select('PLAYER');
		$sel->Select('PLAYER_ID','AMPLUA');
		$sel->applyEnv(self::$env);
		$sel->Where('SURNAME',eq,$player['PLAYER_NAME']);
		
		if ($obj=$sel->selectObject()){
			return $obj;
		}
		/* Ищем по фамилии и имени: */
		if ($lastSpace=strrpos($player['PLAYER_NAME'],' ')){
			$sel= new rbcc5_select('PLAYER');
			$sel->applyEnv(self::$env);
			$sel->Where('SURNAME',eq,substr($player['PLAYER_NAME'],0,$lastSpace));
			$sel->Where('NAME',eq,substr($player['PLAYER_NAME'],$lastSpace));
			if ($obj=$sel->selectObject()){
				return $obj;
			}	
		}
		// Ничего не найдено:
		return false;
	}
	/**
	 * 
	 * @return boolean
	 */
	function exportEventsCSV($fileName, $glue=';'){
		/* Собираем список игроков: */
		$playersID=array();
		$indexPlayers=array();
		foreach ($this->getPlayers() as $id=>$player){
			if ($player['PLAYER_ID']) $indexPlayers[]=$player;
			$playersID[str::lower($player['PLAYER_NAME'],'UTF-8')]=$id;
		}
		
		/**
		 * Собираем типы событий:
		 * 
		 * Каждое событие может быть задано в csv файле либо через заголовок, либо через специальное значение для экспорта (директива export)   	
		 */
		$typesID=array();
		$typeField=rbcc5_metadata::getFieldData('MATCH_EVENT','TYPE');
				
		foreach ($typeField['value_list'] as $value){
			$typesID[str::lower($value['title'])]=$value['value'];
			$typesID[str::lower($value['value'])]=$value['value'];
			foreach ($value['export'] as $e){
				$typesID[str::lower($e)]=$value['value'];
			}		
		}
		
		$metaField=rbcc5_metadata::getFieldData('MATCH_EVENT','META');
				
		foreach ($metaField['value_list'] as $value){
			$metaID[str::lower($value['title'])]=$value['value'];
			$metaID[str::lower($value['value'])]=$value['value'];
			foreach ($value['export'] as $e){
				$metaID[str::lower($e)]=$value['value'];
			}	
		}		
		$i=0;		
		$errors=array();
		
		$sel= new rbcc5_select('MATCH_EVENT');
		$sel->applyEnv(self::$env);
		$sel->Where('MATCH_ID',eq, $this->getID());
		$ins= dbselect_insert::fromSelect($sel);
		/**
		 * Читаем файл:
		 */
		if (!$fp= fopen($fileName,'r')) throw new exception('Invalid file '.$fileName);
		fgets($fp,1000);//Первая строка- несчитово
		while ($s=fgets($fp, 1000)){
			$line=explode($glue, iconv(self::csvEncoding, self::dbEncoding, trim($s)));
			$i++;
			//if (!$line[0]) continue;
			$playerName=str::lower($line[0]);
			
			$eventType=str::lower($line[2]);
			
			$event= array(
				'MATCH_EVENT_ID'=>0,
				'FIRETIME'=>(int)$line[1],
				'INJURYTIME'=>0,
				'GUID'=>'',
				'PLAYER_ID'=>0,
				'META'=>'',
				'TEXT'=>'',
			);
			//Проверяем тип:			
			if (isset($typesID[$eventType])){
				$event['TYPE']=$typesID[$eventType];
			}			
			else{
				$errors[]=array('type'=>'invalidType','event'=>$eventType, 'line'=>$i);
				continue;
			}
			
			//Проверяем игрока:
			if (isset($playersID[$playerName])){
				//Событие типа комментарий можент быть не связанно с игроком:
				$event['MATCH_PLAYER_ID']=$playersID[$playerName];
			}
			elseif ($event['TYPE']=='Comment'){
			 	$event['MATCH_PLAYER_ID']=0;
			}else {
				$errors[]=array('type'=>'invalidPlayer','player'=>$playerName, 'line'=>$i);
				continue;
			}
			//Проверяем мета:
			if ($line[3]){
				
				if (isset($metaID[str::lower($line[3])])){
					$event['META']=$metaID[str::lower($line[3])];
				}
				else {
					$errors[]=array('type'=>'invalidMeta','event'=>$line[3], 'line'=>$i);
				}
			}
			if (isset($line[4])){
				//Время вне игры:
				$event['INJURYTIME']=$line[4];
			}
			if (isset($line[4])){
				//Комментарий:
				$event['TEXT']=$line[4];
			}
			//Вставляем:
			$ins->Insert($event);
		}
		
		if (count($errors)){
			throw new sport_match_export_exception('invalid events', $errors);
		}
		//Выполняем:
		$sel->Delete();
		$ins->Commit();
		if ($matchInfo=$this->getCalendar()){
		
			/*Обновляем статистику игроков:*/
			foreach ($indexPlayers as $playerID){	
				$stat= new sport_player_stat(array('PLAYER_ID'=>$playerID));		
				$stat->update($matchInfo['TOURTAMENT_ID']);
			}
		}
		return true;
	}
	/**
	 * Обновить игроков:
	 * @return unknown_type
	 */
	function updatePlayersStat(){
		$players=rbcc5::select('MATCH_PLAYER');
		$players->Where('MATCH_ID',eq,$this->getID());
		$players->Where('PLAYER_ID',grater,0);
		
		foreach ($players as $obj){		
			$ps= new sport_player_stat($obj);
			$ps->update($this->getCalendar()->TOURTAMENT_ID);
		}
	}
}


/**
 * Исключение при выводе:
 * @author atukmanov
 *
 */
class sport_match_export_exception extends Exception {
	
	var $errors=array();
	
	function __construct($message, $errors){
		parent::__construct($message);
		$this->errors=$errors;
	}
}
?>