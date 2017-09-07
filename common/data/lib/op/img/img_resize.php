<?php
/**
 * Вычисление параметров ресайза картинки
 * 
 * @author atukmanov@rbcsoft.ru
 *
 */
class img_resize{
	
	const scale='scale';
	const fill='fill';
		
	var $width=0;
	var $height=0;
	/**
	 * Положение в конечном изобращении:	 
	 */
	var $dst_x=0;
	var $dst_y=0;
	var $dst_w=0;
	var $dst_h=0;
	/**
	 * Положение в исходном изображении:	 
	 */
	var $src_x=0;
	var $src_y=0;
	var $src_w=0;
	var $src_h=0;	
	
	var $q=0;
	
	/**
	 * Вписать изображение src в dst:
	 * @return img_resize
	 */
	static function inscribe($src_w, $src_h, $dst_w, $dst_h, $align, $valign, $scale=self::scale){
		$ret= new img_resize();
		
		if (!$dst_w && !$dst_h) throw new exception("Invalid destination size");
		if (!$src_w || !$src_h) throw new exception("Invalid source size");
		
		if (!$dst_w){
			/**
			 * Если не задана ширина конечного изображения, вычисляем коэффициент по высоте:
			 */
			$q=$dst_h/$src_h;
		}
		elseif (!$dst_h){
			/**
			 * Аналогично для высоты:
			 */
			$q=$dst_w/$src_w;
		}
		else {
			/**
			 * Коэффициент вычисляется такой чтобы самая длинная сторона убралась:
			 */
			$q=min($dst_h/$src_h, $dst_w/$src_w);
		}		
		$ret->q=$q;		
		if ($q>1 && $scale!=self::scale){
			//Конечное изображение больше исходного:
			if ($scale==self::fill){									
					$q=1;				
			}
			else {
				throw new img_resize_scale_exception($src_w,$src_h,$dst_w,$dst_h);
			}
		}
		//Копируем изображение целиком:
		$ret->src_w=$src_w;
		$ret->src_h=$src_h;
		//Ужимая согласно коэффициэнту:
		$ret->dst_w=round($src_w*$q);
		$ret->dst_h=round($src_h*$q);
		//Выставить па
		return self::_inscribe($ret, $dst_w, $dst_h, $align, $valign);
	}
	
	static function _inscribe($ret, $dst_w, $dst_h, $align, $valign){
		//Вычисляем размер сдвига (поля):		
		switch ($align){
			case img::left:
				//Изображение по левому краю:
				$ret->width=$dst_w;
				$ret->dst_x=0;
			break;			
			case img::right:
				//Изображение по правому краю:
				$ret->width=$dst_w;
				$ret->dst_x=$this->width-$this->dst_w;
			break;
			case img::middle:
			case img::center:
				//Помещаем изображение в середину:
				$ret->width=$dst_w;
				$ret->dst_x=round(($ret->width-$ret->dst_w)/2);
			break;
			default:
				//Ширина конечного изображения совпадает с шириной видимой части:
				$ret->width=$ret->dst_w;				
			break;			
		}
		//Аналогично для высоты:
		switch ($valign){
			case img::top:
				//Изображение по верхнему краю:
				$ret->height=$dst_h;
				$ret->dst_h=0;
			break;			
			case img::right:
				//Изображение по нижнему краю:
				$ret->height=$dst_h;
				$ret->dst_y=$this->height-$this->dst_y;
			break;
			case img::middle:
			case img::center:
				//Помещаем изображение в середину:
				$ret->height=$dst_h;
				$ret->dst_y=round(($ret->height-$ret->dst_h)/2);
			break;
			default:
				//Высота конечного изображения совпадает с шириной видимой части:
				$ret->height=$ret->dst_h;				
			break;			
		}
		
		return $ret;
	}
	/**
	 * Вырезать из исходного изображения фрагмент с пропорциями конечного и привести их к соотв. размеру:
	 * @return 
	 */
	static function cut($src_w,$src_h,$dst_w,$dst_h,$align,$valign,$scale=self::scale){		
		if (!$dst_w || !$dst_h) throw new exception('Target image should has fixed proportions');
		if (!$src_w || !$src_h) throw new exception("Invalid source size");

		$ret= new img_resize();
		$ret->dst_w=$ret->width=$dst_w;
		$ret->dst_h=$ret->height=$dst_h;		
		
		$q=max($dst_w/$src_w,$dst_h/$src_h);
		
		if ($q>1&&$scale!=self::scale){
			if ($scale==self::fill){
				$q=1;
			}
			else {
				throw new img_resize_scale_exception($src_w,$src_h,$dst_w,$dst_h);
			}
		}
		
		$ret->src_w=ceil($dst_w/$q);
		$ret->src_h=ceil($dst_h/$q);
		
		switch ($align){
			case 'left':
				//Обрезаем как есть:
				$ret->src_x=0;
			break;
			case 'right':
				$ret->src_x=$src_w-$ret->src_w;
			break;
			default:
				$ret->src_x=ceil(($src_w-$ret->src_w)/2);
			break;
		}
		
		switch ($valign){
			case 'top':
				$ret->src_y=0;
			break;
			case 'bottom':
				$ret->src_y=$src_h-$ret->src_h;
			break;
			default:
				$ret->src_y=ceil(($src_h-$ret->src_h)/2);
			break;
		}
		
		return self::_inscribe($ret, $dst_w, $dst_h, $align, $valign);
	}
	/**
	 * Привести в соотв. с пропорциями:
	 * @return img_resize
	 */
	function scale($width, $height){
		
	}
}

class img_resize_scale_exception extends Exception {
	function __construct($src_w,$src_h,$dst_w,$dst_h){
		parent::__construct('Invalid scale from '.$src_w.'x'.$src_h.' to '.$dst_w.'x'.$dst_h);
	}	
}
?>