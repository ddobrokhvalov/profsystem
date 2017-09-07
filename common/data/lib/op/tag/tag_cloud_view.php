<?php
class tag_cloud_view extends array_iterator_rnd  implements Iterator {
	
	const FromAVG='FromAVG';
	const FromMin='FromMin';
	const Exp='Exp';
	const Line='Line';
	/**
	 * Получить из запроса
	 * 
	 * @param dbselect $sel
	 * @param $from
	 * @param $method
	 * @return unknown_type
	 */
	static function fromSelect($sel, $from=self::FromAVG, $method=self::Line){
		$ret= new tag_cloud_view(array('From'=>$from, 'Method'=>$method), $sel);
		$ret->Info['total']=$sel->selectCount();
		$ret->Info['offset']=$sel->Offset;
		$ret->Info['limit']=$sel->Limit;
	}
	
	function __construct($Info, $arr){
		parent::__construct($Info,$arr);
		
		$this->calculateStat();
		$this->calculateSize();
	}
	
	var $Max=false;
	var $Min=true;
	var $Avg=0;
	var $Step=0;
	/**
	 * Статистические данные:
	 */
	function calculateStat(){
		str::print_r($this->var);
		for ($i=0; $i<$this->count;$i++){
			$weight=$this->getObjWeight($this->var[$i]);
			$this->Min=min($weight, $this->Min);
			$this->Max=max($weight, $this->Max);
			$this->Avg+=$weight/$this->count;
		}
	}
	
	var $var=array();
	
	function calculateSize(){
		
		if ($this->getInfo('From')==self::FromMin){
			$From=$this->Min;
			$Default=0;			
		}
		else {
			$From=$this->Avg;
			$Default=1;			
		}
		
		$Quantity=$this->getSetting('Quantity',5)-$Default;
		$From=($this->getSetting('From',self::FromMin)==self::FromMin)?$this->Min:$this->Avg;
		$Delta=$this->Max-$From;
		$Method=$this->getInfo('Method');
		
		if ($Method==self::Exp) $Delta=log($Delta);
		for ($i=0; $i<$this->count; $i++){
			$v=$this->getObjWeight($this->var[$i])-$From;
			if ($v<=0){
				$this->var[$i]['size']=1;
			}
			else {
				if ($Method==self::Exp) $v=log($v);
				$this->var[$i]['size']=$Default+ceil($v*$Quantity/$Delta);
			}			
		}
	}
		
	
	function getObjWeight($obj){
		return $obj['weight']['weight'];
	}
}
?>