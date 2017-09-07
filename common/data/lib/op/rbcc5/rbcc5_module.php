<?php
include_once(params::$params['common_htdocs_server']['value'].'medialib/init.inc.php');
/**
 * Базовый класс модуля
 * @author atukmanov
 *
 */
class rbcc5_module extends module implements form_interface{
	/**
	 * Таблица:
	 * @var stru
	 */
	protected $table=null;
	/**
	 * Данные:
	 * @var boolean
	 */
	var $info=array();
	/**
	 * Выбранный объект:
	 * @var rbcc5_object
	 */
	protected $obj;
	
	/**
	 * Выставить тип шаблона
	 * @var string
	 */
	protected $tplType='tpl';
	/**
	 * Выставить тип вывода:
	 * @param $mode
	 * @return unknown_type
	 */
	function setMode($mode){
		$this->tplType=$mode.'.tpl';
	}
	/**
	 * Вывести шаблон:
	 * @param $tplName имя шаблона
	 * @param $info	   данные
	 * @param $return  вернуть результат шаблона	
	 * @return string
	 */
	protected function render($tplName, $info=null,$interactive='index.php'){
		$tpl= new smarty_ee_module($this);
		
		if (!$tplPath=$this->getTplPath($tplName)){
			
			if (!$interactive) return null;			
			
			header('location: '.$interactive);
		}						
		$tpl->assign(($info)?$info+$this->info:$this->info);
		$tpl->assign('includePath',$this->tpl_dir);
		$tpl->assign('baseURL',$this->buildLink());
		
		if ($interactive){
			$this->body=$tpl->fetch($tplPath);			
		}
		else{
			return $tpl->fetch($tplPath);	
		}
		
	}
	/**
	 * Вывести тег:
	 * @return unknown_type
	 */
	function doTag(){
		if (!$tag=rbcc5_tag::load($this->q_param('tag'))){
			application::throwError('404');
		}
		$this->setLinkParam('do','tags');
		$this->setLinkParam('tag',$tag->getID());
		$this->displayTagItems($tag, $this->q_param('page'));	
	}
	/**
	 * Вывод тегов объекта на странице
	 * @return boolean
	 */
	function modeTag($tpl='tag_items'){
		$tag=null;
		//Получаем тег:
		$tagID=$this->view_param('tagID');
		
		if (preg_match('/{(.*?)}/',$tagID,$m)){
			if ($id=request::String($m[1])){
				$tag=rbcc5_tag::load(str_replace('{'.$m[1].'}', $id, $tagID));
			}
			else return;
		}
		$ib= rbcc5_inf_block::loadByID($this->env['block_id'],'INF_BLOCK');
		$this->info['blockURL']=$ib->buildURL(array('id'=>''),$this->env['site_id']);
		
		//Если тег не найден- ничего не ищем:
		if (!$tag) return null;
		//Выводим теги шаблона:
		
		$this->displayTagItems($tag, 1, $tpl);
	}
	/**
	 * Информер:
	 * @return unknown_type
	 */
	function modeInformer(){
		$ib= rbcc5_inf_block::loadByID($this->env['block_id'],'INF_BLOCK');
		$this->info['blockURL']=$ib->buildURL(array('id'=>''),$this->env['site_id']);
		
		if ($tpl=$this->view_param('tagID')){
			return $this->modeTag();
		}
		
		$sel= $this->getSelect();
		$sel->Limit(0, $this->getLimit());
		$this->render('informer', array(
			'list'=>rbcc5_object::fetchList($sel)
		));		
	}
	/**
	 * 
	 * @param $tag
	 * @param $page
	 * @return unknown_type
	 */
	function displayTagItems($tag, $page=1, $tpl='tag_items'){
		$sel=$tag->applyTaxonomy($this->getSelect());
		$sel->Page($page,$this->getLimit());		
		$this->render($tpl, array(
			'tag'=>$tag, 
			'list'=>rbcc5_object::fetchList($sel), 
			'pages'=>pager::load($sel, $this->getDisplayedPageCount(),$this),
		));
	}
	/**
	 * Получить путь до шаблона:
	 * @param $tpl_name
	 * @return string
	 */
	protected function getTplPath($tplName){
		$tplPath=$this ->tpl_dir.$tplName.'.'.$this->tplType;
		
		if (file_exists($tplPath)) return $tplPath;
		
		//Пытаемся загрузить технологический шаблон:
		$tplPath=dirname($this ->tpl_dir).'tech/'.$tplName.'.'.$this->tplType;
		if (file_exists($tplPath)) return $tplPath;
		//Вообще такого шаблона нет:
		return null;
	}
	
	function content_init(){	
		
		rbcc5_object::$env=$this->env;
		if (isset($this->view_param['view_mode'])&&($mode=$this->view_param['view_mode'])){
			$method='mode'.$mode;
			if (method_exists($this,$method)){
				return $this->$method();
			}			
		}
		return $this->modeRequest();
	}
	/**
	 * 
	 * @param $key
	 * @param $default
	 * @return unknown_type
	 */
	function view_param($key, $default=null, $returnEmpty=false){
		if (!isset($this->view_param[$key])) return $default;
		if ($this->view_param[$key]) return $this->view_param[$key];
		return ($returnEmpty)?null:$default;
	}
	/**
	 * Запрос:
	 * @return boolean
	 */
	function modeRequest(){
		
		if ($do=$this->q_param('do')){
			$method='do'.$do;
			if (method_exists($this, $method)){
				return $this->$method();
			}
		}
		
		$this->main();
	}
	/**
	 * (non-PHPdoc)
	 * @see common/data/module/module/module#content_init()
	 */
	protected function main(){			
		if ($id=$this->q_id()){
			$this->displayElement($id);
		}
		else {
			$this->displayList($this->q_id('page',1));
		}
	}
	/**
	 * Вывести элемент:
	 * @return boolean
	 */
	protected function displayElement($id){		
		
		if ($this->obj=$this->getElement($id)){
			$this->setLinkParam('id',$id);			
			self::addBreadCrump($this->obj->TITLE, '?id_'.$this->env['area_id'].'='.$this->obj->getID());
			//Подключаем фотографии:
			
			//Вывод:
			$this->render('element',array('obj'=>$this->obj)+$this->getItemPhotos($this->requestInt('photo')));
		}
		else {
			application::throwError('404');
		}
	}
	/**
	 * Получить фотографии:
	 * @return unknown_type
	 */
	protected function getItemPhotos($photoID){
		
		$photos=null;
		if ($medialibInfo=$this->getProperty('medialib')){
			include_once params::$params['common_htdocs_server']['value'].'medialib/init.inc.php';			
			if ($lib=$this->obj->getMedialib()){
				
				$selectedPhoto=null;					
				foreach ($lib->getList(null,$medialibInfo['area']) as $photo){
					
					if ((!$photoID && !$selectedPhoto)||$photo->getID()==$photoID){						
						$selectedPhoto=$photo;
						$photo->Info['selected']=true;
					}
					$photo->Info['URL']=$this->buildLink(array('photo'=>$photo->getID()));
					
					$photos[]=$photo;						
				}					
			}				
		}
		return array('photo'=>$selectedPhoto, 'photos'=>$photos);
	}
	/**
	 * Получить магазин:
	 * @param $id
	 * @return rbcc5_object
	 */
	function getElement($id){
		$sel= new rbcc5_select($this->table);
		$sel->applyEnv($this->env, 0);
		$sel->Where($sel->primary_key, eq, $id);
		return rbcc5_object::fetchObject($sel);
	}
	/**
	 * Вывести список:
	 * @param $page
	 * 
	 * @todo filters
	 * @return unknown_type
	 */
	protected function displayList($page){
		$sel= $this->getSelect();		
		$sel->applyEnv($this->env,0);
		//Доступные параметры сортировки:
		$orders=$this->getOrderFields();
		
		/*<order> Сортировка */
		$order=new rbcc5_order($this->getOrderFields(), $sel->getOrderField());
		if (!$order->orderBy($this->q_param('order'),$this->q_param('dir',null,rbcc5_order::preg))){						
			application::throwError('404');
		}
		/**
		 * Если сортировка задана явно:
		 */
		if ($order['order']){		
			$sel->OrderBy($order->order, $order->dir);
		}
		//Запоминаем для формирования ссылок:
		$this->setLinkParam('order',$order->order);
		$this->setLinkParam('dir',$this->dir);
		//Формируем шаблон ссылки для сортировки:
		$order->setHref($this);		
		/*</order>*/
		/*<filter> Фильтрация */
		$filters=array();
		foreach ($this->getProperty('filters',array()) as $filterID){			
			if ($fmenu[$filterID]=$sel->getFieldData($filterID)){	
				
				if (!$filter=filter_input::factory($filterID, $fmenu[$filterID])) continue;			
				if (!$filter->validate($this)) application::throwError('404');//Кривой фильтр
				$filter->execute($sel, $this);
				$filters[$filterID]=$filter;
			}
		}		
		foreach ($filters as $filterID=>$filter){
			/*@var rbcc5_filter $filter*/						
			$fmenu[$filterID]['items']=$filter->getMenu($sel, $this);
			$fmenu[$filterID]['obj']=$filter->obj;
			$fmenu[$filterID]['skip']=$this->buildLink(array($filterID=>null));			
		}
		
		if ($filters_area=$this->view_param('filters_area')){
			$this->assignStub($filters_area,$this->render('filters',array('filters'=>$fmenu),false));			
		}
		/*</filter>*/
		//Постраничный вывод:
		if ($limit=$this->getLimit()){
			$sel->Page($page, $this->getLimit());
		}
		if ($groupBy=$sel->getProperty('groupBy')){
			//Группировка:
			foreach ($this->fetchList($sel) as $item){				
				if (!isset($list[$obj[$groupBy]])){
					$list[$obj[$groupBy]]=array();
				}
				$list[$obj[$groupBy]][$item->getID()]=$item;
			}
		}
		else {			
			$list=$this->fetchList($sel);
		}
		$this->info['debug']['sql']=$sel->getSql();
		//Список:
		$this->render('list',array(
			'list'=>$list,
			'pages'=>pager::load($sel, $this->getDisplayedPageCount(),$this),
			'order'=>$order,
			'orderDir'=>$orderDir,
			'orders'=>$orders,
			'filters'=>$fmenu,
			'filters_area'=>$filters_area,
		));		
	}
	/**
	 * Получаем список из данных:
	 * @param $sel
	 * @return array
	 */
	protected function fetchList($sel){
		return rbcc5_object::fetchList($sel);
	}
	
	protected $stubs=array();
	/**
	 * Выставить значение заглушки
	 * @param $id
	 * @param $html
	 * 
	 */
	function assignStub($id, $html){		
		require_once params::$params['common_data_server']['value'].'module/stub/m_stub.php';
		$this->stubs[$id]=$html;//remember for cache
		m_stub::assignHTML($id,$html);
	}		
	/**
	 * Укладка кэша блока на файловую систему
	 * Дополнительно сохраняются данные для заглушек
	 */
	protected function make_cache($filename){
		$dir=params::$params["common_data_server"]["value"]."block_cache/block".$this->env["block_id"]."/";
		$content=serialize(array($this->title, $this->body, $this->keywords, $this->description,$this->stubs));		
		@mkdir($dir, 0777);
		@file_put_contents($dir.$filename, $content);
		@chmod($dir.$filename, 0777);
	}

	/**
	 * Забор кэша с файловой системы и внедрение его в инстанс.
	 * Дополнительно получаются данные для заглушек 
	 */
	function get_cache($filename){
		list($this->title, $this->body, $this->keywords, $this->description,$this->stubs)=unserialize(file_get_contents(params::$params["common_data_server"]["value"]."block_cache/block".$this->env["block_id"]."/".$filename));
		if ($this->stubs) foreach ($this->stubs as $id=>$html) $this->assignStub($id,$html);
	}
	/**
	 * Выборка:
	 * @var rbcc5_select
	 */
	var $sel=null;
	/**
	 * Получить свойство:
	 * @return 
	 */
	function getProperty($property, $default=null){
		return $this->getSelect()->getProperty($property, $default);
	}
	/**
	 * Выборка:
	 */
	function getSelect(){		
		if (!$this->sel) $this->sel= new rbcc5_select($this->table);
		return $this->sel;
	}
	/**
	 * Получить поля для сортировки:
	 * @return boolean
	 */
	protected function getOrderFields(){		
		return $this->getProperty('order',array());
	}
	
	/**
	 * Количество записей на страницы
	 * @return int
	 */
	protected function getLimit(){
		return $this->view_param('items_per_page',10);
	}
	/**
	 * Количество страниц в блоке
	 * @return int
	 */
	protected function getDisplayedPageCount(){
		return 8;
	}	
	/**
	 * Добавить хлебную крошку:
	 */
	protected static function addBreadCrump($title, $url){
//		include_once params::$params['common_data_server']['value'].'module/menu/m_menu.php';
//		m_menu::addBreadCrumb($title, $url);
	}
	/*<input_form_interface>*/
	/**
	 * Получить имя поля
	 * @return string
	 */
	function getFieldName(){
		$args=func_get_args();
		$a=DataStore::normalPath($args);
		$count=count($a);
		$ret= $a[0].'_'.$this->env['area_id'];
		for ($i=1; $i<$count; $i++){
			$ret.='['.$a[$i].']';
		}
		return $ret;
	}
	/**
	 * Получить id поля:	 
	 * @return string
	 */
	function getFieldID(){
		$args=func_get_args();
		
		return implode('_',dataStore::normalPath($args)).'_'.$this->env['area_id'];	
	}
	/**
	 * Строка из запроса:
	 * @param mixed $key		путь до переменной запроса
	 * @param mixed $default	значение по умолчанию
	 * @param $preg				регулярное выражение
	 * @return mixed
	 */
	function requestValue($key, $default=null, $preg=null){
		
		$a=Datastore::normalPath($key);
		$count=count($a);
		$ret=$this->q_param;		
		//Fetch query:		
		for ($i=0; $i<$count; $i++){
			
			if (!is_array($ret)) return $default;
			if (!isset($ret[$a[$i]])) return $default;
			$ret=$ret[$a[$i]];
		}
		//Validate preg:
		if ($preg&&(!is_string($ret)||!preg_match($preg, $ret))){
			return $default;
		}		
		return $ret;
	}
	/**
	 * Число из запроса:
	 * @param $key
	 * @param $default
	 * @return int
	 */
	function requestInt($key, $default=0){
		return $this->requestValue($key, $default, '@^[1-9]\d*$@');
	}
	/**
	 * (non-PHPdoc)
	 * @see common/data/lib/op/input/input_form_interface#buildLink($params)
	 */
	function buildLink($query=array()){
		$a=array();
		foreach ($query as $k=>$v){
			$a[$k.'_'.$this->env['area_id']]=$v;
		}
		return '?'.http_build_query($a+$this->linkParams);
	}
	/**
	 * Параметры ссылки:
	 * @var array
	 */
	protected $linkParams=array();
	/**
	 * Выставить пар-р ссылки:
	 * @return boolean
	 */
	function setLinkParam($key, $value){
		$this->linkParams[$key.'_'.$this->env['area_id']]=$value;
	}
	
	function getFormID(){
		return 'form_'.$this->env['area_id'];
	}	
	/**
	 * Переменные окружения
	 * @return array
	 */
	function getEnv(){
		return $this->env;
	}
	/**
	 * 
	 * Строка из запроса:
	 * @param $key
	 * @param $default
	 * @param $preg
	 * @return mixed
	 */
	function q_param($key, $default=null, $preg=null){
		return $this->requestValue($key, $default, $preg);
	}
	/**
	 * Число из запроса:
	 * @param $key
	 * @param $default
	 * @return int
	 */
	function q_id($key='id', $default=0){
		return $this->requestValue($key, $default, '@^[1-9]\d*$@');
	}
	/*</input_form_interface>*/
	
	
	function throwError($field, $data=array('code'=>'invalid')){
		$this->info['errors'][$field]=$data;
		return false;	
	}
}
?>