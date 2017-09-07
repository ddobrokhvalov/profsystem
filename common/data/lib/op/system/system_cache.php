<?php
/**
 * Полная интеграция кэшэй:
 * 
 */
class system_cache{
	/**
	 * Уничтожить:
	 *
	 * @param string $Name
	 */
	static function Destruct(){	
		$a= func_get_args();
		$Names=DataStore::Argv2Path($a);	
		/**
		 * Удаляем данные в кэше:
		 */		
		/**
		 * Удаляем страницы:
		 */
		foreach ($a as $Name) {
			servlet_cache::Destruct($Name);
		}					
	}
	
	static $providers=array();
	static $currentID=0;
	/**
	 * Добавить провайдера:
	 *
	 */
	static function registerProvider($provider, $id=0){
		if (!$id){
			$id=self::$currentID;
			self::$currentID++;
		}
				
		self::$providers[$id]=$provider;
	}
	/**
	 * Получить провайдера по id:
	 * 
	 * @return Zend_Cache_Backend_Memcached
	 */
	static function getProvider($id){
		if (isset(self::$providers[$id])) return self::$providers[$id];
		else return null;
	}
	/**
	 * Вычистить по тегу:
	 */
	static function removeCacheByTags($tags){
		foreach (self::$providers as $provider){
			/*@var $provider Zend_Cache_Backend_Memcached*/
			$provider->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $tags);
		}
	}	
	
	static $usedTags=null;
	/**
	 * Уведомить о тегах:
	 */
	static function notify($tags){
		
	}
		
}

/**
 * Ф-я уведомления об использованных тегах:
 * 
 * Позволяет производить внутри блока вычисления, а потом кэшировать результат
 * 
$p= system_cache::getProvider('blocks');
if ($ret=$p->get($newsID)){
	echo $ret;
}
else {
	$t= system_cache_tags::start();
	//Тут мы делаем вычисления (разные):
	$news= news_viewer::loadByID($newsID);
	$channel= news_channel_reader::loadByID($news->channel);
	$tree= $comments=$news->getCommentsTree();
	$sameday=$channel->selectByDate($news->getInfo('ts.Y'),$news->getInfo('ts.m'),$news->getInfo('ts.d'));
	ob_start();
	$this->render('newsPage');
	$str=ob_get_flush();
	
	$p->save($str,$newsID,$t->getTags());
	
}
 */
class system_cache_tags {
	
	static $usedTags=array();
	static $filter;
	
	/**
	 * Добавить теги к использованным:
	 */
	static function notify($tags){				
		array_merge(self::$usedtags, array_sort::unique(DataStore::inline_array($tags)));
	}
	/**
	 * Запустить:
	 * 
	 * @return system_cache_tags
	 */
	static function start(){
		return new system_cache_tags(self::$usedTags);	
	}
	
	var $start=0;
	var $stop=0;
	
	function __construct($start=0){
		$this->start=$start;
	}
	/**
	 * Остановить:
	 */
	function stop(){
		$this->stop=count(self::$usedTags);
	}
	/**
	 * Получить теги:
	 * 
	 * @return array
	 */
	function getTags(){
		if (!$this->stop) $this->stop();
		return array_sort::unique(array_slice(self::$usedTags, $this->start, $this->stop-$this->start));
	}
}
?>