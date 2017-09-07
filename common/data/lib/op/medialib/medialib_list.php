<?
class medialib_list extends array_iterator {
	/**
	 * Библиотека
	 * @var medialib
	 */
	var $lib;
	/**
	 * Тип контента
	 * @var int
	 */
	var $type;
	/**
	 * Область контента
	 * @var string
	 */
	var $area;
	/**
	 * Создать
	 * @param medialib $lib
	 * @param int $type
	 * @param string $area	можно задать список областей через запятую: photo,map,interview
	 * 						можно изключить области поставив ^: ^icon -кроме иконки
	 * @return medialib_list
	 */
	function __construct($lib=null, $type=null, $area=null){
		$this->lib=$lib;
		$this->type=$type;
		$this->area=$area;
		parent::__construct(null,null);	
	}
		
	protected function _loadData(){
		$sel=$this->getSelect();
		if ($this->limit){
			$sel->Limit($this->offset, $this->limit);
		}
		$this->fetchSelect($sel);
	}
	/**
	 * Разобрать:	 
	 */
	function fetchSelect($sel){
		$this->res=array();
		foreach ($sel as $obj){
			$this->res[$obj['id']]=medialib_item::instance($obj, $this);
		}
	}
	/**
	 * Получить объект
	 * @param $id
	 * @return medialib_item
	 */
	function getItemByID($id){
		$sel=$this->getSelect();
		$sel->Where('id',eq,$id);
		if ($obj=$sel->selectObject()){
			return medialib_item::instance($obj, $this);
		}
		return null;
	}
	

	const prev=false;
	const next=true;
	
	function getNeighbours($item, $size){
		$nextLimit=floor($size/2);
		$prevLimit=$size-$nextLimit;//Мы какбэ устремлены вперед
		
		$next= $this->getSelect($item, true);
		$nextCount=$next->selectCount();
		if ($nextCount<$nextLimit){
			$prevLimit+=$nextLimit-$nextCount;
			$nextLimit= $nextCount;
		}
		
		$prev= $this->getSelect($item, false);
		$prevCount=$prev->selectCount();
		if ($prevLimit>$prevCount){
			$nextLimit=min($nextCount, $nextLimit+$prevLimit-$nextCount);
			$prevLimit=$prevCount;
		}
		$ret= new medialib_list($this->lib, $this->type, $this->area);
		$ret->orderBy($this->order, $this->orderDir);
		$ret->res=array();
		/**
		 * Предыдущие записи:
		 */
		$res=array();
		foreach ($prev as $obj){
			$item= medialib_item::instance($obj, $ret);
			$res[$item->getID()]=$item;
		}
		/**
		 * Следующие записи:
		 */
		$ret->res= array_reverse($res);
		foreach ($next as $obj){
			$item= medialib_item::instance($obj, $ret);
			$ret->res[$item->getID()]=$item;
		}
		return $ret;
	}	
	/**
	 * Предыдущая запись:
	 * 
	 * @param $item
	 * @return medialib_item
	 */
	function getPrevItem($item){
		if ($obj=$this->getSelect($item, false)->selectObject()){
			return medialib_item::instance($obj, $this);
		}
		else return null;		
	}
	/**
	 * Следующая запись:
	 * 
	 * @param $item
	 * @return medialib_item
	 */
	function getNextItem($item){
		if ($obj=$this->getSelect($item, true)->selectObject()){
			return medialib_item::instance($obj, $this);
		}
		else return null;	
	}

	protected $offset;
	protected $limit;
	
	function limit($offset,$limit){
		$this->offset=$offset;
		$this->limit=$limit;
	}
	/**
	 * Получить объект для постаничного вывода:
	 * @param $href
	 * @param $displayedPageCount
	 * @return pager
	 */	
	function getPager($href, $displayedPageCount=10){
		return new pager($this->offset,$this->limit,$this->getSelect()->selectCount(),$displayedPageCount,$href);
	}
	
	var $sel=null;
	/**
	 * Выборка
	 * @return dbSelect
	 */
	function getSelect($item=null, $forward=true){
		if ($this->sel) return $this->sel;		
		if (!$this->lib->getID()) $this->res=array();
		$items= medialib_select::items('lib', $this->lib->getID());
		$files= medialib_select::files();
		$filesJoin='LEFT';
		
		if ($this->type&&$this->type!=medialib::all){			
			$files->Where('{$type}&'.$this->type);
			$filesJoin='INNER';
		}
		
		if ($this->area){
			if (substr($this->area,0,1)=='^'){
				$whereType=noteq;
				$area=substr($this->area,1);
			}
			else {
				$whereType=eq;
				$area=$this->area;
			}
			if (strpos($area, ',')) $items->Where('area',$whereType,explode(',',$area));
			else $items->Where('area',$whereType,$area);
		}
		
		if ($forward) $orderDir=$this->orderDir;
		elseif ($this->orderDir==self::ASC) $orderDir=self::DESC;
		else $orderDir=self::ASC; 
		
		
		switch ($this->order){
			case self::none:			
			case self::title:
				//Выставляем параметр сортировки в зависимости от:								
				if ($item){
					$items->Where($this->order, ($orderDir==self::DESC)?smaller:grater, $item[$this->order]);
				}				
				$items->orderBy($this->order, $orderDir);											
			break;
			case self::ts:				
			case self::size:
				//Выставляем параметр сортировки в зависимости от:								
				if ($item){
					$files->Where($this->order, ($orderDir==self::DESC)?smaller:grater, $item[$this->order]);
				}
				$filesJoin='INNER';
				$files->orderBy($this->order,$orderDir);
			break;
		}
		$items->join($files,'file','id','file',$filesJoin);
		return $items;
	}
	/**
	 * Типы сортировки:	 
	 */
	const ts='ts';
	const none='order';
	const size='byteSize';
	const title='title';
	/**
	 * @todo сделать рейтинги и скачивания	 
	 */
	const rate='rate';
	const stat='stat';
	
	const ASC='ASC';
	const DESC='DESC';
	/**
	 * Сортировки по умолчанию
	 * @var unknown_type
	 */
	static $defaultOrderDir=array(
		medialib_list::ts=>self::DESC,
		medialib_list::none=>self::ASC,
		medialib_list::title=>self::ASC,
		//medialib_list::rate=>self::DESC,
		//medialib_list::stat=>self::DESC,
	);
	
	protected $order=self::none;
	protected $orderDir=self::ASC;
	/**
	 * Сортировать по:
	 * @param string $order
	 * @param string $dir		направление сортировки
	 * @return boolean
	 */
	function orderBy($order, $dir=null){
		if (!isset(self::$defaultOrderDir[$order])) throw new Exception('invalid order');
		$this->order=$order;
		
		if (!$dir) $this->orderDir=self::$defaultOrderDir[$this->order];
		elseif ($dir!=self::ASC&&$dir!=self::DESC) throw new Exception('Invalid order direction');
		else $this->orderDir=$dir;
	}
	/**
	 * Адрес попапа для картинки
	 * 
	 * @return string 
	 */
	function getItemPopupURL(){
		return str_replace(
			array('{$libID}','{$type}','{$area}','{$order}','{$orderDir}'),
			array($this->lib->getID(), $this->type, $this->area, $this->order, $this->orderDir),
			$this->lib->getSetting('popupURL','/medialib/popup/{$libID}/{$preview}/{$itemID}/?type={$type}&area={$area}&order={$order}&orderDir={$orderDir}')
		);		
	}	
}
?>