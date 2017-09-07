<?php
class NestedSets extends dbselect {
	function __construct($table){
		parent::__construct(array('id','RootID','LeftKey','RightKey','Level'), $table);	
	}
	/**
	 * Вставить запись
	 *
	 * @param int $Root
	 * @param string $id
	 * @param array $data	данные
	 */
	function AddNode($Root,$id=0, $data=null){		
		if (is_numeric($Root)){
			$sel= clone $this;
			$sel->Where('id',eq,$RootID);		
			if ($RootObj=$sel->SelectObject('datacommon')) return $this->AddNode($RootObj, $id); 
			else throw new Exception('Root not found');			
		}		
		//Уникальность id нас не ебет- может там мегонавигация:
		if (!$id=(int)$id) $id= kernel::next();
		//Новый узен:
		$New['id']=$id;
		$New['LeftKey']=$Root->getInfo('RightKey');
		$New['RightKey']=$Root->getInfo('RightKey')+1;
		$New['Level']=$Root->getInfo('Level')+1;
		$New['RootID']=$Root->getInfo('id');
		
		if ($data) foreach ($data as $k=>$v) $New[$k]=$v;
		/**
		 * Увеличиваем RightKey вверх по ветке:
		 */
		$sel= $this;
		$sel->dropWhere();
		$sel->Where('RightKey',grater_or_eq,$Root->getInfo('RightKey'));
		$sel->Where('LeftKey',smaller_or_eq,$Root->getInfo('LeftKey'));
		$sel->Update(array('RightKey'=>'{$RightKey}+2'));		
		/**
		 * Увеличиваем LeftKey вправо:
		 */
		$sel= $this;
		$sel->dropWhere();
		$sel->Where('LeftKey', grater, $Root->getInfo('RightKey'));		
		$sel->Update(array('LeftKey'=>'{$LeftKey}+2','RightKey'=>'{$RightKey}+2'));		
		/**
		 * Вставляем:
		 */
		$ins= $this;
		$sel->dropWhere();
		$New['id']=$ins->Insert($New);
		return $New;
	}
	/**
	 * Удалить запись:
	 *
	 * @param int $id
	 */
	function Remove($id){
		$sel= clone $this;
		$sel->Where('id',eq,$id);
		/*@var $Old datastore*/
		if (!$Old=$sel->SelectObject('datastore')) throw new Exception('Record does not exists');
		$Delta=$Old->getInfo('RightKey')-$Old->getInfo('LeftKey')+1;
		/**
		 * Вверх по ветке:
		 */
		$sel= clone $this;		
		$sel->Where('RightKey', grater, $Old->getInfo('RightKey'));
		$sel->Where('LeftKey',smaller,$Old->getInfo('LeftKey'));			
		$sel->Update(array(
			'RightKey'=>'{$RightKey}- '.$Delta,			
		));
		/**
		 * Вправо по ветке:
		 */
		$sel= clone $this;		
		$sel->Where('LeftKey',grater,$Old->getInfo('LeftKey'));
		$sel->Update(array(
			'LeftKey'=>'{$LeftKey}-'.$Delta,
			'RightKey'=>'{$RightKey}-'.$Delta,	
		));
		/**
		 * Удаляем поддерево:
		 */
		$sel= clone $this;
		$sel->selectBranch($Old);
		$sel->Delete();
		
	}
	/**
	 * Выбрать подветку
	 * 
	 * @var datastore $obj	узел
	 */
	function selectBranch($obj){
		$this->Where('LeftKey',grater_or_eq,$obj->getInfo('LeftKey'));
		$this->Where('RightKey',smaller_or_eq,$obj->getInfo('RightKey'));
		return $this;
	}
	
	function selectChilds($obj){
		$this->Where('LeftKey',grater,$obj->getInfo('LeftKey'));
		$this->Where('RightKey',smaller,$obj->getInfo('RightKey'));
		$this->OrderBy('LeftKey','ASC');
		return $this;
	}
	/**
	 * Выбрать путь:
	 * 
	 * @var datastore $obj	узел
	 */
	function selectPath($obj){
		$this->Where('LeftKey',smaller,$obj->getInfo('LeftKey'));
		$this->Where('RightKey',grater,$obj->getInfo('RightKey'));
		$this->OrderBy('LeftKey','ASC');		
	}
	/**
	 * Переместить узел $Node в узел $Root
	 * 
	 * @var datastore $Node
	 * @var datastore $Root
	 * 
	 * @return datatore	новый узел $Node
	 */
	function Move($Node, $Root){
		/*@var $Node datastore*/
		/*@var $Root datastore*/
		if ($Node->getInfo('LeftKey')<=$Root->getInfo('LeftKey')&&$Node->getInfo('RightKey')>=$Root->getInfo('LeftKey')) throw new Exception('Try to move node to its child');
		if ($Node->getInfo('RootID')==$Root->getID()) throw new Exception('Node just in root');
		$sel= $this;
		$Delta=$Old->getInfo('RightKey')-$Old->getInfo('LeftKey')+1;
		/**
		 * Схлапываем поддерево:
		 */
		/**
		 * Вверх по ветке:
		 */
		$sel= $this;
		$sel->Where('RightKey', grater, $Old->getInfo('RightKey'));
		$sel->Where('LeftKey',smaller,$Old->getInfo('LeftKey'));			
		$sel->Update(array(
			'RightKey'=>'{$RightKey}- '.$Delta,			
		));
		/**
		 * Вправо по ветке:
		 */
		$sel= $this;
		$sel->Where('LeftKey',grater,$Old->getInfo('LeftKey'));
		$sel->Update(array(
			'LeftKey'=>'{$LeftKey}-'.$Delta,
			'RightKey'=>'{$RightKey}-'.$Delta,	
		));
		/**
		 * Расхлапываем новый:
		 */
	}
}
?>