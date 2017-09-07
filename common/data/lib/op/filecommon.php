<?php
class filecommon{
	
	const Kb=1024;
	const Mb=1048576;
	const Gb=1073741824;
	
	static $BasePath;
	/**
	 * Прочитать файл:
	 * 
	 * @var $FileName	файл
	 * @return	string	содержимое файла
	 * 			false	файла нет
	 */
	static function read($FileName){
		if (file_exists(self::getFileName($FileName))) return file_get_contents(self::$BasePath.$FileName); 		
		else return false;
	}
	/**
	 * Десериализация данных из файлов
	 * @var string $FileName	имя файлов
	 * 
	 * @return 	null	- пустой файл
	 * 			false	- нет файла
	 * 			mixed	- данные
	 */
	static function unserialize($FileName){
		if ($ret=self::read($FileName)){
			return unserialize($ret);
		}
		else{
			return $ret;//Пустой результат- null, 
		}
	}
	
	static function IdToPath($id){
		return self::IdToDir($id).$id;
	}
	
	static function IdToDir($id){
		return round($id/1000000).'/'.round($id/1000).'/';
	}
	/**
	 * Тип открытия
	 *
	 * @param string $FileName
	 * @param string $Data
	 * @param string $Mode			r|w
	 * @param boolean $MakeDir		создать необходимые папки
	 * @param boolean $Lock			залочить
	 * @return	true	- записано
	 * 			false	- нет	
	 */
	static function write($FileName, $Data, $MakeDir=true, $Lock=true, $Mode='w'){
		$FullPath=self::getFileName($FileName);
				
		if ($MakeDir) if (!self::mkdir(dirname($FileName))) return false;
		if ($fp=@fopen($FullPath, $Mode)){
			if ($Lock) flock($fp, LOCK_EX);
			fwrite($fp, $Data);
			if ($Lock) flock($fp, LOCK_UN); 
			fclose($fp);
			return true;
		}
		else return false;
	}
	/**
	 * Дописать в файл
	 *
	 * @param string $FileName
	 * @param string $Data
	 * @param string $Separator
	 */
	static function append($FileName, $Data, $Separator=PHP_EOL){
		return self::write($FileName, $Data.$Separator, true, true, 'a');
	}
	
	/**
	 * Безопасное копирование (создает папки, при необходимости)
	 *
	 * @param string $src
	 * @param string $dst
	 * @return boolean
	 */
	static function copy($src, $dst){
		if (!file_exists($src)) throw new Exception('Source file does not exists');
		self::mkdir(dirname($dst));
		return copy($src, $dst);
	}
	
	static function serialize($FileName, $Data, $MakeDir=true){
		return filecommon::write($FileName,serialize($Data),$MakeDir);
	}
	
	static function getFileName($FileName){
		if (false!==strpos($FileName,'..')) throw new Exception('Wrong file name');
		return self::$BasePath.$FileName;
	}
	
	static function mkdir($Dirname){		
		if ($Dirname=='.'&&(!self::$BasePath||file_exists(self::$BasePath))){
			return true;		
		}
		if (file_exists(self::$BasePath.$Dirname)) return true;
		if (!self::mkdir(dirname($Dirname))) return false;
		if (!@mkdir(self::$BasePath.$Dirname)){
			str::print_r($BasePath.$Dirname);
			throw new Exception('Unable to create dir');
		}	
		return true;	
	}
	/**
	 * Прочитать папку
	 *
	 * @param string $Dirname	имя директории
	 * @param string $regexp	регулярка для проверки /^(\w).[jpeg|gif|png]$/
	 * @param mixed  $Sort		сортировка
	 * 							1	по возрастанию
	 * 							-1 	по убыванию
	 * 							или имя пользовательской функции	
	 * @return array/null
	 */
	static function ls($Dirname, $regexp=null, $Sort=null){
		if ($dir=@opendir($Dirname)){
			$ret=array();
			while($file= readdir($dir)){
				if ($file=='.'||$file=='..'||($regexp&&!preg_match($regexp,$file))) continue;
				$ret[]=$file;
			}
			if ($Sort==1) 		sort($ret, SORT_STRING);
			elseif($Sort==-1) 	rsort($ret, SORT_STRING);
			elseif($Sort) 		usort($ret, $Sort);
			return $ret;
		}
		return null;
	}
	/**
	 * Удалить (рекурсивно) файл/папку:
	 * @var $FileName	имя файла
	 * @var $Safe		собирать путь
	 * 
	 * @return true		удалено
	 * 		   false	нет такого файла/папки
	 */
	static function rm($FileName, $Safe=true){
		if ($Safe) $FileName=self::getFileName($FileName);
		if (!file_exists($FileName)) return false;//Нет файла
		if (is_dir($FileName)){
			$dir=opendir($FileName);
			while($File=readdir($dir)){
				if ($File=='.'||$File=='..') continue;
				if (!self::rm($FileName.'/'.$File, false)) return false;
			}
			return 	@rmdir($FileName);
			
		}
		else {
			return @unlink($FileName);
		}
		return true;
	}
	/**
	 * Порезать файл с длинным именем как папки:
	 * 
	 * Надо для автоматической склейки
	 * 
	 * abcdf , 2 -> ab/cd/f
	 * 
	 * @var string $FileName	имя файла
	 * @var string $maxLenght	длина
	 * @return string
	 */
	static function getLengthNormalizedFileName($FileName, $maxLenght){		
		
		$len=strlen($FileName);
		$ret='';
		
		while($len>$maxLenght){			
			$ret.=self::goodName(substr($FileName, -$len, $maxLenght)).'/';
			$len-=$maxLenght;
		}
		return $ret.self::goodName(substr($FileName, -$len, $maxLenght));		
	}
	/**
	 * Обратное преобразование:
	 * 
	 * @return string
	 */
	static function getPlainFileNameFromNormalized($FileName){
		return str_replace(array('/','_.','._'),array('','.','.'),$FileName);
	}
	
	static function goodName($val){
		if (substr($val,0,1)=='.') $val='_'.$val;
		if (substr($val,-1,1)=='.') $val=$val.'_';
		return $val;
	}
	/**
	 * Строгое имя файла: разрешены только латинские буквы, цифры и -.
	 * Автоматически транслитерируется
	 * 
	 * @return array(ИмяФайла, Расширение)
	 */
	static function getStrongFileName($fileName){
		if ($pos=strrpos($fileName,'.')){
			$ext=str::StrongString(substr($fileName, $pos+1));
			$fileName=str::StrongString(substr($fileName,0,$pos), 100).'.'.$ext;
		}
		else {
			$ext='';
			$fileName=str::StrongString($fileName,100);
		}
		
		return array($fileName,$ext);
	}
	/**
	 * Записать в случайный файл:
	 * 
	 * @param string $path		путь к файлу
	 * @param string $data		данные
	 * @param string $length	длина имени
	 * 
	 * @return string имя файла
	 */
	static function writeToRandomFile($path, $data, $length=32){
		$i=0;
		while(true){
			$name= str::randString($length);
			if (!file_exists($path.$name)) break;
			$i++;
			if ($i>1000) throw new Exception('Limit expired');
			
		}
		if (is_string($data)) filecommon::write($path.$name, $data);
		else filecommon::serialize($path.$name, $data);
		return $name;
	}
}
?>