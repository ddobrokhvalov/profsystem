<?php
/**
 *	Утилиты массовой вставки записи:
 */
class dbselect_insert extends DataStore {
	/**	 
	 *
	 * @var int
	 */
	var $InsertAny=20;
	/**	 
	 * 
	 * @var dbselect
	 */
	var $to;
	/**	 
	 * @var dbselect
	 */
	var $sel;
	/**
	 * @param dbselect $to
	 */
	function __construct($Info, $sel){		
		$this->sel=$sel;
		
		foreach ($this->sel->Where as $Where) {
			list($Field,$Type,$Value)=$Where;
			if ($Type==eq) $Info[$Field]=$Value;
		}
		
		parent::__construct($Info);
	}
	/**
	 * @return dbselect_insert
	 */
	static function fromSelect($sel){
		return new dbselect_insert(null, $sel);
	}
	
	static function execute($sel, $res, $method=dbselect::INSERT){
		if (!count($res)) return;
		$i=dbselect_insert::fromSelect($sel);
		foreach ($res as $obj) $i->Insert($obj);
		$i->Commit(20, $method);
	}
	
	function Insert($info){
		$Insert='';
		//str::print_r($info, $this->Info, $this->sel->Select);
		if ($count=count($this->sel->Select)){
			for ($i=0;$i<$count;$i++){
				$obj=&$this->sel->Select[$i];
							
				if (array_key_exists($obj, $info)){
					str::Add($Insert, "'".dbselect::_UpdateEscape($info[$obj])."'",',');
				}
				elseif($this->Info&&array_key_exists($obj, $this->Info)){
					str::Add($Insert, "'".dbselect::_UpdateEscape($this->Info[$obj])."'",',');
				}
				else{
					//Primary key
					if ($i==0){
						str::Add($Insert,'0',',');
					}
					else{
						str::print_r($obj, $info);
						throw new Exception('Incomplete data: '.$obj);
					}
				}
			}
		}
		$this->Insert[]='('.$Insert.')';
	}
	/**	
	 */
	function IncrementUsingWhere($arr, $key, $IncrementKey, $Increment=1){
		$basic=array();
		
		foreach ($this->sel->Where as $where) {
			if ($where[1]==eq) $basic[$where[0]]=$where[2];
		}
		$Items=array();
		$i=0;
		foreach ($arr as $obj) {
			$Items[$i]=$basic;
			$Items[$i][$key]=$obj;
			$i++;
		}
		
		$this->Increment($Items, $IncrementKey, $Increment);
	}
	/**
	 * @param array $Items
	 * @param string $IncrementKey
	 * @param int $Increment
	 */
	function Increment($Items, $IncrementKey, $Increment=1){
		
		if (!$Increment=(int)$Increment) throw new Exception('ZeroIncrement');
		if ($Increment>0){
			$Insert='';
			if ($count=count($Items)){
				$sel_count=count($this->sel->Select);
				for ($i=0;$i<$count;$i++){
					$obj=&$Items[$i];
					$InsertItem='';				
					for ($j=0;$j<$sel_count;$j++){
						if ($this->sel->Select[$j]!=$IncrementKey){
							str::Add($InsertItem, "'".dbselect::_UpdateEscape($obj[$this->sel->Select[$j]])."'",',');					
						}
						else {
							str::Add($InsertItem, "'".$Increment."'",',');
						}					
					}
					str::Add($Insert,'('.$InsertItem.")",",");
				}
				$Into='';
				for($j=0; $j<$sel_count;$j++){
					str::Add($Into, '`'.$this->sel->Select[$j].'`',',');	
				}
				$sql="INSERT INTO ".$this->sel->table." (".$Into.") VALUES ".$Insert." ON DUPLICATE KEY UPDATE ";				
				if (is_array($IncrementKey)){
					if ($count=count($IncrementKey)){
						$Update='';
						for ($i=0;$i<$count;$i++){
							$obj=&$IncrementKey[$i];
							str::Add($Update, (false!==strpos($obj,'='))?$obj:'`'.$obj.'`=`'.$obj.'`+VALUES('.$obj.')',',');
						}
					}
				}
				else {
					$sql.=$IncrementKey."=".$IncrementKey."+".$Increment;
				}
				
				$this->sel->query($sql);
			}
			
		}
		else {
			
			if ($count=count($Items)){
				$Where='';
				for ($i=0;$i<$count;$i++){
					$obj=&$Items[$i];					
					foreach ($obj as $k=>$v) {
						$this->sel->Where($k,eq,$v);
					}
					str::Add($Where, "(".$this->sel->getSqlWhere().")"," OR ");
					$this->sel->dropWhere();
				}
			
				$sql="UPDATE ".$this->sel->table." SET ".$IncrementKey."=".$IncrementKey."-".abs($Increment)." WHERE ".$Where;					
				$this->sel->query($sql,'UPDATE');			
				$sql="DELETE FROM ".$this->sel->table." WHERE (".$Where.") AND ".$IncrementKey."=0;";
								
				$this->sel->query($sql);				
			}
		}
	}
		

	var $Insert=array();
	
	function Commit($rpq=20, $method=dbSelect::INSERT){
		$ins='';
		if ($count=count($this->sel->Select)){
			for ($i=0;$i<$count;$i++){
				str::Add($ins, '`'.$this->sel->Select[$i].'`',',');				
			}
		}
		$sql=$method." INTO ".$this->sel->table." (".$ins.") VALUES ";
		$c=count($this->Insert);
		$i=0;
		
		if (!$c) return;
		while ($i<=$c) {
			$query=implode(',',array_slice($this->Insert, $i, min($rpq, $c-$i)));
					
			//
			$res=dbselect::getDBProvider()->query($sql.$query);			
			if (!$res){	
				//dbselect::Log($sql,$res);

				throw new Exception('ExecuteFault');
			}		
						
			$i+=$rpq;
		}
		
	}
		
}
?>