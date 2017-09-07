<?php
setcookie('APPLICATION_CONTEXT', $geo_context->URL, time()+2592000,'/');

class geo_context {
	/**
	 * Таблица связей:
	 * @var geo_ipgeobase
	 */
	var $geobase;
	/**	 
	 * @param geo_ipgeobase $geobase
	 */
	function __construct($geobase){
		$this->geobase=$geobase;
	}
	/**
	 * Получить контекст
	 * @param $ip
	 * @return application_context
	 */
	function getContext($ip){
		$codes=$this->geobase->getRegion($ip);
		if (!$codes||!count($codes)) return null;
		$sel=application_context::select();
		$sel->Where('GEO_ID',eq,$codes);
		//Предпочитаем вложенные города:
		$sel->OrderBy('PARENT_ID','ASC');
		return $sel->selectObject('application_context');		
	}
}
?>