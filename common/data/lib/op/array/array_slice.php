<?php
class array_slice {
	/**
	 * Вырезать участок размером $length вокруг элемента $key
	 *
	 * @param array 	$array	массив
	 * @param string 	$key	ключ
	 * @param int		$length	длина
	 */
	static function slice(&$array, $key, $bounds){
		$ak= array_keys($array);		
		$pos=array_search($key, $ak);
				
		$left=max(0, $pos-floor($bounds/2));				
		$right=min(count($ak), $left+$bounds);
		if ($left) $left=max(0, $left-$bounds+($right-$left));		
		$ret=array();
		for ($i=$left; $i<$right; $i++){$ret[$ak[$i]]=$array[$ak[$i]];}
		
		return $ret;
	}
}
?>