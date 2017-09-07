<?
class array_sort{
	/**
	 * Поле
	 *
	 * @var string
	 */
	var $Field;
	/**
	 * Метод:
	 *
	 * @var unknown_type
	 */
	var $Method;
	
	const ASC=1;
	const DESC=-1;
	function __construct($Field=null, $Method=self::ASC){
		$this->Field=$Field;
		$this->Method=$Method;
	}
	
	function cmp($a,$b){
		$_a=($this->Field)?$a[$this->Field]:$a;
		$_b=($this->Field)?$b[$this->Field]:$b;
		
		if($_a==$_b) return 0;
		$ret=(($_a>$_b)?1:-1)*$this->Method;		
		return $ret;
	}
	
	function sort(&$arr, $Field, $Method){
		return usort($arr, array(new array_sort($Field, $Method),'cmp'));
	}	
	/**
	 * Оставить только уникальные:
	 *
	 * @var array 	$arr	массив
	 * @var string 	$hash	хэш функция 		
	 */
	static function unique($arr, $hash=null){
		if (!$arr) return array();
		$exists=array();
		$ret=array();
		
		foreach ($arr as $obj){
			if ($hash){
				$_o=$obj[$hash];
				if (in_array($_o, $exists)) continue;
				$exists[]=$_o;
			}
			else{
				if (is_array($obj, $ret)) continue;
			}
			$ret[]=$obj;
		}
		
		return $ret;
	}	
}
?>