<?php
/**
 * Сохраняемый объект.
 * 
 * Смысл в том, что datastore_dbobject может сохраняться и восстанавливаться по указанной схеме.
 * 
 * Схема представляет собой массив "ключ-тип"
 * Поддерживаемые типы:
 * 
 * datastore_dbobject::int		число
 * datastore_dbobject::string	строка
 * datastore_dbobject::data		сериализуемые данные	(на самом деле ничем от строки не отличается)
 * storage::Text				текст, сохраняемый в общем хранилище
 * storage::Data				данные в общем хранилище
 * +
 * объект	dbSelect			связанная выборка (сохраняется id)
 * 
 * @todo объект input				
 * 
 */
abstract class datastore_dbobject extends DataStore {
	/**
	 * Сам по себе:
	 *
	 * @var datastore
	 */
	protected $self;
	/**
	 * Схема:
	 *
	 * @var datastore
	 */
	protected $scheme;
	
	const int='datastore_int';
	const string='datastore_string';
	const tag_set='tag_set';
	const data='datastore_dbobject_data';
	/**
	 * @var array $Info	информация
	 * @var 
	 * 
	 */
	function __construct($Info=null, $scheme=null){
		$this->scheme=$scheme;
		if ($Info) $this->init($Info);
	}
	
	function init($Info){
				
		$this->self= new DataStore($Info);
		
		parent::__construct(array());
		$this->Info['id']=$this->self->getID();
		//$this->scheme=$scheme;
		foreach ($this->scheme as $key=>$type) {
			switch ($type){
				case storage::Text:
				case storage::Data:									
					$this->Info[$key]=$this->self->getInfo($key,$type);
				break;				
				case datastore_dbobject::int:
				case datastore_dbobject::string:												
					$this->Info[$key]=$this->self->getInfo($key);
				break;
				case datastore_dbobject::tag_set:					
					$this->Info[$key]=$this->self->getInfo($key,'Data');
				break;
				default:
					$this->Info[$key]=$this->self->getInfo($key);
				break;
			}
		}
		
	}
	/**
	 * @return dbSelect
	 */
	abstract function getSelect();
	/**
	 * Сохранить:
	 *	
	 * Приводит объект в соответсвие со схемой
	 * 
	 * @return int id
	 */
	function save(){
		$tags=array();
		if (!$this->getID()){
			$this->Info['id']=kernel::getNextID();
		}
		foreach ($this->scheme as $key=>$type) {
			
			$obj=$this->getInfo($key);
			if (is_object($obj)&&method_exists($obj,'commitData')) $obj=$obj->commitData($this->getID());		
			switch ($type){
				case storage::Text:
				case storage::Data:
					$Update[$key]=storage::save($this->self->getInfo($key,'id'),$this->getInfo($key),$type);
				break;
				case datastore_dbobject::tag_set:
					$Update[$key]=storage::save($this->self->getInfo($key,'id'),$this->getInfo($key),storage::Data);
					/**
					 * Обновляем индекс:
					 */

					$tags[$key]= new tag_set_diff($this->getInfo($key), $this->self->getInfo($key,'Data'));					
				break;
				case datastore_dbobject::string:						
					$Update[$key]=$this->getInfo($key);
				break;					
				default:						
					$Update[$key]=DataStore::toInt($this->getInfo($key));
				break;
			}
			
		}
		str::print_r($Update);
		if ($this->self->getID()){
			$this->getSelect('id',$this->getID())->Update($Update);
		}
		else {
			$Update['id']=$this->getID();
			$this->getSelect()->Insert($Update);
		}
		
		foreach ($tags as $key=>$diff) {
			//Проставляем теги:
			/*@var $diff tag_set_diff*/
			$sel=$this->getTagsSelect($key);
			/*@var $sel dbSelect*/
			$sel->Where('id',eq,$this->getID());
			
			$diff->addRef($sel);
			if ($selectCloud=$this->getTagsCloudSelect($key));
			/*@var $selectCloud*/
			$diff->incrementRef($selectCloud);
		}
		
		return $this->getID();
	}
	
	function delete(){
		if (!$this->getID()) return false;
		foreach ($this->scheme as $key=>$type) {
			switch ($type){
				case storage::Text:
					if ($ref=$this->self->getInfo($key,'id')) storage::selectTexts('id',$ref)->Delete();
				case storage::Data:
					if ($ref=$this->self->getInfo($key,'id')) storage::selectData('id',$ref)->Delete();
				break;
				case datastore_dbobject::tag_set:
					$tsd= new tag_set_diff(null,$this->getInfo('key'));
					//Удаляем связь с тегами:
					$sel=$this->getTagsSelect($key);
					$sel->Where('id',eq,$this->getID());
					$tsd->addRef($sel);		
					if ($selectCloud=$this->getTagsCloudSelect($key)){
						/*@var $selectCloud dbSelect*/
						$tsd->incrementRef($selectCloud);
					}
				break;
				default:
					if (is_object($this->getInfo($key))) $this->getInfo($key)->Delete();
				break;
			}
		}
		echo $this->getSelect('id',$this->getID());
		$this->getSelect('id',$this->getID())->Delete();
		return true;				
	}
	
	static function loadByID($id, $className){
		if (!$id=str::natural($id)) return null;
		$ret= new $className();
		/*@var $ret datastore_dbobject*/
		$sel=$ret->getSelect();
		$sel->Where('id',eq,$id);
		foreach ($ret->scheme as $key=>$type) {
			if (is_object($type)){
				$sel->leftJoin($type,$key);
			}
			else {
				switch ($type){
					case storage::Text:
						$sel->leftJoin(storage::selectTexts(),$key);
					break;
					case datastore_dbobject::tag_set:
					case storage::Data:
						$sel->leftJoin(storage::selectData(),$key);
					break;										
					default:
						if ($type!=self::int&&$type!=self::string&&method_exists($type,'select')){
							$sel->leftJoin(call_user_func(array($type,'select')), $key, $type);
						}
					break;
					
				}
			}
		}
		
		if ($Info=$sel->SelectObject()){
			
			$ret->init($Info);
			return $ret;
		}
		else {
			return null;
		}
	}

	var $Errors=array();
	
	function throwError($Field, $Message='invalidValue'){
		$this->Errors[$Field]=array('Field'=>$Field,'Message'=>$Message);
	}
	
	function isValid(){
		return (count($this->Errors))?false:true;
	}
	
	function _getInfo($path){
		if (isset($path[0])&&$path[0]=='_Error'){
			if (isset($path[1])) return (isset($this->Errors[$path[1]]))?$this->Errors[$path[1]]['Message']:null;
			else return $this->Errors;
		}
		return parent::_getInfo($path);
	}
}
?>