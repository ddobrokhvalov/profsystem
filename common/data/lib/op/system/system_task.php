<?php
class system_task extends DataStore {
	const High='High';
	const Middle='Middle';
	const Low='Low';
	/**
	 * Выборка:
	 * 
	 * @return dbSelect	 
	 */
	static function select(){
		$a= func_get_args();
		return dbselect::factory(array('id','class','obj','do','ts','priority'),'system_tasks');
	}
	/**
	 * Создать:
	 */
	static function create($className, $obj, $do, $wait=0, $priority=self::Low){
		if (!class_exists($className)) throw new Exception('Invalid class');		
		if (!$objID=DataStore::toInt($obj)) throw new Exception('Invalid object');
		
		$ret= new system_task(array(		
			'class'=>$className,
			'obj'=>$objID,
			'do'=>$do,
			'ts'=>time()+$wait,
			'priority'=>$priority,
		));
		$ret->Info['id']=self::select()->Insert($ret->Info);
		return $ret;
	}
	
	function exec(){
		if ($instance= call_user_func(array($this->getInfo('className'),'loadByID'),$this->getInfo('obj'))){
			$do=$this->do;
			$instance->$do();
		}
	}

}
?>