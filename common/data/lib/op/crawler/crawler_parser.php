<?php
/**
 * Парсер HTML
 * @author atukmanov
 *
 */
class crawler_parser {
	
	var $preg=null;
	
	var $markers=null;
	/**
	 * Создать:
	 * @param $preg	 
	 */
	function __construct($preg){
		$this->preg=$preg;
		if (!preg_match_all('@\{\$(.*?)\}@',$this->preg, $m)){
			throw new Exception('preg is empty');
		}
		/**
		 * Получаем ключи:
		 */
		$i=1;
		foreach ($m[1] as $value){			
			if ($value){
				$this->markers[$i]=$value;
			}
			$i++;
		}
		/**
		 * Делаем регулярку:
		 */
		$this->preg='@'.
		preg_replace('@\s+@','\s*',
			preg_replace('@\{\$(.*?)\}@','(.*?)',
					preg_replace('@\'\{\$(.*?)\}\'@','\'([^\']*?)\'',
						preg_replace('@"\{\$(.*?)\}"@','"([^"]*?)"',
							str_replace(array('=','[',']'),array('\=','\[','\]'),$this->preg))))).
			 '@is';		
		
	}
	/**
	 * Распарсить текст в массив:
	 * 
	 * @return array
	 */
	function parse($html){			
		$ret=array();
		//str::print_r($this->preg);
		if (preg_match_all($this->preg, $html, $m)){
			
			foreach ($m[0] as $i=>$text){
				$obj=array();
				
				foreach ($this->markers as $pregKey=>$hashKey){
					$obj[$hashKey]=$m[$pregKey][$i];
				}
				$ret[]=$obj;
			}
		}
		else {
			throw new Exception('document '.$this->url.' does not match preg');
		}
		return $ret;
	}
}
?>