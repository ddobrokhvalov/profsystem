<?php
class array_util {
	/**
	 * Выбрать поле из массива:
	 * @param array	 $arr	массив
	 * @param string $field	ключ
	 * @return array
	 */
	static function selectDistict($arr, $field){
		$ret=array();
		foreach ($arr as $obj){
			if (!in_array($obj[$field], $ret)) $ret[]=$obj[$field]; 
		}
		return $ret;
	}
	/**	 
	 * @param $res
	 * @param $groupBy
	 * @param $dict
	 * @return unknown_type
	 */
	static function groupJoin($res, $groupBy, $joinKey, $dict){
		$ret=array();
		foreach ($res as $obj){
			if (!isset($ret[$obj[$groupBy]])) $ret[$obj[$groupBy]]=array();
			$ret[$obj[$groupBy]][]=$dict[$obj[$joinKey]];
		}
		return $ret;
	}
}
?>