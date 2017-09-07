<?php
/**
 * Конвертер видео с поддержкой очереди:
 * @author atukmanov
 *
 */
class video_converter extends rbcc5_object {
	
	var $table='VIDEO';
	/**
	 * Загрузить по id
	 * @param $id
	 * @return video_converter
	 */
	static function loadByID($id){
		$sel= rbcc5_select::getInstance('VIDEO',0,false);
		$sel->Where($sel->primary_key, eq, $id);
		return $sel->selectObject(__CLASS__);
	}
	
	const embed='embed';//Внешний источник
	const queue='queue';//Видео в очереди
	const in_progress='in_progress';//Видео в очереди
	const error='error';//Ошибка
	const complete='complete';//Обработка завершена
	
	/**
	 * Обновить статус
	 * @return string новый статус
	 */
	function updateStatus(){
		/**
		 * Если для видео указан код конвертация не требуется:
		 */
		if ($this->EMBED){
			$this->Update(array('STATUS'=>self::embed));
			return self::embed;
		}
		/**
		 * Проверяем AVI:
		 */
		if (!$this->getAviPath()){
			return $this->Update(array(
				'STATUS'=>self::error, 
				'MESSAGE'=>'file is not uploaded',
			));
		}
		/**
		 * Проверяем не сконвертировалось ли уже:
		 */
		if (!$this->checkConvert()){
			//Если нет добавляем в очередь:
			$this->enqueue();
		}
	}
	/**
	 * Проверяем не сконвертировалось ли:
	 */
	public function checkConvert(){
		$flv=$this->getFlv();
		/**
		 * После конвертации скрипт создает статусный файл:
		 */		
		if (!$status=filecommon::read(self::$flvBaseDir.$flv.'/flv.status')){
			//Конвертация не завершена:						
			return false;
		}
		/**
		 * Конвертация завершена успешно, если файл содержит строку типа:
		 * 
		 * video:106022kB audio:6603kB global headers:0kB
		 */
		if (preg_match('@video:(\d+)kB audio:(\d+)kB@',$status)){			
		
			//Вычисляем длительность:			
			if (preg_match('@Duration: (.*?),@',$status,$m)){				
				list ($h, $i, $s)=explode(':', $m[1]);
				$duration=3600*(int)$h+60*(int)$i+(int)$s;							
			}
			
			$this->Update(array(
				'STATUS'=>self::complete,
				'FLV'=>$flv,
				'DURATION'=>$duration,
			));				
		}
		else {
			//Последная строка содержит сообщение об ошибке:
			$status=trim($status);			
			$error=trim(substr($status, strrpos($status, PHP_EOL)));
			//Обновляем:
			$this->Update(array(
				'STATUS'=>self::error,
				'MESSAGE'=>$error
			));			
		}
		return true;
	}
	/**
	 * Добавить в очередь на обработку
	 * @return void
	 */
	protected function enqueue(){
		return $this->Update(array(
			'FLV'=>$this->getFlv(),
			'STATUS'=>self::queue,
		));
	}
	/**
	 * Сконвертировать:
	 * @return void
	 */
	function convert(){

		$flv=$this->getFlv();
		/**
		 * Проверяем не запущена ли уже конвертация:
		 */		
		if (!file_exists(self::$flvBaseDir.$flv.'/flv.progress')){
			/**
			 * Создаем папку:
			 */
			filecommon::mkdir(self::$flvBaseDir.$flv);
			/**
			 * Выполняем конвертацию:
			 */
			$cmd=self::$cmd;
			$cmd=str_replace('{$src}', $this->getAviPath(), $cmd);
			$cmd=str_replace('{$dst}', self::$flvBaseDir.$flv, $cmd);			
			`$cmd`;
		}
		//Сохраняем статус:
		$this->Update(array(
			'FLV'=>$flv,
			'STATUS'=>self::in_progress,
		));
	}
	/**
	 * Проверить AVI файл:
	 * @return boolean
	 */
	protected function getAviPath(){		
		/**
		 * Вычисляем полный путь:
		 */
		if (!preg_match(self::$pregLocalFile, $this->AVI)){					
			return false;
		}
		
		/**
		 * Получаем путь к файлу и проверяем существование файла:
		 */
		$path=self::$aviBaseDir.preg_replace(self::$pregLocalFile,'',$this->AVI);
		
		if (!file_exists($path)){
			return false;
		}
		return $path;
	}
	/**
	 * Получить путь до FLV
	 * @return string
	 */	
	protected function getFlv(){
		if ($path=$this->getAviPath()){
			return md5($path.'@'.filemtime($path));
		}
		else {
			return null;
		}
	}	
	/**
	 * Базовая папка для флеш файлов:
	 * @var string
	 */
	static $flvBaseDir=null;
	/**
	 * Команда конвертации:
	 * @var unknown_type
	 */
	static $cmd=null;
	/**
	 * Базовая папка для avi файлов:
	 */
	static $aviBaseDir=null;
	/**
	 * Регулярка для определения локального файла:
	 */
	static $pregLocalFile='@^/common/@';
}
?>