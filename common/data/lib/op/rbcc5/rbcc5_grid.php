<?php
/**
 * Табличко:
 * @author atukmanov
 *
 */
class rbcc5_grid extends DataStore{
	/**
	 * Выборка
	 * @var dbselect
	 */
	var $sel;
	/**
	 * Создать
	 * @param $sel
	 * @return
	 */
	function __construct($sel, $settings=null){
		$this->sel=$sel;
		parent::__construct($settings);
	}
	/**
	 * Выполнить:
	 * @return string
	 */
	function execute($q){		
	}
	
	protected function _order($q){
		if (isset($q['order'])&&($defaultOrderDir=$this->getInfo('allowedOrders', $q['order']))){
			if (!isset($q['orderDir'])||!in_array($q['orderDir'], array('ASC','DESC'))) $q['orderDir']
			$this->sel->OrderBy($q['order'])
		}
		$this->sel->OrderBy($this->orderBy, $this->orderDir);
	}
}
?>