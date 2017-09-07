<?php
class str_parser {
	
	protected $str;
	/**
	 * Создать:
	 * @param $str	 
	 */
	function __construct($str){
		$this->str=$str;
	}
	/**
	 * Обрезать
	 * @return 
	 */
	function cut($cutText='Еще',$aClass='jsLabel', $txtClass='underCut'){
		$preg='/==+(.*?)==+/';
		$hash=md5($this->str);
		if (preg_match_all($preg,$this->str,$m)){

			$parts=preg_split($preg, $this->str);
			$cut='';
			
			$count= count($parts);			
			for ($i=0; $i<$count; $i++){
				if ($i%2==0){					
					$cut.=$parts[$i];
				}
				else {
					$cut.='<p><a href="#cut_'.$hash.'_'.$i.'" name="cut_'.$hash.'_'.$i.'" rel="cut_'.$hash.'_'.$i.'" class="'.$aClass.'" onclick="op.lbl(this);" >'.(($m[1][$i-1])?$m[1][$i-1]:$cutText).'</a></p>'.PHP_EOL.'<div class="'.$txtClass.'" id="cut_'.$hash.'_'.$i.'">'.$parts[$i].'</div>';
				}				
			}
			$this->str=$cut;
		}
		
	}
	
	function __toString(){
		return $this->str;
	}
}
?>