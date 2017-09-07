<?
class servlet_module extends module {
	/**
	 * (non-PHPdoc)
	 * @see common/data/module/module/module#content_init()
	 */
	function content_init(){
		list($this->info['requestUri'])=explode($_SERVER['REQUEST_URI'],'?',2);
		$this->info['url']=new servlet_module_url('_'.$this->env['area_id'], $this->q_param, $this->info['request_uri']);
		if ($this->q_param['do']){
			$method='do'.$this->q_param['do'];
			if (method_exists($this, $method)) return $this->$method();
		}
		$this->main();
	}
	/**
	 * Сборник данных
	 * @var array
	 */
	var $info=array();
	
	const arr='array';
	const str='str';
	const int='/^[1-9]\d*$/';
	/**
	 * Запрос
	 * 
	 * @param $key
	 * @param $default
	 * @param $preg
	 * @return string
	 */
	function request($key,$validate=self::str,$default=null){
		if (isset($this->q_param[$key])){
			switch ($validate){
				case self::arr:
					return (is_array($this->q_param[$key]))?$this->q_param[$key]:$default;
				break;
				case self::str:
					return (is_string($this->q_param[$key]))?$this->q_param[$key]:$default;
				break;
				default:					
					return (is_string($this->q_param[$key])&&preg_match($validate,$this->q_param[$key]))?$this->q_param[$key]:$default;
				break;
			}
		}
		else return $default;
	}
	/**
	 * Число:
	 * @param $key
	 * @return unknown_type
	 */
	function requestInt($key, $default=0){
		return $this->request($key,self::int,$default);
	}	
	/**
	 * Отрендерить:
	 * 
	 * @param $tpl_file
	 * @param $assign
	 * @return unknown_type
	 */
	function render($tpl_file, $assign=array()){
		$tpl = new smarty_ee_module($this);		
		foreach ($assign as $key=>$value){
			$tpl->assign($key, $value);
		}		
		foreach ($this->info as $key=>$value){
			$tpl->assign($key, $value);
		}
		$this->body=$tpl->fetch($this -> tpl_dir.$tpl_file.'.tpl');
	}
	/**
	 * Сцылко
	 * @param $params
	 * @return string
	 */
	function getLink($params){
		
	}
}

class servlet_module_url {
	
	var $query=null;
	var $tpl=null;
	var $url=null;
	/**
	 * URL
	 * @param $q
	 * @param $tpl
	 * @return unknown_type
	 */
	function __construct($tpl, $query=array(), $url=''){
		if (is_array($query)){
			foreach ($query as $k=>$v) $this->query[$k.$tpl]=$v;
		}
		$this->tpl=$tpl;
		$this->url=$url;
	}
	/**
	 * 
	 * @param $query
	 * @return string
	 */
	function getLink(){
		$q=$this->query;
		$a=func_get_args();
		$count=count($a);
		for ($i=0; $i<$count;$i++){
			if ($i%2) $q[$a[$i-1].$this->tpl]=$a[$i];
		}
		return $this->url.'?'.http_build_query($q); 
	}
	
	function __toString(){
		return $this->getLink();
	}
}
?>