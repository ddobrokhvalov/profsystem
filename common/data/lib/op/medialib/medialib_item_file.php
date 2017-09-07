<?
class medialib_item_file extends medialib_item{
	/**
	 * URL превью
	 * @return string
	 */
	function getPreviewUrl(){
		/**
		 * Прикреплен файл превью:
		 */
		if ($previewImage=$this->getInfo('file.meta.previewImage')){
			return new medialib_preview(
				str_replace('{$fileName}',filecommon::IdToPath($previewImage).'.jpg',$this->list->lib->getSetting(
					'iconPreview','/medialib/preview/{$preview}/{$fileName}'
				))
			);
		}
		/**
		 * Выводим иконку:
		 */
		else {
			return new medialib_preview(
				str_replace('{$ext}',$this->getInfo('ext'),$this->list->lib->getSetting(
					'iconPreview','/medialib/icon/{$preview}/{$ext}.png'
				))
			);
		}
	}
	
	function getArchiveURL($type){
		return str_replace('{$fileName}',$this->getInfo('fileName'),$this->list->lib->getSetting(
			'archiveURL','/medialib/archive/{$fileName}.'.$type
		));
	}
	
	function _getInfo($path){
		switch ($path[0]){
			case 'downloadZip':
				return $this->getArchiveURL('zip');
			break;
			case 'downloadRar':
				return $this->getArchiveURL('rar');
			break;
		}
		return parent::_getInfo($path);
	}
}
?>