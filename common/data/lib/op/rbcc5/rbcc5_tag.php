<?php
class rbcc5_tag extends rbcc5_object {
	/**
	 * Получить из системного объекта:
	 * @param rbcc5_object $obj
	 * @param boolean $create	создать
	 * @return rbcc5_tag
	 */
	static function fromObject($obj, $create=true){
		$sel= new rbcc5_select('TAG');
		$sel->Where('SYSTEM_NAME',eq,$obj->table.'.'.$obj->getID());
		//Получить объект:
		if ($ret=rbcc5_object::fetchObject($sel)){
			return $ret;
		}
		if (!$create) return null;
		//Создаем:
		$tag= array(
			'TITLE'=>$obj->getItemTitle(),
					
		);
	
		$tag['TAG_ID']=$sel->Insert($tag,'INSERT');
		return rbcc5_object::instance($tag, 'TAG');
	}
	/**
	 * Загрузить тег:
	 * @param $tag
	 * @return rbcc5_tag
	 */
	static function load($tag){
		$sel= new rbcc5_select('TAG');
		if (preg_match('/^[1-9]\d*$/', $tag)){
			$sel->Where('TAG_ID',eq,$tag);
		}
		elseif (preg_match('/^\w+\.\d+$/',$tag)){
			$sel->Where('SYSTEM_NAME',eq,$tag);
		}
		else {
			$sel->Where('TITLE',eq,$tag);
		}
		return rbcc5_object::fetchObject($sel);
	}
	/**
	 * 
	 * @param rbcc5_select $sel
	 * @return rbcc5_select
	 */
	function applyTaxonomy($sel){
		$taxonomy= new rbcc5_select('TAG_OBJECT');
		$taxonomy->Where('TAG_ID',eq,$this->getID());
		$taxonomy->Where('TE_OBJECT_ID',eq,rbcc5_te_object::loadBySystemName($sel->table)->getID());
		$sel->Join($taxonomy, $sel->primary_key, 'OBJECT_ID','OBJECT','INNER');
		return $sel;
	}
	/**
	 * Системный объект:
	 * @param $sel
	 * @return unknown_type
	 */
	function getSystemObject(){
		if (preg_match('/^(\w+)\.(\d+)$/',$this->SYSTEM_NAME)){
			try {
				return rbcc5_object::loadByID($m[2],$m[1],rbcc5_object::$env);
			}
			catch (exception $e){
				return null;
			}
		}
		else {
			return null;
		}
	}
	/**
	 * Получить похожие объекты:
	 * @param rbcc5_object $obj
	 * @return rbcc5_select
	 */
	function getSimilar($obj){
		
	}
	/**
	 * Теги объекта:
	 * @param rbcc5_object $obj
	 * @return array
	 */
	static function getObjectTags($obj){
		$t= new rbcc5_select('TAG');
		$to= new rbcc5_select('TAG_OBJECT');		
		$to->Where('TE_OBJECT_ID',eq,rbcc5_te_object::loadBySystemName($obj->table)->getID());
		$to->Where('OBJECT_ID',eq,$obj->getID());
		$to->Join(new rbcc5_select('TAG'), 'TAG_ID','TAG_ID','TAG');
		$ret=array();
		foreach ($to as $obj){
			$ret[]=rbcc5_object::instance($obj['TAG'],'TAG');
		}
		return $ret;
	}	
	/**
	 * Добавить объект:
	 * @param rbcc5_table $obj
	 * @return 
	 */
	function addObject($obj){
		$sel= new rbcc5_select('TAG_OBJECT');
		
		$sel->Insert(array(
			'TAG_ID'=>$this->getID(),
			'OBJECT_ID'=>$obj->getID(),
			'TE_OBJECT_ID'=>rbcc5_te_object::loadBySystemName($obj->table)->getID()
		),'REPLACE');
	}
	/**
	 * Выбрать объекты с данным тегом
	 * @return rbcc5_select
	 */
	function selectItems($table){
		$sel= rbcc5_select::getInstance($table, rbcc5_select::skipBlocks, false);
		$te_object= rbcc5_te_object::loadBySystemName($table);
		$tag_object= new rbcc5_select('TAG_OBJECT');
		$tag_object->Where('TE_OBJECT_ID',eq,$te_object->getID());
		$tag_object->Where('TAG_ID',eq,$this->getID());
		$sel->Join($tag_object, $sel->primary_key,'OBJECT_ID','TAG_OBJECT','INNER');
		return $sel;
	}
}
?>