<?php
/**
 * Класс для работы с каптчей
 *
 * @package    RBC_Contents_5_0
 * @subpackage lib
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class captcha extends lib_abstract
{
	/**
	 * Генерация кода для картинки, сохранение его в сессии
	 */
	public static function generate( $value_length = 5 )
	{
		$captcha_value = ''; $symbols = array();
		
		for ( $i = ord( 'A' ); $i <= ord( 'Z' ); $i++ )
			$symbols[] = chr($i);
		
		for ( $i = 0; $i <= 9; $i++ )
			$symbols[] = $i;
		
		$symbol_count = count( $symbols );
		
		for ( $i = 0; $i < $value_length; $i++ )
			$captcha_value .= $symbols[mt_rand( 0, $symbol_count - 1 )];

		$captcha_id = md5( uniqid() );

		if( !isset( $_SESSION['CAPTCHA'] ) )
			$_SESSION['CAPTCHA'] = array();

		$_SESSION['CAPTCHA'][$captcha_id] = $captcha_value;
		
		return $captcha_id;
	}
	
	/**
	 * Проверка кода на картинке
	 */
	public static function check( $captcha_id, $captcha_value )
	{
		$check = isset( $_SESSION['CAPTCHA'][$captcha_id] ) && 
			strtolower( $_SESSION['CAPTCHA'][$captcha_id] ) == strtolower( $captcha_value );
		
		unset( $_SESSION['CAPTCHA'][$captcha_id] );
	
		return $check;
	}
	
	/**
	 * Вывод изображения в браузер
	 */
	public static function display( $captcha_id )
	{
		session_start();
		
		if ( isset( $_SESSION['CAPTCHA'][$captcha_id] ) )
			$captcha_value = $_SESSION['CAPTCHA'][$captcha_id];
		else
			$captcha_value = 'ERROR!';

		$im = imagecreatetruecolor( CAPTCHA_WIDTH, CAPTCHA_HEIGHT );
		$tile = imagecreatetruecolor( 3, 3 );

		$bg_color = imagecolorallocate( $im, CAPTCHA_BG_COLOR1_R, CAPTCHA_BG_COLOR1_G, CAPTCHA_BG_COLOR1_B); // Фон над горами
		$bg_color2 = imagecolorallocate( $im, CAPTCHA_BG_COLOR2_R, CAPTCHA_BG_COLOR2_G, CAPTCHA_BG_COLOR2_B); // Цвет гор
		$text_color = imagecolorallocate( $im, CAPTCHA_TEXT_COLOR1_R, CAPTCHA_TEXT_COLOR1_G, CAPTCHA_TEXT_COLOR1_B); // Цвет текста над горами
		$text_color2 = imagecolorallocate( $im, CAPTCHA_TEXT_COLOR2_R, CAPTCHA_TEXT_COLOR2_G, CAPTCHA_TEXT_COLOR2_B); // Цвет текста на горах

		imagefill( $tile, 0, 0, $bg_color );
		imagesetpixel( $tile, 2, 2, $bg_color2 );
		imagesettile( $im, $tile );
		imagefilledrectangle( $im, 1, 1, CAPTCHA_WIDTH - 2, CAPTCHA_HEIGHT - 2, IMG_COLOR_TILED );

		$font = params::$params['adm_data_server']['value'] . 'class/te/table/wf_workflow/arial.ttf';

		$size = CAPTCHA_HEIGHT / 2; $coord = array();

		for ( $i = 0; $i < strlen( $captcha_value ); $i++ )
			$coord[] = array(
				'x' => $size * ( $i + 1 ) + mt_rand( -2, 2 ), 'y' => CAPTCHA_HEIGHT - ( CAPTCHA_HEIGHT - $size ) / 2 + mt_rand( -5, 5 ),
				'angle' => mt_rand( -10, 10 ), 'text' => str_replace( '0', '&#0216;', substr( $captcha_value, $i, 1 ) ) );

		$im2 = imagecreatetruecolor(CAPTCHA_WIDTH, CAPTCHA_HEIGHT);
		$tile2 = imagecreatetruecolor(3, 3);

		imagefill( $tile2, 0, 0, $bg_color2 );
		imagesetpixel( $tile2, 1, 1, $bg_color );
		imagesettile( $im2, $tile2 );
		imagefilledrectangle( $im2, 1, 1, CAPTCHA_WIDTH - 2, CAPTCHA_HEIGHT - 2, IMG_COLOR_TILED );

		foreach( $coord as $c )
		{
			imagettftext( $im, $size, $c['angle'], $c['x'], $c['y'], $text_color, $font, $c['text'] );
			imagettftext( $im2, $size, $c['angle'], $c['x'], $c['y'], $text_color2, $font, $c['text'] );
		}

		$pol = array(); $step = rand( 10, 15 );
		for ( $i = 0; $i <= CAPTCHA_WIDTH; $i += $step )
		{
			$pol[] = $i; $pol[] = ( ( $i % ( $step * 2 ) ) ? -1 : 1 ) * mt_rand( 0, CAPTCHA_HEIGHT / 2 - 10 ) + CAPTCHA_HEIGHT / 2;
		}

		$pol[] = CAPTCHA_WIDTH; $pol[] = CAPTCHA_HEIGHT; $pol[] = 0; $pol[] = CAPTCHA_HEIGHT;

		imagesettile( $im, $im2 ); imagefilledpolygon( $im, $pol, count( $pol ) / 2, IMG_COLOR_TILED );

		header( 'Content-type: image/jpeg' );
		
		imagejpeg( $im );
	}
}
?>