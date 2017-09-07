<?php
class crawler_html {
	var $responseLang='ru';
	var $url=null;
	var $pattern=null;
	/**
	 * �������	 
	 */
	function __construct($url, $pattern){
		$this->url=$url;
		$this->preg=$pattern;
	}
	
	var $html;
	
	const error404=404;
	const errorPreg=500;
	/**
	 * ������� ��� ������ �� ��������������� �������:
	 * @param $while
	 * @return unknown_type
	 */
	function select($transform){
		
		if (!$html=$this->html) throw new Exception('Empty html');
		
		list($preg, $assoc)= $this->getPreg();	
		str::print_r($preg, $assoc);	
		$ret=array();
		if (preg_match_all($preg, $html, $m)){
			
			foreach ($m[0] as $i=>$text){
				$obj=array();
				
				foreach ($assoc as $pregKey=>$hashKey){
					$obj[$hashKey]=$m[$pregKey][$i];
				}
				if ($o=$transform->validate($obj)){					
					$ret[]=$o;
				}
			}
		}
		else {
			throw new Exception('document '.$this->url.' does not match preg',self::errorPreg);
		}
		return $ret;
	}
	
	protected function getPreg(){
		
		if (!preg_match_all('@\{\$(.*?)\}@',$this->preg, $m)){
			throw new Exception('preg is empty', self::errorPreg);
		}
		
		$i=1;
		foreach ($m[1] as $value){			
			if ($value){
				$assoc[$i]=$value;
			}
			$i++;
		}
		
		$preg='@'.
		preg_replace('@\s+@','\s*',
			preg_replace('@\{\$(.*?)\}@','(.*?)',
					preg_replace('@"\{\$(.*?)\}"@','"([^"]*?)"',
						str_replace('=','\=',$this->preg)))).
			 '@is';		
		return array($preg, $assoc);
	}
	/**
	 * 
	 * @return text
	 */
	function getBody($html, $openTag){
		//��������� �� ����������� �����:
		$pos=strpos($html,$openTag);
		if ($pos===false) return '';
		preg_match('<(\w+?) ',$openTag,$m);
		$tagName=$m[1];
		$text=substr($html, $pos);
			
	}
}
?>