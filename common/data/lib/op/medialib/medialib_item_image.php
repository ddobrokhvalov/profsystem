<?php
class medialib_item_image extends medialib_item{
	const proportion4x3=1.3333;
	const proportion1x1=1;
	const proportion3x4=0.75;
	
	const gif='image/gif';
	const jpeg='image/jpeg';
	const png='image/png';
	
	static $supportedMime=array(self::gif, self::jpeg, self::png);
	
	/**
	 * Провалидировать файл
	 * @param $file
	 * @return array
	 */
	static function validateFile(&$file){		
		$imageSize= getimagesize($file->getPath());
		if (!$imageSize) throw new Exception('unsupported file type');
		if (!in_array($imageSize['mime'],self::$supportedMime)) throw new Exception('unsupported file type');
		
		$file->Info['meta']=array(
			'mime'=>$imageSize['mime'],
			'width'=>$imageSize[0],
			'height'=>$imageSize[1],
			'proportion'=>self::calculateProportions($imageSize[0], $imageSize[1]),	
		);
		$file->state=1;
	} 
	
	/**
	 * Системные пропорции:
	 * @var array
	 */	
	static $proportions=array(
		'4x3'=>1.3333,
		'1x1'=>1,
		'3x4'=>0.75,
	);
	/**
	 * Вычислить пропорции:
	 * @param int $width
	 * @param int $height
	 * @return string
	 */
	static function calculateProportions($width, $height){
		/**
		 * Подбираем наиболее близкую пропорцию:
		 */
		if (!$height) return 0;
		$proportion=$width/$height;
		
		$proportionID=0;
		$currentDelta=0;
		foreach (self::$proportions as $id=>$p){
			$delta=abs($p-$proportion);			
			if (!$proportionID){
				$proportionID=$id;
				$currentDelta=$delta;
			}
			elseif ($delta<$currentDelta){				
				$proportionID=$id;
				$currentDelta=$delta;
			}
		}
		
		return $proportionID;
	}	
}
?>
