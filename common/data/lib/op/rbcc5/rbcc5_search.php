<?
/**
 * Утилита для поиска по данным
 * @author atukmanov
 *
 */
class rbcc5_search extends DataStore{
	/**
	 * Количество выводимых записей:
	 * @var int
	 */
	var $limit=10;
	/**
	 * Получить URL:
	 * @param $query
	 * @return string
	 */
	function getURL($query){
		return http_build_query($query);
	}
	/**
	 * Выбрать 
	 * @param $q
	 * @return unknown_type
	 */
	function __construct($q){
		parent::__construct($q);
		
		
		//Создаем выборку:
		$sel= rbcc5::select($this->table, true);
		$sel->setMode(rbcc5_select::selectMode);
		//Применяем родительский объект:		
		$query=array('search'=>null, 'rootID'=>null);
		
		if ($parentKey=$sel->getProperty('parent_field')){
			if ($this->rootID){
				/**
				 * Проверяем наличие объекта:
				 */
				if (!$this->root=rbcc5_object::loadByID($this->rootID, $this->table)){
					throw new exception('root object not exists');
				}
				/**
				 * Формируем URL для родительской ссылки:
				 */
				$this->Info['urlParent']=$this->getURL(array(
					'table'=>$this->table,
					'rootID'=>$this->root->getInfo($parentKey),
				));			
				$query['rootID']=$this->root->getID();
				/**
				 * Накладываем условие:
				 */
				$sel->Where($parentKey, eq, $this->rootID);
			}
			elseif (!$this->search){
				$sel->Where($parentKey, eq, 0);
			}
			
		}
		//Полнотекстовый поиск:
		
		if ($search=$this->search){
			$query['search']=$search;			
			$searchLike="'%".dbselect::Escape($search)."%'";			
			$like=array();
			foreach ($sel->getProperty('fields') as $k=>$field){			
				if ($field['type']=='text'||$field['type']=='textarea'){
					$like[]="`$k` LIKE $searchLike";
				}
			}
			if (count($like)){
				$sel->Where(implode(' OR ', $like));
			}
		}
		
		//Ограничиваем число элементов в выборке:
		$this->Info['offset']=(preg_match('/^\d+$/', $this->offset))?$this->offset:0;
		$sel->Limit($this->offset, $this->limit);
		
		$list=array();
		$this->Info['total']=$sel->selectCount();
		foreach ($sel as $obj){			
			$this->Info['list'][$obj[$sel->table.'_ID']]=rbcc5_object::instance($obj, $sel->table);
		}
		//Выставляем URL для перехода внутрь:
		if ($parentKey && count($this->Info['list'])){
			$sel= rbcc5::select($this->table);
			$sel->Where($parentKey, eq, array_keys($this->Info['list']));
			$sel->GroupBy($parentKey);
			$sel->Select($parentKey);
			foreach ($sel as $hasChilds){				
				$this->Info['list'][$hasChilds[$parentKey]]->Info['urlParent']=$this->getURL(array('rootID'=>$hasChilds[$parentKey]));
			}
		}
		
		//Постаничный вывод:
		
		
				
		if ($this->offset>0){
			$this->Info['prevURL']=$this->getURL($query+array('offset'=>$this->offset-$this->limit));
		}
		
		if ($this->total>$this->offset+$this->limit){
			$this->Info['nextURL']=$this->getURL($query+array('offset'=>$this->offset+$this->limit));
		}
		
	}
}
?>