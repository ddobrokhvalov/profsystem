<?php
/**
 * Экспорт игровой статистики
 * @author atukmanov
 *
 */
class fclm_stat extends rbcc5_object{
	/**
	 * Загрузить по id
	 * @return fclm_stat
	 */
	static function loadByID($id){
		$sel= new rbcc5_select('MATCH_INFO');
		$sel->Where($sel->primary_key,eq,$id);
		if ($info=$sel->selectObject()){
			return new fclm_stat($info, $sel->table);
		}		
		return null;
	}
	const master=1;
	const slave=2;
	
	protected $_teams=null;
	/**
	 * Получить тип команды:
	 * @param $name
	 * @return int
	 */
	protected function getTeamType($name){
		if ($this->_teams===null){
			$this->_teams=array();
			$this->_teams[str::lower($this->getObject('TEAM1')->TITLE)]=1;
			$this->_teams[str::lower($this->getObject('TEAM2')->TITLE)]=0;
		}
		if (isset($this->_teams[str::lower($name)])){
			return $this->_teams[str::lower($name)];
		}
		else return false;
	}
	
	const invalidTeam=1;
	const invalidUser=2;
	const invalidEvent=4;

	protected $_matchPlayer=array();
	/**
	 * Получить id игрока:
	 * @param string $player
	 * @param int $team
	 * @return int
	 */
	function getPlayerID($player, $team){
		if (isset($this->_matchPlayer[str::lower($player)])){
			return $this->_matchPlayer[str::lower($player)];
		}
	}
	/**
	 * Получить команду по названию:
	 * @param $name
	 * @return rbcc5_object
	 */
	static function getTeamByName($name){
		$sel=new rbcc5_select('TEAM');
		$sel->Where('TITLE',eq,$name);
		return rbcc5_object::fetchObject($sel);
	}
	/**
	 * Найти игрока по имени, в случае отсутсвия создает
	 * @param string $name
	 * @return rbcc5_object
	 */
	static function getPlayerByName($name){
		/**
		 * Так как игрок может иметь составную фамилию ищем всеми доступными способами:
		 */
		$arr=explode(' ',$name);
		$count=count($arr);
		$surname='';
		$surnames=array();
		foreach ($arr as $part){
			if ($surname) $surname.=' ';
			$surname.=$part;
			$surnames[]=$surname;
		}
		$sel= new rbcc5_select('PLAYER');
		$sel->Where($surname, eq, $surnames);
		$users=$sel->ToArray();
		if (count($users)==0) return self::createPlayer($arr);
		for ($i=0; $i<count($users); $i++){
			$namePointer=count(explode($users[$i]['SURNAME']));
			if (!isset($arr[$namePointer])){
				//Имя не указано:
				return rbcc5_object::instance($users[$i],$sel->table);
			}
			else {
				//Сравнивае имя:
				if (str::lower($users[$i]['NAME'])==str::lower($arr[$namePointer])){
					return rbcc5_object::instance($users[$i],'PLAYER');
				}
			}
		}
	}
	/**
	 * Создать игрока:	 
	 * Последний и предпоследний элементы соотв. имя и отчество
	 * @param $arr
	 * @return rbcc5_object
	 */
	protected static function createPlayer($arr, $usePatronymic=false){
		$patronymic=($usePatronymic)?1:0;
		$count=count($arr);
		$new['SURNAME']=implode(' ', array_slice($arr,0,$count-1-$patronymic,1));
		$new['NAME']=($count>1)?$arr[$count-1-$patronymic]:'';
		if ($patronymic) $new['PATRONYMIC']=($count>2)?$arr[$count-$patronymic]:'';
		
		$sel= new rbcc5_select('PLAYER');
		$new['PLAYER_ID']=$sel->Insert($new);
		return rbcc5_object::instance($new,'PLAYER');
	}
}
?>