<?
/**
 * 
 * @author atukmanov
 *
 */
class medialib_admin_area extends DataStore{
	
	
	/**
	 * 
	 * @var unknown_type
	 */
	var $allowedTypes=null;
	var $isMultiload=true;
	var $required=false;
	/**
	 * 
	 * @param $allowedTypes
	 * @param $isMultiload
	 * @param $required
	 * @return unknown_type
	 */
	function __construct($allowedTypes=medialib::all, $isMultiload=true, $required=false){
		$this->allowedTypes=$allowedTypes;
		$this->isMultiload=$isMultiload;
		$this->required=$required;
	}
	/**
	 * 
	 * @return 
	 */
	function printForm($area, $title, $items, $prefix='medialib'){		
		$justUploaded=medialib_admin::justUploaded($this->allowedTypes);
		$isMultiload=$this->isMultiload;
		
		try {
			$options=$this->tagsDict->getOptions();
			
		}
		catch (Exception $e){
			str::print_r($e->getMessage());
		}
		if (!$this->items) $this->items=($items)?$items:array();
		if ($this->isMultiload){
			$areaName=$prefix.'['.$area.'][$i][$name]';
			$allowedTypes=$this->allowedTypes;
			
			include medialib::$conf->tplPath.'multiheader.php';			
			$_itemNumber=0;
			$title='';
			
			foreach ($this->items as $item){
				
				$areaID=$prefix.'_'.$area.'_'.$_itemNumber;
				$areaName=$prefix.'['.$area.']['.$_itemNumber.']';
				include medialib::$conf->tplPath.'item.php';
				$_itemNumber++;
			}
			$areaID=  $area.'_new';
			$areaName='['.$area.'][new]';
			$item=null;
			include medialib::$conf->tplPath.'multifooter.php';			
		}
		else {
			$areaName=$prefix.'['.$area.']';
			$areaID=$prefix.'_'.$area;
			if (isset($this->items[0])){
				$item=$this->items[0];
			}
			elseif (isset($items[0])){
				$item=$items[0];
			}
			else {
				$item=null;
			}
			/*@var $item medialib_item*/
			$file=($item)?$item->getFile():null;
			
			include medialib::$conf->tplPath.'single.php';
		}
	}
	
	function getItems(){
		return $this->items;
	}
	/**
	 * Сравнить сортировку:
	 * @param $item1
	 * @param $item2
	 * @return -1/0/1
	 */
	static function cmpOrder($item1, $item2){
		if ($item1->order==$item2->order) return 0;
		return ($item1->order < $item2->order) ? -1 : 1;
	} 
	
	var $overwrite=false;
	/**
	 * Текущий порядок сортировки
	 * @var int
	 */
	var $currentOrder=0;
	/**
	 * Проверить запрос:
	 * 
	 * @param string $area
	 * @return boolean
	 */
	function validateRequest($request, $area, $prefix){
		
		if ($this->isMultiload){
			$this->items=array();
			
			if (isset($request->Info[$prefix][$area])){
				/**
				 * Проходимся по всем файлам:
				 */	
				$ret=true;			
				foreach (array_keys($request->Info[$prefix][$area]) as $uid){
					
					
					if ($item=$this->processUpload($request, array($prefix,$area, $uid))){										
						if ($item->error){
							//Если в хотя бы в одном файле есть ошибка: результат будет отрицательным
							$ret=false;
						}				
						//Запоминаем область:
						$item->Info['area']=$area;
						//Добавляем к списку:		
						$this->items[]=$item;
					}
					
				}
				
				/**
				 * Нормализуем сортироку:
				 */
				if (count($this->items)){					
					usort($this->items, array(__CLASS__,'cmpOrder'));
					$i=0;
					foreach ($this->items as &$item){
						$item->Info['order']=++$i;
					}
				}
				
				if (!$ret){										
					return false;
				}
				elseif ($this->required){
					//Если поле обязательное должен быть загружен хотя бы один файл: 
					return (count($this->items))?true:false;
				}
				else {
					return true;
				}
			}
			else {
				return ($this->required)?false:true;
			}
		}
		else{
			if ($item=$this->processUpload($request, array($prefix,$area))){
				$item->Info['area']=$area;
				$item->Info['order']=1;//Единственный- всегда первый
				$this->items=array($item);
				
				return ($item->error)?false:true;			
			}
			else {
				/**
				 * Если поле не обязательное, можно и не грузить:
				 */
				$this->items=array();
				return ($this->required)?false:true;
			}
			
		}
	}
	/**
	 * Текущий файл:
	 * @return medialib_file
	 */
	protected function currentFile($request, $requestKey){
		if (!$request->getInt($requestKey,'file')){
			return new medialib_file();
		}
		$file= medialib_file::loadByID($request->getInt($requestKey,'file'));
		if ($file->links_count>1&&!$this->overwrite){
			/**
			 * Если на файл более чем одна ссылка, файл не перезаписывается
			 * Кроме случая, когда явно указано, что файл должен быть перезаписан ($this->overwrite):
			 */
			return new medialib_file();
		}
		return $file;
	}
	
	const invalidFileType='invalidFileType';
	/**
	 * Провести загрузку:
	 * @param datastore $request
	 * @param string 	$requestKey
	 * @return medialib_item
	 */
	protected function processUpload($request, $requestKey){
		
		$fileError=null;
		$file=null;

		if ($uploaded=medialib_file::requestFile($requestKey, 'upload')){
			
			try {
				$file= $this->currentFile($request, $requestKey);					
				$file->setFile($uploaded[0], $uploaded[1]);
			}
			catch (Exception $e){
				$fileError=$e->getMessage();
			}
		}
		elseif ($link=$request->getInfo($requestKey,'url')){
			
			try {
				$file= $this->currentFile($request, $requestKey);
				/**
				 * Некоторые URL обрабатываются специальными браузерами.
				 * Так например ссылка на youtube преобразуется в видеофайл:
				 */
				foreach (medialib_admin::$browsers as $preg=>$browser){
					if (preg_match($preg, $link)){
						$file=$browser->downloadFile($link, $file);
					}
				}
			}	
			catch (Exception $e){
				$fileError=$e->getMessage();
			}
		}
		elseif ($fileID=$request->getInt($requestKey, 'file')) {					
			$file= medialib_file::loadByID($fileID);
		}
		else {
			
			return null;
		}
		/**
		 * Если сортировка не задана явно, берем следующий:
		 */
		if ($order=$request->getInt($requestKey,'order')){
			$this->currentOrder=max($order, $this->currentOrder);
		}
		else {
			$order=$this->currentOrder++;
		}
		
		$item= medialib_item::fromFile($file, $request->getInfo($requestKey,'title'), $order);		
		if ($fileError){			
			$item->error=$fileError;
		}
		elseif (!($file->type&$this->allowedTypes)){			
			$item->error=medialib_file::invalidFileType;
		}
		else {					
			$item->file->save();
		}
		if ($this->tagsDict){			
			if ($tags=$request->getInfo($requestKey,'tags')){
				/**
				 * @todo validare position
				 */
				list($item->tags, $errors)=$this->tagsDict->validateTags($tags, $item->file);						
				if (count($errors)) $item->Error='tags';
			}
			else {
				$item->tags=array();
			}			
		}
		return $item;
	}
	
	var $items=array();
	/**
	 * 
	 * @var medialib_admin_tags
	 */
	var $tagsDict;
	/**
	 * Выставить словарь для тегов:
	 * @param medialib_admin_area_dict $dict
	 * @return dbSelect
	 */
	function setTagsDict($dict){
		$this->tagsDict=$dict;
	}
	/**
	 * Пересчитать облако тегов
	 * @param array $increment
	 */
	function updateTagsCloud($increment){
		if ($this->dict) $this->dict->updateTagsCloud($increment);
	}
}

abstract class medialib_admin_area_dict {
	/**
	 * Обновить облако тегов:
	 * @param array $diff
	 * @return unknown_type
	 */
	function updateTagsCloud($diff){		
		foreach ($diff as $tag=>$increment){
			$this->getCloudSelect('tag',$tag)->Increment('total',$increment);	
		}
	}
	/**
	 * Выборка тегов
	 * @return dbselect
	 */
	abstract function getSelect(); 
 	/**
 	 * Облако тегов
 	 * @return dbselect
 	 */
	abstract function getCloudSelect();
}
?>