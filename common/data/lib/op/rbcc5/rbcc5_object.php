<?php
/**
 * Базовый объект:
 * @author atukmanov
 * 
 * {$PAGE.TEMPLATE.TEMPLATE_TYPE}
 *
 */
class rbcc5_object extends DataStore {
	
	static $env=array('vervion'=>1,'lang_id'=>1);
	
	protected static $_cache=array();
	/**
	 * Таблица:
	 * @var string
	 */
	var $table=null;
	/**
	 * Загрузить объект:
	 * @param $id
	 * @param $table
	 * @param $env
	 * @param $envFlags
	 * @return rbcc5_object
	 */
	static function loadByID($id, $table,$env=null,$envFlags=rbcc5_select::skipBlocks){
		
		if (!$id) return null;
		if (isset(self::$_cache[$table.'_'.$id])) return self::$_cache[$table.'_'.$id];		
		$sel= new rbcc5_select($table);
		$sel->applyEnv(($env)?$env:self::$env, $envFlags);
		$sel->Where($sel->primary_key,eq,$id);
		
		self::$_cache[$table.'_'.$id]=self::fetchObject($sel);
		return self::$_cache[$table.'_'.$id];		
	}
	/**
	 * Заглушка:
	 * @return rbcc5_object
	 */
	static function fakeObject($id, $table){
		return rbcc5_object::instance(array(
			$table.'_ID'=>$id,
			'LANG_ID'=>self::$env['lang_id'],
			'VERSION'=>self::$env['version'],
		),$table);
	}
	/**
	 * Замороженные данные:
	 * @var array
	 */
	protected $_frozen=array();
	/**
	 * Обновить данные:
	 * @return void
	 */
	function Update($data){		
		//Сохраняем в объекте:
		foreach ($data as $k=>$v){
			$this->_frozen[$k]=$this->getInfo($k);//Замораживаем
			$this->Info[$k]=$v;//Обновляем
		}
		//Сохраняем в БД:
		$sel= new rbcc5_select($this->table);
		$sel->applyEnv(array(
			'version'=>$this->VERSION,
			'lang_id'=>$this->LANG_ID,
		));
		$sel->Where($sel->primary_key, eq, $this->getID());
		
		return $sel->Update($data);
	}
	/**
	 * Воссоздать из массива:
	 * @param array  $obj
	 * @param string $table
	 * @return rbcc5_object
	 */
	static function instance($info, $table){
		if (!$info) return null;
		$className=(class_exists('rbcc5_'.$table))?'rbcc5_'.$table:'rbcc5_object';
		$ret=new $className($info);
		/*@var $ret rbcc5_object*/
		$ret->table=$table;
		//Подготавливаем поля:
		foreach (rbcc5_metadata::query(array($table,'fields')) as $k=>$field){
			if (!isset($info[$k])) continue;
			switch ($field['type']){
				case 'select1':
					if (isset($ret->Info[$k]) && ($value=$ret->Info[$k])){
						foreach ($field['value_list'] as $option){
							if ($option['value']==$value) $ret->Info[$k.'_TITLE']=$option['title'];
						}
					}
				break;
				case 'select2':
					$objectKey=preg_replace('@_ID$@','',$k);
					if (isset($info[$objectKey])){
						if (is_array($info[$objectKey])){
							$ret->_objects[$objectKey]=rbcc5_object::instance($info[$objectKey], $field['fk_table']);
						}
					}
				break;
				case 'datetime':					
					$ret->Info[$k.'_OBJECT']=str::getDateArray($ret->Info[$k]);
				break;
			}
		}
		
		return $ret;
	}
	/**
	 * Разбить список:
	 * @param dbselect $sel
	 * @return array
	 */
	static function fetchList($sel){
		$ret=array();
		$i=0;
		foreach ($sel as $obj){
			$key=(isset($obj[$sel->table.'_ID']))?$obj[$sel->table.'_ID']:$i; 
			$ret[$key]=self::instance($obj, $sel->table);
			$i++;
		}
		return $ret;
	}
	
	static function fetchObject($sel){
		if ($obj=$sel->selectObject()){
			return self::instance($obj, $sel->table);
		}
	}
	
	protected $_objects=array();
	/**
	 * Получить объект:
	 * @return rbcc5_object
	 */
	function getObject($key){
		//Смотрим в кэше:
		if (isset($this->_objects[$key])) return $this->_objects[$key];
		
		if (!isset($this->Info[$key.'_ID'])||!$id=$this->Info[$key.'_ID']){
			//Нет ID:			
			return null;
		}
		if (!$field=rbcc5_metadata::getFieldData($this->table,$key.'_ID')){
			//Нет поля:			
			return null;
		}
			
		if ($field['type']!='select2'){
			//Неверный тип поля:
			return null;
		}

		return rbcc5_object::loadByID($id, $field['fk_table'], null, rbcc5_select::skipBlocks);
	}
		
	/**
	 * Получить медиаданные:
	 * @return medialib
	 */
	function getMedialib(){		
		$sel=new rbcc5_select($this->table);
		$key=array('content_id'=>$this->getID(),'type'=>$sel->table);		
		if ($sel->hasDecorator('version')){
			$key['version']=$this->VERSION;
		}
		if ($sel->hasDecorator('lang')){
			$key['lang']=$this->LANG_ID;
		}
		$ret=medialib::instance($key);
		return ($ret->getID())?$ret:null;
	}
	
	protected $m2m=array();
	/**
	 * m2m
	 * @param $key
	 * @return array
	 */
	function getM2M($key){
		if (isset($this->Info[$key])) return $this->Info[$key];
		if (isset(metadata::$objects[$this->table]['m2m'][$key])){
			$m2m=new DataStore(metadata::$objects[$this->table]['m2m'][$key]);
		}
		else return null;
		$m2m_select= new rbcc5_select($m2m->getSetting('m2m_table',$key));
		$m2m_select->Where($m2m->getSetting('primary_m2m_field',$this->table.'_ID'),eq,$this->getID());
		$sel= new rbcc5_select($m2m->getInfo('secondary_table'));
		$sel->OrderBy($sel->getOrderField(),'ASC');
		$sel->applyEnv(rbcc5_object::$env, rbcc5_select::skipBlocks);
		$sel->Join($m2m_select, $this->getSetting('secondary_m2m_field',$sel->primary_key),$sel->primary_key, 'm2m','INNER');		
		return $this->Info[$key]=rbcc5_object::fetchList($sel);
	}
	
	protected $links=array();
	function getLinks($key){
		if (isset($this->links[$key])) return $this->links[$key];
	}
	/**
	 * Получить родителя:
	 * @return rbcc5_object
	 */
	function getRoot(){
		$sel= new rbcc5_select($this->table);
		if ($rootKey=$sel->getProperty('parent_field')){
			if (!isset($this->Info[$rootKey])) return null;
			if (!$this->Info[$rootKey]) return null;
			$sel->Where($sel->primary_key,eq,$this->Info[$rootKey]);
			return self::fetchObject($sel);
		}
	}
	/**
	 * (non-PHPdoc)
	 * @see lib/DataStore#getID()
	 */
	function getID(){
		return $this->_getInfo(array($this->table.'_ID'));
	}
	/**
	 * Дочерние узлы
	 * 
	 * @return rbcc5_select
	 */
	function getChildNodes(){
		$sel= new rbcc5_select($this->table);
		if ($rootKey=$sel->getProperty('parent_field')){
			$sel->Where($rootKey, eq, $this->getID());
			if ($sel->hasDecorator('version')) $sel->Where('VERSION',eq,$this->VERSION);
			return $sel;
		}
		else {
			return array();
		}
	}	
	/**
	 * Сохранить:
	 */
	function save(){
		
		include_once(params::$params['adm_data_server']['value'].'class/core/object/object.php');
		
		if (!$this->getID()){				
			$table= object::factory($this->table);				
			$this->Info[$this->table.'_ID']=$table->exec_add($this->Info,'');			
		}
		else {
			$table= object::factory($this->table);			
			$table->exec_change($this->Info,'',$this->getPK());	
		}
		return $this->getID();
	}
	
	function getPK(){
		$ret=array();
		$ret[$this->table.'_ID']=$this->getID();
		if (rbcc5_metadata::hasDecorator($this->table,'version')) $ret['VERSION']=$this->VERSION;
		if (rbcc5_metadata::hasDecorator($this->table,'lang')) $ret['LANG_ID']=$this->LANG_ID;
		return $ret;
	}
	/**
	 * Хэш код:
	 * @return string
	 */
	function getHash(){
		$pk=$this->getPK();		
		return md5(serialize($pk));
	}
	
	function getTable(){
		return $this->table;
	}
	
	function getItemTitle(){
		$ret='';
		foreach (rbcc5_metadata::query(array($this->table,'fields')) as $fieldName=>$field){
			if ($field['is_main']){
				
				str::Add($ret, $this->getString($fieldName),' ');
			}
		}
		
		return $ret;
	}
	
	static $dateFormat='d.m.Y H:i:s';
	/**
	 * Получить строку как строку:
	 * @return unknown_type
	 */
	function getString($field){
		
		$fieldData=rbcc5_metadata::getFieldData($this->table, $field);
		
		switch ($fieldData['type']){
			case 'select2':
				if ($id=$this->getInfo($field)){
					
					if ($obj=self::loadByID($id, $fieldData['fk_table'])){
						return $obj->getItemTitle();
					}
				}
			break;
			case 'select1':
				$value=$this->getInfo($field);
				foreach ($fieldData['value_list'] as $option){
					if ($option['value']==$value) return $option['title'];
				}
			break;
			case 'datetime':
				if ($date=$this->getInfo($field)){
					return date(self::$dateFormat, strtotime($date));
				}
			
			default:
				return $this->getInfo($field);
			break;
		}
	}
	
	function getMainFieldValue(){
		foreach (rbcc5_metadata::query(array($this->table,'fields')) as $k=>$v){
			if (isset($v['is_main'])&&$v['is_main']) return $this->Info[$k];
		}
		
	}
	/**
	 * (non-PHPdoc)
	 * @see lib/DataStore#_getInfo()
	 */
	function _getInfo($path){
		switch (str::lower($path[0])){
			case 'id':
				return $this->Info[$this->table.'_ID'];
			break;
			case 'uid':
				return $this->table.'.'.$this->Info[$this->table.'_ID'];
			break;			
			case 'itemtitle':
				return $this->getItemTitle();
			break;
			case 'root':
				return $this->getRoot();
			break;
			case 'pk':
			case 'primary_key':
				return $this->getPK();
			break;
			case 'hash':
				return $this->getHash();
			break;
			case 'table':
				return $this->table;	
			break;	
			case 'url':
				return $this->getURL();
			break;
			case 'tags':
				if ($this->_tags) return $this->_tags;
				return $this->_tags=rbcc5_tag::getObjectTags($this);
			break;
		}
				
		//getObject gate:
		
		if (isset($this->Info[$path[0].'_ID'])){

			return $this->getObject($path[0]);
		}
		//getM2M gate:
		$sel= new rbcc5_select($this->table);
		
		if (isset(metadata::$objects[$this->table]['m2m'][$path[0]])){
			return $this->getM2M($path[0]);
		}
		if ($linked=$this->getLinked($path[0])){
			return $linked;
		}
		return parent::_getInfo($path);
	}
	
	protected $_tags=null;
	
	function getLinked($link){
		if ($sel=$this->selectLinked($link)){
			return rbcc5_object::fetchList($sel);
		}	
		else {
			return null;
		}
	}
	
	function selectLinked($link){
		if (rbcc5_metadata::query(array($this->table,'links',$link))){
			$sel=rbcc5::select($link, true);
			$sel->Where($this->table.'_ID',eq,$this->getID());
			return $sel;
		}
	}
	/**
	 * Есть ли декоратор:
	 * @param $decorator
	 * @return boolean
	 */
	function hasDecorator($decorator){
		return rbcc5_metadata::hasDecorator($this->table, $decorator);
	}
	
	protected $_URL=null;
	/**
	 * Получить URL:
	 * @return string
	 */
	function getURL(){
		
		if (rbcc5_metadata::hasDecorator($this->table, 'block')){
			return $this->getMainBlock()->buildURL(array('id'=>$this->getID()));
		}
		else {
			return null;
		}
	}
		
	protected $_mainBlock=null;
	/**
	 * Получить основной инф. блок
	 * @return rbcc5_inf_block
	 */
	function getMainBlock(){
		
		if ($this->_mainBlock){
			return $this->_mainBlock;
		}
		if (isset($this->Info['CONTENT_MAP']) && $this->Info['CONTENT_MAP']['IS_MAIN']){
			//На всякий случай смотрим нет ли уже данных:
			return rbcc5_object::loadByID($this->Info['CONTENT_MAP']['INF_BLOCK_ID'],'INF_BLOCK');
		}
		
		//Получаем инф блок (блок указанный как главный CONTENT_MAP.IS_MAIN = 1):
		$te= rbcc5_te_object::loadBySystemName($this->table);
		$contentMap= new rbcc5_select('CONTENT_MAP');
		$contentMap->Where('CONTENT_ID',eq,$this->getID());
		$contentMap->Where('IS_MAIN',eq,1);
		
		$ib=$te->selectInfBlocks();
		$ib->Join($contentMap,$ib->primary_key, $ib->primary_key,'CONTENT_MAP','LEFT');
		$this->_mainBlock=rbcc5_object::fetchObject($ib);
		
		return $this->_mainBlock;
	}
	
	
}
?>