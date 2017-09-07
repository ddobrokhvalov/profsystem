<?php
/**
 * Отображение резолюций:
 * 
 * 
 * @author atukmanov
 *
 */
class rbcc5_resolution extends DataStore{
	/**
	 * Загрузить по id
	 * @param $id
	 * @return rbcc5_resolution
	 */
	static function loadByID($id){
		$sel= rbcc5_select::WF_RESULUTION();
		$sel->Where($sel->primary_key,eq,$id);
		
		$f_state=rbcc5_select::WF_STATE();
		$sel->Join($f_state,'FIRST_STATE_ID',$f_state->primary_key,'FIRST_STATE','LEFT');
		
		$l_state=rbcc5_select::WF_STATE();
		$sel->Join($l_state,'LAST_STATE_ID',$l_state->primary_key,'LAST_STATE','LEFT');
		
		return $sel->selectObject(__CLASS__);
	}
	
	const test_version='test_version';
	const two_version='two_versions';
	const no_version='no_version';
	
	const work=0;
	const test=1;
	/**
	 * 
	 * @param $Info	 
	 */
	function __construct($Info){
		parent::__construct($Info);		
	}
	
	var $objectsMap=array();
	/**
	 * Добавить отобраэение
	 * @param $src
	 * @param $dst
	 */
	protected function addMap($src, $dst){
		$this->objectsMap[]=array($src, $dst);
	}
	/**
	 * Возвращает отображение версия-> версия
	 * @param $obj			исходный объект
	 * @param $_version		ключ для версии
	 * @param $_lang		ключ для языка
	 * @return boolean
	 */
	function mapObject($obj, $_version='version', $_lang='lang'){
		$src=array();
		if (!$obj) $obj=array();
		foreach ($obj as $k=>$v){
			$src[$k]=$v;
			$dst[$k]=$v;
		} 
		
		switch ($this->MAIN_VERSION){
			case self::test:
				//Отображаем тестовую версию на рабочую:
				$src[$_version]=self::test;
				switch ($this->getInfo('LAST_STATE.VERSIONS')){
					case self::two_version:
						$dst[$_version]=self::work;//Отображаем на рабочию
					break;
					case self::test_version:
						$dst[$_version]=self::test;//Отображаем на тестовую
					break;
					default:
						throw new Exception('Can not map test object to null');
					break;
				}
				if ($this->getInfo('LANG_ID'))	$dst[$_lang]=$this->getInfo('LANG_ID');
				//Отображаем:
				$this->addMap($src, $dst);		
			break;
			case self::work:
				//Отменяем изменения:
				$src[$_version]=0;
				$dst[$_version]=1;
				$this->addMap($src, $dst);
			break;
			default:				
				//Работаем с текущей версией:
				switch ($this->getInfo('LAST_STATE.VERSIONS')){
					case self::no_version:
						//Удалить тестовую:
						$this->addMap(null, $obj+array($_version=>1));
						if ($this->getInfo('FIRST_STATE.VERSIONS')==self::two_version){
							//Удалить рабочую:
							$this->addMap(null, $obj+array($_version=>0));
						}
					break;
					case self::test_version:						
						if ($this->getInfo('LANG_ID')){
							//Перевод с языка на язык:
							$dst[$_lang]=$this->getInfo('LANG_ID');
							$src[$_version]=($this->getInfo('FIRST_STATE.VERSIONS')==self::two_version)?self::work:self::test;
							$dst[$_version]=self::test;						
						}
						else {
							//Снять публикацию:			
							$src=null;			
							$dst[$_version]=self::work;							
						}										
						$this->addMap($src, $dst);
					break;
					case self::two_version:
						//Опубликовать:						
						$src[$_version]=self::test;
						$dst[$_version]=self::work;
						$this->addMap($src, $dst);
					break;
				}
			break;
		}
	}
	
	
		
	
	var $src=array();
	var $dst=array();
}
?>