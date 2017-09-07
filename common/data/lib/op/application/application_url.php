<?
/**
 * Обработка URL:
 * 
 * Позволяет осуществлят rewrite URL, построение относительных URL, сборку запросов с учетом префиксов и постфиксов полей
 */
class application_url {
	/**
	 * Объекты:
	 * @var array
	 */
	protected static $_instances=array();
	/**
	 * Базовый URL:
	 * @var array
	 */
	var $url=array();
	/**
	 * Запрос переданный в URL:
	 * @var array
	 */
	protected $action=array();
	/**
	 * Шаблон полей запроса:
	 * @var array
	 */
	protected $queryTemplate=null;
	
	var $ext;
	/**
	 * URL:
	 * @param $url
	 * @param $action
	 * @param $queryTemplate	шаблон для GET параметров, prefix{$}postfix
	 * @return unknown_type
	 */
	function __construct($url, $action=array(), $queryTemplate=null){
		$this->url=self::explode($url);
		$this->action=self::explode($action);
		list($this->action[count($this->action)-1],$this->ext)=explode('.',$this->action[count($this->action)-1]);
		//перезапись GET:
		if ($queryTemplate){
			$this->queryTemplate=(is_array($queryTemplate))?$queryTemplate:explode('{$}',$queryTemplate);			
		}
		//запишем для сброса кэша при rewrite:
		self::$_instances[]=$this;
	}
	/**
	 * Разбить URL в массив:
	 * @param $url
	 * @return array
	 */
	public static function explode($url){
		if (is_array($url)) return $url;
		return explode('/',trim($url,'/'));
	}
	/**
	 * Правила перезаписи URL:
	 * @var array
	 */
	static $rewriteURL=array();
	/**
	 * Правила перезаписи запроса:
	 * @var array
	 */
	protected static $rewriteQuery=array();
	/**
	 * Назначить правила перезаписи:
	 * @param string $url		правила для URL: могут иметь вид 'previx{$url}postfix'
	 * 							или в виде массива, тогда элемент **подставляется**:
	 * 							array(1=>'msk') -заменить второй элемент, т.е. /ru/office/bankomat/ -> /ru/msk/office/bankomat/
	 * 							-1 -ключ для записи в конец: (-1=>'rss.xml') ->/ru/office/bankomat/ -> /ru/office/bankomat/rss.xml
	 * @param array  $query		параметры, которые будут дописываться ко всем URL
	 * 							удобно для подсветки поиска:
	 * 							application_url::rewrite(null, array('search'=>$search));
	 * 							foreach ($result as $obj) echo $obj->card();
	 * @return void
	 */
	static function rewrite($url=null, $query=null){
		
		if (!$url){
			self::$rewriteURL=null;	
		}
		elseif (is_string($url)){
			//previx{$url}postfix
			list(self::$rewriteURL[0],self::$rewriteURL[-1])=explode('{$url}',$url);
		}
		else {
			//явно задано: 
			self::$rewriteURL=$url;
		}
		
		self::$rewriteQuery=$query;		
	}
	/**
	 * Получить перезаписанный URL:
	 * @param string	$url
	 * @param boolean	$ignorePostfix	игнорировать постфикс
	 * @return string
	 */
	static function rewriteURL($url, $rewriteURL=null, $ignorePostfix=false, $debug=false){
		//не переписываем внешние ссылки:
		if (is_string($url)&&preg_match('@^(\w+)://@',$url)) return $url;
		// /common/ не реврайтить
		if (is_string($url)&&preg_match('@^/common/@', $url)) return $url;
		if (!$rewriteURL) $rewriteURL=self::$rewriteURL;
			
		$arr=self::explode($url);
		if (isset($rewriteURL[0])){
			$ret=$rewriteURL[0];
			$i=1;
		}
		else {
			$ret='/';
			$i=0;
		}
		foreach ($arr as $part){
			if (isset($rewriteURL[$i])){
				//есть участок для перезаписи:				
				$ret.=$rewriteURL[$i].'/';
				$i++;
			}			
			if (false==strpos($part,'.')){
				//file.ext
				$ret.=$part.'/';
			}
			else {
				//dir:
				$ret.=$part;
			}
			$i++;
		}
		if (isset($rewriteURL[$i])) $ret.=$rewriteURL[$i].'/';
		if (!$ignorePostfix&&isset($rewriteURL[-1])){
			$ret.=$rewriteURL[-1];
		}
		if ($debug) str::print_r(__FUNCTION__,$url,self::$rewriteURL,$arr);
		return $ret;
	}
	
	/**
	 * Получить GET запрос
	 * @param $query
	 * @return string
	 */
	static function buildQuery($query){
		if (!$query&&!self::$rewriteQuery) return '';
		$arr=array_merge(($query)?$query:array(),(self::$rewriteQuery)?self::$rewriteQuery:array());
		ksort($arr);
		return http_build_query($arr);
	}
	/**
	 * Получить запрос:
	 * @param $query
	 * @return string
	 */
	function getQuery($query){		
		if (!$query||!$this->queryTemplate){
			return self::buildQuery($query);
		}
		$arr=array();
		foreach ($query as $k=>$v){
			$arr[$this->queryTemplate[0].$k.$this->queryTemplate[1]]=$v;
		}
		return self::buildQuery($arr);
	}
	
	function getURL($url,$query=null){		
		$ret=self::rewriteURL(array_merge($this->url,self::explode($url)));		
		if ($http_query=$this->getQuery($query)){
			return $ret.((strpos($ret,'?')!==false)?'&':'?').$http_query;
		}
		return $ret;	
	}
	/**
	 * Получение действий:	 
	 */
	const dir='/';
	const notdir='^/';
	/**
	 * Получить действие:
	 * @param $i
	 * @param $type
	 * @return unknown_type
	 */
	function getAction($i, $type=null){
		if (!isset($this->action[$i])) return null;
		//Расширение не совпадает:
		if (!$type){
			return $this->action[$i];
		}
		elseif ($i<count($this->action)-1){
			//Промежуточный элемент имеет тип /
			if ($type==self::dir) return $this->action[$i];
			else return null;
		}
		else {
			//1. Нет расширения и требуется чтобы его небыло:
			if ($type==self::dir&&!$this->ext) return $this->action[$i];
			//2. Требуется любое расширение и оно есть:
			elseif ($type==self::notdir&&$this->ext) return $this->action[$i];
			//3. Требуется конкретное расширение, оно и есть:
			elseif ($type==$this->ext) return $this->action[$i];
			//4. PROFIT
			else return null;
		}				
	}
	
	function getExt(){
		return ($this->ext)?$this->ext:self::dir;	
	}
	/**
	 * Получить часть URL
	 * @param $url
	 * @param $offset
	 * @param $limit
	 * @return unknown_type
	 */
	static function getUrlPart($url,$offset,$limit=null){
		return implode('/', ($limit)?array_slice(self::explode($url),$offset,$limit):array_slice(self::explode($url),$offset));
	}
	/**
	 * Служебная: разбить URL
	 * @param $url
	 * @return array(директория, файл)
	 */
	static function splitURL($url){
		$pi=pathinfo($url);
		
		return array(
			0=>($pi['dirname']=='/'||$pi['dirname']=='.')?null:$pi['dirname'],
			1=>$pi['basename']
		);	
	}
	/**
	 * Получить URL без rewrite:
	 * @todo поддержка self::$rewriteURL[-1], self::$rewriteQuery
	 * @return string
	 */
	static function unrewrite($url){
		$arr=self::explode($url);
		$ret='';
		foreach ($arr as $i=>$obj){
			if (isset(self::$rewriteURL[$i])) continue;
			$ret.='/'.$obj;
		}
		return $ret;
	}
}
?>