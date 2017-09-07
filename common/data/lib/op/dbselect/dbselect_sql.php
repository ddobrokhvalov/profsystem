<?
define('eq', 'eq');
define('noteq', 'noteq');
define('grater', '>');
define('grater_or_eq','>=');
define('smaller','<');
define('smaller_or_eq','<=');
define('exist','exist');
define('notexist', 'notexist');
define('between', 'between');
define('asis', 'asis');
define('like', 'like');	
define('notnull','not null');
define('isnull','is null');
define('match','match');
define('where_all', ' 1 ');
/**
 * Класс для формирования sql запросов:
 * 
 * см. http://wiki.toukmanov.ru/dbSelect
 * 
 */
class dbselect_sql {
	/**
	 * Список полей
	 *
	 * @var array
	 */
	var $Select=array();
	/**
	 * Ограничить поля выборки:
	 * Можно передать как аргументы: $sel->Select('id','Name','Age');
	 * Можно передать как массив:	 $sel->Select(array('id','Name','Age'));
	 * Можно передать как строку:	 $sel->Select('id, Name, Age');
	 * 
	 * Внимание! Первое поле будет первичным ключем
	 */
	function &Select(){
		$num=func_num_args();
		if ($num==1){			
			$arg=func_get_arg(0);
			if (is_array($arg)) $this->Select=$arg;
			else $this->Select=$this->_strToSelect($arg);
		}
		else {
			$this->Select=func_get_args();			
		}
		if (count($this->Select)) $this->primary_key=$this->Select[0];
		return $this;
	}
	
	protected function _strToSelect($str){
		$ret=explode(',',$str);
		if ($count=count($ret)){
			for($i=0; $i<$count; $i++){
				$ret[$i]=trim($ret[$i]);
			}
		}
		return $ret;
	}
	/**
	 * Имя таблицы
	 *
	 * @var string
	 */
	var $table=null;
	/**
	 * Первичный ключ
	 *
	 * @var string
	 */
	var $primary_key=null;
	/**
	 * Первичный ключ:
	 * @return string
	 */
	function getPrimaryKey(){
		return $this->primary_key;
	}
	/**
	 * Построитель запросов для выборки из базы
	 *
	 * @param string/array $fields				список полей (массив или через запятую)
	 * @param string $table						имя таблицы
	 * @param string/array $serialized_fields	сериализованные поля
	 * @param string $primary_key				первичный ключ
	 */
	function __construct($fields, $table) {
		
		if ($fields){
			$this->Select($fields);			
		}
		
		if (!$table) throw new Exception('Table undefined');
		$this->table=$table;					
	}
	/**
	 * Получить запрос:
	 */
	function getSql($NoLimit=false){
				
		$ret= 'SELECT '.$this->getSelect();		
		$ret.=' FROM '.$this->getSqlTables();		
		$ret.=' WHERE '.$this->getSqlWhere();		
		$ret.=$this->getSqlGroupBy();		
		$ret.=$this->getSqlOrderBy();		
		if (!$NoLimit) $ret.=$this->getSqlLimit();	
			
		return $ret;
	}
	/**
	 * Получить запрос для количества записей:
	 */
	function getCountSql(){
		return 'SELECT count(*) FROM '.$this->getSqlTables(true, false)." WHERE ".$this->getSqlWhere();
	}	
	/**
	 * Эскейпит строку
	 *
	 * @param string $str
	 * @return string
	 */
	static function Escape($str){
		global $db;
		if (is_array($str)){
			$ret='';
			foreach ($str as $a) {
				str::Add($ret, (is_numeric($a))?$a:"'".dbselect_sql::Escape($a)."'", ',');
			}			
			return $ret;
		}
		else {			
			return dbselect::$DB->escape($str);		
		}
	}
	
	var $_SqlTable=null;
	/**
	 * Таблица в sql представлении
	 *
	 * @param string $Table
	 */
	public function getSqlTable(){
		if ($this->_SqlTable){
			return $this->_SqlTable;
		}
		else {
			$Table=explode('.', $this->table);
			$count=count($Table);
			for ($i=0;$i<$count;$i++){
				$this->_SqlTable.=(($this->_SqlTable)?'.':'').'`'.$Table[$i].'`';
			}
			return $this->_SqlTable;
		}
	}
	/**
	 * Префикс поля (по умолчанию- имя таблицы)
	 */
	protected function getFieldPrefix($As=null){
		if ($As) return $As;
		else return $this->getSqlTable();
	}
	
	var $Where=array();
	var $WhereCounter=0;
	/**
	 * Условия
	 * !Notice: все условия идут через AND, если надо условие через OR его придеться написать руками
	 * @var string $Field	поле
	 * @var string $Type	тип сравнения (см константы сверху)
	 * @var mixed  $Value	сравниваемое значение
	 * 
	 * f.ex:
	 * $sel->Where('id', eq, 1) -> WHERE `id`=1
	 * $sel->Where('id', eq, array(1,2,3)) -> WHERE `id` IN (1,2,3)
	 * $sel->Where('date',between,array('2008-01-03','2008-01-10')) -> WHERE `date` BETWEEN '2008-01-03' AND '2008-01-10'
	 * 
	 * Notice: если вы употребляете asis условия, поля можно заключить в {$FieldName} в тогда к ним подставиться префикс текущей таблицы
	 * 	
	 * @return WhereID (по которому можно удалить условие)
	 */
	function Where($Field, $Type=asis, $Value=null){
		if (!$Field) str::print_r("WTF");		
		$this->Where[$this->WhereCounter]=array($Field, $Type, $Value);
		return $this->WhereCounter++;
	}	
	/**
	 * SQL условие для Where:
	 * 
	 * @var string $As	префикс
	 * @return string кусок sql
	 */
	function getSqlWhere($As=null){
		$ret='';
		$FieldPrefix=$this->getFieldPrefix($As);
		
		if ($count=count($this->Where)){
			foreach ($this->Where as $i=>$obj){
				list($Field, $Type, $Value)=$obj;

				switch ($Type){
					case eq:
						
						if (is_numeric($Value)){
							str::Add($ret, $FieldPrefix.'.`'.$Field.'`='.$Value, ' AND ');
						}
						elseif (is_string($Value)){
							str::Add($ret, $FieldPrefix.'.`'.$Field.'`=\''.dbselect_sql::Escape($Value).'\'', ' AND ');
						}
						elseif (is_array($Value)){
							str::Add($ret, $FieldPrefix.'.`'.$Field.'` IN ('.dbselect_sql::Escape($Value).')', ' AND ');
						}
					break;
					case noteq:
						if (is_numeric($Value)){
							str::Add($ret, $FieldPrefix.'.`'.$Field.'`!='.$Value, ' AND ');
						}
						elseif (is_string($Value)){
							str::Add($ret, $FieldPrefix.'.`'.$Field.'`!=\''.dbselect_sql::Escape($Value).'\'', ' AND ');
						}
						elseif (is_array($Value)){
							str::Add($ret, $FieldPrefix.'.`'.$Field.'` NOT IN ('.dbselect_sql::Escape($Value).')', ' AND ');
						}
					break;
					case grater:
						if (is_numeric($Value)){
							str::Add($ret, $FieldPrefix.'.`'.$Field.'`>'.$Value, ' AND ');
						}
						else {
							str::Add($ret, $FieldPrefix.'.`'.$Field.'`>\''.dbselect_sql::Escape($Value).'\'', ' AND ');
						}
					break;
					case grater_or_eq:
						if (is_numeric($Value)){
							str::Add($ret, $FieldPrefix.'.`'.$Field.'`>='.$Value, ' AND ');
						}
						else {
							str::Add($ret, $FieldPrefix.'.`'.$Field.'`>=\''.dbselect_sql::Escape($Value).'\'', ' AND ');
						}
					break;
					case smaller:
						if (is_numeric($Value)){
							str::Add($ret, $FieldPrefix.'.`'.$Field.'`<'.$Value, ' AND ');
						}
						else {
							str::Add($ret, $FieldPrefix.'.`'.$Field.'`<\''.dbselect_sql::Escape($Value).'\'', ' AND ');
						}
					break;
					case smaller_or_eq:
						if (is_numeric($Value)){
							str::Add($ret, $FieldPrefix.'.`'.$Field.'`<='.$Value, ' AND ');
						}
						else {
							str::Add($ret, $FieldPrefix.'.`'.$Field.'`<=\''.dbselect_sql::Escape($Value).'\'', ' AND ');
						}
					break;
					case between:
						str::Add($ret, $FieldPrefix.'.`'.$Field.'` BETWEEN \''.dbselect_sql::Escape($Value[0]).'\' AND \''.dbselect_sql::Escape($Value[1]).'\'', ' AND ');
					break;
					case like:
						str::Add($ret, $FieldPrefix.'.`'.$Field.'` LIKE \''.dbselect_sql::Escape($Value).'\'', ' AND ');
					break;
					case exist:
						str::Add($ret, $FieldPrefix.'.`'.$Field.'`!=\'\'', ' AND ');
					break;
					case notexist:
						str::Add($ret, 'NOT '.$FieldPrefix.'.`'.$Field.'`', ' AND ');
					break;
					case asis:						
						str::Add($ret, preg_replace('/\{\$(\w+?)\}/',$FieldPrefix.'.`\\1`',$Field), ' AND ');
					break;
					case notnull:
						str::Add($ret, 'NOT '.$Field.' IS NULL', ' AND ');
					break;
					case isnull:
						str::Add($ret, $Field.' IS NULL', ' AND ');
					break;	
					case match:
						str::Add($ret, 'MATCH('.implode(',',$Field).') AGAINST (\''.self::Escape($Value).'\')',' AND ');
					break;			
				}									
			}				
		}

		/**
		 * Условия на приджойненных таблицах:
		 */
		if ($count=count($this->Join)){
			foreach ($this->Join as $i=>&$JoinData){
				$Join=&$JoinData[0];									
				if (($JoinWhere=$Join->getSqlWhere($this->Join[$i][3]))&&($JoinWhere!=where_all)) str::Add($ret, '('.$JoinWhere.')', ' AND ');
			}
		}
		return ($ret)?$ret:where_all;
	}
	/**
	 * Сбросить условие (по умолчанию- все)
	 * @var $WhereID - id условия f.ex.:
	 * 		//Обновляем кучу говней через один объект:
	 * 		$sel->Where('editable',eq,1);//Это условие будет всегда:
	 * 		foreach($UpdateOrder as $id=>$NewOrder){
	 * 			$WhereID=$sel->Where('id',eq,$id);
	 * 			$sel->Update(array('OrderParam'=>(int)$NewOrder));
	 * 			$sel->DropWhere($WhereID);
	 * 		}  
	 */
	function dropWhere($WhereID=false){
		
		if ($WhereID===false){
			$this->Where=array();
			return true;
		}
		elseif(isset($this->Where[$WhereID])){
			$this->Where[$WhereID]=null;
			return true;
		}
		else {
			return false;
		}
	}
	
	var $Join=array();
	var $joinCounter=0;
	/**
	 * Приджойнить другой селекотор
	 * @var dbSelect $Table 	таблица
	 * @var string 	 $ForeingKey	ключ в текущей таблице
	 * @var string 	 $JoinedKey	ключ в присоединяемой таблице
	 * @var string 	 $As			алиас таблицы
	 * @var string 	 $Type		тип присоединение (LEFT|INNER)
	 * @var string	 $class		класс обертка для приджойненного	 		
	 */
	function Join($Table, $ForeingKey, $JoinedKey, $As, $Type='INNER', $isRight=false, $class=null){		
		$this->Join[$this->joinCounter]=array($Table, $ForeingKey, $JoinedKey, $As, $Type, $isRight, $class);
		return $this->joinCounter++;
	}	
	
	function dropJoin($handler){
		unset($this->Join[$handler]);
	}
	/**
	 * Приекрепить справочник:
	 * 
	 * @var dbSelect $sel справочник
	 * @var string	 $ForeingKey	ключ
	 * @var string	 $class	класс в который пакуется
	 */
	function leftJoin($sel, $ForeingKey, $class=null){
		$this->Join($sel, $ForeingKey, 'id', $ForeingKey, 'LEFT', false, $class);
	}
	/**
	 * Список таблиц для sql запроса
	 * @var $ReturnSelf	возвращать себя/только прикрепленные (для след. итерации- false)
	 * @var $LeftJoin	левое поле
	 * @var $SelfAlias	собственный алиас ($As)
	 * 
	 * @return string 
	 */
	function getSqlTables($ReturnSelf=true, $LeftJoin=true, $SelfAlias=null){
		$SelfAlias=$this->getFieldPrefix($SelfAlias);			
		$ret=($ReturnSelf)?$this->getSqlTable():'';
		//str::print_r($this->Join,'JOIN',true);
		if ($count=count($this->Join)){
			
			foreach (array_keys($this->Join) as $i){
				list ($Join, $ForeingKey, $JoinedKey, $As, $Type)=$this->Join[$i];
				/*@var $Join dbSelect*/
				if (!$LeftJoin&&$Type=='LEFT') continue;
				str::Add($ret, ' '.$Type.' JOIN '.$Join->getSqlTable().(($As)?" AS ".$As:""),' ');
				if ($ForeingKey&&$JoinedKey){
					$ret.=' ON '.$SelfAlias.'.`'.$ForeingKey.'`='.$Join->getFieldPrefix($As).'.`'.$this->Join[$i][2].'`';
				}
				elseif($ForeingKey){
					$ForeingKey=preg_replace('/{\$1\.(\w+?)}/i', $SelfAlias.'.`\\1`',$ForeingKey);
					$ForeingKey=preg_replace('/{\$2\.(\w+?)}/i', $Join->getFieldPrefix($As).'.`\\1`',$ForeingKey);
					$ret.=' ON '.$ForeingKey;
				}
				/*@var $Join dbselect_sql*/
				str::Add($ret, $Join->getSqlTables(false, $LeftJoin, $As),' ');
			}
		}	
		
		return $ret;
	}
	
	var $GroupBy=array();
	/**
	 * Группировать по полю
	 */
	function GroupBy(){		
		$this->GroupBy+=func_get_args();
	}
	/**
	 * Получить sql запрос для группировки
	 * @var $SelfAlias	алиас для таблицы
	 * @var $FieldsOnly	только список полей
	 * 
	 * @return string 'GROUP BY `some_table`.`some_field`, some_alias.`another_field`
	 */
	function getSqlGroupBy($SelfAlias='', $FieldsOnly=false){
		$ret='';
		if ($count=count($this->GroupBy)){
			for ($i=0; $i<$count; $i++){
				$obj=&$this->GroupBy[$i];
				if (false!==$pos=strrpos($obj,' ')){
					str::Add($ret, substr($obj,$pos+1), ',');
				}
				else {
					str::Add($ret, $this->getFieldPrefix($SelfAlias).'.`'.$obj.'`', ',');
				}
			}
		}
		if ($count=count($this->Join)){
			foreach (array_keys($this->Join) as $i){
				$Join=&$this->Join[$i][0];
				str::Add($ret, $Join->getSqlGroupBy($this->Join[$i][3],true), ',');
			}
		}
				
		if ($FieldsOnly) return $ret;
		else return ($ret)?' GROUP BY '.$ret:'';
	}
	
	/**
	 * Параметры сортировки
	 *
	 * @var array
	 */
	var $Order;
	/**
	 * Сортировка:
	 *
	 * @param string $OrderBy	поле
	 * @param string $How		ASC/DESC
	 */
	function OrderBy($OrderBy, $How='DESC'){
		$How=strtoupper($How);
		if ($How!='ASC'&&$How!='DESC') throw new Exception('Undefined order type');
		
		$this->Order[]=array($OrderBy, $How);
		return true;
	}
	/**
	 * Сортировка
	 * 
	 * @var boolean $FieldsOnly	только поля
	 * @var string $As алиас таблицы
	 *
	 * @return string параметры сортировки
	 */
	function getSqlOrderBy($FieldsOnly=false, $As=null){
		$ret='';
		$FieldPrefix=$this->getFieldPrefix($As);
		
		if ($this->Order&&$count=count($this->Order)){
			for ($i=0; $i<$count; $i++){
				$obj=&$this->Order[$i];
				
				if (false===strpos($obj[0],'(')&&false==strpos($obj[0],' AS ')){									
						str::Add($ret, $FieldPrefix.'.`'.$obj[0].'` '.$obj[1], ',');							
				}
				else {
					//По функции:						
					$Field=(false===$pos=strpos($obj[0], ' AS '))?$obj[0]:substr($obj[0], $pos+4);					
					str::Add($ret, $Field.' '.$obj[1], ',');
				}
				
			}
		}
		
		if ($count=count($this->Join)){
			foreach (array_keys($this->Join) as $i){
				$Join=&$this->Join[$i][0];
				str::Add($ret, $Join->getSqlOrderBy(true, $this->Join[$i][3]), ' ,');					
			}
		}
		if ($FieldsOnly) return $ret;
		return ($ret)?' ORDER BY '.$ret:$ret;
	}
	
	/**
	 * Смещение:
	 *
	 * @var int
	 */
	var $Offset=0;
	/**
	 * Количество:
	 *
	 * @var int
	 */
	var $Limit=0;
	/**
	 * Ограничить
	 *
	 * @param int $Offset	 ОБЮЙОБС У
	 * @param int $Limit	 УЛПМШЛП
	 * @param boolean $Round ПЛТХЗМЙФШ РП $Limit
	 * @return boolean	true/false
	 */
	function Limit($Offset=0, $Limit=0, $Round=false){
		$Limit=(int)$Limit;
		$Offset=(int)$Offset;
		if ($Limit<=0||$Offset<0) throw new Exception('Limit and offset must be positive');
		if ($Round) $Offset-=$Offset%$Limit;
		$this->Offset=$Offset;
		$this->Limit=$Limit;
		return true;
	}
	
	function Page($Page, $Limit){
		$Page=str::natural($Page,1);
		$this->Limit(($Page-1)*$Limit,$Limit);
	}
	
	function getOffset(){return $this->Offset;}
	
	function getLimit() {return $this->Limit; } 
	/**
	 * Получить лимит для запроса:
	 * 
	 * @return string 'LIMIT 5,10'
	 */
	function getSqlLimit(){
		return ($this->Limit)?' LIMIT '.$this->Offset.",".$this->Limit:'';
	}
	/**
	 * Список полей для выборки:
	 *
	 * @param string $As алиас таблицф
	 */
	function getSelect($As=null){
		$ret='';
		$TablePrefix=$this->getFieldPrefix($As);
					
		if ($count=count($this->Select)){
			for ($i=0; $i<$count; $i++){
				if (!$this->Select[$i]) continue;
				
				if (strpos($this->Select[$i],' ')===false){
					str::Add($ret, $TablePrefix.'.`'.$this->Select[$i].'`', ', ');
				}
				else {					
					list($field, $as)=explode(' AS ', $this->Select[$i]);
					$field=preg_replace('/\{\$(\w+?)\}/', $TablePrefix.'.`\1`', $field);
					str::Add($ret,$field.' AS '.$as, ', ');
				}					
			}
		}

		if ($count=count($this->Join)){
			foreach (array_keys($this->Join) as $i){
				$Join=&$this->Join[$i][0];
				/*@var $Join dbSelect*/
				str::Add($ret, $Join->getSelect($this->Join[$i][3]), ',');
			}
		}		
		return $ret;
	}
	/**
	 * Запрос на удаление:
	 */
	function getDeleteSql(){
		return "DELETE ".$this->getSqlTable()." FROM ".$this->getSqlTables()." WHERE ".$this->getSqlWhere();		
	}
	/**
	 * Запрос на вставку:
	 * 
	 * $Insert	массив ключ-значение для вставки
	 * $Method	INSERT|REPLACE|INSERT INGORE
	 * $Secure	boolean	проверка полей
	 * 
	 * @return string запрос
	 */
	function getInsertSql($Data, $Method='INSERT', $Secure=false){
		$Insert='';
		$Values='';
		if ($this->Where){
			foreach ($this->Where as $Where) {
				if ($Where[1]==eq&&!isset($Data[$Where[0]])){							
					str::Add($Insert,'`'.$Where[0].'`',',');
					str::Add($Values, "'".self::_UpdateEscape($Where[2])."'",',');
				}
			}
		}
		
		if (is_array($Data)){	
			foreach ($Data as $key=>$value) {
				
				str::Add($Insert,'`'.$key.'`',',');				
				str::Add($Values, "'".self::_UpdateEscape($value)."'",',');
			}
			
			$ret=$Method." INTO ".$this->getSqlTable()." (".$Insert.") VALUES (".$Values.")";
			
			return $ret;			
		}		
		elseif(is_object($Data)){
			if ($count=count($this->Select)){
				$Insert='';
				for ($i=0;$i<$count;$i++){					
					str::Add($Insert,'`'.$this->Select[$i].'`',',');
				}
				$Insert='('.$Insert.')';				
			}
			
			else $Insert='';
			$Fields='';		
			/*@var $Data dbselect*/
			$ret= $Method." INTO ".$this->getSqlTable()." ".$Insert." ".$Data->getSql(true);
			
			return $ret;
		}
		else throw new Exception('Wrong insert data format');

						
	}
	/**
	 * Запрос на обновление:
	 * @var array $Data		можно использовать переменные запроса: $sel->getUpdateSql(array('total'=>'{$total}+1'));
	 */
	function getUpdateSql($Data){		
		return 'UPDATE '.$this->getSqlTables()." SET ".$this->getSqlSet($Data)." WHERE ".$this->getSqlWhere();				
	}
	
	protected function getSqlSet($Data, $Increment=false){
		$Set='';
		foreach($Data as $key=>$value){
			
			if (is_string($value)&&preg_match('/\{\$(\w+?)\}/', $value)){
				$value=preg_replace('/\{\$(\w+?)\}/', $this->getFieldPrefix().'.`\\1`', $value);
			}
			else {
				$value="'".self::_UpdateEscape($value)."'";
			}
			$key=self::Escape($key);
			str::Add($Set, $this->getSqlTable().".`".$key."`=".(($Increment)?'`'.$key.'`+':'').$value,',');
			
		}
		return $Set;
	}
	/**
	 * Строка для вставки (массивы- автоматически сериализуются)
	 * @var mixed $value	значение
	 * @return string строка для вставки в БД
	 */
	static function _UpdateEscape($value){
		
		return (!is_null($value))?self::Escape((is_string($value)||is_numeric($value))?$value:serialize($value)):'NULL';
	}
	
	 
	/**
	 * Увеличение или добавление:
	 */
	function getIncrementSql($Field, $Increment=1, $Default=false){
				
		$Insert='';
		$Values='';
				
		$count=count($this->Where);
		for ($i=0;$i<$count;$i++){
			list($key,$method,$value)=$this->Where[$i];
			if ($method!=eq) continue;
			str::Add($Insert,'`'.$key.'`',',');
			str::Add($Values, "'".self::_UpdateEscape($value)."'",',');			
		}
		str::Add($Insert, '`'.$Field.'`',',');
		str::Add($Values,"'".self::Escape(($Default)?$Default:$Increment)."'",',');
		
		return "INSERT INTO ".$this->getSqlTable()." (".$Insert.") VALUES (".$Values.") ON DUPLICATE KEY UPDATE `".$Field."`=`".$Field."`+".$Increment;		
	}
	/**
	 * Получить запрос на обновление:
	 */
	function getInsertOrUpdateSql($Data, $Increment=false){
		$set=$this->getSqlSet($Data, $Increment);		
		if ($count=count($this->Where)){
			for ($i=0;$i<$count;$i++){
				$obj=&$this->Where[$i];
				if ($obj[1]!=eq) continue;
				$Data[$obj[0]]=$obj[2];
			}
		}
		return $this->getInsertSql($Data).' ON DUPLICATE KEY UPDATE '.$set;
	}
	/**
	 * Привести к строке
	 */
	function __tostring(){
		return $this->getSql();
	}
}

?>