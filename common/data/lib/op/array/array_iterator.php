<?php
/**
 * Проходит по массиву:
 */
class array_iterator extends DataStore implements Iterator, Countable  {
	
	var $res=array();
	
	function __construct($Info=null, $var=null){
		parent::__construct($Info);
		if ($var){
			$this->setArr($var);
		}		
	}
		
	function setArr($var){
		if (is_object($var)){
			$this->res=$var->toArray();
		}
		elseif (is_array($var)){
			
			foreach ($var as $obj) {
				$this->res[]=$obj;
			}
			
		}
		else {
			$arr=unserialize($var);
			if (!$arr||!is_array($arr)) throw new Exception('Invalid data format');
			
			foreach ($arr as $obj) {
				$this->res[]=$obj;
			}
		}
		
		$this->count=count($this->res);
	}
	
	protected function _loadData(){
		
		throw new Exception('Invalid iterator');
	}
	
	var $count;
	
	public function count(){
		if (!$this->res) $this->_loadData();
		return count($this->res);
	}
	
	public function rewind() {
		if (!$this->res) $this->_loadData();
		reset($this->res);
	}

	public function current() {
		if (!$this->res) $this->_loadData();
		$var = current($this->res);
		return $var;
	}

	public function key() {
		if (!$this->res) $this->_loadData();		
		$var = key($this->res);
		return $var;
	}

	public function next() {
		if (!$this->res) $this->_loadData();
		$var = next($this->res);
		return $var;
	}

	public function valid() {
		if (!$this->res) $this->_loadData();
		$var = $this->current() !== false;
		return $var;
	}
}
/**
 * Случайный доступ:
 *
 */
class array_iterator_rnd extends array_iterator {
	
	var $rnd=array();
	
	var $current=null;
	/**
	 * Сброс:
	 */
	public function rewind() {		
		$this->rnd=array();
		$rnd=rand(0,$this->count-1);
		$this->rnd[]=$rnd;
		$this->current=$this->var[$rnd];
	}

	public function current() {
		return $this->current;
	}

	public function key() {
		return $this->rnd[count($this->rnd)-1];
	}
	
	

	public function next() {
		
		if (count($this->rnd)>=$this->count){			
			return $this->current=false;
		}
		
		while (true){
			$rnd=rand(0,$this->count-1);
			if (!in_array($rnd,$this->rnd)) break;
		}
		$this->rnd[]=$rnd;
		return $this->current=$this->var[$rnd];
	}

	public function valid() {
		$var = $this->current() !== false;
		return $var;
	}
	
	var $limit=0;
	var $offset=0;
	
	function limit($offset, $limit){
		$this->limit=(int)$limit;
		if ($this->limit<=0) throw new Exception('invalid limit');
		$this->offset=(int)$offset;
		if ($this->offset<0) throw new Exception('invalid offset');		
	}
	
	function page($page, $limit){
		if (!$page=(int)$page) $page=1;
		return $this->limit(($page-1)*$limit, $limit);
	}
}
?>