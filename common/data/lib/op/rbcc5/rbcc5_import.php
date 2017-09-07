<?php
class rbcc5_import {
	/**
	 * id страницы:
	 * @var int
	 */
	var $page=array();
	/**
	 * Выбрать дерево:
	 * @var int
	 */
	var $pagesID=array();
	
	var $tree=array();
	/* <Получение из БД> */
	/**
	 * Из базы:
	 * @param $pageID корень
	 * @return 
	 */	
	static function loadFromDatabase($pageID){
		$sel= new rbcc5_select('PAGE');
		$sel->Where($sel->primary_key, eq, $pageID);
		$sel->Where('VERSION',eq,rbcc5_select::testVersion);//also use test version
		$ret=new rbcc5_import();
		if (!$ret->page=$sel->selectObject()) throw new Exception('Page with id '.$pageID.' not found');
		$ret->pagesID=array($ret->page['PAGE_ID']);
		$ret->_selectTree($ret->pagesID);		
		return $ret;
	}
	/**
	 * 
	 * @return выбрать дерево
	 */
	protected function _selectTree($rootID){
		$sel= new rbcc5_select('PAGE');
		$sel->Where('PARENT_ID',eq,$rootID);
		$sel->Where('VERSION',eq,rbcc5_select::testVersion);//test version
		$sel->OrderBy('PAGE_ORDER','ASC');
		$newRoot=array();
		foreach ($sel as $obj){
			$this->pagesID[]=$obj['PAGE_ID'];
			$this->tree[$obj['PARENT_ID']][]=$obj;
			$newRoot[]=$obj['PAGE_ID'];
		}
		if (count($newRoot)) $this->_selectTree($newRoot);
	}
	/* </Получение из БД> */
	/* <Экспорт в CSV> */
	
	static $systemCharset='UTF-8';
	static $csvCharset='windows-1251';
	/**
	 * Получить CSV:
	 * @param $glue разделитель
	 * @return string
	 */
	function getCsv($glue=";", $newLine="\n"){
		$ret=$this->page['DIR_NAME'].$glue.$this->page['TITLE'];
		if (isset($this->tree[$this->page['PAGE_ID']])){
			$ret.=$newLine.$this->_getTreeCsv($this->page['PAGE_ID'],1,$glue,$newLine);
		}
		return iconv(self::$systemCharset, self::$csvCharset, $ret);
	}
	/**
	 * Получить CSV для дерева:
	 * @return string
	 */
	protected function _getTreeCsv($parentID,$level,$glue,$newLine){
		$ret='';
		foreach ($this->tree[$parentID] as $page){
			$ret.=str_repeat($glue,$level).$page['DIR_NAME'].$glue.$page['TITLE'].$newLine;
			if (isset($this->tree[$page['PAGE_ID']])){				
				//Рекурсивно:
				$ret.=$this->_getTreeCsv($page['PAGE_ID'],$level+1,$glue,$newLine);
			}
		}
		return $ret;
	}	
	/**
	 * Разбить CSV в массив rbcc5_import
	 * @param string $fileName
	 * @param string $delimiter
	 * @return array of rbcc5_import
	 */
	static function fetchCSV($fileName, $delimiter=';'){
		$ret=array();
		if (!$fp=fopen($fileName, 'r')){
			throw new Exception('Invalid csv '.$fileName);
		}
		$id=0;
		if (!$str=fgets($fp)){			
			throw new Exception('Invalid csv '.$fileName);
		}
		else {
			$line=explode(';',trim($str));
		}
		$ret=array();
		$import= new rbcc5_import();
				
		while ($line=$import->readCsv($line, $fp, $id, $delimiter)){
			$ret[]=$import;
			$import= new rbcc5_import();
		}
		$ret[]=$import;
		fclose($fp);
		return $ret;
	}
	/**
	 * Прочитать SVN файл
	 * @param $line
	 * @param $fp
	 * @return 
	 */
	protected function readCsv($line, $fp, &$id, $delimiter){
		
		$this->page=array('PAGE_ID'=>$id,'DIR_NAME'=>iconv(self::$csvCharset, self::$systemCharset, $line[0]),'TITLE'=>iconv(self::$csvCharset, self::$systemCharset, $line[1]));
		$stack=array($id);
		while ($str=fgets($fp)){//, 0, $delimiter)){
			$line=explode(';',trim($str));
			$id++;			
			if ($line[0]) return $line;//новый элемент			
			//Страничко:
			$page=array('PAGE_ID'=>$id, 'TITLE'=>null, 'DIR_NAME'=>null);
			//Вычисляем уровень исходя из вложенности:
			$level=0;
			
			foreach ($line as $point){					
				if (!$point){					
					$level++;
				}
				elseif (!$page['DIR_NAME']){
					$page['DIR_NAME']=$point;
				}
				elseif (!$page['TITLE']){
					$page['TITLE']=iconv(self::$csvCharset, self::$systemCharset, $point);
					break;
				}
				else {
					throw new Exception('Invalid csv at line '.$id);
				}
			}		
			//Проверяем валидна ли структура и вложенность:
			if (!$page['DIR_NAME']||!$page['TITLE']) throw new Exception('Invalid csv '.$fileName.' at line '.$id);
			if (!$level||$level-$lastLevel>1) throw new Exception('Invalid level structure at line '.$id);
			//Вычисляем родителя:
			$parentID=$stack[$level-1];
			if (!isset($this->tree[$parentID])) $this->tree[$parentID]=array();
			$this->tree[$parentID][]=$page;
			$stack[$level]=$page['PAGE_ID'];
			$lastLevel=$level;
		}
		return null;
	}
	/* </Экспорт в CSV> */
	var $report=array();
	/**
	 * Вставить в базу:
	 * @param $siteID		сайт
	 * @param $rootID		корень
	 * @param $templateID	шаблон
	 * @return int (id)
	 */
	function save($parentID, $info=array()){
		/*Получаем максимальный PAGE_ORDER: */
		if (!$parent= rbcc5_page::loadById($parentID)) throw new Exception('Invalid tree root');
		try {
			
			$root=$parent->create($this->page+$info);
			//Записываем в отчет:
			$this->report[$this->page['PAGE_ID']]=array('TITLE'=>$this->page['TITLE'],'PAGE_ID'=>$root->PAGE_ID);
			if (isset($this->tree[$this->page['PAGE_ID']])){
				$this->saveTree($root, $this->page['PAGE_ID'], $info);	
			}
		}
		catch (Exception $e){
			//Записываем в отчет:
			$this->report[$this->page['PAGE_ID']]=array('TITLE'=>$this->page['TITLE'],'ERROR'=>$e->getMessage());
		}
		return $this->report;
	} 
	/**
	 * Сохранить дерево
	 * @param rbcc5_page $parent корень
	 * @param int 		 $key	 ключ дерева в $tree
	 * @param $info		 $
	 * @return unknown_type
	 */
	protected function  saveTree($parent, $key, $info){
		foreach ($this->tree[$key] as $page){
			try {
				$newPage=$parent->create($page+$info);
				//Помечаем как сохраненный:
				$this->report[$page['PAGE_ID']]= array(
					'TITLE'=> $this->report[$key]['TITLE'].'/ '.$page['TITLE'],
					'PAGE_ID'=>$newPage->PAGE_ID
				);			
				if (isset($this->tree[$page['PAGE_ID']])){
					$this->saveTree($newPage, $page['PAGE_ID'],$info);
				}
			}
			catch (Exception $e){
				//Помечаем как ошибку:
				$this->report[$page['PAGE_ID']]= array(
					'TITLE'=> $this->report[$key]['TITLE'].'/ '.$page['TITLE'],
					'ERROR'=> $e->getMessage()
				);
			}
		}
	}
	/**
	 * Создать дерево:
	 * @param $treeParent
	 * @param $parentID
	 * @param $info
	 * @return void
	 */
	protected function _createTree($treeParent, $parentID, $info){
		$pageOrder=0;
		foreach ($this->tree[$treeParent] as $page){
			//Каждый следующий элемент ниже предыдущего:
			$pageOrder+=10;
			$page['PAGE_ORDER']=$pageOrder;
			//Сохраняем:
			$newPageID=$this->createPage($page, $parentID, $info);
			if (isset($this->tree[$page['PAGE_ID']])){
				//Проверяем потомков:
				$this->_createTree($page['PAGE_ID'],$newPageID,$info);
			}
		}
	}
	
	protected function createPage($page, $parentID, $info){
		foreach ($info as $k=>$v) $page[$k]=$v;
		$page['PARENT_ID']=$parentID;
		$new= object::factory('PAGE');
		return $new->exec_add($page, '');
	}
}
?>