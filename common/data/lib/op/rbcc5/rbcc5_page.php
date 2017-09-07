<?php
class rbcc5_page extends rbcc5_object {
	
	var $table='PAGE';
	/**
	 * Создать страницу:
	 * @param array $info
	 * @return rbcc5_page
	 */
	function create($info){

		$info['PAGE_ID']=0;//Обнуляем старый pageID
		$info['PARENT_ID']=$this->PAGE_ID;
		$info['SITE_ID']=$this->SITE_ID;
		$info['PAGE_ORDER']=$this->getNextOrderBy();
	
		if (!isset($info['TEMPLATE_ID'])) $info['TEMPLATE_ID']=$this->TEMPLATE_ID;
		if (!isset($info['PAGE_TYPE'])) $info['PAGE_TYPE']='page';
		if (!isset($info['TIMESTAMP'])) $info['TIMESTAMP']=time();	
			
		$page=new rbcc5_page($info);
		
		$page->root=$this;		
		$page->save();
		
		return $page;
	}
	/**
	 * Сохранить страницу
	 * @return int
	 */
	function save(){
		if (!$this->PAGE_ID){
			if (!$root=$this->getRoot()) throw new Exception('Root does not exists');
			if (!$this->PAGE_ORDER) $this->Info['PAGE_ORDER']=$root->getNextOrderBy();
			parent::save();
		}
		else {
			parent::save();
		}
	}	
	/**
	 * Получить выборку
	 * @return dbselect
	 */
	static function select(){
		$a= func_get_args();
		$ret= new rbcc5_select('PAGE');
		return dbselect::where_command($sel, $a);
	}
	/**
	 * Загрузить по id
	 * @param $id
	 * @return rbcc5_page
	 */
	static function loadById($id){
		return parent::loadByID($id,'PAGE');
	}
	/**
	 * Загрузить страницу по URL
	 * @param $url
	 * @return rbcc5_page
	 */
	static function loadByURL($url){
		$urlInfo=parse_url($url);
		if ($urlInfo['host']){
			if (!$site=rbcc5_site::loadByHost($urlInfo['host'])){
				return null;
			}			
		}
		else {
			$site=rbcc5_site::loadDefaultSite();
		}
		$siteID=$site->getID();
		$rootID=0;
		$i=0;
		foreach (application_url::explode($urlInfo['path']) as $dir){			
			if (false!==strpos($dir,'.')) break;
			if ($useContext&&$i==$useContext){
				$i++;
				continue;
			}
			$sel= new rbcc5_select('PAGE');
			$sel->Where('PARENT_ID',eq,$rootID);
			$sel->Where('SITE_ID',eq,$siteID);
			$sel->Where('DIR_NAME',eq,$dir);
			$sel->Where('VERSION',eq,$site->getVersion());
			if ($page=$sel->selectObject()){
				$rootID=$page['PAGE_ID'];
			}
			else {
				return null;
			}
			$i++;
		}
		return new rbcc5_page($page);
	}
	/**
	 * Получить язык страницы
	 * @return rbcc5_object
	 */
	function getLang(){
		if ($this->LANG_ID) return $this->getObject('LANG');
		$root=$this;
		while ($root->PARENT_ID){
			$root=$root->getRoot();
		}
		$sel= new rbcc5_select('LANG');
		$sel->Where('ROOT_DIR',eq,$root->DIR_NAME);
		return rbcc5_object::fetchObject($sel);
	}
	
	protected  $orderBy=null;
	const orderByIncrement=10;
	/**
	 * Следующий order_by
	 * @return int
	 */
	function getNextOrderBy(){
		if ($this->orderBy!==null){
			$this->orderBy+=self::orderByIncrement;
			return $this->orderBy;
		}
		else {
			$sel= new rbcc5_select('PAGE');
			$sel->Where('PARENT_ID',eq,$this->PAGE_ID);
			$sel->OrderBy('PAGE_ORDER','DESC');
			$sel->Select('PAGE_ORDER');
			$this->orderBy=(int)$sel->selectString();
			return $this->getNextOrderBy();
		}
	}
	/**
	 * Получить версию
	 * @return int
	 */
	function getVersion(){
		return $this->VERSION;
	}
	/**
	 * Получить блоки на сайте
	 * @return array
	 */
	function getBlocks(){
		/**
		 * Получаем список блоков:
		 */
		$sel= new rbcc5_select('PAGE_AREA');
		$sel->Where('PAGE_ID',eq,$this->PAGE_ID);
		$sel->Where('VERSION',eq,$this->getVersion());
		$ret=array();
		foreach ($sel as $obj){
			$ret[$obj['TEMPLATE_AREA_ID']]=$obj;
		}
		/**
		 * Получаем параметры блока:
		 */
		$sel= new rbcc5_select('PAGE_AREA_PARAM');
		$sel->Where('PAGE_ID',eq,$this->PAGE_ID);
		$sel->Where('VERSION',eq,$this->getVersion());
		foreach ($sel as $obj){
			$ret[$obj['TEMPLATE_AREA_ID']]['PARAMS'][$obj['MODULE_PARAM_ID']]=$obj['VALUE'];
		}		
		return $ret;
	}
	/**
	 * Получить блок:
	 * @param $areaID
	 * @return datastore
	 */
	function getPageArea($areaID){
		$sel= new rbcc5_select('PAGE_AREA');
		$sel->Where('TEMPLATE_AREA_ID',eq,$areaID);
		$sel->Where('PAGE_ID',eq,$this->getID());
		$sel->Where('VERSION',eq,$this->getInfo('VERSION'));
		return rbcc5_object::fetchObject($sel);
	}
	/**
	 * Получить шаблон:
	 * @return 
	 */
	function getTemplate(){
		return rbcc5_template::loadById($this->TEMPLATE_ID);
	}
	/**
	 * Выставить блок:
	 * @param int   $areaID 	область
	 * @param int   $blockID	блок
	 * @param array $params		array
	 * 
	 * @return 
	 */
	function setBlock($areaID, $blockID, $params){
		$sel= new rbcc5_select('PAGE_AREA');
		$insert=array(
			'PAGE_ID'=>$this->PAGE_ID,
			'VERSION'=>$this->getVersion(),
			'TEMPLATE_AREA_ID'=>$areaID,
			'INF_BLOCK_ID'=>$blockID
		);
		
		$sel->Insert($insert, dbselect::REPLACE);
		
		$sel= new rbcc5_select('PAGE_AREA_PARAM');
		$sel->Where('PAGE_ID',eq,$this->PAGE_ID);
		$sel->Where('VERSION',eq,$this->getVersion());
		$sel->Where('TEMPLATE_AREA_ID',eq,$areaID);
		//Очистим:
		$sel->Delete();
		//Вставим:
		if ($params){
			foreach ($params as $paramID=>$value){
				$sel->Insert(array('MODULE_PARAM_ID'=>$paramID, 'VALUE'=>$value));
			}
		}
	}
	/**
	 * Обновить блоки:
	 * @param $blocks		массив блоков
	 * @param $overwrite	переписать
	 * @return blocks
	 */
	function updateBlocks($blocks, $overwrite=false){
		foreach ($blocks as $areaID=>$area){
			
			$this->setBlock($areaID, $area['INF_BLOCK_ID'],$area['PARAMS']);
		}
		$this->refresh();
	}
	/**
	 * Обновить страницу:
	 * @return boolean
	 */
	function refresh(){
		include_once(params::$params['adm_data_server']['value'].'class/core/object/object.php');
		$page= object::factory('PAGE');
		/*@var page $page*/
		$page->exec_gen_page($this->PAGE_ID, $this->VERSION);
	}	
	/**
	 * Получить по URL:
	 * @return 
	 */
	function getURL(){
		
		if ($this->PARENT_ID){
			return $this->getRoot()->getURL().$this->DIR_NAME.'/';			
		}
		else {
			//Корень:
			if (self::$env['site_id']==$this->SITE_ID){
				//Сайт совпадает с текущим:
				$ret='/';
			}
			else {
				//Загружаем сайт:
				$ret=rbcc5_object::loadByID($this->SITE_ID,'SITE')->getURL();		
			}
			//Добавляем URL:
			$ret.=$this->DIR_NAME.'/';
			//Добавляем контекст (если есть):		
			if (($context=application::getContext())&&$context->URL){
				$ret.=$context->URL.'/';
			}				
			return $ret;
		}
		
	}
}
?>