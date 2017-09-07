<?php
/**
 * Выборка из нескольких таблиц:
 * @author 1
 *
 */
class dbselect_union extends array_iterator{
	function __construct(){
		
	}
	
	var $sel=array();
	var $selTitle=array();
	/**
	 * Добавить выборку
	 * @param string $key	 
	 * @param dbSelect $sel
	 * @param string $title	 
	 */
	function appendSelect($key, $sel, $title){
		$this->sel[$key]=$sel;
		$this->selTitle[$key]=$title;
	}
	
	function _loadData(){
		foreach ($this->sel as $k=>$sel){
			foreach ($sel as $obj){
				$obj['uid']=$k.'_'.$obj['id'];
				$obj['select']=$k;
				$obj['selectTitle']=$this->selTitle[$k];
				$this->res[$obj['uid']]=$obj;
			}
		}
	}
	var $orderBy='';
	var $orderDir=null;
	function orderBy($field, $dir){
		
	}
}
?>