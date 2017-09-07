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
	Тудой 9 8 7 
 * 
 *
 */
class dbselect_pager extends DataStore implements Iterator {
	/**
	 * Текущее смещение
	 *
	 * @var int
	 */
	var $Offset=0;
	/**
	 * Максимальное смещение:
	 * 
	 * @var int
	 */
	var $MaxOffset=0;
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
				'PageCount'=>ceil($count/$limit),
				//Текущая страница:
				'current'=>new dbselect_pager_element($offset, $this),
				//Предыдущая страница:
				'prev'=>($offset-$limit>=0)?new dbselect_pager_element($offset-$limit, $this):null,
				//Следующая страница:
				'next'=>($offset+$limit<=$count)?new dbselect_pager_element($offset, $this):null,				
			)
		);
		/**
		 * 		 
		 */
		if ($DisplayedPageCount){
			//Симметрично двигаем страницы, начало находиться на половину влево от текущей:
			$this->Offset=max($offset-$limit*ceil($DisplayedPageCount/2)*$limit,0);
			//Конец через $DisplayedPageCount от начальной:
			$this->MaxOffset=$this->Offset+$DisplayedPageCount*$limit;
			//Если влево не все, двигаем обратно:
			if ($MoveLeft=floor(($this->MaxOffset-$count)/$limit)>0){
				$this->Offset=max($this->Offset-$MoveLeft*$limit,0);
				$this->MaxOffset=$count;
			}
		}
		else {
			$this->Offset=0;
			$this->MaxOffset=$count;
		}
	}
	/**
	 * Выставить лими по странице $page с лимитом $limit
	 * 
	 * @var dbSelect $sel	ссыкла на выборку
	 * @var int		 $page	страница
	 * @var int		 $limit	лимит
	 */
	static function limit(&$sel, $page, $limit=10){
				
	}
}

/**
 * Элемент постраничного вывода
 *
 */
class dbselect_pager_element extends datastore_inherit {
	/**
	 * Страница пейджера
	 *
	 * @param int $offset	 		смещение
	 * @param dbselect_pager $Pager	элемент постраничного вывода	 
	 */
	function __construct($offset, &$Pager){		
		parent::__construct(array(
			'offset'=>$offset,
			'page'=>ceil($offset/$Pager->getInfo('limit'))+1,
			'end'=>min($offset+$Pager->getInfo('limit'),$Pager->getInfo('count')),
			'current'=>($offset==$Pager->getInfo('offset'))?true:false			
		),
		$Pager);	
	}
	
	function __getInfo($path){
		if ($path[0]=='href'){
			return str_replace(array('{offset}','{page}','{end}'), array($this->Info['offset'],$this->Info['page'],$this->Info['end']), $this->Parent->getInfo('href'));
		}
		else return parent::__getInfo($path);
	}
	
}
?>