<?
class filecommon_dir {
	/**
	 * Базовые папки:
	 */
	var $BaseDir=array();
	/**
	 * Добавить базовую директорию:
	 *
	 * @param string $path
	 * @param string $name
	 */
	function addBaseDir($path, $name=null){
		if (!file_exists($path)) throw new Exception('Base dir does not exists');
		if ($name){
			if (!preg_match('/^[a-z0-9]{1,32}$/',$name)) throw new Exception('Invalid dir alias');
		}
		elseif ($name=md5($name));
		
		self::$BaseDir[$name]=$path;
	}		
	
	var $dirs;
	
	function __construct($BaseDir=null){
		if ($BaseDir) $this->BaseDir=$BaseDir;
	}
	/**
	 * Породить объект:
	 * 
	 * @var $dir array
	 * 
	 * @return filecommon_dir
	 */
	function getInstance($dir=null){
		$ret= new filecommon_dir($this->BaseDir);
		if ($dir){
			foreach($dir as $dir){
				$ret->addDir($dir[0],$dir[1]);
			}
		}
		return $ret;
	}
	/**
	 * Добавить папку:
	 * 	 
	 * @var $dir	папка
	 * @var $base	базовая директория (индекс в self::$BaseDir)
	 *
	 * @return boolean
	 */
	function addDir($dir, $base=null){
		if (!isset($this->BaseDir[$base])) throw new Exception('Invalid base dir "'.$base.'"');
		$this->dirs[]=array($dir, $base);
	}
	/**
	 * Найти файл:
	 *
	 * @param string $file
	 * @param array	 $add
	 * 
	 * @todo $optionalDirs
	 * 
	 * @return filecommon_dir_file
	 */
	function find($fileName, $current=null){//, $optionalDirs=null){
		$level=0;
		while (substr($fileName,0,3)=='../'){
			$level++;
			$file=substr($fileName,3);
		}		
		
		foreach ($this->dirs as $dir){
			while($file=$this->find_r($dir[0],$dir[1],$fileName)){				
				if ($level==0) return $file;
				if (!$current){
					//Идем от текущего объекта:
					$level--;
				}
				else {
					//Проверяем:
					if ($current->equal($file)){						
						$current=0;
						$level--;
					}
				}
				$dir[1]=$file->dir;
			}
		}
		
		return null;
	}
	/**
	 * Найти:
	 * 
	 * @var $base
	 * @var $dir
	 * @var $file
	 * 
	 * @return filecommon_dir_file
	 */
	function find_r($dir, $base, $file){
		$i=0;
		if (substr($dir,0,1)=='/') $dir=substr($dir,1);
		while (true){
			
			if (file_exists($this->BaseDir[$base].$dir.$file)) return new filecommon_dir_file($base, $dir, $file, $this);
			if (!$dir) return null;
			$dir=self::dirname($dir);
			$i++;
			if ($i>4){
				str::print_r(func_get_args());
				str::print_r($dir);
				die();
			}
		}
	}
	
	static function dirname($file){
		$ret=dirname($file);
		if (!$ret||'.'==$ret) return '';
		else return $ret.'/';
	}
	
}

class filecommon_dir_file{
	/**
	 * id базовой директории
	 * 
	 * @var string	servlet
	 */
	var $base=null;
	/**
	 * относительный путь в базовой директории:
	 * 
	 * @var string news/add/
	 */
	var $dir=null;
	/**
	 * Файл в базовой директории:
	 * 
	 * @var string 
	 */
	var $file=null;		
	/**
	 * Абсолютный путь до базовой директории:
	 * 
	 * @var string /usr/local/me/engine/servlet/
	 */
	var $BaseDir=null;
	/**
	 * Поиск:
	 *
	 * @param string $base	базовая директория
	 * @param string $dir	папка
	 * @param string $file	файл
	 * @param filecommon_dir $parent
	 * 	
	 */
	function __construct($base, $dir, $file, $parent){
		$this->base=$base;
		$this->dir=$dir;
		$this->file=$file;		
		$this->BaseDir=$parent->BaseDir[$base];
	}
	
	function __toString(){
		return $this->path(null);
	}
	/**
	 * Равенство:
	 * 
	 * @param filecommon_dir_file $file
	 * 
	 * @return boolean
	 */
	function equal($file){
		if ($file->base==$this->base&&$file->dir==$this->dir) return true;
		else return false;
	}
	/**
	 * Прочитать:
	 * 
	 * @return string
	 */
	function read($baseDir=null){
		return filecommon::read($this->path($baseDir));
	}
	/**
	 * Записать:
	 * 
	 * @param string $data		данные
	 * @param string $baseDir	базовая папка
	 * @return 
	 */
	function write($data, $baseDir=null){
		return filecommon::write($this->path($baseDir), $data);
	}
	/**
	 * Относительный путь:
	 *
	 * @param string $baseDir	базовая папка
	 * @return string
	 */
	function path($baseDir=null){
		if ($baseDir){
			$baseDir=$baseDir.$this->base.'/';
		}
		else {
			$baseDir=$this->BaseDir;
		}
		
		return $baseDir.$this->dir.'/'.$this->file;
	}
	
	
}
?>