<?php
/**
 * Постраничный вывод:
 * 
 * prev		предыдущая страница (null- если текущая страница первая)
 * next		следующая страница (null- если текущая страница последняя)
 * current	текущая страница
 * 
 * может быть использован как итератор e.c.
 *  
	$sel= new someDbSelect();	
	$sel->Limit(request::int('offset'),10);//10 записей начиная с переданной в запросе
	$pager= new dbselect_pager($sel->Offset, $sel->Limit,$sel->selectCount(),5,'http://mysite.com/some/url/?offset={offset}');

	if ($pager->prev){
		?><a href="<?=$pager->prev->href;?>">Тудой</a>
	}
	foreach ($pager as $page){
		if ($page->current){
		?>[<?=$page->page;?>]<?
		}
		else {
		?><a href="<?=$page->href;?>"><?=$page->page;?></a><?
		}
	}
	if ($pager->next){
		?><a href="<?=$pager->next->href;?>">Сюдой</a>
	}

	Соотв. выводит постраничную навигацию с 5 страницами f.ex.:
	Тудой  7  8 [9] 10 11 Сюдой
	или
	[1] 2 3 4 5 Сюдой
 * 
 *
 */
class pager extends DataStore implements Iterator, Countable {
	/**
	 * Из селекта:
	 *
	 * @param dbselect $sel
	 * @param int $PageCount
	 * @param string $href
	 */
	static function load($sel, $DisplayedPageCount, $href){	
		if ($sel->getLimit()){		
			return new pager($sel->getOffset(),$sel->getLimit(), $sel->selectCount(), $DisplayedPageCount, $href);
		}
		else {
			return new pager(0,1,1,$DisplayedPageCount,$href);
		}
	}
	
	function count(){
		return $this->DisplayedPageCount;
	}
	/**
	 * Текущее смещение
	 *
	 * @var int
	 */
	var $Current=0;
	/**
	 * Максимальное смещение:
	 * 
	 * @var int
	 */
	var $Max=0;
	/**
	 * Первый
	 *
	 * @var int
	 */
	var $First=0;
	/**
	 * Последний
	 *
	 * @var int
	 */
	var $Last=0;
	/**
	 * Конструктор
	 *
	 * @param int $offset				текущее смещение
	 * @param int $limit				кол-во записей на странице
	 * @param int $count				кол-во записей всего
	 * @param int $DisplayedPageCount	отображаемое кол-во страниц
	 * @param string $href				ссылка
	 */
	function __construct($offset,$limit,$count,$DisplayedPageCount,$href){
		
		parent::__construct(		
			array(
				'offset'=>$offset,
				'limit'=>$limit,
				'count'=>$count,				
				'href'=>$href,
				'DisplayedPageCount'=>$DisplayedPageCount,
				'PageCount'=>($limit)?ceil($count/$limit):1,								
			)
		);				
	}
	
	function _getInfo($path){
		if (!isset($this->Info[$path[0]])){
			/**
			 * Подгружаем сб
			 */
			switch($path[0]){
				//Текущая страница:
				case 'current': 
					$this->Info['current']=new pager_element($this->offset, $this);
				break;
				//Предыдущая страница:
				case 'prev': 					
					$this->Info['prev']=($this->offset-$this->limit>=0)?new pager_element($this->offset-$this->limit, $this):null;					
				break;
				//Следующая страница:
				case 'next':					
					$this->Info['next']=($this->offset+$this->limit<$this->count)?new pager_element($this->offset+$this->limit, $this):null;
				break;
				//Последняя страница:
				case 'end': 
					if ($this->limit) return 1;
					$this->Info['end']=new pager_element($this->count-$this->count%$this->limit, $this);
				break;
			}
		}
		
		return parent::_getInfo($path);
	}
	/**
	 * 	Перемотка в начало:
	 *
	 */
	function rewind(){
		if (!$this->limit) return false;
		if ($this->getInfo('DisplayedPageCount')){
			//Симметрично двигаем страницы, начало находиться на половину влево от текущей:
			$this->Current=max($this->offset-$this->limit*floor($this->DisplayedPageCount/2),0);								
			//Конец через $this->DisplayedPageCount от начальной:
			$this->Max=$this->Current+$this->DisplayedPageCount*$this->limit;
			//Если влево не все, двигаем обратно:			
			$MoveLeft=$this->Max-$this->count;
			$MoveLeft-=$MoveLeft%$this->limit;			
			if ($MoveLeft>0){						
				$this->Current=max($this->Current-$MoveLeft,0);
				$this->Max=$this->count;
			}			
		}
		else {
			$this->Current=0;
			$this->Max=$this->count;
		}
		$this->First=$this->Current;
		$this->Last=$this->Max-$this->Max%$this->limit;
		
	}
	
	/**
	 * Текущий элемент:
	 */
	function current(){
		return ($this->Current<$this->Max)?new pager_element($this->Current,$this):false;
	}
	/**
	 * Ключ (номер страницы)
	 */
	function key(){
		return floor($this->Current/$this->limit)+1;
	}
	/**
	 * Следующая страница:
	 *
	 */
	function next(){
		$this->Current+=$this->limit;
		return ($this->Current<$this->count)?new pager_element($this->Current, $this):false;
	}
	/**
	 * Проверка
	 */
	function valid(){
		return $this->current();
	}
	
}
/**
 * Элемент постраничного вывода
 * 
 *
 */
class pager_element extends datastore_inherit {
	/**
	 * Страница пейджера
	 *
	 * @param int $offset	 		смещение
	 * @param dbselect_pager $Pager	элемент постраничного вывода
	 */
	function __construct($offset, &$Pager){		
		parent::__construct(array(
			'offset'=>$offset,
			'page'=>($Pager->limit)?floor($offset/$Pager->getInfo('limit'))+1:1,						
			'end'=>min($offset+$Pager->getInfo('limit'),$Pager->getInfo('count')),
			'isCurrent'=>($offset==$Pager->getInfo('offset'))?true:false,
			'isFirst'=>($offset==$Pager->First)?true:false,
			'isLast'=>($offset==$Pager->Last)?true:false,			
		),	
		$Pager);

		$this->Info['rpage']=$Pager->PageCount-$this->page;
	}
	
	function _getInfo($path){	
	
		if ($path[0]=='href'){							
			$href=$this->Parent->getInfo('href');
			if (is_object($href)){
				return $href->buildLink(array('page'=>$this->Info['page'],'offset'=>$this->Info['offset']));
			}
			return str_replace(array('{offset}','{page}','{end}'), array($this->Info['offset'],$this->Info['page'],$this->Info['end']), $this->Parent->getInfo('href'));			
		}
		else return parent::_getInfo($path);
	}
	
	function __toString(){				
		return ($this->isCurrent)?'<b rel="page.current">'.$this->page.'</b>':'<a href="'.$this->href.'" rel="page.'.$this->page.'">'.$this->page.'</a>';
	}
	
}
?>