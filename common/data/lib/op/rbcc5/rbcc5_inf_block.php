<?
/**
 * Информационные блоки:
 * @author atukmanov
 *
 */
class rbcc5_inf_block extends rbcc5_object {
	
	var $table='INF_BLOCK';
	/**
	 * Построить URL
	 * @return string
	 */
	function buildURL($q=null, $siteID=0){
		list($page, $area_id)=$this->getPageArea($siteID);
		
		if (!$page) return null;
		/*@var rbcc5_page $page*/
		
		$q2=array();
		foreach ($q as $k=>$v) $q2[$k.'_'.$area_id]=$v;
		return $page->getURL().(($q)?'?'.http_build_query($q2):'');
	}
	/**
	 * Получить область:
	 * Кэш областей	 
	 */
	static $pageArea=array();
	/**
	 * Получить pageArea для инфоблока
	 * @param $siteID
	 * @return rbcc5_page_area
	 */
	function getPageArea($siteID=0){
		if (!$siteID) $siteID=self::$env['site_id'];
			
		//Мы кэшируем в static переменной потому-то:
		if (isset(self::$pageArea[$siteID][$this->getID()])) return self::$pageArea[$siteID][$this->getID()];
		//Выбираем область содержащую данный блок в текущей версии:
		$pa= new rbcc5_select('PAGE_AREA');
		$pa->Where('INF_BLOCK_ID',eq,$this->getID());
		$pa->Where('VERSION',eq,(isset(self::$env['version']))?self::$env['version']:1);
		//Область должна быть главной:
		$ta= new rbcc5_select('TEMPLATE_AREA');
		$ta->Where('IS_MAIN',eq,1);
		$pa->naturalJoin($ta,'INNER');
		//Страница должна относиться к текущему сайту:
		$p= new rbcc5_select('PAGE');
		$p->applyEnv(self::$env);		
		if ($siteID){
			$p->Select[]='IF (SITE_ID='.$siteID.',1,0) AS CURRENT_SITE';
			//Если сайт указан- предпочитаем его:
			$p->OrderBy('IF (SITE_ID='.$siteID.',1,0) AS CURRENT_SITE','DESC');
		}
		else {
			//Иначе- ближе к корню:
			$p->OrderBy('SITE_ID','ASC');
		}
		$pa->naturalJoin($p,'INNER');
		
		if ($obj=$pa->selectObject()){
			//Кэшируем:			
			self::$pageArea[$siteID][$this->getID()]=array(rbcc5_object::instance($obj['PAGE'],'PAGE'), $obj['TEMPLATE_AREA_ID']);
		}
		else {
			//Shit happend:
			self::$pageArea[$siteID][$this->getID()]=array(null,0);
		}
		//Ура:
		return self::$pageArea[$siteID][$this->getID()];
	}
	/**
	 * 
	 * @param $systemName
	 * @return rbcc5_inf_block
	 */
	static function getPrimaryBlock($systemName){
		/**
		 * Ищем PRG_MODULE с заданным системным именем:
		 */
		$sel= rbcc5_select::getInstance('PRG_MODULE',0,false);
		$sel->Where('SYSTEM_NAME',eq,$systemName);
		if (!$prgModuleID=$sel->selectString()) return null;
		/**
		 * Ищем инф. блок:
		 */
		$sel= rbcc5_select::getInstance('INF_BLOCK');
		$sel->Where('PRG_MODULE_ID',eq,$prgModuleID);
		foreach ($sel as $obj){
			$ib=new rbcc5_inf_block($obj);
			list ($page, $page_area)=$ib->getPageArea();
			if ($page) return $ib;	
		}
		return null;
		
	}
}
?>
