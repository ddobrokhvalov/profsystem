<?php
class medialib_search extends array_iterator {
	/**
	 * Выборка
	 * @var unknown_type
	 */
	var $sel;
	/**
	 * Галлерея:
	 * @var array
	 */
	var $gallery=null;
	/**
	 * Строка поиска:
	 * @var array
	 */
	var $search=null;
	/**
	 * Количество галлерей
	 * @var int
	 */
	var $galleriesCount=0;
	/**
	 * Количество записей
	 * @var int
	 */
	var $itemsCount=0;
	
	var $galleries=array();
	/**
	 * 
	 * @param dbselect $sel	выборка
	 * @return 
	 */
	function addSelect($sel, $type='gallery'){
		//Прикрепить 
		$this->sel=medialib_util::joinAreaItem($sel);	
	}
	/**
	 * Запрос:
	 * @param $q
	 * @return datastore
	 */
	function execute($q){
		//str::print_r($q);
		$this->offset=str::natural($q[$this->offsetKey]);
		$limit=$this->limit;
		
		
		if ($galleryID=str::natural($q[$this->galleryKey])){
			$this->sel->Where($this->sel->Select[0],eq,$galleryID);			
			if (!$this->gallery=$this->sel->selectObject()){
				return $this->throwError('gallery','404');
			}
			$this->total=$this->selectItems($this->offset, $this->limit);
		}
		elseif ($this->search=(isset($q[$this->searchKey]))?str::HTML($q[$this->searchKey],str::StripTags):''){
			$this->total=$this->selectGalleries($this->offset);		
			if (!$this->limit||$this->offset+$this->limit>$this->total){
				$this->total+=$this->selectItems($offset);
			}			
		}
		else {
			$galleriesCount=$this->selectGalleries($offset);
		}			
	}
	
	var $items=array();
	
	function selectGalleries($offset){
		
		if ($this->search){
			$this->sel->Where($this->sel->Select[1],like,'%'.$this->search.'%');
		}
		$ret=$this->sel->selectCount();
		if ($ret<$offset) return $ret;
		
		$this->sel->Limit($offset, $this->limit);	
	
		foreach ($this->sel as $obj){

			$this->galleries[]=array(
				'id'=>	 	$obj[$this->sel->Select[0]],
				'title'=>	$obj[$this->sel->Select[1]],
				'preview'=>	medialib::$conf->previewURL.'100x100/'.$obj['medialib']['item']['file']['fileName']
			);
		}
		return $ret;	
	}
	
	function selectItems($offset){
		if (!$this->gallery&&!$this->search) return null;
		$sel= medialib_select::items();
		if ($this->gallery){			
			$sel->Where('lib',eq,$this->gallery['medialib']['id']);			
		}
		if ($this->search){
			$sel->Where('title',like,'%'.$this->search.'%');
			$sel->GroupBy('file');
		}
		/*@var $sel dbselect*/
		$sel->leftJoin(medialib_select::files(),'file');
		$sel->limit($offset, $this->limit);
		$i=0;
		foreach ($sel as $item){
			$this->items[$i]=medialib_item::fromFile(new medialib_file($item['file']),$item['title'],$i);
			$this->items[$i]->id=$item['id'];
			$i++;
		}		
		return $sel->selectCount();
	}
	
	function getNext(){
		if (!$this->limit) return null;
		$next=$this->offset+$this->limit;		
		if ($next>$this->total) return null;		
		$ret=array($this->offsetKey=>$next);
		if ($this->search) $ret[$this->searchKey]=$this->search;
		if ($this->gallery)$ret[$this->galleryKey]=$this->gallery[$this->sel->Select[0]];
		return $ret;
	}
	
	function getPrev(){
		if (!$this->limit) return null;
		if (!$this->offset) return null;
		$ret=array($this->offsetKey=>$this->offset-$this->limit);
		if ($this->search) $ret[$this->searchKey]=$this->search;
		if ($this->gallery)$ret[$this->galleryKey]=$this->gallery[$this->sel->Select[0]];	
		return $ret;	
	}
}
?>