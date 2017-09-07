<?
/** 
 * Строит имена кэшей на основе выборки:
 * 
 * а) если переданы шаблоны, то исходя из Where в запросе строится по шаблонам.
 * Синтаксис шаблона [.]test_{$param1}_{$param2} - где $param1, $param2 - имя поля, если оно есть во Where заменяется на значение
 * Если в начале шаблона стоит точка, в начало подставляется имя таблицы
 * 
 * б) иначе строятся по правилу имяТаблицы.имяПоля_значениеПоля
 * 
 * Применяется для всех условий на строгое равенство (eq) с цифровыми значениями, или строка из латинских букв и цифр
 * 
 * $sel->tagsProvider= new dbSelect_Tags('.region_{$region}', 'new_calendar.channel_{$channel}');
 * $sel->Where('region',2);
 * $sel->getTags() -> array('news.region_2')
 * 
 * $sel->Where('channel',5);
 * $sel->getTags() -> array('new_calendar.channel_5')
 */
class dbSelect_Tags{
	
	var $tpl=array();	
	/**
	 * Создать:
	 * 
	 * Список пременимых шаблонов	 
	 *
	 */
	function __construct(){
		$a= func_get_args();				
		if (count($a)) $this->tpl= DataStore::normalPath($a, ',', ' ');
		else $this->tpl=true;
		
	}
	/**
	 * Получить теги:
	 * 
	 * @param dbSelect $sel
	 */
	function fromSelect($sel){
		if ($this->tpl===true){
			$ret=array();
		}
		else {
			$find=array();
			$replace=array();
		}
			
		foreach ($sel->Where as $w){
			
			if ($w[1]==eq&&(is_string($w[2])||is_numeric($w[2]))){				
				if ($this->tpl===true){
					
					$ret[]=($w[0]==$sel->primary_key)?$sel->table.'.'.$w[2]:$sel->table.'.'.$w[0].'_'.$w[2];
				}
				else {
					$find[]='{$'.$w[0].'}';
					$replace[]=$w[2];
				}
			}
		}
		
		if ($this->tpl===true) return $ret;
		return $this->replaceTpl($find, $replace);
	}
	/**
	 * При обновлении:
	 * 
	 * @var array 		$Update
	 * @var dbSelect	$sel
	 * 
	 * @return array
	 */
	function fromUpdate($Update, $sel){		
		$ret=$this->fromSelect($sel);
		
		$find=array();
		$replace=array();
		foreach ($Update as $k=>$v){
			if (is_numeric($v)){
				if ($this->tpl===true){
					$ret[]=($k==$sel->primary_key)?$sel->table.'.'.$v:$sel->table.'.'.$k.'_'.$v;
				}
				else {
					$find[]=$k;
					$replace[]=$v;
				}
			}
		}
		
		if ($this->tpl===true) return $ret;
		else return $ret+$this->replaceTpl($find, $replace);
	}
	
	protected function replaceTpl($find, $replace){
		$ret=array();
		foreach ($this->tpl as $tpl){
			$tpl=str_replace($find, $replace, $tpl);			
			if (false===strpos($tpl, '$')){
				if ($tpl[0]=='.') $tpl=$sel->table.$tpl;
				$ret[]=$tpl; 
			}
		}
		return $ret;
	}
}
?>