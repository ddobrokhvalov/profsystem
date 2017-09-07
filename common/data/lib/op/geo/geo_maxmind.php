<?php
require_once "Net/GeoIP.php";
/**
 * Работа с geoIP MaxMind
 * @author atukmanov@rbcsoft.ru
 *
 */
class geo_maxmind {
	
	var $geoip=null;
	/**
	 * 
	 * @param $file
	 * @return unknown_type
	 */
	function __construct($file){
		$this->geoip = Net_GeoIP::getInstance($file);
		str::print_r($this->geoip);
	}
	/**
	 * Получить регион из ip
	 * @param $ip
	 * @return array
	 */
	function getRegion($ip){
		str::print_r($ip, $this->geoip);
		if (!$this->geoip) return array();
		if ($location=$this->geoip->lookupLocation($ip)){
			str::print_r('location', $location);
			return array($location->city, $location->region);
		}
		else {
			str::print_r('WTF?');
		}
	}
}
?>