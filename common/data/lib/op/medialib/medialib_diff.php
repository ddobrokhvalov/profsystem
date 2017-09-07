<?
/**
 * Класс позволяет принять изменения библиотеку, т.е. спроэцировать одну библиотеку ($src) в другую ($dst)
 * Если конечная библиотека не сохранена, она сохраняется автоматически.
 * При этом конечная и начальная библиотеки могут быть не заданы.
 * 
 *  1. Сохранение измений после обработки формы: 
 *  	$src- null, 
 *  	$dst- библиотека привязанная к тестовой версии объекта
 *  	Новые объекты накидываются при помощи метода addSrcItem
 *  
 *  2. Принятие тестовой версии
 *  	$src- библиотека привязанная к тестовой версии
 *  	$dst- библиотека привязанная к публичной версии
 *  
 *  3. Откат тестовой версии:
 *  	$src- библиотека привязанная к рабочей версии
 *  	$dst- библиотека привязанная к тестовой версии
 *  
 *  4. Удаление объека
 *  	$src- библиотека, привязанная к тестовой версии
 *  	$dst- null 
 *  
 *  5. Принятие удаления
 *  	$src- библиотека, привязанная к рабочей версии
 *  	$dst- null
 *  
 *  Зачем весь этот геморрой:
 *  В базе и для URL первичным ключем является medialib_items.id
 *  Фактически записи идентичны по сигнатуре: {lib, area, file}
 *  При этом сохранение идет по id, так как адресация на фронтэнде предполагается по item.id
 *  Аналогично теги идентифицируются по связке {item, tag}, а адресуется по id
 *  
 *  Логика такая:
 *  	а) проходимcя по всем элементом srcItems
 *  		если у конечной библиотеке уже есть объект с заданной сигнатурой
 *  			обновляем его (заголовки и т.п.) помечаем как обновленный
 *  			проходимся по тегам объекта источника (src)
 *  				тег есть у объекта приемника- обновляем meta данные (помечаем как обновленный)
 *  				тег не существует- добавляем
 *  			проходимя по тегам объекта приемника
 *  				тег не помечен как обновленный- удаляем
 *  		объекта нет- создаем новый и источнике
 *  				создаем теги
 *  	б) проходимся по объектам приемника
 *  		если объект не помечен как обновленный- удаляем его и теги
 *  
 *  При этом создается 
 *
 * @author Andrey Toukmanov
 *
 */
class medialib_diff {
	/**
	 * Создать:
	 * @param medialib $src
	 * @param medialib $dst
	 * @return unknown_type
	 */
	function __construct($src=null, $dst=null){
		foreach ($this->getAreaItems($src) as $item) $this->addSrcItem($item);
		foreach ($this->getAreaItems($dst) as $item) $this->addDstItem($item);
		$this->src=$src;
		$this->dst=$dst;
	}
	/**
	 * Исходная
	 * @var medialib
	 */
	var $src;
	/**
	 * Конечная
	 * @var medialib
	 */
	var $dst;
	/**
	 * Список записей бибилиотеки:
	 * @param $lib
	 * @return array
	 */
	function getAreaItems($lib){
		if (!$lib||!$lib->getID()){
			//Нет ножек- нет мультиков
			return array();
		}
		$sel= medialib_select::items('lib',$lib->getID());		
		$ret=array();
		foreach ($sel as $obj){
			$ret[$obj['id']]=new medialib_item($obj);			
		}
		if (count($ret)){
			$sel= medialib_select::tags();
			$sel->Where('item',eq,array_keys($ret));
			foreach ($sel as $tag){				
				if (!$ret[$tag['item']]->tags) $ret[$tag['item']]->tags=array(); 
				$ret[$tag['item']]->tags[$tag['tag']]=$tag['position'];
			}						
		}		
		return $ret;
	}
	
	protected $srcItems=array();
	/**
	 * Добавить объект источника
	 * 
	 * @param $item	 
	 */
	function addSrcItem($item){		
		$this->srcItems[$item->Info['area']][$item->getFile()->getID()]=$item;
	}		
	
	protected $dstItems=array();
	/**
	 * Добавить объект в назначении 
	 * 
	 * @param $item
	 */
	function addDstItem($item){		
		$this->dstItems[$item->Info['area']][$item->getFile()->getID()]=$item;
	}	
	/**
	 * Выполнить:	 
	 */
	function execute(){	
		
		if (!$this->dst&&count($this->srcItems)) throw new Exception('Not dst lib found');
		if (!$this->dst->getID()) $this->dst->save();
		/**
		 * Проходимся по объектам источника:
		 */
		foreach ($this->srcItems as $area=>$areaItems){
			foreach ($areaItems as $file=>$item){				
				/*@var $item medialib_item*/
				if (isset($this->dstItems[$area][$file])){

					$dst= $this->dstItems[$area][$file];
					//В приемнике есть идентичный объект- обновляем его					
					$this->updateItem($item, $dst);
					//Удаляем конечный из списка (помечаем как обновленный):					
					unset($this->dstItems[$area][$file]);				
				}
				else {
					//Создаем объект в "приемнике"
					$this->createItem($item);
				}
			}
		}
		
		/**
		 * Удаляем старые:
		 */
		foreach ($this->dstItems as $area=>$areaItems){
			foreach ($areaItems as $file=>$item){
				
				$this->deleteItem($item);
			}
		}	
		
		/**
		 * Принимаем:
		 */	
		$this->commit();
	}
	/**
	 * ----------------------------------------------------------------------------------------------------	 
	 */	
	
	protected function createItem($item){		
		$item->Info['lib']=$this->dst->getID();
		$item->Info['id']=medialib_select::items()->Insert(array(
			'title'=>$item->Info['title'],
			'area'=> $item->Info['area'],
			'file'=> $item->getFile()->getID(),
			'lib'=>  $item->Info['lib'],
			'order'=>$item->Info['order']	
		));
		medialib_select::files('id',$item->getFile()->getID())->Update(array('links_count'=>'{$links_count}+1'));
		
		foreach ($item->getTagsList() as $tag=>$info){
			$this->replaceTags[]=array('tag'=>$tag, 'item'=>$item->getID(), 'position'=>$info);
		}		
	}
	
	var $replaceItems=array();
	/**
	 * Сохраняем запись	 
	 * 
	 * @var medialib_item $new
	 * @var medialib_item $old
	 */
	protected function updateItem($new, $old){
		/**
		 * Обсчитываем теги:
		 */
		$newTags=$new->getTagsList();		
		$oldTags=$old->getTagsList();
		/**
		 * Меняем свойства:
		 */
		$new->Info['id']=$old->Info['id'];
		$new->Info['lib']=$this->dst->getID();
		medialib_select::items('id', $new->Info['id'])->Update(array('title'=>$new->title, 'order'=>$new->order));
		
		/**
		 * Добавляем или обновляем:
		 */
		foreach ($newTags as $tag=>$position){
			
			$this->replaceTags[]=array('tag'=>$tag, 'item'=>$new->getID(), 'position'=>$position);
			if (isset($oldTags[$tag])){				
				unset($oldTags[$tag]);
			}
			else {
				$this->incrementTagsCloud($tag, $new->area, 1);
			}
		}
		/**
		 * Удаляем теги:
		 */
		
		foreach ($oldTags as $tag=>$obj){			
			medialib_select::tags('tag',$tag, 'item', $old->id)->delete();
			$this->incrementTagsCloud($tag, $old->area,-1);
		}
	}	
	/**
	 * Удалить
	 * 
	 * @param medialib_items $item	 
	 */
	protected function deleteItem($item){
		//Удаляем запись:
		$this->deleteItems[]=$item->Info['id'];
		//Уменьшаем ссылку:
		$this->decrementFiles[]=$item->Info['file'];
		//Удаляем теги:
		foreach ($item->getTagsList() as $tag=>$obj){		
			$this->incrementTagsCloud($tag, $item->area, -1);
		}
	}
	/**
	 * Список id к удалению:
	 * 
	 * @var array
	 */
	var $deleteItems=array();
	/**
	 * Обновить теги:
	 * @var array
	 */
	var $replaceTags=array();
	/**
	 * Удалить теги:
	 * @var array
	 */
	var $deleteTags=array();	
	/**
	 * Список файлов к откреплению:
	 * 
	 * @var array
	 */
	var $decrementFiles=array();
	/**
	 * ---------------------------------------------------------------------------------------------
	 */	
	var $tagsCloud=array();
	/**
	 * Изменить облако:
	 * @param string $tag 	тег
	 * @param string $area	область
	 * @param $increment	изменить	 
	 */
	function incrementTagsCloud($tag, $area, $increment){
		if (!isset($this->tagsCloud[$area])) $this->tagsCloud[$area]=array();
		if (!isset($this->tagsCloud[$area][$tag])) $this->tagsCloud[$area][$tag]=0;
		$this->tagsCloud[$area][$tag]+=$increment;
	}
	
	/**
	 * ---------------------------------------------------------------------------------------------	 
	 */
	protected function commit(){
		//Удаляем то что надо удалить:
		if (count($this->deleteItems)){
			$sel=medialib_select::items();
			$sel->Where('id', eq, $this->deleteItems);
			$sel->Delete();
			
			$sel=medialib_select::tags();
			$sel->Where('item',eq,$this->deleteItems);
			$sel->Delete();
		}
		
		//Декрементируем файлы:
		if (count($this->decrementFiles)){
			$sel=medialib_select::files();
			$sel->Where('id',eq,$this->decrementFiles);
			$sel->Update(array('links_count'=>'{$links_count}-1'));			
		}
		//Обновляем теги:		
		str::print_r(medialib_select::tags());	
		dbselect_insert::execute(medialib_select::tags(), $this->replaceTags, dbselect::REPLACE);
		//Удаляем теги:
		if (count($this->deleteTags)){
			$sel=medialib_select::tags();
			$sel->Where('id', eq, $this->deleteTags);
			$sel->Delete();
		}
		
	}
	
	
}
?>