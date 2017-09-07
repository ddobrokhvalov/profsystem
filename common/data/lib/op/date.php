<?
/**
 * Утилиты для работы с датами
 *
 */
class date {
	const Day=86400;
	const Week=604800;
	const Month=2592000;
	const Year=31536000;
	const Hour=3600;
	const Min=60;
	/**
	 * Привести к началу периода:
	 *
	 * @param int $ts
	 * @param int $length
	 */
	static function addPeriod($ts, $length){
		if ($length==self::Month){
			//Конец месяца:
			$till=mktime(23,59,59,date('m',$ts)+1,-1,date('Y',$ts));
		}
		elseif ($length==self::Year){
			$till=mktime(23,59,59,12,31,date('Y',$ts));
		}
		elseif ($length==self::Week ){
			$w=date('w',$ts);
			$till=mktime(23,59,59,date('m',$ts),($w)?7-$w+date('d',$ts):date('d',$ts),date('Y',$ts));
		}
		else $till=$ts+$length;
		
		return $till;
	}
	
	static function getPeriod($Y, $m=null,$d=null){
		if (!$Y) throw new Exception('Invalid period',self::Year);
		if (!$m){
			$ts=mktime(0,0,0,1,1,$Y);
			return array($ts, self::addPeriod($ts,self::Year));
		}
		if (!$d){
			if ($m>12) throw new Exception('Invalid period',self::Month );
			$ts=mktime(0,0,0,$m,1,$Y);
			return array($ts, self::addPeriod($ts,self::Month));
		}
		if (!checkdate($m,$d,$Y)) throw new Exception('Invalid period',self::Day );
		$ts= mktime(0,0,0,$m,$d,$Y);
		return array($ts, $ts+self::Day);
	}

	static $strPeriod= array(
		'ru'=>array(
			self::Day=>array('день','дня','дней'),
			self::Hour=>array('час','часа','часов'),
			self::Min=>array('минуту','минуты','минут'),			
		),
	);
	/**
	 * Приводит период к человеческому виду (округляет):
	 * 
	 * @var $period	кол-во секунд
	 * @var $lang	пар-р округления
	 * 
	 * 86400 	->	1 день
	 * 172800	-> 	2 дня
	 * 21600 	->	6 часов
	 * 600		->  10 минут
	 * 3500		->	59 минут - округлили до минут вверх
	 * 3700		->	2 часа	 - округлили до часов вверх
	 * 
	 * @return string	 
	 */
	static function strPeriod($period, $lang='ru'){
		if (is_array($lang)) $l=$lang;
		elseif (isset(self::$strPeriod[$lang])) $l=self::$strPeriod[$lang];
		else throw new Exception('Invalid lang format');
		$i=0;$max=count($p)-1;
		foreach ($l as $p=>$l){
			if ($period>=$p||$i==$max){
				$round=ceil($period/$p);
				return str::ToNum($period,$l[0],$l[1],$l[2]);
			}
		}
	}
	/**
	 * Получить неделю:
	 * @param $ts
	 * @return unknown_type
	 */
	static function getWeek($ts){
		if (0==$w=date('w',$ts)){
			//Sunday:
			return array(
				mktime(0,0,0,date('m',$ts),date('d',$ts)-6,date('Y',$ts)),
				mktime(23,59,59,date('m',$ts),date('d',$ts),date('Y',$ts)),
			);
		}
		else {
			return array(
				mktime(0,0,0,date('m',$ts),date('d',$ts)-$w+1,date('Y',$ts)),
				mktime(23,59,59,date('m',$ts),date('d',$ts)+7-$w,date('Y',$ts)),
			);
		}
	}
}
?>