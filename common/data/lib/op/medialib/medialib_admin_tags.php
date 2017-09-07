<?php
class medialib_admin_tags {
	var $table;
	/**
	 * Версия
	 * 
	 * @var int
	 */
	var $version;
	/**
	 * Словарь:
	 * @var rbcc5_multitable_input
	 */
	protected $dict;
	/**
	 * 
	 * @param $version	версия (соотв. при создании- тестовая, при принятии- рабочая)
	 * 					используется при создании облака	 
	 * @param $table
	 * @return unknown_type
	 */
	function __construct($dict=null){
		$this->table=$table;
		$this->dict=$dict;
	}
	
	function getOptions(){
		if ($this->dict) return $this->dict->getOptions();		
	}
	/**
	 * 
	 * @param $name
	 * @param rbcc5_object $dictObject
	 * @return unknown_type
	 */
	function getTagID($name, $dictObject){
		//Пользуем таксономию:
		$sel= new rbcc5_select('TAG');
		$sel->Where(($this->dict)?'SYSTEM_NAME':'TITLE',eq,$name);
		if ($obj=$sel->selectObject()){
			return $obj['TAG_ID'];
		}
		//Создаем новый:
		if ($dictObject){
			$obj=array(
				'TITLE'=>$dictObject->getItemTitle(),
				'SYSTEM_NAME'=>$name,
			);
		}
		else {
			$obj=array(
				'TITLE'=>$name,
				'SYSTEM_NAME'=>url_manager::FromSelect(new rbcc5_select('TAG'),'SYSTEM_NAME',50)->getURL($name)
			);
		}
		return $sel->Insert($obj);
	}
	/**
	 * Валидация тегов:
	 * 
	 * @param $tags
	 * @return array (теги, ошибки)
	 */
	function validateTags($tags, $file){
		$ret= array();
		$errors=array();
		foreach ($tags as $obj){
			
			if (!isset($obj['name'])) continue;
			/**
			 * Проверяем вхождение словаря:
			 */
			$dictObject=null;
			if ($this->dict&&!$dictObject=$this->dict->checkOption($obj['name'])){
				$errors[]=array('invalidName', $obj['name']);
			}
			/**
			 * Получаем id тега:
			 */
			if (!$tagID=$this->getTagID($obj['name'],$dictObject)){
				$errors[]=array('invalidName', $obj['name']);	
			}
			/**
			 * Проверяем уникальность:
			 */
			if (isset($ret[$tagID])){
				//Один тег- одна область:
				$errors[]=array('duplicateName', $obj['name']);
				continue;
			}
			
			$tag=array('name'=>$obj['name']);
			$valid=true;
			
			if ($file->type==medialib::image){
				//Проверяем область:			
				foreach (array('left','top','width','height') as $size){
					$sizeExists=0;
					if (isset($obj[$size])){
						$sizeExists++;
						if (!preg_match('/^\d+$/',$obj[$size])){
							$tag[$size]=$obj[$size];
						}
						else {
							$tag=null;//Обнуляем теги
							$errors[]=array('invalidSize',$obj['name']);						
							$valid=false;
							break;							
						}											
					}
					/**
					 * Должны быть заданы все размеры или ни одного:
					 */	
					if ($sizeExists&&$sizeExists!=4){
						$tag=null;//Обнуляем теги
						$errors[]=array('invalidSize',$obj['name']);						
						$valid=false;
						break;
					}
													
				}
				if ($valid){
					if ($sizeExists){
						//Проверяем размеры области (если область задана):
						if ($tag['left']+$tag['width']>$file->getInfo('meta.width')||$tag['top']+$tag['height']>$file->getInfo('meta.height')){
							$errors[]=array('invalidSize',$obj['name']);
							$tag=null;
						}
					}
				}
			}
			//Добавляем тег:	
			if ($tag) $ret[$tagID]=$tag;
		}
		
		return array($ret, $errors);
	}
	/**
	 * Обновить облако:
	 * @return false
	 */
	function updateCloud($increment){			
		/*@todo Обновление одним запросом */
		return;
		foreach ($increment as $tag=>$total){
			$sel=$this->selectTagsCloud();
			$sel->Where('version',eq,$this->version);//Учитываем версию
			$sel->Where('tag',eq,$tag);
			$sel->Increment('Total',$total);
		}
	}
	/**
	 * Выборка для облака тегов:
	 * @return dbselect
	 */
	function selectTagsCloud(){
		$a= func_get_args();
		return dbselect::factory(array('tag','Total','version'),$this->table.'_cloud',null,$a);
	}
	
	var $cache=array();
	
	function prepare($arr){
		if (count($items)){
			foreach ($arr as $i){
				$id=$i['id'];
			}
		}
		$sel= medialib_select::tags();
		$sel->Join(new dbSelect(array('id','Name'),$this->table),'id','id','tag','LEFT');
		foreach ($sel as $obj){
			if (!isset($this->cache[$obj['item']])) $this->cache[$obj['item']]=array();
			$this->cache[$obj['item']][$obj['id']]=array(
				'name'=>$obj['tag']['name'],
				'left'=>$obj['left'],
				'top'=>$obj['top'],
				'width'=>$obj['width'],
				'height'=>$obj['height'],
			);
		}
	}
	
	function getPrepared($id){
		return (isset($this->cache[$id]))?$this->cache[$id]:array();
	}
}
?>