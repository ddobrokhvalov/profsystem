<?php
class url extends dbselect {
	
	var $MaxLength=250;
	/**
	 * Конструктор
	 *
	 * @param string $table
	 * @param string $key
	 * @param int $MaxLength
	 */
	function __construct($table, $key='Url', $MaxLength=250){
		parent::__construct(array($key),$table);
		$this->MaxLength=$MaxLength;
	}
	/**
	 * Получить URL по имени
	 *
	 * @param string $Url
	 */
	function getUrl($Url){
		$Url=str::StrongString($Url, $this->MaxLength);
		$wh=$this->Where($this->primary_key, eq, $Url);
		if (!$this->SelectString()) return $Url;
		$i=1;
		while(true){
			$Substr=strlen($Url)+strlen($i)-$this->MaxLength;			
			if ($Substr>0) $Url=substr($Url, 0, -$Substr);
			if (strlen($Url)==1) throw new Exception('UrlLimitExceed');
			$this->dropWhere();
			$this->Where($this->primary_key, eq, $Url.$i);
			if (!$this->SelectObject()) return $Url.$i;
		}
	}
}
?>