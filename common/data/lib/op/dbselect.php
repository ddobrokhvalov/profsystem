<?php
/**
 * Библиотека доступа к БД
 * 
 */
class dbselect extends dbselect_fetch implements Iterator, Countable {

	var $lifetime=null;
	
	function setCacheLifetime($lifetime=0){
		$this->lifetime=$lifetime;
	}
	
	/**
	 * Аргументы-> в условие
	 *
	 * @param string/array $fields				список полей (массив или через запятую)
	 * @param string $table						имя таблицы
	 * @param string/array $serialized_fields	сериализованные поля	 
	 * @param array $where	линейный массив (func_get_args) e.c. имеем фабрику:
	 * class some_factory{
	 * 	static function getselect(){
	 * 		$sel= new dbselect(array('id','age','status','name'),'emploees');
	 * 		dbselect_factory::argv_select($sel, func_get_args());
	 * 		return $sel;		 
	 * 	}		  
	 * }
	 * 
	 * можно сразу сделать запрос:
	 * some_factory::getselect('age',19,'status','manager');
	 * 
	 * @return dbselect
	 */
	static function factory($fields, $table, $serialized_fields=null, $where=null){		
		$dbselect=new dbselect($fields, $table, $serialized_fields);
		self::where_command($dbselect, $where);
		return $dbselect;
	}
	
	static $defaultCacheLifetime=date::Hour;
	/**
	 * Создать кэшируемый объект см. factory
	 * Внимание! $serialized_fields - переехал в самый конец, ибо редко используется	 
	 *
	 * @param string/array $fields
	 * @param string $table
	 * @param array $where
	 * @param mixed $cache	параметры кэширования	
	 * @param stirng/array $serialized_fields
	 * 
	 * @return dbSelect
	 */
	static function factoryCacheable($fields, $table, $where=null, $cache=null, $lifetime=true, $serialized_fields=null){
		$ret= self::factory($fields, $table, $serialized_fields, $where);
		/*@var $ret dbSelect*/
		if ($cache===true){			
			$ret->tagsProvider= new dbSelect_Tags();
		}
		elseif (is_object($cache)){
			$ret->tagsProvider= new $cache;
		}
		elseif ($cache){
			$ret->tagsProvider= new dbSelect_Tags($cache);
		}
		if ($lifetime==true){
			$ret->setCacheLifetime();
		}
		
		return $ret;
	}
		
	/**
	 * Команда на запрос:
	 */
	static function &where_command(&$dbselect, $where){
		
		foreach (DataStore::inline_array($where) as $k=>$v) {
			$dbselect->Where($k,eq,$v);
		}		
		return $dbselect;
	}
	/**
	 * Сериализованные поля
	 *
	 * @var string
	 */
	var $serialized_fields=null;
	/**
	 * Выборка из базы
	 *
	 * @param string/array $fields				список полей (массив или через запятую)
	 * @param string $table						имя таблицы
	 * @param string/array $serialized_fields	сериализованные поля
	 * @param string $primary_key				первичный ключ
	 */
	function __construct($fields, $table, $serialized_fields=null){
		parent::__construct($fields, $table);
		if ($serialized_fields) $this->serialized_fields=(is_array($serialized_fields))?$serialized_fields:explode(',', $serialized_fields);
	}
	/**
	 * Провайдер бд
	 *
	 * @var dbselect_provider
	 */
	static $DB;
	/**
	 * Провайдер БД
	 *
	 * @return DB
	 */
	static function &getDBProvider(){
		if (dbselect::$DB) return dbselect::$DB;
		global $db;			
		return $db;
	}
	/**
	 * Количество записей:
	 *
	 */
	function selectCount(){
		$sql=$this->getCountSql();
		$cache= dbSelect_Cache::instance('count://'.$sql, $this->lifetime, $this->getTags());	
		
		if ($cache->isFound) return $cache->res;	
		/**
		 * Выбираем:
		 */							
		$rows=self::$DB->fetchResult($this->query($sql));
		$ret=(isset($rows[0][0]))?$rows[0][0]:0;
		/**
		 * Кэшируем:
		 */
		$cache->set($ret);
		return $ret;		
	}
	/**
	 * Получить теги запроса:
	 */
 	function getTags(){
		$ret=array();
		/**
		 * Явно заданные имена:
		 */
		if ($this->names) $ret= $this->names;
		/**
		 * Генератор имен:
		 */
		if ($this->tagsProvider) $ret= array_merge($ret, $this->tagsProvider->fromSelect($this));
		/**
		 * Имена в приджойненных таблицах:
		 */
		foreach ($this->Join as $j){
			$sel =$j[0];
			/*@var $sel dbSelect*/
			$ret=array_merge($ret, $sel->getTags());
		}
		
		return $ret;
	}
	
	protected $names=null;
	protected $tagsProvider=null;
	/**
	 * Добавить тег явно:
	 * 
	 * @var $name тег
	 */
	function setCacheName($name){		
		if (!$this->names){
			$this->names=array();
		}
		$this->names[]=($name[0]=='.')?$this->table.$name:$name;		
	}
	/**
	 * RIGHT JOIN (фактически текущая таблица джойниться к таблице $sel)
	 * 
	 * @var dbSelect $sel таблица присоединяемая "справа"
	 * @var string $ForeingKey ключ в текущей таблицы
	 * @var string $JoinedKey  ключ в присоединяемой таблице
	 * @var string $As		   алиас
	 * 
	 * @return dbselect
	 * 
	 * Т.е. у нас в корне выборки таблица $Items, но нам надо вывести $Items с рейтингом $Rate
	 * при этом не делая два шаблона, меняем местами
	 * 
	 * e.c. $Items=$Items->RightJoin($Rate,'id','id','Rate');
	 */
	function RightJoin($sel, $ForeingKey, $JoinedKey, $As){
		$sel->Join($this, $JoinedKey,$ForeingKey,$As,'LEFT',true);
		return $sel;
	}
	/**
	 * @var array_iterator
	 */
	var $res=null;
	/**
	 * Выбрать результат:
	 *
	 * @return boolean
	 */
	function selectResult($print=false){
		$sql=$this->getSql();
		/**
		 * Получаем объект кэша:
		 */
		$this->res= dbSelect_Cache::instance($sql, $this->lifetime, $this->getTags());
		if ($this->res->isFound){
			return true;
		}	
		/**
		 * В кэше ничего нет, выбираем:
		 */							
		$dbRes=$this->query($sql);	
			
		$this->res->res= self::$DB->fetchResult($dbRes);

		if (!$this->res->res) $this->res->res=array();
		$this->res->save();
		return true;
	}
	/**
	 * Количество записей:
	 */
	function numRows(){
		if (!$this->res) $this->selectResult();				
		return count($this->res);
	}
	function Free(){
		$this->res= null;
	}
	/**
	 * Разбить запись в массив:
	 */
	function assocResult(&$row, &$Offset=0){		
		$count=count($this->Select);
		$ret=array();
		
		for ($i=0; $i<$count;$i++){
			$key=(false==$pos=strrpos($this->Select[$i],' '))?$this->Select[$i]:substr($this->Select[$i],$pos+1);
			$ret[$key]=($this->serialized_fields&&in_array($key,$this->serialized_fields)&&$row[$Offset])?unserialize($row[$Offset]):$row[$Offset];
			$Offset++;
		}

		if ($count=count($this->Join)){
			foreach ($this->Join as $i=>$j){
				list($Join, $ForeingKey, $JoinedKey, $As)=$j;				
				$key=($As)?$As:$Join->Table;
				$res=$Join->AssocResult($row,$Offset);
				if ($this->Join[$i][5]){
					//RIGHT JOIN:
					//Для единого вывода
					$res[$key]=$ret;
					$ret=$res;				
				}
				else {
					$ret[$key]=$res;
				}
			}
		}
		
		return $ret;
	}
	/**
	 * Выбрать одну запись
	 */
	function selectObject($className=null){
		$this->Limit(0,1);
		$sql=$this->getSql();
		$cache= dbSelect_Cache::instance('object://'.$sql,$this->lifetime,$this->getTags());
		if ($cache->isFound) return $cache->res;
		/**
		 * Выбираем:
		 */
		$rows= self::$DB->fetchResult($this->query($sql));	
		if (isset($rows[0])) $ret=$this->assocResult($rows[0]);
		else return null;//Ничего не найдено	
		
		/**
		 * Кэшируем:
		 */
		$cache->set($ret);
		
		return ($className)?new $className($ret):$ret;
	}
	/**
	 * Выбрать первое поле первой записи:
	 */
	function selectString(){		
		$this->Limit(0,1);
		$this->Select($this->Select[0]);
		$sql=$this->getSql();
		$cache= dbSelect_Cache::instance('string://'.$sql, $this->lifetime, $this->getTags());	
		
		if ($cache->isFound) return $cache->res;	
		/**
		 * Выбираем:
		 */				
		$rows=self::$DB->fetchResult($this->query($sql));					
		if (isset($rows[0][0])){
			/**
			 * Кэшируем:
			 */
			$cache->set($rows[0][0]);
			return $rows[0][0];
		}
		else return null;
	}
	/**
	 * Удалить записи удвлетворяющие условию
	 */
	function Delete(){				
		$this->query($this->getDeleteSql());		
	}
	
	const Insert_INSERT='INSERT';
	const Insert_REPLACE='REPLACE';
	const Insert_INSERT_IGNORE='INSERT IGNORE';
	const INSERT='INSERT';
	const REPLACE='REPLACE';
	const INSERT_IGNORE='INSERT IGNORE';
	/**
	 * Добавить запись:
	 * @var array/dbselect $Data	данные	либо хэш данных, либо выборка, которая
	 * @var string (dbSelect::Insert_*) $Method	метор (INSERT,REPLACE,INSERT INGNORE)
	 * 
	 * @return insert_id/true - true- если выборку перенаправляем
	 */
	function Insert($Data, $Method='INSERT'){		
		$sql=$this->getInsertSql($Data,$Method);					
		$res=$this->query($sql);
			
		if (is_array($Data)&&isset($Data[$this->primary_key])) return $Data[$this->primary_key];
		//Стыт-позор- mysql_insert_id
		return self::$DB->lastInsertID();							
	}
	
	static $debug=false;
	
	function query($sql){		
		if (!$ret= self::$DB->query($sql)){
			throw new dbselect_exception($sql);
		}
		return $ret;		
	}
	
	function InsertOrUpdate($Data, $Increment=false){			
		$this->query($this->getInsertOrUpdateSql($Data, $Increment));
	}
	function Increment($Field='Total',$Increment=1,$Default=false){			
		$this->query($this->getIncrementSql($Field, $Increment, $Default));
	}
	/**
	 * Обновить:
	 * @var array $Data данные
	 */
	function Update($Data){	
			
		$this->query($this->getUpdateSql($Data));
	}

	/**
	 * Следующая запись:
	 */
	function next(){		
		if (!$this->res) $this->selectResult();
		if ($row=$this->res->next()) return $this->AssocResult($row);
		else return false;		
	}
	/**
	 * перемотка в начало
	 * так как у pear db нет нормальной перемотки в начало, то заново выбираем
	 */
	function rewind(){		
		if (!$this->res) $this->selectResult();
		$this->res->rewind();		
	}
	
	function current(){		
		if (!$this->res) $this->selectResult();
		return $this->assocResult($this->res->current());
	}
	
	var $i=0;
	function key(){		
		if (isset($this->current[$this->primary_key])) return $this->current[$this->primary_key];
		else return $this->i++;
	}
	
	function valid(){	
		if (!$this->res) $this->selectResult();
		return $this->res->valid();
	}
	
	const LogMethodScreen=1;	
	const LogMethodLogger=2;
	
	static $LogMethod=1;
	static $Logger=null;
	/**
	 * Лог запроса:
	 * @var string 	  $sql	запрос
	 * @var Exception $e	ошибка
	 */
	function Log($sql,$e=null){
		log::Event($sql, log::Error, log::SQL);
		if (self::$LogMethod=self::LogMethodScreen){
			echo $sql;
			
			die();
		}
		else {
			if ($e){
				self::$Logger->Error($sql, $e);				
			}
			else {
				self::$Logger->Debug($sql);
			}
		}
	}
	/**
	 * Вывести в табличку:
	 * 
	 * @return string	HTML таблица
	 */
	function render(){
		return 'not supported yet';		
	}
	
	function render_thead($Alias=''){
		$head2='';				
		if ($count=count($this->Select)){
			for ($i=0;$i<$count;$i++){
				$arr=explode(' AS ',$this->Select[$i],2);
								
				$head2.='<th>'.((count($arr)==1)?$arr[0]:$arr[1]).'</th>';
			}
		}
		else return '';
		$head1='<th colspan="'.$count.'">'.$this->getFieldPrefix($Alias).'</th>';
		if ($count=count($this->Join)){
			for ($i=0;$i<$count;$i++){
				$Join=&$this->Join[$i][0];				
				list($head1_,$head2_)=$Join->render_thead($this->Join[$i][3]);
				$head1.=$head1_; $head2.=$head2_;
			}
		}
		return array($head1,$head2);
	}
	
	function render_row($row){
		$ret='';
		if ($count=count($row)){
			for ($i=0;$i<$count;$i++){				
				$ret.='<td>'.$row[$i].'</td>';
			}
		}
		
		return $ret;
	}

	function count(){
		return $this->NumRows();
	}
	/**
	 * Грохнуть по итогам:
	 */
	static $TruncateOnUnload=null;
	
	static function TruncateOnUnload($Table){
		dbselect::factory(array(),$Table)->Delete();
//		if (!self::$TruncateOnUnload){
//			self::$TruncateOnUnload=array($Table);
//			register_shutdown_function(array('dbselect','TruncateTemporaryTables'));
//		}
//		else {
//			self::$TruncateOnUnload[]=$Table;
//		}
	}
	/**
	 * Обнулить временные таблицы:
	 */
	static function TruncateTemporaryTables(){
		if ($count=count(self::$TruncateOnUnload)){
			for ($i=0;$i<$count;$i++){
				$this->query('TRUNCATE TABLE `'.self::$TruncateOnUnload[$i].'`;');
			}
		}
	}
	/**
	 * Клонировать таблицу:
	 * @var string $Table	имя таблицы
	 * @var boolean $CopyData	копировать данные
	 * 
	 * @return dbselect
	 */
	function CloneTable($Table, $CopyData=false){
		$this->query('DROP TABLE IF EXISTS '.$Table);
		$this->query('CREATE TABLE '.$Table.' LIKE '.$this->getSqlTable());
		if ($CopyData){ $this->query("INSERT INTO ".$Table." SELECT * FROM ".$this->getSqlTable());}
		return new dbselect($this->Select, $Table, $this->serialized_fields);
	}
	/**
	 * Клонирование (сбрасываем условия)
	 */
	function __clone(){
		//$this->Where=array();
	}
}

interface dbselect_provider {
	/**
	 * Выбрать запись:
	 * @param $sql
	 * @return unknown_type
	 */
	function query($sql);
	
	function fetchResult($res);
	
	function lastInsertID();
}
?>
