<?
/**
 * ��������:
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
 * ���������� ���������:
 */
class dbSelect_Cache extends dbSelect_Cache_Stub   {
	/**
	 * ��������� � ����
	 *
	 * @var Zend_Cache_Core
	 */
	static $provider;
	/**
	 * ����� ����������� �� ���������
	 *
	 * @var int
	 */
	static $defaultCacheLifetime;
	/**
	 * ������������ ����� �����������
	 *
	 * @var int
	 */
	static $maxCacheLifetime;
	/**
	 * ���������������:
	 * 
	 * @param Zend_Cache_Core $provider	��������� � ����
	 * @param $defaultCacheLifetime		����� ����������� �� ���������
	 * @param $maxCacheLifetime			������������ ����� �����������
	 * 	 	 
	 */
	static function connect($provider, $defaultCacheLifetime=0, $maxCacheLifetime=date::Day){
		self::$provider=$provider;
		self::$defaultCacheLifetime=$defaultCacheLifetime;
		self::$maxCacheLifetime=$maxCacheLifetime;		
	}
	/**
	 * ������ ����:
	 *
	 * @param string $sql		������
	 * @param string $lifetime	����� �����
	 * @param array  $tags		����
	 * 
	 * @return dbSelect_Cache
	 */
	static function instance($sql, $lifetime=null, $tags=null){
		//���������� ��������� ���:
		system_cache::notify($tags);
		//��� �����������:
		if (!self::$provider) return new dbSelect_Cache_Stub();
		//��������� ����� �����:
		$lifetime=($lifetime===null)?self::$defaultCacheLifetime:min($lifetime, self::$maxCacheLifetime);
		//����������� ��������� ��� �������:
		if (!$lifetime) return new dbSelect_Cache_Stub();
		//�������� �������� ������:
		if ($data=self::$provider->load($sql)){
			return new dbSelect_Cache($sql, $lifetime, $tags, $data);
		}
		else {
			return new dbSelect_Cache($sql, $lifetime, $tags);
		}
	}
		
	/**
	 * ������:
	 *
	 * @var string
	 */
	var $sql;
	/**
	 * ����� �����:
	 *
	 * @var string
	 */
	var $lifetime=0;
	/**
	 * ���������:
	 *
	 * @var int
	 */
	var $priority=5;
	/**
	 * ����:
	 *
	 * @var array
	 */
	var $tags=null;
	
	var $isFound=false;
	/**
	 * �������:
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
	 * ���������:
	 *
	 */
	function save(){
		self::$provider->save($value, $this->sql, $this->tags, $this->lifetime, $this->pririty);
	}
}
?>