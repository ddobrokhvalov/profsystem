<?
class kernel {
	static function select(){
		$a= func_get_args();
		return dbselect::factory(array('id','class','ext_id'),'unique_id',null,$a);
	}
	/**
	 * Уникальный id
	 * @param $className
	 * @return int
	 */
	static function getNextID($className){
		return self::select()->Insert(array('class'=>$className));
	}
	
	static function getUniqueID($id, $className){
		if (!$id=self::select('class',$className,'ext_id',$id)->selectString()){
			$id=self::select()->Insert(array('class'=>$className, 'ext_id'=>$id));
		}
		return $id;
	}
	
	static function factory($id){
		if ($obj=self::select('id',$id)->selectObject()){
			return datastore::factory(($obj['ext_id'])?$obj['ext_id']:$obj['id'],$obj['class']);
		}	
		else {
			return null;
		}
	}
}
?>