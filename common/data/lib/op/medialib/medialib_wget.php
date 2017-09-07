<?php
/**
 * Загрузчик файлов по ftp/http:
 * @author atukmanov
 *
 */
class medialib_wget {
	/**
	 * Загрузить файл:
	 * @param string $url
	 * @param medialib_file $file
	 * @return medialib_file
	 */
	function downloadFile($url, &$file){
		if (!$fileName=$this->_downloadURL($url)){
			throw new Exception('UrlNotFound');
		}
		$file->setFile($fileName);
		return $file;
	}
	
	var $contentType=array(
		'image/gif'=>'gif',
		'image/jpeg'=>'jpeg',
		'image/png'=>'png',
	);
	
	protected function _downloadURL($url){
		$tmpPath=tempnam(medialib_file::$tmpDir);
		$logPath=tempnam(medialib_file::$tmpDir);
		$downloadURL=escapeshellarg($url);
		
		`wget -o $logPath -O $tmpPath $downloadURL`;
		$log=filecommon::read($logPath);
		filecommon::rm($logPath);

		if (!$log) return null;//error while downloading
		if (!preg_match('/HTTP request sent, awaiting response... (\d+)/',$log,$m)) return null;
		if ($m[1]!='200') return null;//HTTP Code must be 200 OK
		$pathinfo=pathinfo($url);
		/**
		 * Если URL задан с расширением берем имя файла:
		 */
		if (isset($pathinfo['extension'])){
			return array($tmpPath, $pathinfo['basename']);
		}
		/**
		 * Пытаемся получить расширение из ответа (ContentType)
		 */
		if (!preg_match('/Length: .* \[(.*?)\]/',$log,$m)){
			if (isset($this->contentType[$m[1]])){
				return array($tmpPath, $pathinfo['basename'].'.'.$this->contentType[$m[1]]);
			}
		}
		/**
		 * Если ничего не выщло скачиваем так:
		 */
		return array($tmpPath, $pathinfo['basename'].'.'.$this->contentType[$m[1]]);
	}
}
?>