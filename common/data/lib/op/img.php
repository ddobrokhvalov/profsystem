<?php
class img extends DataStore{

	var $websafe=false;
	/**
	 * 
	 * @param array $Info
	 * @param gd_image $im
	 * @return unknown_type
	 */
	function __construct($Info, $img=null){
		
		parent::__construct($Info);
		if ($img){
			$this->img=$img;
		}
		else {
			//Создаем пустое изображение:
			if (function_exists('imagecreatetruecolor')){
				$this->img= imagecreatetruecolor($Info[0],$Info[1]);				
			}
			elseif (function_exists('imagecreate')){
				$this->img= imagecreate($Info[0], $Info[1]);
				$this->websafe= true;				
			}
			else throw new Exception('Unable to create new image');
		}
	}
	/**
	 * Указатель на GD image:
	 * 
	 * @var datastore
	 */
	protected $img;
	
	const jpeg='image/jpeg';
	const gif='image/gif';
	const png='image/png';
	/**
	 * Функции для создания GD изображений из файлов:
	 * @var array
	 */
	static $imageTypes=array(
		self::jpeg=>'imagecreatefromjpeg',
		self::gif=>'imagecreatefromgif',
		self::png=>'imagecreatefrompng'
	);
	/**
	 * Поддерживаемые расщирения
	 * @var array
	 */
	static $filesExt=array(
		'gif'	=>	self::gif,
		'jpeg'	=>	self::jpeg,
		'jpg'	=>	self::jpeg,
		'png'	=>	self::png,
	);
	/**
	 * Загрузить из файла:
	 * 
	 * @return img
	 */
	static function loadFromFile($fileName){
		if (!file_exists($fileName)) throw new Exception('Invalid file name');
		if (!is_readable($fileName)) throw new Exception('File not readable');
		if (!$i=getimagesize($fileName)) throw new Exception('Not image');
		if (!isset(self::$imageTypes[$i['mime']])) throw new Exception('Image type not supported');
		$function= self::$imageTypes[$i['mime']];
		if (!function_exists($function)) throw new Exception('Image type not supported by system');
		if (!$im= @$function($fileName)) throw new Exception('Unable to load image');
		return new img($i, $im);
	}
	
	const ignore=	'';
	const top	=	'top';
	const bottom=	'bottom';
	const middle=	'middle';
	const left	=	'left';
	const right	=	'right';
	const center=	'center';
	const cut='cut';
	const forceSize=true;
	/**
	 * Получить новый файл:
	 * 
	 * @param int $width	ширина
	 * @param int $height	высота 
	 * @param $align		горизонтальное выравнивание left/right/center ��� ignore
	 * @param $valign		вертикальное выравнивание left/right/center ��� ignore
	 * @param $background	цвет фона
	 * @return img
	 */
	function resize($width, $height, $align, $valign, $background=self::white, $cut=false, $scale='scale'){
				
		if ($cut){			
			$resize=img_resize::cut($this->width, $this->height, $width, $height, $align, $valign,$scale);			
		}
		else {
			$resize=img_resize::inscribe($this->width, $this->height, $width, $height, $align, $valign,$scale);
		}

		return $this->cp($resize, $background);
	}	
	/**
	 * Скопировать:
	 * @param $resize
	 * @return unknown_type
	 */
	function cp($resize, $background){
		$ret= new img(array(
			$resize->width, $resize->height
		));

		if ($background){			
			imagefill($ret->img,0,0,$ret->rgb($background));
		}
		
		if (function_exists('imagecopyresampledbicubic')){			
			imagecopyresampledbicubic($ret->img, $this->img, $resize->dst_x, $resize->dst_y, $resize->src_x, $resize->src_y, $resize->dst_w, $resize->dst_h, $resize->src_w, $resize->src_h);
		}
		elseif (function_exists('imagecopyresampled')){			
			imagecopyresampled($ret->img, $this->img, $resize->dst_x, $resize->dst_y, $resize->src_x, $resize->src_y, $resize->dst_w, $resize->dst_h, $resize->src_w, $resize->src_h);
		}
		else {
			imagecopy($ret->img, $this->img, $resize->dst_x, $resize->dst_y, $resize->src_x, $resize->src_y, $resize->dst_w, $resize->dst_h, $resize->src_w, $resize->src_h);
		}
		
		return $ret;
	}
	/**
	 * crop:
	 * 
	 * @return array (left, top, width, height) � ���������
	 */
	function calculateRelativeCrop($left, $top, $width, $height){
		return array(
			0=> $left-$this->left/$this->width,
			1=> $top-$this->top/$this->height,
			2=> $width/$this->width,
			3=> $height/$this->height
		);
	}
	/**
	 * Обрезать:
	 * 
	 * @param $left
	 * @param $top
	 * @param $width
	 * @param $height
	 * @param $background
	 * 
	 * @return img
	 */
	function cropRelative($left, $top, $width, $height, $background){
		$ret= new img(array($width*$this->width, $height*$this->height,'left'=>$left*$this->width, 'top'=>$top*$this->height));
		$copy_width=$ret->width+($ret->left<0)?$ret->left:0;
		$copy_height=$ret->height+($ret->top<0)?$ret->top:0;	
		if ($background) imagefill($this->im, 0, 0, $ret->rgb($background));
		//int $dst_w  , int $dst_h  , int $src_w  , int $src_h  )
		imagecopyresampled(
			$ret->im, 						//$dst_image
			$this->im, 						//$src_image
			($ret->left<0)?-$ret->left:0,	//$dst_x -���� $left �������������- ��������� ��� ��������
			($ret->top<0)?-$ret->top:0,		//$dst_y
			($ret->left>0)?$ret->left:0,	//$src_x	�������� ������������
			($ret->top>0)?$ret->top:0,		//$src_y
			$copy_width,					//$dst_w
			$copy_height,					//$dst_h
			$copy_width,					//$src_w
			$copy_height					//$src_h
		);
		
		return $ret;
	}
	/**
	 * Обрезать:
	 * 
	 * @return img
	 */
	function crop($left, $top, $width, $height){
		if ($left<0||$left+$width>$this->width) throw new Exception('Out of range');
		if ($top<0||$top+$height>$this->height) throw new Exception('Out of range');
		if ($width<0||$height<0) throw new Exception('Invalid size');
		
		$ret= new img(array(0=>$width, 1=>$height,'mime'=>$this->mime));
		
		if (function_exists('imagecopyresampledbicubic')){
			imagecopyresampledbicubic($ret->img, $this->im, 0, 0, $left, $top, $width, $height, $width, $height);
		}
		elseif (function_exists('imagecopyresampled')){
			imagecopyresampled($ret->img, $this->img, 0, 0, $left, $top, $width, $height, $width, $height);
		}
		else {
			imagecopy($ret->img, $this->img, 0, 0, $left, $top, $width, $height, $width, $height);
		}
		
		return $ret;
	}
	/**
	 * Спозиционировать
	 * 
	 * @param $width
	 * @param $height
	 * @param $align
	 * @param $valign
	 * @return array
	 */
	function _positionate($width, $height, $align, $valign){
		$incribe= $this->_inscribe($width, $height);
		switch ($align){			
			case self::right:
				$incribe[2]=$width-$incribe[0];
			break;
			case self::center:
				$incribe[2]=round(($width-$incribe[0])/2);
			break;
			default:
				$incribe[2]=0;
			break;
		}
		switch ($valign){
			case self::bottom:
				$incribe[3]=$height-$incribe[1];
			break;
			case self::middle:
				$incribe[3]=round(($height-$incribe[1])/2);
			break;
			default:
				$incribe[3]=0;
			break;
		}
		return $incribe;
	}
	
	/**
	 * Вписать в квадрат $widthx$height
	 *  
	 * @param $width
	 * @param $height
	 * @return array (0-> newwidth, 1->newheight)
	 */
	function _inscribe($width=0, $height=0, $align=0, $valign=0){
		if (!$width&&!$height) throw new Exception('Zero size image');
		if (!$width) $q=$height/$this->height;
		elseif (!$height) $q=$width/$this->width;
		else $q=min($width/$this->width, $height/$this->height);
		$q=min($q,1);//
		return array(round($q*$this->width), round($q*$this->height),0,0,0,0);		
	}	
	/**
	 * ��������� ����
	 * 
	 * @param $fileName
	 * @return boolean
	 */
	function save($fileName){
		
		$pathinfo= pathinfo($fileName);
		filecommon::mkdir($pathinfo['dirname']);
		$ext=str::lower($pathinfo['extension']);
		if (!isset($ext)||!isset(self::$filesExt[$ext])) throw new Exception('Unsupported extension');		
		list($void, $type)= explode('/', self::$filesExt[$ext]);
		$function='image'.$type;
		if (!function_exists($function)) throw new Exception('Image type not supported by system');
		
		$function($this->img, $fileName);		
	}
	/**
	 * ������� ����:
	 * 
	 * @param $imageType
	 * @return unknown_type
	 */
	function display($imageType){
		
		//if (headers_sent()) throw new Exception('Headers just send');
		if (!isset(self::$imageTypes[$imageType])) throw new Exception('Image type not supported');
		list($void, $type)=explode('/', $imageType);
		$function='image'.$type;
		if (!function_exists($function)) throw new Exception('Image type not supported by system');
		header('Content-type: '.$imageType);	
			
		$function($this->img);
	}
	
	function _getInfo($path){
		if (!isset($path[0])) return $this->Info;
		switch ($path[0]){
			case 'width':
			case 'w':
				return $this->Info[0];
			break;
			case 'height':
			case 'h':
				return $this->Info[1];
			break;
			case 'proportion':
				return $this->Info[0]/$this->Info[1];
			break;
			case 'max_size':
				return max($this->Info[0], $this->Info[1]);
			break;
			default:
				return parent::_getInfo($path);
			break;			
		}	
	}
	const white='#FFFFFF';
	const black='#000000';
	/**
	 * string 2 rgb
	 * 
	 * @param $rgb
	 * @return array
	 */	
	function rgb($rgb){
		$ret=array();
		
		if (is_array($rgb)){
			if (count($rgb)!=3) throw new Exception('Invalid rgb');
			for ($i=0; $i<3; $i++) $ret[$i]=min(255, max(0, (int)$rgb[$i]));
			return imagecolorallocate($this->img, $ret[0], $ret[1], $ret[2]);			
		}
		elseif (is_string($rgb)){
			if (strlen($rgb)!=7) throw new Exception('Invalid rgb');
			return imagecolorallocate(
				$this->img,
				min(255, max(0, hexdec(substr($rgb,1,2)))),//red
				min(255, max(0, hexdec(substr($rgb,3,2)))),//green
				min(255, max(0, hexdec(substr($rgb,5,2)))) //blue
			);
		}
		else {
			throw new Exception('Invalid color');
		}
	}
}
?>
