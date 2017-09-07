<?
/**
 * Облако тегов:
 * 
 * 
 */
class tag_cloud extends dbselect {
	/**
	 * Из выборки
	 *
	 * @param dbselect $sel
	 * @return tag_cloud
	 */
	static function fromSelect($sel){
		return new tag_cloud($sel->Select, $sel->table, $sel->serialized_fields);		
	}
	/**
	 * Вычесть облако:
	 * 
	 * @var dbselect $sel
	 */
	function decrement($sel){
		$this->Join($sel, 'tag', 'tag', 'Decr','INNER');
		$this->Update(array('weight'=>'{$weight}-Decr.weight'));
		$me= clone $this;
		$me->Where('weight',eq,0);
		$me->Delete();
	}
	/**
	 * Инкрементировать.
	 *
	 * @param dbselect $sel
	 */
	function increment($sel){
		$Insert='';
		$Values='';
		foreach ($this->Where as $Where) {
			if ($Where[1]!=eq) continue;			
			str::Add($Insert, "`".$Where[0]."`",',');
			str::Add($Values=dbselect::Escape($Where[2]).' AS i_'.$Where[0],',');
		}
		if ($Insert){
			$Insert=','.$Insert;
			$Values=','.$Values;
		}		
		
		$sql="INSERT INTO ".$this->table." (`tag`,`weigth`".$Insert.") SELECT increment.`tag`,increment.`weight`,".$Values." FROM ".$sel->getSqlTables(true, true, 'increment')." WHERE ".$sel->getSqlWhere('increment')." ON DUPLICATE KEY UPDATE SET `weight`=`weight`+VALUES(increment.weight)";
		dbselect::query($sql);
	}
	
	/**
	 * Вычислить облако
	 *
	 * @param tag_cloud_weight_calculator $WeightCalculator
	 */
	function getCloud($WeightCalculator){
		$sel=tag::select()->RightJoin($this, 'id', 'tag', 'weight');
		$MaxWeight=0;
		$MinWeight=false;
		$SumWeight=0;
		if (!$ItemsCount=$sel->NumRows()) return array();
		$ret=array();
		foreach ($sel as $tag) {
			if ($MaxWeight<$tag['weight']['weight']) $MaxWeight=$tag['weight']['weight'];//Максимальное, чтобы не менять переменную каждый раз, типа "экономим на спичках"
			if ($MinWeight===false||$MinWeight>$tag['weight']['weight']) $MinWeight=$tag['weight']['weight'];//Минимальное
			$SumWeight+=$tag['weight']['weight'];
			$ret[$tag['id']]=$tag;
		}
		$WeightCalculator->setStat($SumWeight, $ItemsCount, $MaxWeight, $MinWeight);
		foreach ($ret as $id=>$tag) {
			$ret[$id]['size']=$WeightCalculator->calculateRelativeWeight($tag['weight']['weight']);
		}
		return $ret;
	}
	/**
	 * Максимальный вес:
	 * 
	 * @return int
	 */
	function getMaxWeight(){
		$sql="SELECT MAX(`weight`) FROM ".$this->getSqlTables()." WHERE ".$this->getSqlWhere();
		
		if (!$res=$this->getCache($sql)){			
			$res=$this->query($sql);			
		}		
		$ret=$res->fetchRow(DB_FETCHMODE_ORDERED);
		if ($this->Cache){
			$this->Cache->Add($ret);
			$this->Cache->Store();
		}
		return $ret[0];
	}
	/**
	 * Средний вес:
	 * 
	 * @return double
	 */
	function getAvgWeight(){
		$sql="SELECT AVG(`weight`) FROM ".$this->getSqlTables()." WHERE ".$this->getSqlWhere();
		
		if (!$res=$this->getCache($sql)){			
			$res=$this->query($sql);			
		}		
		$ret=$res->fetchRow(DB_FETCHMODE_ORDERED);
		if ($this->Cache){
			$this->Cache->Add($ret);
			$this->Cache->Store();
		}
		return $ret[0];
	}
}
/**
 * Калькулятор определяющий относительные вес
 */
class tag_cloud_weight_calculator{
	/**
	 * Линейно:
	 */
	const Line=1;
	/**
	 * Экспоненциально:
	 */
	const Exp=2;
	/**
	 * Использовать среднее:
	 */
	const UseAvg=4;
	
	var $MinSize;
	var $MaxSize;
	var $Mode;
	/**
	 * Настройки
	 *
	 * @param int $MinSize			минимальный шрифт (id класса css)
	 * @param int $MaxSize			максимальный
	 * @param int $CalculateMode	тип вычисления	 
	 */
	function __construct($MinSize=1, $MaxSize=5, $CalculateMode){
		if ($MinSize>=$MaxSize) throw new Exception('WTF? Min font seems to be bigger than max.');
		$this->MinSize=$MinSize;
		$this->MaxSize=$MaxSize;
		$this->DeltaSize=$MaxSize-$MinSize;
		
		$this->Mode=$CalculateMode;
	}
	/**
	 * Суммарный:
	 */
	var $Sum=0;
	/**
	 * Кол-во тегов:
	 */
	var $Count=0;
	/**
	 * Средний вес тега
	 */
	var $AVG=0;
	/**
	 * Максимальный вес тега
	 */
	var $Max=0;
	/**
	 * Минимальный вес тега
	 */
	var $Min=0;
	/**
	 * Статистика:
	 *
	 * @param int $Sum
	 * @param int $Count
	 * @param int $Max
	 * @param int $Min
	 */
	function setStat($Sum, $Count, $Max, $Min){
		$this->Sum=$Sum;
		$this->Count=$Count;
		$this->AVG=$Sum/$Count;
		$this->Max=$Max;		
	}
	/**
	 * Вычислить относительный вес:
	 */
	function calculateRelativeWeight($Weight){
		if ($this->Mode&self::Line){
			//Линейное вычисление:
			if ($this->Mode&self::UseAvg){
				//Использовать среднее
				if ($Weight<$this->AVG) return $this->MinSize;//Меньше среднего- минимальным шрифтом
				return $this->MinSize+ceil(($Weight-$this->AVG)*($this->MaxSize-$this->MinSize)/($this->Max-$this->AVG)); 
			}
			else{
				return $this->MinSize+round($Weight);
			}
		}
	}
}
?>