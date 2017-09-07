<?
class crawler_channer extends DataStore {
	/**
	 * Выполнить:
	 * @return int количество экспортированных элементов
	 */
	function execute(){
		$preg= new crawler_parser($this->preg);
		if (!$html= browser::request($this->url)->response) throw new Exception('Empty page by URL: '.$this->url);
		$i=0;	
		$ret=array();	
		if ($res=$preg->parse($html)){		
			foreach ($res as $obj){
				//Work till stop:
				if ($obj[$this->primaryKey]==$this->lastElement) break;
				//Save record:
				$ret[]=call_user_func($this->export, $obj);
				$i++;
			}
		}
		if ($i){
			//Запоминаем последний обработанный элемент:
			$this->lastElement=$res[0][$this->primaryKey];
		}
		$this->lastUpdate=time();
		$this->lastCount=$i;
		
		return $ret;
	}
}
?>