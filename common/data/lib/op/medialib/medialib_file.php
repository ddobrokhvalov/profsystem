<?php
/**
 * Модель файла
 * 
 * @author atukmanov
 *
 */
class medialib_file extends DataStore{
	/**
	 * Базовая папка для файлов:
	 * 
	 * @var string
	 */
	static $baseDir=null;
	/**
	 * Сгенеренные файлы:
	 * @var string
	 */
	static $subfilesDir=null;
	
	static $typeMap=array(
		//картинки:
		'jpeg'=>medialib::image,
		'jpg'=>medialib::image,
		'gif'=>medialib::image,
		'png'=>medialib::image,
		//видео:
		'avi'=>medialib::video,
		'3gp'=>medialib::video,
		'wmv'=>medialib::video,
		'mpg'=>medialib::video,
		'mpeg'=>medialib::video,
		'flv'=>medialib::video,
		//аудио:
		'wav'=>medialib::audio,
		'mp3'=>medialib::audio,
		'wma'=>medialib::audio,	
		'au'=>medialib::audio,
		'au'=>medialib::audio,
		'aac'=>medialib::audio,
		'aif'=>medialib::audio,
		'gsm'=>medialib::audio,
		'mid'=>medialib::audio,
		'rm'=>medialib::audio,
		'm4b'=>medialib::audio,
		//flash:
		'swf'=>medialib::flash,
	); 
	
	static $medialibControls=array(
		medialib::image=>array('medialib_item_image','validateFile'),
		medialib::video=>array('medialib_item_video','validateFile'),
		medialib::audio=>array('medialib_item_audio','validateFile'),
		medialib::flash=>array('medialib_item_flash','validateFile'),
	);
	
	static function select(){
		$a= func_get_args();
		return medialib_select::files($a);
	}
	
	static $_cache=array();
	/**
	 * Загрузить по id
	 * @param $id
	 * @return medialib_file
	 */
	static function loadByID($id){
		return self::select('id',$id)->selectObject('medialib_file');
	}
	/**
	 * Загрузить по файлу
	 * @param $fileName
	 * @return medialib_file
	 */
	static function loadByFile($fileName){
		return self::select('fileName',$fileName)->selectObject('medialib_file');
	}
	
	const invalidFileType='invalidFileType';

	const invalidFile=1;
	const invalidExt=2;
	static $tmpDir='/tmp/';	
	/**
	 * 
	 * @param $name
	 * @param string/array $name путь до файла
	 * @return array($tmp_name, $username)
	 */
	static function requestFile(){
		$a= func_get_args();
		$path=DataStore::Argv2Path($a);
		
		if (!isset($_FILES[$path[0]])) return null;
		$tmp_name=$_FILES[$path[0]]['tmp_name'];
		$username=$_FILES[$path[0]]['name'];
		$error=$_FILES[$path[0]]['error'];
		
		for ($i=1; $i<count($path); $i++){
			if (!isset($tmp_name[$path[$i]])){
				return null;
			}
			$tmp_name=$tmp_name[$path[$i]];
			$username=$username[$path[$i]];
			$error=$error[$path[$i]];
		}

		if (!$tmp_name) return null;
		if ($error) return null;
		if (!is_uploaded_file($tmp_name)) return null;
		$new_tmp_name=tempnam(medialib::$conf->tmpPath,'medialib');	
		
		/**
		 * Смысл в том, что некоторые настройки php запрещают работать напрямую с загруженными файлами
		 */
		move_uploaded_file($tmp_name, $new_tmp_name);
		return array($new_tmp_name, $username);
	}	
	/**
	 * medialib_typeException
	 * @var medialib_typeException
	 */
	var $convert=null;
	
	protected $tmpName=null;
	/**
	 * Прикрепить файл
	 * 
	 * @param $src		путь до исходного файла
	 * @param $username	имя загруженного файла
	 * @return boolean
	 */
	function setFile($src, $username=null){
		if (!$username) $username=$src;
		$pathinfo=pathinfo($username);
		$this->Info['ext']=(isset($pathinfo['extension']))?$pathinfo['extension']:null;

		$this->tmpName=$src;
		$this->username=$username;
		$this->Info['meta']=array();
		if (isset(self::$typeMap[$this->Info['ext']])){
			$this->Info['type']=self::$typeMap[$this->Info['ext']];
			try {
				call_user_func(self::$medialibControls[$this->type], &$this);
			}
			catch (medialib_typeException $convert){
				$this->Info['state']=$convert->state;
				$this->Info['meta']=$convert->meta;
				$this->convert=$convert;				
			}
			catch (Exception $e){				
				$this->Info['type']=medialib::file;
				$this->Info['meta']=array('_error'=>$e->getMessage(), '_errorCode'=>$e->getCode());
			}
		}
		else {
			$this->Info['type']=medialib::file;
			$this->Info['meta']=array('archive'=>in_array($this->ext, array('zip','rar','tr')));
		}	
				
		$this->Info['byteSize']=filesize($src);		
		
	}
	
	function getPath(){
		if ($this->tmpName) return $this->tmpName;
		return medialib::$conf->$baseDir.$this->Info['fileName'];
	}
	/**
	 * Исходный файл:
	 * @var string
	 */
	var $src=null;
	/**
	 * Пользовательское имя файла:
	 * @var string
	 */
	var $username=null;

	function save(){
		
		if (!$this->tmpName){
			//Без файла нельзя сохранить:
			return ($this->getID())?true:false;
		}
		
		if (!$this->getID()){			
			//Создаем новый id:			
			$this->Info['id']=kernel::getNextID('medialib');
			$new=true;			
		}
		else{
			$new=false;
			//Удаляем связанные файлы:
			//Превью и все такое
			$this->deleteFiles();
		}
		
		$this->Info['fileName']=$this->goodFileName($this->username);
		filecommon::copy($this->tmpName, medialib::$conf->filesPath.$this->fileName);
		if ($new){			
			/**
			 * Добавляем новую запись:
			 */
			self::select()->Insert(array(
				'id'=>$this->getID(),
				'type'=>$this->getInfo('type'),			//тип файла medialib_image, medialib_file etc
				'ext'=>$this->getInfo('ext'),				//расширения	
				'fileName'=>$this->getInfo('fileName'),						
				'byteSize'=>$this->getInfo('byteSize'),	//размер в байтах
				'meta'=>$this->getInfo('meta'),			//метаданные (длина ролика, тип изображений и т.п.)
				'ts'=>time()
			));
		}
		else {	
			/**
			 * Создаем:
			 */	
			self::select('id', $this->getID())->Update(array(					
				'type'=>$this->type,			//тип файла medialib_image, medialib_file etc
				'ext'=>$this->ext,				//расширения	
				'fileName'=>$this->fileName,						
				'byteSize'=>$this->byteSize,	//размер в байтах
				'meta'=>$this->meta,			//метаданные (длина ролика, тип изображений и т.п.)
			));
		}
		if ($this->convert) $this->convert->start($this);
		/**
		 * Удаляем временный файл:
		 */
		filecommon::rm($this->tmpName);
		$this->tmpName=null;
		return true;
	}
	
	function getFile(){
		if (!$this->file){
			if ($fileID=$this->getInfo('file')) $this->file=medialib_file::loadByID($this->getInfo('file'));			
		}
		return $this->file;
			
	}
	
	const maxFileNameLength=80;
	/**
	 * Хорошее имя файла
	 * 
	 * @param $fileName
	 * @param $ext
	 * @return array
	 */
	function goodFileName(){
		$pathinfo=pathinfo($this->username);		
		$fileName=$pathinfo['filename'];
		$ext=$this->ext;
			
		$fileName=str::Translate($fileName);
		
		$fileName=trim(preg_replace('/([_|-]{2})/', '_', preg_replace('/[^a-zA-Z0-9\-_]/','_',$fileName)),'-_');
		if (strlen($fileName)+strlen($ext)+1>self::maxFileNameLength){
			$fileName=substr($fileName, self::maxFileNameLength-strlen($ext)-1);
		}
		$dir=round($this->id/100000).'/'.round($this->id/1000).'/';
		if (!file_exists(medialib::$conf->filesPath.$dir.$fileName.'.'.$ext)) return $dir.$fileName.'.'.$ext;
		
		$i=1;
		while (file_exists(medialib::$conf->filesPath.$dir.$fileName.'_'.$i.'.'.$ext)){
			$i++;
			if ($i>100) throw new Exception('tempnam fault');
		}
		return $dir.$fileName.'_'.$i.'.'.$ext;
	}
	/**
	 * Удалить файл:	 
	 */
	function delete(){
		if ($this->linksCount) throw new Exception('file refered with another');
		$this->deleteFiles();
		self::select('id', $this->getID())->Delete();
	}
	
	protected function deleteFiles(){
		//Удаляем файлы:
		if ($this->fileName) filecommon::rm(medialib::$conf->filesPath.$this->fileName);
		//Удаляем порожденные:
		$sel=medialib_select::subfiles('file',$this->getID());
		foreach ($sel as $subfile){
			filecommon::rm(medialib::$basePath.$subfile['fileName']);
		}
		$sel->Delete();
	}
	/**
	 * 
	 * @param $fileName
	 * @return unknown_type
	 */
	function attachFile($fileName, $type){
		medialib_select::subfiles()->Insert(array(
			'file'=>$this->getID(),			
			'type'=>$type,			//тип превью			
			'fileName'=>$fileName,	//имя файла
			'ts'=>time()			//время создания	
		), 'REPLACE');
	}
	/**
	 * Сборщик мусора: удаляем все старше $age (5 дней по дефолту)
	 * 	 
	 */
	static function gc($age=432000){
		$sel=medialib_select::files();
		$sel->Where('links_count',eq,0);
		$sel->Where('ts', smaller, time()-$age);
		foreach ($sel as $obj){
			$deleteMe= new medialib_file($obj);
			$deleteMe->delete();
		} 
	}
		
	
	function _getInfo($path){
		if (isset($path[0])){
			switch (strtolower($path[0])){
				case 'path':
					return medialib::$conf->filesPath.$this->Info['fileName'];
				break;
				case 'basename':
					return basename($this->Info['fileName']);
				break;
				case 'filesize':
					if (!$this->Info['byteSize']) return 0;					
					foreach (medialib::$conf->getInfo('sizeName') as $s=>$n){						
						if ($this->Info['byteSize']<$s){
							break;
						}
						$size=$s;
						$name=$n;
					}					
					return round($this->Info['byteSize']/$size, (10*$size<$this->Info['byteSize'])?0:2).''.$name;
				break;				
				case 'preview':
					return new medialib_file_preview($this);
				break;
				case 'downloadurl':
					return medialib::$conf->downloadURL.$this->Info['fileName'];
				break;
			}
		}
		return parent::_getInfo($path);
	}
}

class medialib_file_preview implements ArrayAccess{
	/**
	 * Файл
	 * @var medialib_file
	 */
	var $file;
	/**
	 * Создать превью:
	 * @param $file
	 * @return unknown_type
	 */
	function __construct($file){
		$this->file=$file;
	}	
	/**
	 * Получить URL:
	 * @param $key
	 * @return string
	 */
	function getURL($key='100x100'){
		if ($this->file->type==medialib::image){
			return str_replace(
				array('{%preview%}','{%file%}'),
				array($key, $this->file->fileName),
				medialib::$conf->previewURL
			);
			//return medialib::$conf->previewURL.$key.'/'.$this->file->fileName;
		}
		else {
			return medialib::$conf->iconURL.$key.'/'.$this->file->ext.'.png';
		}
	}
	
	function __toString(){
		return $this->getURL();
	}
	
	/**
	 * @todo validate preview	 
	 */
	function offsetExists($key){
		return true;
	}
	
	function offsetGet($key){
		
		return $this->getURL($key);
	}
	
	function offsetSet($key,$value){
		return true;
	}
	
	function offsetUnset($key){
		return true;
	}
}
?>