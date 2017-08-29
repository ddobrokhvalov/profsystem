<?php
/**
 * Префиксы проверки полей на неправильное заполнение для использования в def-файлах
 *
 * @package    RBC_Contents_5_0
 * @subpackage core
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
define('_no_error_',	0);
define('_nonempty_',	1);
define('_email_',		2);
define('_date_',		4);
define('_time_',		8);
define('_datetime_',	16);
define('_alphastring_',	32);
define('_login_',		64);
define('_dirname_',		128);
//define('_radio_',		256);
//define('_radioalt_',	512);
define('_int_',			2048);
define('_float_',		4096);
?>
