<?php
/**
 * Генерация каптчи
 * @package		RBC_Contents_5_0
 * @subpackage lib
 */
include_once( '../../data/config/params.php' );

// Размер картинки
define( 'CAPTCHA_WIDTH', 193 );
define( 'CAPTCHA_HEIGHT', 55 );

// Фон над горами
define( 'CAPTCHA_BG_COLOR1_R', 255 );
define( 'CAPTCHA_BG_COLOR1_G', 255 );
define( 'CAPTCHA_BG_COLOR1_B', 255 );

// Цвет гор
define( 'CAPTCHA_BG_COLOR2_R', 0 );
define( 'CAPTCHA_BG_COLOR2_G', 0 );
define( 'CAPTCHA_BG_COLOR2_B', 0 );

// Цвет текста над горами
define( 'CAPTCHA_TEXT_COLOR1_R', 0 );
define( 'CAPTCHA_TEXT_COLOR1_G', 0 );
define( 'CAPTCHA_TEXT_COLOR1_B', 0 );

// Цвет текста на горах
define( 'CAPTCHA_TEXT_COLOR2_R', 255 );
define( 'CAPTCHA_TEXT_COLOR2_G', 255 );
define( 'CAPTCHA_TEXT_COLOR2_B', 255 );

captcha::display( $_GET['captcha_id'] );
?>
