<?php
class array_tree {
	/**
	 * Построить линейное дерево:
	 * @param array $tree
	 * @param int	$rootID
	 * @param int	$level
	 * @return array
	 */
	static function buildInlineTree($tree, $checked=array(), $primaryKey='id', $rootID=0, $level=0, $ret=array()){
				
		foreach ($tree[$rootID] as $obj){
			$obj['level']=$level;					
			$id=$obj[$primaryKey];
			if (!$id) continue;
			$obj['id']=$id;
			$obj['checked']=in_array($id, $checked);
			$obj['hasChilds']=isset($tree[$id]);						
			$ret[$obj['id']]=$obj;
			if ($obj['hasChilds']){								
				$ret=self::buildInlineTree($tree, $checked, $primaryKey, $id, $level+1, $ret);
			}
		}
		return $ret;
	}	
}
?>