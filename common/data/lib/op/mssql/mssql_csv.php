<?php
/**
 * Утилита для чтения файлов mssql_csv
 * @author 1
 *
 */
class mssql_csv {
	/**
	 * 
	 * @var int
	 */
	protected $fp=null;
	/**
	 * 
	 * @param $filename
	 * @return mssql_csv
	 */
	function __construct($filename){
		if (!$this->fp=fopen($filename,'r')){
			throw new Exception('Invalid file name');	
		}		
	}
	/**
	 * Разделитель:
	 */
	var $delimiter=',';
	/**
	 * Ключи:
	 * @var array
	 */
	protected $keys=null;
	/**
	 * Получить схему:
	 * @return array
	 */
	function readKeys(){
		if ($this->keys!==null) return;
		$this->keys=array();
		$line=fgetcsv($this->fp, 10000, $this->delimiter);
		$count=count($line);
		for ($i=0; $i<$count; $i++){
			$this->keys[strtoupper($line[$i])]=$i;
		}
	}
	/**
	 * Лог:
	 * @var array
	 */
	static $log=array();
	/**
	 * Записать id объекта <-> guid
	 * @param $id
	 * @param $table
	 * @param $guid
	 * @return unknown_type
	 */
	static function log($id, $table, $guid){
		return self::$log[$table][$guid]=$id;
	}
	/**
	 * Получить id объекта
	 * @param $guid
	 * @param $table
	 * @return unknown_type
	 */
	static function elementID($guid,$table){
		if (!isset(self::$log[$table])){
			$sel= new rbcc5_select($table);
			$sel->Select($sel->primary_key,'GUID');
			self::$log[$table]=$sel->ToArray('GUID',true,$sel->primary_key);
		}
		return (int)self::$log[$table][$guid];
	}
	/**
	 * Ключ:
	 * @param $key
	 * @return int
	 */
	protected function k($key){
		if (is_numeric($key)) return $key;
		return $this->keys[strtoupper($key)];
	}
	/**
	 * Пример записи:
	 * @return array
	 */
	function example(){
		$this->readKeys();
		$line=fgetcsv($this->fp, 10000, $this->delimiter);
		foreach ($this->keys as $k=>$i){
			$ret[$k]=iconv('windows-1251','utf-8',str::cut($line[$i],50));
		}
		return $ret;
	}
	/**
	 * Поля
	 * @var array
	 */
	var $fields=array();
	/**
	 * Таблица:
	 * @var string
	 */
	var $table=null;
	
	protected $titleField=null;
	/**
	 * Значение поле для сортироки:
	 * @var int
	 */
	var $order=0;
	
	protected $baseObject=array();
	/**
	 * Выставить таблицу:
	 * @return string
	 */
	function setTable($table){
		$this->table=$table;
		$sel= new rbcc5_select($table);
		$this->fields=$sel->getProperty('fields');
		foreach ($this->fields as $k=>$field){
			if ($field['type']=='order'){
				$sel->OrderBy($k,'DESC');
				$sel->Select($k);
				$this->order=(int)$sel->selectString();
			}
			if ($field['is_main']){
				$this->titleField=$k;
			}
		}
		if ($sel->hasDecorator('lang')) $this->baseObject['LANG_ID']=1;
		if ($sel->hasDecorator('version')) $this->baseObject['VERSION']=1;
		if ($sel->hasDecorator('workflow')) $this->baseObject['WF_STATE_ID']=1;	
	}
	/**
	 * Прочитать строку:
	 * @return boolean
	 */
	function read(){
		return fgetcsv($this->fp, 10000, $this->delimiter);
	}
	
	function import($table, $blockID=0){
		$this->setTable($table);
		$this->readKeys();
		$sel= new rbcc5_select($this->table);
		if ($blockID&&$sel->hasDecorator('block')){
			$contentMap= rbcc5_content_map::select();
		}
		else {
			$contentMap=null;
		}
		$total=0;
		while ($line=$this->read()){			
			if (!$res=$this->fetch($line)) continue;
			$id=$sel->Insert($res);
			if ($contentMap){				
				$contentMap->Insert(array('CONTENT_ID'=>$id,'INF_BLOCK_ID'=>$blockID,'IS_MAIN'=>1));
			}
			self::log($id, $this->table,$res['GUID']);
			console::write($id.'->'.$res[$this->titleField].' ('.$res['GUID'].')');
			$total++;
		}
		
		console::write('__________________',$total.' records done');
	}
	/**
	 * Прочитать линию:
	 * @param $line
	 * @return int
	 */
	function fetch($line){
		$ret=$this->baseObject;
		$valid=true;	
		
		foreach ($this->fields as $k=>$field){
			//Автоинкрементальное поле сортировки:
			if ($field['type']=='order'){
				$this->order+=10;
				$ret[$k]=$this->order;
			}
			//Связь через дерево:
			if (isset($field['csv_tree'])){
				$sel=new dbselect(array('treeID','leafID'),'fclm_tree');
				$sel->Where('leafID',eq,$ret['GUID']);
				$select2=new rbcc5_select($field['fk_table']);
				$select2->Select($select2->primary_key);
				$select2->Join($sel,'GUID','treeID','fk','INNER');
				$ret[$k]=$select2->selectString();
				if ($field['errors']&1){
					if (!$ret[$k]) return null;
				}
			}
			//Проверяем на csv:
			if (!isset($field['csv'])||$field['csv']===false) continue;
			//Получаем индекс:
			if ($field['csv']===true){
				$index=$this->keys[strtoupper($k)];
			}
			elseif(is_numeric($field['csv'])){
				$index=$field['csv'];			
			}
			else{				
				$index=$this->keys[strtoupper($field['csv'])];
			}
			
			switch ($field['type']){
				case 'datetime':
					$ret[$k]=date('YmdHis',$line[$index]);					
				break;
				case 'checkbox':
					console::write($line[$index]);
					$ret[$k]=($line[$index]=='True')?1:0;
				break;
				case 'select2':
					$ret[$k]=self::elementID($line[$index],$field['fk_table']);					
				break;
				case 'select1':
					//Собираем:
					foreach ($field['value_list'] as $option){
						if ($line[$this->k($option['value'])]=='True'){
							$ret[$k]=$option['value'];
							break;
						}
					}
				break;				
				default:
					$ret[$k]=iconv('windows-1251','utf-8',$line[$index]);
				break;
			}
			if ($field['errors']&1){
				if (!$ret[$k]) return null;
			}
		}
		return $ret;
	}
	
	function close(){
		fclose($this->fp);
	}
}
?>