<?php
/**
 * Доступ к данным:
 */
class DataStore implements ArrayAccess {
	var $Info=null;
	
	function __construct($Info=null){
		$this->Info=$Info;
	}
	
	public function __get($key){		
		return $this->getInfo($key);
	}
	
	public function __set($key, $value){
		$this->Info[$key]=$value;
	}
	
	public function getInfo(){				
		return $this->_getInfo($this->_argv2path(func_get_args()));
	}
	
	public function getInt(){
		return DataStore::toInt($this->_getInfo($this->_argv2path(func_get_args())));
	}
	/**
	 * Получает значение поля $name если поле не задано (или нулевое с $allow_empty=false) возвращает $default
	 *
	 * @param string/array 	$name			ключ поля
	 * @param mixed 		$default		значение по умолчанию
	 * @param boolean		$allow_empty	возвращать пустое значение
	 * 										поясним:
	 * 										
	 * 										$a->Info['SomeField']='';
	 * 										$a->getSetting('SomeField', 'DefaultValue', true); ->	''
	 * 										$a->getSetting('SomeField', 'DefaultValue', false); ->	'DefaultValue'
	 * 
	 * 										так метод нужен главным образом для получения настроек, $allow_empty=true
	 * 										e.c. 'MaxUserImages' - максимальное кол-во изображений
	 * 															по умолчанию -3
	 * 															чтобы отключить явно выставляем в 0
	 * 															
	 * @return mixed
	 */
	public function getSetting($name, $default=null, $allow_empty=true){
		if ($ret=$this->getInfo($name)) return $ret;
		if ($ret===null) return $default;
		return ($allow_empty)?$ret:$default;
	}
		
	public function getID(){
		return $this->getInfo('id');
	}
	
	var $Error=null;
	public function Error($Error=null){
		if ($Error){
			$this->Error=$Error;
			return false;
		}
		else {
			return $this->Error();
		}
	}
	/**
	 * Получить объект ответа
	 *
	 * @return unknown
	 */
	public function &getObject(){
		$ret=null;
		if ($Info=$this->_getInfo($this->_argv2path(func_get_args()))){
			$ret= (is_object($Info))?$Info:new DataStore($Info);			
		}
		return $ret;		
	}
	/**
	 * Приводит аргументы к общему виду
	 *
	 * Все приводиться к линейному массиву аргументов:
	 * 
	 * array(array('Item','User'),'Photo.Width','px'))=> arry('Item','User','Photo','Width','px')
	 */
	public static function Argv2Path($arguments){
		
		if (is_array($arguments)){
			
			$argv=&$arguments;
		}
		else {
			
			$argv=explode('.', (string)$arguments);
		}		
		$count=count($argv);
		if ($count==1&&$argv[0]=='$') return null;
		
		$ret=array();
		foreach($argv as $obj){
			if (is_array($obj)){
				$ret=array_merge($ret,self::Argv2Path($obj));
			}
			elseif (false!==strpos($obj,'.')){
				$ret=array_merge($ret,self::Argv2Path(explode('.', $obj)));
			}
			else {
				$ret[]=trim($obj,'$');
			}			
		}
		
		return $ret;
	}
	/**
	 * Рудимент
	 */
	protected static function _argv2path($arguments){
		return self::Argv2Path($arguments);
	}
	/**
	 * Возврачает ссылку на элемент по ключу $path (массив)
	 *
	 * @param array $path
	 */
	protected function _getInfo($path){	

		if (!$path||!count($path)) return $this->Info;
		$ret=&$this->Info;
		$void=null;
		$count=count($path);		
		
		for($i=0; $i<$count; $i++){			
			if (is_array($ret)&&isset($ret[$path[$i]])){
				
				$ret=&$ret[$path[$i]];
				
				if (is_object($ret)&&method_exists($ret,'getInfo')){
					if ($i==$count-1) return $ret;
					else {
						return $ret->getInfo(array_slice($path,$i+1));
					}
				}
			}
			else return $void;
		}
		return $ret;
	}
	
	function __tostring(){
		if (is_string($this->Info)) return $this->Info;
		else return '<pre>'.print_r($this->Info, true).'</pre>';
	}
	/**
	 * Рекурсивно получает данные по ключу $key
	 * Если указан implode -собираются все данные от узла к корню и объединяются через $implode
	 * иначе ищеться первое вхождение
	 *
	 * @param string/array 	 $path		path.to.section.separated.by.dot
	 * @param string 		 $key		ключ до узла
	 * @param string/boolean $implode	объединение
	 */
	function getInfoRecursive($path, $key, $implode=false){
		$_path=$this->_argv2path($path);
		if ($implode) $ret=array();
		while(count($_path)){
			$_path[]=$key;
			$i= $this->_getInfo($_path);
			
			if ($i!==null){
				if ($implode) $ret[]=$i;
				else return $i;
			}
			$_path=array_slice($_path,0,-2);
		}
		
		if ($implode&&count($ret)) return implode($implode, $ret);
		else return null;
	}

	static function toInt($obj){
		
		if (!$obj) return 0;//Пустой объект
		if (is_numeric($obj)) return $obj;
		elseif (is_object($obj)&&method_exists($obj,'getID')) return $obj->getID();
		elseif (is_array($obj)){
			return $obj['id'];
		}
		else{			
			throw new Exception('Invalid object type');
		}
	}
	/**
	 * Фабрика кухня:
	 * 
	 * @return datastore
	 */
	static function factory($obj, $class='datastore', $loader='loadByID'){
		
		if (is_object($obj)){
			if (get_class($obj)!=$class) return new $class($obj->Info);
			else return $obj;
		}
		elseif (is_array($obj)){
			return new $class($obj);
		}
		elseif ($obj&&is_numeric($obj)){							
			$ret= call_user_func(array($class,$loader),$obj);			
			return $ret;
		}
		else return null;
	}
		
	/**
	 * Приведение к массиву:
	 */
	static function toArray($obj){
		if ($obj===null||$obj===false||$obj==='') return null;
		if (is_array($obj)) return $obj;
		if (is_object($obj)) return $obj->getInfo();
		return explode(',',$obj);
	}
	/**
	 * 
	 */
	static function inline_array($a, $explode=null, $trim=null){
		$p= self::normalPath($a, $explode, $trim);
		
		$ret=array();
		$count=count($p);
		for ($i=0; $i<$count; $i++){
			if (!isset($p[$i+1])) throw new Exception('Invalid path');
			$ret[$p[$i]]=$p[$i+1];
			$i++;	
		}
		return $ret;
	}
	/**
	 * Приводит кашу к нормализованному одномерному массиву:
	 * 
	 * @var mixed 	$path
	 * @var string  $explode	разделители
	 * @var string	$trim		триммер
	 * 
	 * используется для нормализации сложных путей, используется для фикса ф-й использующих func_get_args е.с.
	 * 
	 * function foo(){
	 * 	$a= func_get_args();
	 * 	print_r(datastore::normalPath($a, ',');
	 * }
	 * 
	 * function bar(){
	 * 	$a= func_get_args();
	 * 	foo($a, 'bar');
	 * }
	 * 
	 * bar(array('a,b,c','d',array('e','f')),'g'); -> array(a, b, c, d, e, f, g, bar)
	 * 
	 */
	static function normalPath($path, $explode=null, $trim=' '){
		
		$ret= array();
		if ($path===null) return $ret;
		if (is_array($path)){
			foreach ($path as $o){
				if (is_array($o)){
					$ret+=self::normalPath($o, $explode);
				}
				elseif($explode){
					$ret+=self::normalPath(explode($explode,trim($o,$trim)));					
				}
				else {
					$ret[]=trim($o,$trim);
				}
			}
		}
		elseif ($explode){
			return self::normalPath(explode($explode, $path), $explode, $trim);
		}
		else {
			return array(trim($path, $trim));
		}
		
		return $ret;
	}


	function offsetExists($offset){
		return $this->_getInfo(explode('.',$offset)===null)?false:true;
	}
	
	function offsetGet($offset){
		return $this->_getInfo(explode('.',$offset));
	}
	
	function offsetSet($offset, $value){
		$o=explode('.',$offset);
	}
	
	function offsetUnset($offset){
		
	}
	
	var $errors=array();
	
	function throwError($field, $code='invalid'){
		$this->errors[$field]=$code;
	}
	
	function isValid(){
		return (count($this->errors))?false:true;
	}
}

class datastore_duplicateEntry_Exception extends Exception{
	var $Entry;
	function __construct($Entry,$Message='EntryJustExists'){
		parent::__construct($Message,0);
		$this->Entry=$Entry;
	}
}

class datastore_invalidEntry extends Exception{
	var $Name=null;
	function __construct($Name, $Code=0, $Message=null){
		parent::__construct(($Message)?$Message:$Name.' invalid', $Code);
		$this->Name=$Name;
	} 
}
?>