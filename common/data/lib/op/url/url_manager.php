<?php
class url_manager extends dbselect{
	const Code_EmptyUrl=1;
	const Code_LimitExceed=2;
	var $MaxLength=20;
	/**
	 * Менеджер URL
	 *
	 * @param string $Table
	 * @param string $UrlKey	поле для URL
	 * @param string $MaxLength	максимальная длина
	 * @return url_manager
	 */
	function url_manager($Table, $UrlKey='Url', $MaxLength=20){
		parent::__construct($UrlKey,$Table);
		$this->primary_key=$UrlKey;
		$this->MaxLength=$MaxLength;
	}
	/**
	 * Из селекта
	 *
	 * @param dbselect $sel
	 * @param string $UrlKey
	 * @param int $MaxLength
	 * 
	 * @return url_manager
	 */
	static function FromSelect($sel, $UrlKey='Url',$MaxLength=20){		
		$ret=new url_manager($sel->table,$UrlKey,$MaxLength);
		$ret->Where=$sel->Where;
		return $ret;
	}
	/**
	 * Проверка URL:
	 * URL может состоять из цифр, латинских букв и '-', при этом должен начинаться строго с буквы, а заканчиваться строго буквой или цифрой
	 * и содержать минимум 2 символа
	 * 
	 * @param string $MaxLength
	 * @return string
	 */
	static function getPreg($MaxLength=20){
		return '/^[a-z][a-z0-9\-]{0,'.($MaxLength-2).'}[a-z0-9]$/';
	}
	/**
	 * Получить URL
	 *
	 * @param string $url
	 */
	function getURL($url){		
		$url=str::StrongString($url, $this->MaxLength);		
		if (!$url){
			throw new Exception('EmptyUrl', url_manager::Code_EmptyUrl);
		}				
		$this->Where($this->primary_key, eq, $url);		
		if (!$this->SelectObject()){
			return $url;
		}
		$i=0;
		//Подставляем циферку в конец:
		while(strlen($i)<$this->MaxLength){
			$Substr=strlen($i)+strlen($url)-$this->MaxLength;
			$ret=(($Substr>0)?substr($url,0,-$Substr):$url).$i;
			$this->dropWhere();			
			$this->Where($this->primary_key,eq,$ret);					
			if (!$this->SelectCount()){				
				return $ret;
			}
			$i++;
		}
		
		//Кончились урлы (должна остаться хоть одна буковка):
		throw new Exception('Url length limit exceed', self::Code_LimitExceed);
	}
}
?>