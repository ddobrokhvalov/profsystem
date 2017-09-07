<?php
/**
 * Есть объект а
 */
class place extends DataStore {
	/**
	 * Выборка
	 *
	 * @return dbSelect
	 */
	static function select(){
		$a= func_get_args();
		return dbselect::factory(array('id','Title','Url','RefCount','Region'));
	}
	/**
	 * Загрузить по id
	 *
	 * @param int $id
	 * @return place
	 */
	static function loadByID($id){
		return self::select('id',$id)->SelectObject('place');
	}
	/**
	 * Загрузить по URL
	 * @param int $URL	 
	 * @return place
	 */
	static function loadByURL($URL){
		return self::select('id',$id)->SelectObject('place');
	}
	/**
	 * Добавить ссылку
	 *	 
	 */
	function addRef(){
		self::select('id',$this->getID())->Update(array('RefCount'=>$this->getInfo('RefCount')+1));
	}
	/**
	 * Создать
	 * @var string		$Title	название
	 * @var datastore 	$Region	регион
	 *
	 * @return place
	 * 
	 */
	static function create($Title, $Region){
		if (!$Url=str::formString($Title)) $Url='place';
		if (self::select('Url',$Url)->selectCount()){
			$Url.='-'.$Region->getInfo('Index','Url');
			if (self::select('Url',$Url)->selectCount()){				
				$Url= url_manager::FromSelect($sel)->getURL($Url);
			}
		}
		self::select()->Insert(array(
			'id'=>kernel::getNextID(),
			'Url'=>$Url,
			'Title'=>$Title,
			'Region'=>$Region->getID(),
			'IndexRegion'=>$Region->getInfo('Index','id'),			
		));
	}
}

class place_references extends DataStore  {
	/**
	 * Выборка
	 * 
	 * @return dbselect
	 */
	static function select(){
		$a= func_get_args();
		return dbselect::factory(array('id','UserID','Place','ts'),'place_references',null,$a); 
	}
	
	static function loadById($id){
		$sel=self::select('id',$id);
		$sel->Join(new dbselect_scalar(array('id','tags')),'id','id','tags','LEFT');
		$sel->Join(place::select(),'Place','id','Place','LEFT');
		return $sel->SelectObject('place_references');
	}

	/**
	 * Создать
	 *
	 * @param int 		$UserID
	 * @param int 		$PlaceID	  
	 * @param datastore $Region
	 * @param string 	$PlaceTitle
	 * @param string	$Tags
	 */
	static function Create($UserID, $PlaceID, $Region, $PlaceTitle=''){

		
	}
}
?>