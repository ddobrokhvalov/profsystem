<?php
/**
 * Модуль с поиском по базе данных
 * @author atukmanov
 *
 */
class rbcc5_module_search extends rbcc5_module {
	
	public function content_init(){
		$this->info['ru_month']=array('1'=>'января','2'=>'февраля','3'=>'марта','4'=>'апреля','5'=>'мая','6'=>'июня','7'=>'июля','8'=>'августа','9'=>'сентября','10'=>'октября','11'=>'ноября','12'=>'декабря');
		parent::content_init();
	}
	
	function modeArchives(){
		if ($this->requestValue('do')=='suggest'){
			return $this->doSuggest();
		}
		if ($id=$this->q_param('id')){
				
			$this->displayElement($id);
		}
		else {
			$this->displayArchive($this->q_id('page'));
		}
	}
	/**
	 * Получить поля для списка
	 * @return array
	 */
	protected function getListFields(){
		return array('TITLE','ANNOUNCE','NEWS_DATE','LIST_PHOTO');
	}
	/**
	 * Поле "дата"
	 * @return unknown_type
	 */
	function getDateField(){
		return $this->table.'_DATE';
	}
	/**
	 * 
	 * Списко
	 * @see common/data/lib/op/rbcc5/rbcc5_module#displayList()
	 */
	function displayArchive($page){
		
		$sel= new rbcc5_select($this->table);
		
		$sel->applyEnv($this->env, ($this->view_param('ignore_blocks'))?rbcc5_select::skipBlocks:0);
		$sel->OrderBy($this->getDateField(),($this->view_param('sort_order')=='asc')?'ASC':'DESC');				
		$sel->Page($page,$this->view_param('items_per_page',1));
		
		//1. taxonomy:
		if ($tagID=$this->q_param('tag')){
			//Загружаем тег:
			if (!$tag= rbcc5_object::loadByID($tagID,'TAG',$this->env)){
				application::throwError('404');
			}					
		}
		elseif ($search=$this->q_param('search')) {
			//Не тег ли это:
			$t= new rbcc5_select('TAG');
			$t->Where('TITLE',eq,$search);
			if (!$tag=rbcc5_object::fetchObject($t)){
				if (strlen($search)>4){
					//применяем полнотекстовый поиск:
					$sel->Where(array('TITLE','ANNOUNCE','BODY'),match,$search);
					$this->info['search']=$search;
					$this->setLinkParam('search',$search);
				}
				else {
					$this->info['error']='query_is_to_short';
				}
			}
			
		}
		//если тег- ищем по тегу:
		
		$ts_from=0;
		if ($tag){
			$sel= $tag->applyTaxonomy($sel);
			$this->info['tag']=$tag;
			$this->info['tag_system']=$tag->getSystemObject();
			$this->setLinkParam('tag',$tag->getID());
		}
		//ограничиваем по дате:
		//начало
		$ts_from=0;
		if ($date_from=$this->q_param('date_from')){
			$m=explode('.',$date_from);
			if (checkdate($m[1],$m[0],$m[2])){
				$ts_from=mktime(0,0,0,$m[1],$m[0],$m[2]);
				$date_from=date('d.m.Y',$ts_from);
				$this->setLinkParam('date_from',$date_from);
				$this->info['date_from']=$date_from;
			}
			else {
				$this->info['error']='invalid_date';
			}
		}
		//конец
		$ts_to=0;
		if ($date_to=$this->q_param('date_to')){
			$m=explode('.',$date_to);
			if (checkdate($m[1],$m[0],$m[2])){
				$ts_to=mktime(23,59,59,$m[1],$m[0],$m[2]);
				$date_to=date('d.m.Y',$ts_to);
				$this->setLinkParam('date_to',$date_to);
				$this->info['date_to']=$date_to;
			}
			else {
				$this->info['error']='invalid_date';
			}
		}
		//устанавливаем дату:
		if (($ts_from||$ts_to)&&$this->info['error']!='invalid_date'){
			if (!$ts_to){
				//Задано только начало:
				$sel->Where($this->getDateField(),grater,date('YmdHis',$ts_from));
			}
			elseif (!$ts_from){
				//Задан только конец:
				$sel->Where($this->getDateField(),smaller,date('YmdHis',$ts_to));
			}
			else {
				$sel->Where($this->getDateField(),between,array(date('YmdHis',min($ts_to,$ts_from)),date('YmdHis',max($ts_to,$ts_from))));
			}
		}
		
		//assign tags:
		$sel->Select(array_merge($sel->getPrimaryKeyFields(),$this->getListFields()));		
		$list=rbcc5_object::fetchList($sel);
		$this->_loadItemTags(array_keys($list));
		if (count($list)){
			foreach ($list as &$item){
				$this->_prepareDate($item);
			}
		}
		//выводим:
		$this->render('index',array(
			'sel'=>$list,											
			'pages'=>pager::load($sel, $this->getDisplayedPageCount(),$this),	
		));
	}
	/**
	 * 
	 * @param $id
	 * @return unknown_type
	 */
	function _loadItemTags($id){
		if (!$id||(is_array($id)&&!count($id))) return;
		//assign tags:
		$to= new rbcc5_select('TAG_OBJECT');
		$to->Where('TE_OBJECT_ID',eq,rbcc5_te_object::loadBySystemName($this->table)->getID());
		$to->Where('OBJECT_ID',eq,$id);
		$to->Join(new rbcc5_select('TAG'),'TAG_ID','TAG_ID','TAG','LEFT');
		
		$this->info['items_tags']=$to->ToArray('OBJECT_ID',false,'TAG');		
	}
	/**
	 * Получить элемент 
	 *
	 * @see common/data/lib/op/rbcc5/rbcc5_module#getElement($id)
	 */
	function getElement($id){
		
		if ($ret=rbcc5_object::loadByID($id, $this->table, $this->env, ($this->view_param('ignore_blocks'))?rbcc5_select::skipBlocks:0)){
			$ret->Info['BODY']=$this->parseBody($ret->Info['BODY']);
			$this->_prepareDate($ret);
			$this->_loadItemTags($ret->getID());
			/**
			 * Загрузить похожие сообытия (парамет loadSimilar имеет смысл времени жизни):
			 */
			if ($similarCount=$this->view_param('similarCount')){
				
				$date=date('YmdHis',strtotime($this->view_param('similarAge','-7 days')));
				
				if (isset($this->info['items_tags'][$id]) && count($this->info['items_tags'][$id])){
					$news=$this->getSelect();
					$news->Select(array_merge($news->getPrimaryKeyFields(),$this->getListFields()));
					$news->Where('NEWS_ID',noteq,$id);
					/**
					 * Получаем теги документов:
					 */
					$tagID=array();
					foreach ($this->info['items_tags'][$id] as $tag) $tagID[]=$tag['TAG_ID'];
					
					$news->Where($this->getDateField(),grater_or_eq,$date);
					/**
					 * Получаем элементы с таким-же тегами:
					 */
					$item_tags= new rbcc5_select('TAG_OBJECT');
					$item_tags->Where('TE_OBJECT_ID',eq,rbcc5_te_object::loadBySystemName($this->table)->getID());
					$item_tags->Where('TAG_ID',eq,$tagID);
					$item_tags->Select('OBJECT_ID','COUNT(*) AS RELEVANCE');
					$item_tags->GroupBy('OBJECT_ID');
					$item_tags->OrderBy('COUNT(*) AS RELEVANCE','DESC');
					
					$news->Join($item_tags,'NEWS_ID','OBJECT_ID','TAGS_LIST','INNER');
					$news->Limit(0, $this->view_param('similarCount',5));
					
					$this->info['similar']=rbcc5_object::fetchList($news);
					
				}
			}
			return $ret;
		}
		else {
			return null;
		}
	}
		
	/**
	 * Подготавливаем:
	 * @return strign
	 */
	function parseBody($body){
		
		return str_replace('src="/core/utils/','src="http://fclm.ru/core/utils/',$body);
	}
	
	function doSuggest(){
				
		$sel= new rbcc5_select($this->table);
		$sel->applyEnv($this->env, ($this->view_param('ignore_blocks'))?rbcc5_select::skipBlocks:0);
		$sel->Select($sel->primary_key);
		$to=new rbcc5_select('TAG_OBJECT');
		$to->Where('TE_OBJECT_ID',eq,rbcc5_te_object::loadBySystemName($this->table)->getID());
		$to->Join($sel, 'OBJECT_ID',$sel->primary_key,'CONTENT','INNER');
		$to->Select('COUNT(*) AS TOTAL');
		$to->GroupBy('TAG_ID');

		$tags= new rbcc5_select('TAG');
		$tags->OrderBy('TITLE','ASC');
		if ($search=$this->q_param('search')){
			$tags->Where('TITLE',like,$search.'%');
		}
		$to->Join($tags,'TAG_ID','TAG_ID','TAG','INNER');
		$this->render('suggest',array('search'=>$search,'tags'=>$to));
	}
	
	function _prepareDate(&$item){
		$item->Info['date']=date_parse($item[$this->getDateField()]);					
		$item->Info['isToday']=(substr($item[$this->getDateField()],0,6)==date('Ymd'))?true:false;
		$item->Info['isYesterday']=(substr($item[$this->getDateField()],0,6)==date('Ymd',time()-86400))?true:false;
	}
	/**
	 * Облако тегов: 
	 * @return
	 */
	function doTags(){
		
		$te= rbcc5_te_object::loadBySystemName($this->table);
		$tags=new rbcc5_select('TAG');
		$cloud=new rbcc5_select('TAG_OBJECT');
		$cloud->Where('TE_OBJECT_ID',eq,$te->getID());
		$cloud->GroupBy('TAG_ID');
		$cloud->Select('TAG_ID','COUNT(*) AS TOTAL');
		$tags->OrderBy('TITLE','ASC');
		$cloud->Join($tags,'TAG_ID','TAG_ID','TAG','LEFT');
		
		$this->render('tags',array('tags'=>$cloud->ToArray()));
	}
}
?>