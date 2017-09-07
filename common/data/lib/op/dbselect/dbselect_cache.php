<?
/**
 * Заглушка:
 *
 */
class dbSelect_Cache_Stub extends array_iterator {
	
	function __construct(){
		parent::__construct(null, null);
	}
	
	var $isFound=false;
	
	function set($value){
		$this->res=$value;
		$this->save();
	}
	
	function add($row){
		if (!$this->res) $this->res=array();
		$this->res[]=$row;
	}
	
	function _loadData(){
		return null;
	}
	
	function save(){
		
	}
}
/**
 * Кэширующая прослойка:
 */
class dbSelect_Cache extends dbSelect_Cache_Stub   {
	/**
	 * коннектор к кэшу
	 *
	 * @var Zend_Cache_Core
	 */
	static $provider;
	/**
	 * время кэширования по умолчанию
	 *
	 * @var int
	 */
	static $defaultCacheLifetime;
	/**
	 * максимальное время кэширования
	 *
	 * @var int
	 */
	static $maxCacheLifetime;
	/**
	 * Приконнектиться:
	 * 
	 * @param Zend_Cache_Core $provider	коннектор к кэшу
	 * @param $defaultCacheLifetime		время кэширования по умолчанию
	 * @param $maxCacheLifetime			максимальное время кэширования
	 * 	 	 
	 */
	static function connect($provider, $defaultCacheLifetime=0, $maxCacheLifetime=date::Day){
		self::$provider=$provider;
		self::$defaultCacheLifetime=$defaultCacheLifetime;
		self::$maxCacheLifetime=$maxCacheLifetime;		
	}
	/**
	 * Объект кэша:
	 *
	 * @param string $sql		запрос
	 * @param string $lifetime	время жизни
	 * @param array  $tags		теги
	 * 
	 * @return dbSelect_Cache
	 */
	static function instance($sql, $lifetime=null, $tags=null){
		//Уведомляем системный кэш:
		system_cache::notify($tags);
		//Нет кэширования:
		if (!self::$provider) return new dbSelect_Cache_Stub();
		//Вычисляем время жизни:
		$lifetime=($lifetime===null)?self::$defaultCacheLifetime:min($lifetime, self::$maxCacheLifetime);
		//Кэширование выключено для запроса:
		if (!$lifetime) return new dbSelect_Cache_Stub();
		//Пытаемся получить данные:
		if ($data=self::$provider->load($sql)){
			return new dbSelect_Cache($sql, $lifetime, $tags, $data);
		}
		else {
			return new dbSelect_Cache($sql, $lifetime, $tags);
		}
	}
		
	/**
	 * Запрос:
	 *
	 * @var string
	 */
	var $sql;
	/**
	 * Время жизни:
	 *
	 * @var string
	 */
	var $lifetime=0;
	/**
	 * Приоритет:
	 *
	 * @var int
	 */
	var $priority=5;
	/**
	 * Теги:
	 *
	 * @var array
	 */
	var $tags=null;
	
	var $isFound=false;
	/**
	 * Создать:
	 * 
	 * @var $sql
	 * @var $lifetime
	 * @var $tags
	 * @var $data
	 */
	function __construct($sql, $lifetime, $tags, $data){
		$this->sql=$sql;
		$this->lifetime=$lifetime;
		$this->tags=$tags;
		if ($this->data){
			$this->res=$data;
			$this->isFound=true;
		}
		else {
			$this->isFound=false;
		}		
	}
	/**
	 * Сохранить:
	 *
	 */
	function save(){
		self::$provider->save($value, $this->sql, $this->tags, $this->lifetime, $this->pririty);
	}
}
?>