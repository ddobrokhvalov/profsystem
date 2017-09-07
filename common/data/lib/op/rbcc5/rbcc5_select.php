<?php
class rbcc5_select extends dbselect{
	/**
	 * Получить объект выборки с примененными параметрами окружения и сортировкой по-умолчанию:
	 * 
	 * @param string $table		таблица по которой идет выборки
	 * @param int 	 $flag		параметры применения окружения
	 * @param mixed  $orderBy	сортировка: если строка-- название поле, если true- сортировка по умолчанию
	 * @param string $orderDir	направление сортировки
	 * @return rbcc5_select
	 */
	static function getInstance($table, $flag=rbcc5_select::skipBlocks, $orderBy=true, $orderDir=null){
		$ret= new rbcc5_select($table);
		$ret->applyEnv(rbcc5_object::$env, $flag);
		if ($orderBy===true){
			$ret->OrderBy($ret->getOrderField(), $ret->getOrderDir());
		}
		elseif ($orderBy){
			$ret->OrderBy($orderBy, $orderDir);
		}
		return $ret;		
	} 
	/**
	 * Прикрепить таблицу по полю:
	 * @param $field
	 * @return int указатель на связь
	 */
	function autojoin($field, $joinType='LEFT'){
		
		$fieldData= $this->getFieldData($field, $mode=rbcc5_select::listMode);
		if ($fieldData['type']!='select2'){			
			throw new exception('Invalid join');
		}
		$sel= rbcc5_select::getInstance($fieldData['fk_table'], rbcc5_select::skipBlocks, false);
		if ($mode){
			$sel->setMode(rbcc5_select::listMode);
		}
		return $this->Join($sel, $field, $sel->primary_key, preg_replace('/_ID$/','',$field),$joinType);		
	}
	
	
	const testVersion=1;
	const workVersion=0;
	/**
	 * Резолюции:
	 * @return unknown_type
	 */
	static function WF_RESULUTION(){
		$a= func_get_args();
		return dbselect::factory(array('WF_RESOLUTION_ID','TITLE','FIRST_STATE_ID','LAST_STATE_ID','MAIN_VERSION','LANG_ID','QUORUM'),'WF_RESOLUTION',null,$a);
	}
	/**
	 * Состояние:
	 * @return unknown_type
	 */
	static function WF_STATE(){
		$a= func_get_args();
		return dbselect::factory(array('WF_STATE_ID','TITLE','VERSIONS'),'WF_STATE',null,$a);
	}
	/**
	 * Создать:
	 * @param $table
	 * @return 
	 */
	function __construct($table){
		
		if (!rbcc5_metadata::query(array($table))) throw new Exception('Invalid table: "'.$table.'"');
				
		parent::__construct($fields,$table);
		
			
		
		$this->orderField=$this->table.'_ID';
		$this->orderDir='DESC';
		
		foreach ($this->getProperty('fields') as $k=>$field){
			if (!$field['virtual']) $this->Select[]=$k;
			/**
			 * Вычисляем поле для сортировки:
			 */
			if (isset($field['sort'])){
				//Поле отмечено как поле для сортировки:
				$this->orderField=$k;
				$this->orderDir=$field['sort'];				
			}
			elseif ($field['type']=='order'){
				//Поле имеет тип "параметр сортировки":
				$this->orderField=$k;
				$this->orderDir='ASC';				
			}
		}
		
		if ($this->hasDecorator(self::version)){
			$this->Select[]='VERSION';
		}
		if ($this->hasDecorator(self::lang)){
			$this->Select[]='LANG_ID';
		}
		$this->primary_key=$table.'_ID';			
	}
	
	const selectMode=1;//Только основное поле
	const listMode=2;//Вывод в списке
	const fullMode=4;//Все
	function setMode($mode){
		if ($mode==self::selectMode){
			$this->Select=$this->getPrimaryKeyFields();
			foreach ($this->getProperty('fields') as $k=>$field){
				if ($field['is_main']) $this->Select[]=$k;
			}
			
		}
		elseif ($mode=self::listMode){
			$this->Select=$this->getPrimaryKeyFields();
			foreach ($this->getProperty('fields') as $k=>$v){
				if ($v['show']) $this->Select[]=$k;
			}
		}
		elseif ($mode=self::fullMode){
			$this->Select=array_keys(rbcc5_metadata::query(array($this->table,'fields')));
		}
		elseif ($select=rbcc5_metadata::query(array($this->table, 'listMode', $mode),null)){
			$this->Select=$select;
		}
	}
	/**
	 * Поля для ключа:
	 * @return array
	 */
	function getPrimaryKeyFields(){
		$ret=array($this->table.'_ID');
		if ($this->hasDecorator('lang')) $ret[]='LANG_ID';
		if ($this->hasDecorator('version')) $ret[]='VERSION';
		if ($parentField=$this->getProperty('parent_field')) $ret[]=$parentField;
		if ($this->getOrderField()!=$ret[0]) $ret[]=$this->getOrderField();
		return $ret;
	}
	
	/**
	 * Заголовок:
	 * @return unknown_type
	 */
	function getTitleField(){
		foreach ($this->getProperty('fields') as $k=>$v){
			if ($v['is_main']) return $k;
		}
	}
	/**
	 * (non-PHPdoc)
	 * @see lib/dbselect#Insert($Data, $Method)
	 */
	function Insert($data, $method='INSERT'){
		if ($method!='REPLACE'){
			unset($data[$this->table.'_ID']);
		}
		$insert=array();
		foreach ($data as $k=>$v){
			if (is_object($v)){
				$insert[$k]=$v->getID();
			}
			else{
				$insert[$k]=$v;
			}
		}		
		return parent::Insert($insert, $method);
	}
	
	const version='version';
	const lang='lang';
	const block='block';
	const context='context';
	/**
	 * Есть ли декоратор:
	 * @param $decorator
	 * @return boolean
	 */
	function hasDecorator($decorator){		
		return rbcc5_metadata::hasDecorator($this->table, $decorator); 
	}
	
	const skipBlocks=1;
	const skipContext=2;
	/**
	 * Пременить окружение:
	 * @param $env
	 * @return boolean
	 */
	function applyEnv($env=null, $flag=self::skipBlocks){
		if ($env==null) $env=rbcc5_object::$env;
		//Язык:
		if ($this->hasDecorator(self::version)){
			$this->Where('VERSION',eq,$env['version']);
		}
		//Версия:
		if ($this->hasDecorator(self::lang)){
			$this->Where('LANG_ID',eq,$env['lang_id']);
		}
		//Контекст:
		if ((!$flag&self::skipContext) && $this->hasDecorator(self::context)){
			if (application::getContextID()){
				$contextContent=new dbselect(array('CONTEXT_ID','CONTENT_ID','CLASS'),'APPLICATION_CONTEXT_CONTENT');
				$contextContent->Where('CLASS',eq,$this->table);
				$contextContent->Where('CONTEXT_ID',eq,array(0, application::getContextID()));
				$this->Join($contextContent, $this->primary_key, 'CONTENT_ID', $contextContent->table, 'INNER');						
			}			
		}
		//Блок:
		if ((!$flag&self::skipBlocks) && $this->hasDecorator(self::block)){
			$this->Join(rbcc5_content_map::select('INF_BLOCK_ID',$env['block_id']),$this->primary_key,'CONTENT_ID','CONTENT_MAP','INNER');
		}
	}
	/**
	 * Получить данные по полю:
	 * @return boolean
	 */
	function getFieldData($field){
		return rbcc5_metadata::getFieldData($this->table,$field);
	}	
	/**
	 * Получить свойство:
	 * @param $property
	 * @param $default
	 * @return unknown_type
	 */
	function getProperty($property, $default=null){
		return rbcc5_metadata::query(array($this->table,$property),$default);		
	}
	/**
	 * Поле для сортировки
	 * @var string
	 */
	var $orderField=null;
	/**
	 * Поле для сортировки
	 * @return string
	 */
	function getOrderField(){
		return $this->orderField;	
	}
	
	protected $orderDir;
	/**
	 * Направление для сортироки:
	 * @return string
	 */
	function getOrderDir(){
		return $this->orderDir;
	}
	/**
	 * Прикрепить:
	 * @param dbselect $sel
	 */
	function naturalJoin($sel, $type='LEFT'){
		$this->Join($sel,$sel->primary_key,$sel->primary_key,$sel->table,$type);
	}
}
?>