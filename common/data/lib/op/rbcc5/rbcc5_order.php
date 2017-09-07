<?
class rbcc5_order extends DataStore {
	/**
	 * Сортировка по умолцания:
	 * @var string
	 */
	var $defaultOrder=null;
	/**
	 * Сортирка:
	 * @var string
	 */
	var $defaultOrderDir='ASC';
	/**
	 * Поля для сортироки:
	 * @var string
	 */
	var $orders=array();
	/**
	 * Из запроса:
	 * @param $sel
	 * @return rbcc5_order
	 */
	function __construct($orders, $defaultOrder){
		$this->orders=$orders;
		$this->defaultOrder=$defaultOrder;		
	}
	
	const preg='@^(ASC|DESC)$@';
	/**
	 * Сортировка по полю:
	 * @var $order
	 * @var $dir
	 * @return boolean
	 */
	function orderBy($order, $dir){
		if (!$order){
			$this->Info['order']=$this->defaultOrder;
			$this->Info['dir']=($dir)?$dir:$this->defaultOrderDir;			
			return true;
		}
		elseif (isset($this->orders[$order])){
			$this->Info['order']=$order;
			$this->Info['dir']=($dir)?$dir:$this->orders[$order];
			return true;
		}
		else return false;
	}
	
	
	
	var $href='';
	/**
	 * Выставить ссылку:
	 * @param $href
	 * @return void
	 */
	function setHref($href){
		$this->href=$href;
	}
	
	function getHref($order, $dir){
		if (is_object($this->href)){
			return $this->href->buildLink(array('order'=>$order, 'dir'=>$dir));
		}
		return str_replace(array('{$order}','{$dir}'),array($order, $dir),$this->href);
	}
	
	protected function revert($dir){
		return ($dir=='DESC')?'ASC':'DESC';
	}
	/**
	 * Получить ссылку для сортироки:
	 * @return unknown_type
	 */
	function getOrderLink($order){
		if ($this->order==$order){
			$ret=array(
				'dir'=>$this->revert($this->dir),
				'selected'=>true,
			);
		}
		elseif ($order==$this->defaultOrder) {
			$ret=array(
				'dir'=>$this->defaultOrderDir,
				'selected'=>false,
			);
		}
		elseif (isset($this->orders[$order])){
			$ret=array(
				'dir'=>$this->orders[$order],
				'selected'=>false,
			);
		}
		else return null;
		$ret['href']=$this->getHref($order,$ret['dir']);
		return $ret;
	}
	
	function _getInfo($path){
		if ($ret=parent::_getInfo($path)) return $ret;
		return $this->getOrderLink($path[0]);
	}
}

?>