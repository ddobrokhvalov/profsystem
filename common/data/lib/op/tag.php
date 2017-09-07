<?
class tag extends DataStore {
	static function select(){
		$a= func_get_args();
		return dbselect::factory(array('id','url','value'),'tags',null,$a);		
	}
	/**
	 * �������:
	 * @param $tag
	 * @return tag
	 */	
	static function instance($tag){
		$tag= str::lower($tag);
		if ($ret=self::select('value',$tag)->selectObject(__CLASS__)) return $ret;
		$ret= new tag();
		$ret->Info['value']=$tag;
		$ret->Info['url']=url_manager::FromSelect(self::select())->getURL($tag);
		$ret->Info['id']=kernel::getNextID(__CLASS__);
		
		self::select()->Insert($ret->Info);
		return $ret;
	}
	/**
	 * �� id
	 * @param $id
	 * @return tag
	 */
	static function loadByID($id){
		return self::select('id',$id)->selectObject(__CLASS__);
	}
	/**
	 * Тег URL
	 * @param $url
	 * @return tag
	 */
	static function loadByURL($url){
		return self::select('url',$url)->selectObject(__CLASS__);
	}
}

class tag_set extends array_iterator {
	static function fromString($tags){
		$arr=explode(',', $tags);
		foreach ($arr as $i=>$o) $arr[$i]=trim($o);
		$sel= tag::select();
		$sel->Where('value',$arr);
		$ret= new tag_set();
		$found=array();
		foreach ($sel as $o){
			$ret->res[$o['id']]=new tag($o);
			$found[]=$o['value'];
		}
		foreach ($arr as $o){
			if (in_array($o['value'], $found)) continue;
			$t= tag::instance($o);
			$ret->res[$t->id]=$t;
		}
		return $ret;
	}	
}

class tag_set_diff {
	
	var $new=array();
	var $old=array();
	var $create=array();
	var $delete=array();
	/**
	 * Разница двух наборов:
	 * @param $old
	 * @param $new
	 * @return unknown_type
	 */
	function __construct($new, $old=null){
		
		$this->old=array();
		if ($old){
			foreach ($old as $obj){
				if ($id=DataStore::toInt($obj)) $this->old[]=$id;
			}
		}
		
		$this->new=array();
		if ($new){
			foreach ($new as $obj){					
				if ($id=DataStore::toInt($obj)){					
					$this->new[]=$id;
					if (!in_array($id, $this->old)) $this->create[]=$id;
				}
			}
		}
		
		foreach ($this->old as $id){
			if (!in_array($id, $this->new)) $this->delete[]=$id;
		}		
	}
	/**
	 * Выборка
	 * @param dbSelect  $sel
	 * @param string 	$key
	 * @return int
	 */	
	function save($sel=null, $key='tag'){		
		$insert=dbselect_insert::fromSelect($sel);
		
		foreach ($this->create as $tag){
			$insert->Insert(array($key=>$tag));
		}		
		$insert->Commit();
		if (count($this->delete)){
			$sel->Where($key,eq,$this->delete);
			$sel->Delete();
		}
	}
	
	function saveCloud($sel, $key='tag', $incrementKey='total'){
		if (count($this->create)){
			$i= dbselect_insert::fromSelect($sel);
			$i->IncrementUsingWhere($this->create, $key, $incrementKey, 1);
		}
		if (count($this->delete)){
			$i= dbselect_insert::fromSelect($sel);
			$i->IncrementUsingWhere($this->delete, $key, $incrementKey, -1);
		}
	}
	/**
	 * 
	 * @param $sel
	 * @param $incrementKey
	 * @return unknown_type
	 */
	function saveComposition($sel, $incrementKey='total'){
		$i= dbselect_insert::fromSelect($sel);
		$items=array();
		foreach ($this->create as $tag1){
			foreach ($this->new as $tag2){
				if ($tag1!=$tag2){
					$items[]=array('tag1'=>$tag1, 'tag2'=>$tag2);
				}
			}
		}
		if (count($items)){
			$i->Increment($items, $incrementKey, 1);
		}
		
		$items=array();
		foreach ($this->delete as $tag1){
			foreach ($this->old as $tag2){
				if ($tag1!=$tag2){
					$items[]=array('tag1'=>$tag1, 'tag2'=>$tag2);
				}
			}
		}
		if (count($items)){
			$i->Increment($items, $incrementKey, -1);
		}
	}
} 
?>