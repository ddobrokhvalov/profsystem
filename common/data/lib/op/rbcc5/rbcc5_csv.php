<?php
/**
 * Класс для работы с CSV:
 * @author atukmanov
 *
 */
class rbcc5_csv extends rbcc5_select {
	var $info=array();
	/**
	 * Выставить общие данные:
	 * @param $info	 
	 */
	function setInfo($info){
		$this->info=$info;
	}
	
	var $errors=array();
	/**
	 * Выполнить csv файл
	 * @param int $fileName
	 * @param boolean $dropIndex
	 * @param string $method
	 * @return int
	 */
	function execute($fileName, $dropIndex=true, $method='REPLACE'){
		if (!$this->open($fileName)){
			return false;
		}
		if (!$this->prepareHeader()){
			return false;
		}
		
		$lineNum=2;
		foreach ($this->info as $k=>$v){
			$this->Where($k,eq,$field);
		}
		
		$ins= dbselect_insert::fromSelect($this);
		
		while ($line=$this->readLine()){
			$obj=$this->info;
			/**
			 * Проходимся по колонкам:
			 */
			foreach ($this->cols as $i=>$field){
				if ($field['type']=='select2'){
					/**
					 * Для select2 поля ищем в БД					 
					 */					
					$sel= rbcc5_select::getInstance($field['fk_table'], rbcc5_select::skipBlocks, false);
					$sel->Where($sel->getTitleField(),eq,$line[$i]);
					
					if (!$obj[$field['NAME']]=$sel->selectString()){
						
						/**
						 * Поле не найдено в БД:
						 */
						$this->throwError(self::invalidField, 
							array(
								'value'=>$line[$i],
								'table'=>$sel->getProperty('title'),
								'line'=>$lineNum,
								'column'=>$field['title'],
							));
					}
				}
				elseif ($field['type']=='select1'){
					
					/**
					 * Для select1 ищем в списке:
					 */
					if (isset($field['select1'][str::lower($line[$i])])){
						$obj[$field['NAME']]=$field['select1'][str::lower($line[$i])];
					}
					elseif ($field['errors']&1){
						$this->throwError(self::invalidField, array(
							'value'=>$line[$i],
							'line'=>$lineNum,
							'column'=>$field['title'],
							'select1'=>implode(', ', $field['select1']),
						));
					}
				}
				elseif ($field['type']=='int'){
					if (!preg_match('/^\d*$/',$line[$i])){
						$this->throwError(self::invalidField, array(
							'value'=>$line[$i],
							'line'=>$lineNum,
							'column'=>$field['title'],
							
						));
					}
					$obj[$field['NAME']]=$line[$i];
				}
				else {
					$obj[$field['NAME']]=$line[$i];
				}				
			}
			
			$insert[]=$obj;
			$lineNum++;	
		}
		/**
		 * Экспорт прошел с ошибками
		 */
		if (!$this->isValid()) return false;
		
		if ($dropIndex) $this->Delete();
		foreach ($insert as $obj){
			$this->Insert($obj,'REPLACE');
		}
		
		return $lineNum-2;	
	}
	
	const invalidField='invalid field';
	const invalidFile='invalid file';
	const invalidColumn='invalid column';
	const requiredColumnNotExists='requiredColumnNotExists';
	
	function throwError($code, $meta=null){
		$this->errors[]=array('code'=>$code,'meta'=>$meta);
		return false;
	}
	/**
	 * Все ли хорошо:
	 * @return unknown_type
	 */
	function isValid(){
		return (count($this->errors)==0)?true:false;
	}
	
	protected $fp=null;
	
	
	var $glue=';';
	/**
	 * Кодировка файла:
	 * @var string
	 */
	var $csvEncoding='cp1251';
	/**
	 * Кодировка БД
	 * @var string
	 */
	var $dbEncoding='utf8';
	/**
	 * 
	 * 
	 * @return unknown_type
	 */
	function readLine(){
		if ($s=fgets($this->fp)){
			return explode($this->glue, iconv($this->csvEncoding, $this->dbEncoding, trim($s)));
		}
		else {
			fclose($this->fp);
			return null;
		}
	}
	
	protected $cols=array();
	
	
	function prepareHeader(){
		$fields=$this->getProperty('fields');
		foreach ($this->getProperty('fields') as $k=>$field){
			$index[str::lower($k)]=$k;
			$index[str::lower($field['title'])]=$k;
			/**
			 * Пробегаемся по полям и создаем индекс заголовка:
			 */
			if (isset($field['csv'])&&$field['csv']){
				if (is_array($field['csv'])){
					foreach ($field['csv'] as $csv) $index[str::lower($csv)]=$k;
				}
				else {
					$index[str::lower($field['scv'])]=$k;
				}
			}
			if ($field['type']=='select1'){
				$fields[$k]['select1']=array();
				foreach ($field['value_list'] as $vl){
					
					$fields[$k]['select1'][str::lower($vl['value'])]=$vl['value'];
					$fields[$k]['select1'][str::lower($vl['title'])]=$vl['value'];
					if (isset($vl['csv'])){
						if (is_array($vl['csv'])){
							foreach ($vl['csv'] as $vl_csv){
								$fields[$k]['select1'][str::lower($vl_csv)]=$v['value'];
							}
						}
						else {
							$fields[$k]['select1'][str::lower($vl['csv'])]=$v['value'];
						}
					}
				}
				
			}
			$fields[$k]['NAME']=$k;
		}
		/**
		 * Вычисляем какому полю соответсвует столбец:
		 */
		foreach ($this->readLine() as $i=>$col){
			if (isset($index[str::lower($col)])){
				$this->cols[$i]=$fields[$index[str::lower($col)]];
				$csvCols[]=$this->cols[$i]['NAME'];
			}
			else {
				$this->throwError(self::invalidColumn, $col);
			}
		}
		/**
		 * Вычисляем обязательные столбцы:
		 */
		
		foreach ($fields as $k=>$field){
			if (!isset($field['errors'])||!$field['errors']&1) continue;
			
			if (!isset($this->info[$k])&&!in_array($k, $csvCols)){
				$this->throwError(self::requiredColumnNotExists,$field);
			}
		}
		
		return $this->isValid();
		
	}
	
	
	function open($fileName){
		return $this->fp=fopen($fileName, 'r');
	}
	
}
?>