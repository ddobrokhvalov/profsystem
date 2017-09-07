<?php
include_once('Cache/Lite.php');
class dbselect_lite_cache {//extends Cache_Lite{
		/**
		 * Провайдер:
		 *
		 * @var dbselect_lite_cache
		 */
		var $Provider=null;
		/**
		 * Настройки:
		 *
		 * @var array()
		 */
		var $Settings=null;
		function __construct($Settings, &$Provider=null){
			
			parent::Cache_Lite($Settings);
			$this->Settings=$Settings;
			$this->Provider=$Provider;
		}
		/**
		 * Вернуть экземпляр:
		 * 
		 * @var int $LifeTime	время жизни
		 * @return dbselect_lite_cache
		 */
		function getInstance($LifeTime){
			if ($this->Settings) return null;
			$settings=$this->Settings;
			$settings['lifeTime']=$LifeTime;
			return new dbselect_lite_cache($settings, $this);
		}
		/**
		 * Удалить именованные запросы:
		 * 
		 * @var $Name
		 */
		function remove($Name){

			if ($res=$this->get(md5($Name))){
				
				$count=count($res);
				for ($i=0; $i<$count; $i++){										
					$this->remove($res[$i]);
				}				
			}			
		}
		
		var $id=null;
		/**
		 * Имя кэша
		 *
		 * @var array
		 */
		var $Name=array();
		/**
		 * Данные:
		 *
		 * @var array
		 */		
		var $res=array();
		/**
		 * Загрузить кэш:
		 *
		 * @param string $id
		 * @return boolean
		 */
		function load($id){		
			$this->id=md5($id);			
			if ($this->res=$this->get($this->id)){				
				return true;				
			}
			else {
				return false;
			}
		}
		
		var $counter=0;
		/**
		 * След. строка:
		 */
		function fetchRow(){
			return $this->res[$this->counter++];
		}
		/**
		 * Очистить:
		 *
		 */
		function free(){
			$this->counter=0;
			unset($this->res);
		}
				
		function add($row){
			$this->res[$this->counter++]=$row;
		}
		/**
		 * Сохранить:
		 *
		 */
		function store($res=null){
			
			$res=$this->save(($res)?$res:$this->res, $this->id);			
			if ($this->Provider){
				$this->Provider->setReferences($this->id, $this->Name);
			}
		}
		/**
		 * Выставить ссылки:
		 */
		protected function setReferences($id, $Names){
			$count=count($Names);
			for ($i=0; $i<$count; $i++){
				$nid=md5($Names[$i]);
				if ($Reference=$this->get($nid)){
					if (!in_array($id, $Reference)) $Reference[]=$id;
				}
				else {
					$Reference=array($id);
				}				
				$this->save($Reference, $nid);
			}
		}
		/**
		 * Количество записей
		 *
		 * @return int
		 */
		function numRows(){
			return count($this->res);
		}
}


?>