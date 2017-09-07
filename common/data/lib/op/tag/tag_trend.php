<?php
class tag_trend extends DataStore {
	/**
	 * Выборка терендоs
	 *
	 * @return dbselect
	 */
	static function select(){
		$a=func_get_args();
		return dbselect::factory(array('id','Url','Title'),'tags_trends',null,$a);
	}
	/**
	 * Загрузить по id
	 *
	 * @param int $id
	 * @return Tag_Trend
	 */		
	static function loadByID($id){
		if (!$id=str::natural($id)) throw new Exception('Invalid trend id',402);
		return self::select('id',$id)->SelectObject('tag_trend');
	}
	
	static function loadByURL($Url){
		return self::select('Url',$Url)->SelectObject('tag_trend');
	}
	/**
	 * Создать
	 *
	 * @param string $Title
	 * @param string $Url
	 * @param int $id
	 * @return tag_trend
	 */
	static function create($Title,$Url,$id=0){		
		if (!preg_match(url_manager::getPreg(), $Url)) throw new Exception('Url invalid');
		if (!$Title||strlen($Title)>50) throw new Exception('Title invalid');
		if (self::loadByURL($Url)) throw new Exception('Url invalid');
		if (!$id) $id=kernel::getNextID();
		$ret=new tag_trend(array('id'=>kernel::getNextID(),'Url'=>$Url,'Title'=>$Title));
		self::select()->Insert($ret->Info);
		return $ret;
	}
	
	static function select_tags(){
		$a= func_get_args();
		return dbselect::factory(array('tag','trend'),'tags_trend_tags',null,$a);
	}
	/**
	 * Выборка тегов:
	 *
	 * @return dbselect
	 */
	function selectTrendTags(){
		return self::select_tags('trend',$this->getID());
	}
	/**
	 * Добавить тег
	 *
	 * @param tag $Tag
	 */
	function Add($Tag){
		$sel=$this->selectTrendTags();
		$sel->Where('tag',eq,$Tag->getID());
		if ($sel->SelectObject()) throw new Exception('Reference just exists',404);
		$sel->Insert(array('trend'=>$this->getID(),'tag'=>$Tag->getID()));
		
	}
	/**
	 * Удалить тег
	 *
	 * @param tag $Tag
	 */
	function Remove($Tag){
		$sel= $this->selectTrendTags();
		$sel->Where('tag',eq,$Tag->getID());		
		if (!$sel->SelectObject()) throw new Exception('Tag not found',404);		
		$sel->Delete();
	}
	
	/**
	 * Удалить:
	 */
	function Delete(){
		$this->selectTags()->Delete();
		$this->selectTrendRels()->Delete();
		self::select('id',$this->getID())->Delete();		
	}
	/**
	 * Выбрать связи:
	 *
	 * @return dbselect
	 */
	static function select_rel(){
		$a= func_get_args();
		return dbselect::factory(array('trend','rel','type'), 'tags_trend_rels', null, $a);		
	}
	/**
	 * Связи тренда:
	 * 
	 * @return dbselect
	 */
	function selectTrendRels(){
		return self::select_rel('trend',$this->getID());
	}
	/**
	 * Добавить связь:
	 * 
	 * @param int 		$Rel
	 * @param string	$Type
	 */
	function AddRel($Rel, $Type){
		if (!$Rel=str::natural($Rel)) throw new Exception('Invalid rel');
		$this->selectTrendRels()->Insert(array('trend'=>$this->getID(), 'rel'=>$Rel, 'type'=>$Type));		
	}		
	/**
	 * Удалить связь
	 *
	 * @param int 		$Rel
	 * @param string 	$Type
	 */
	function RemoveRel($Rel, $Type){
		if (!$Rel=str::natural($Rel)) throw new Exception('Invalid rel');
		$sel=$this->selectTrendRels();
		$sel->Where('rel',$Rel);
		if ($Type) $sel->Where('type',eq,$Type);
		$sel->Delete();		
	}
}
?>