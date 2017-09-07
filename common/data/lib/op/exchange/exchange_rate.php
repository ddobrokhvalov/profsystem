<?php
/**
 * Курсы обмена валют
 * @author atukmanov
 *
 */
class exchange_rate{
	/**
	 * Валюты:
	 * @var array
	 */
	static $currency=null;
	/**
	 * Получить курсы
	 * @return array
	 */
	static function getCurrency(){
		if (!self::$currency){
			$sel= new rbcc5_select('EXCHANGE_CURRENCY');
			$sel->OrderBy($sel->getOrderField(),$sel->getOrderDir());
			foreach ($sel as $obj){
				if ($obj['IS_MAIN']) self::$mainCurrency=rbcc5_object::instance($obj, $sel->table);
				self::$currency[$obj[$sel->primary_key]]=rbcc5_object::instance($obj, $sel->table);
			}
		}
		
		return self::$currency;
	}
	/**
	 * Главная валюта
	 * @var rbcc5_object
	 */
	static $mainCurrency=null;
	/**
	 * Конвертируем:
	 * @return array
	 */
	static function getMainCurrency(){
		if (!self::$currency) self::getCurrency();
		return self::$mainCurrency;
	}
	/**
	 * Дата:
	 * @var дата
	 */
	protected $date=0;
	/**
	 * Блок:
	 * @var int
	 */
	protected $blockID=0;
	/**
	 * Курсы:
	 * @var array
	 */
	public $rate=array();
	
	function __construct($blockID, $date){
		$this->blockID=$blockID;
		$this->date=$date;
	}
	/**
	 * Загрузить курс обмена:
	 * @param int $blockID
	 * @param int $date
	 * @return exchange_rate
	 */
	static function load($blockID, $date){
		$sel= new rbcc5_select('EXCHANGE_RATE');
		$sel->applyEnv(array('block_id'=>$blockID), 0);
		
		$sel->Where('DATE',eq,self::sqlDate($date));
				
		$ret= new exchange_rate($blockID, $date);

		foreach ($sel as $obj){
			$ret->rate[$obj['SRC_CURRENCY_ID']][$obj['DST_CURRENCY_ID']]=$obj;
		}
		if (count($ret->rate)) return $ret;
		else return null;
	}	
	/**
	 * Загрузить текущий
	 * @param int $blockID
	 * @return exchange_rate
	 */
	static function loadCurrent($blockID){
		if ($ret=self::load($blockID, time())){
			return $ret;
		}
		//Неприятность: ищем последнюю дату				
		$sel =new rbcc5_select('EXCHANGE_RATE');
		$sel->applyEnv(array('block_id'=>$blockID),0);
		$sel->Where('DATE',smaller,self::sqlDate(time()));
		$sel->OrderBy('DATE','DESC');
		$sel->Select('DATE');
		if ($date=$sel->selectString()){
			return self::load($blockID, mktime(0,0,0,substr($date,4,2),substr($date,6,2),substr($date,0,4)));
		}
		else {
			return null;
		}
	}
	
	static function instance($blockID, $date){
		if ($ret=self::load($blockID, $date)){
			return $ret;
		}
		else {
			return new exchange_rate($blockID,$date);			
		}
	}
	/**
	 * Привести к дате sql:
	 * @return string
	 */
	protected static function sqlDate($ts){
		return date('Ymd000000',$ts);
	}
	/**
	 * Привести к double
	 * @param string $value
	 * @return double
	 */
	static function formatDouble($value){
		$value=str_replace(array(' ',','),array('','.'),$value);
		if ($value=='0'||preg_match('/^[1-9]\d+$/',$value)||preg_match('/^[1-9]\d+.\d+$/',$value)){
			return $value;
		}
		else {
			return false;
		}
	}
	/**
	 * Обновить курсы:
	 * @param $rates
	 * @return array
	 */
	function update($rates){
		
		//1. Удаляем связи с блокам
		foreach ($rates as $obj){
			$obj['DATE']=self::sqlDate($this->date);
			if (isset($this->rate[$obj['SRC_CURRENCY_ID']][$obj['DST_CURRENCY_ID']])){
				//Обновляем:
				$sel= new rbcc5_select('EXCHANGE_RATE');
				$id=$this->rate[$obj['SRC_CURRENCY_ID']][$obj['DST_CURRENCY_ID']][$sel->primary_key];
				$sel->Where($sel->primary_key, eq, $id);
				if ($obj['RATE']){
					$sel->Update($obj);
					$obj[$sel->primary_key]=$id;
				}
				else {
					$sel->Delete();
				}
			}
			elseif ($obj['RATE']) {
				//Вставляем:
				$sel= new rbcc5_select('EXCHANGE_RATE');
				
				$obj[$sel->primary_key]=$sel->Insert($obj);
				//Привязываем:
				$cm= new rbcc5_select('CONTENT_MAP');
				$cm->Insert(array(
					'CONTENT_ID'=>$obj[$sel->primary_key],
					'INF_BLOCK_ID'=>$this->blockID,
					'IS_MAIN'=>1,
				));				
			}
		}
		//Записываем:
		$this->rate[$obj['SRC_CURRENCY_ID']][$obj['DST_CURRENCY_ID']]=$obj;
	}
	/**
	 * Возвращает JSON:
	 * @return string
	 */
	function getJSON(){
		return json_encode($this->rate);
	}
	
	const direct='src';
	const revert='dst';
	/**
	 * Перевести из одной валюты в другую:
	 * @param double $summ	сумма
	 * @param double $src	исходная валюта
	 * @param double $dst	конечная валюта
	 * @param int 	 $precision округление
	 * @return double
	 */
	function convert($summ, $src, $dst, $precision=2, $direction=self::direct){
		
		if ($rate=$this->getRate(($direction==self::direct)?$src:$dst, ($direction==self::direct)?$dst:$src)){
			
			if ($direction==self::direct){
				return round($summ*$rate,$precision);
			}
			else {				
				return round($summ/$rate,$precision);
			}
		}
		else {
			return null;
		}
	}
	/**
	 * Получить курс обмена:
	 * 
	 * @param int $src		id исходной валюты
	 * @param int $dst		id конечной валюты
	 * @param int $revert	прямой или обратный курс
	 * @return double
	 */
	function getRate($src, $dst, $revert=0){
		if (isset($this->rate[$src][$dst])){
			if ($revert!=$this->rate[$src][$dst]['IS_REVERT']){
				//Обратный курс (EUR->RUR)
				return 1/$this->rate[$src][$dst]['RATE'];
			}
			else {
				//Прямой курс (RUR->EUR):
				return $this->rate[$src][$dst]['RATE'];
			}
		}
		//Строим курс через главную валюту:
		$mainCurrency=self::getMainCurrency();
		if ($mainCurrency->getID()==$src||$mainCurrency->getID()==$dst){
			return null;
		}
		//
		return $this->getRate($src, $mainCurrency->getID())*$this->getRate($mainCurrency->getID(),$dst);
	}
	/**
	 * Предыдущий курс:
	 * 
	 * @return exchange_rate
	 */
	function getLast(){
		$sel= new rbcc5_select('EXCHANGE_RATE');
		$sel->applyEnv(array('block_id'=>$this->blockID),0);
		$sel->Where('DATE',smaller,self::sqlDate($this->date));
		$sel->OrderBy('DATE','DESC');
		
		$sel->Select('DATE');
		
		if ($date=$sel->selectString()){			
			return self::load($this->blockID, mktime(0,0,0,substr($date,4,2),substr($date,6,2),substr($date,0,4)));
		}
		else {
			return null;
		}
	}
	/**
	 * Получить курс для главной валюты:
	 * 
	 * @return array
	 */
	function getRatesTable($calculateDiff=true){
		$mainCurrency=self::getMainCurrency();
		$ret=array();
		/**		 
		 * @var exchange_rate <NULL, exchange_rate>
		 */
		$lastRate=($calculateDiff)?$this->getLast():null;
		
		foreach (self::getCurrency() as $cur){
			
			if ($cur->getID()==$mainCurrency->getID()) continue;			
			//Есть курс:
			$ret[$cur->CODE]=$cur->Info;
			//Продажа:
			$ret[$cur->CODE]['SALE']=$this->getRate($mainCurrency->getID(),$cur->getID(),1);
			$ret[$cur->CODE]['PURCHASE']=$this->getRate($cur->getID(),$mainCurrency->getID(),0);
			//Разница:
			
			if ($lastRate){
				//Продажа:
				$ret[$cur->CODE]['SALE_LAST']=$lastRate->getRate($mainCurrency->getID(),$cur->getID(),1);
				$ret[$cur->CODE]['SALE_DIFF']=$ret[$cur->CODE]['SALE']-$ret[$cur->CODE]['SALE_LAST'];
				//Покупка:
				$ret[$cur->CODE]['PURCHASE_LAST']=$lastRate->getRate($cur->getID(),$mainCurrency->getID(),0);
				$ret[$cur->CODE]['PURCHASE_DIFF']=$ret[$cur->CODE]['PURCHASE']-$ret[$cur->CODE]['PURCHASE_LAST'];
			}
			
		}		
		return $ret;
	}
	/**
	 * Кросс-курсы:
	 * @return array
	 */
	function getCrossRates(){
		$cur=self::getCurrency();
		$main=self::getMainCurrency();
		$ret=array();
		foreach ($cur as $src){
			if ($src->getID()==$main->getID()) continue;			
			foreach ($cur as $dst){
				if ($dst->getID()==$main->getID()||$src->getID()==$dst->getID()) continue;
				if ($rate=$this->getRate($src->getID(),$dst->getID())){
					$ret[]=array('SRC'=>$src, 'DST'=>$dst, 'RATE'=>$rate);
				}
			}
		}
		return $ret;
	}
	/**
	 * Очистка:	 
	 */
	static function clean($blockID){
		$current=self::loadCurrent($blockID);
		//Вчера:
		if (!$last=$current->getLast()){
			return null;
		}
		//Считаем:
		$sel= new rbcc5_select('EXCHANGE_RATE');
		$sel->applyEnv(array('block_id'=>$blockID),0);
		$sel->Where('date',smaller,self::sqlDate($last->date));
		$sel->Select($sel->primary_key);
		foreach ($sel as $obj){
			$deleteID[]=$obj[$sel->primary_key];
		}
		//Удаляем:
		$cm=new rbcc5_select('CONTENT_MAP');
		$cm->Where('INF_BLOCK_ID',eq,$blockID);
		$cm->Where('CONTENT_ID',eq,$deleteID);
		$cm->Delete();
		//Удаляем:
		$er=new rbcc5_select('EXCHANGE_RATE');
		$er->Where($er->primary_key,eq,$deleteID);
		$er->Delete();
	}
}
?>