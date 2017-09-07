<?php
class medialib_item_video extends medialib_item{
	/**
	 * Проверить видео файл:
	 * @param $file
	 * @return array
	 */
	static function validateFile($file){
		if (!class_exists('ffmpeg_movie')) throw new Exception('ffmpeg module not installed');
 
		if ($file->ext=='flv'){
			$file->Info['state']=1;			
		}		
		else {
			$file->Info['state']=0;
		}		
	} 
	/**
	 * Информация о ролике
	 * @param medialib_file $file
	 * @return array
	 */
	static function parseVideoInfo($file){
		
	}
}

class medialib_item_video_convert {
	/**
	 * Начать конвертацию:
	 * @param $file
	 * @return unknown_type
	 */
	function startConvertation($file){
		
	}
}
?>