<?php
$lib_dirname=dirname(__FILE__);
require_once($lib_dirname."/../config/params.php");
include_once($lib_dirname."/lib.php");
include_once($lib_dirname."/db/db.php");
include_once($lib_dirname."/mail/vlibMimeMail.php");
include_once($lib_dirname."/get_tree.php");
include_once($lib_dirname."/filesystem.php");
include_once($lib_dirname."/upload.php");
include_once($lib_dirname."/captcha.php");
include_once($lib_dirname."/system_params.php");
include_once($lib_dirname."/xml/ExpatXMLParser.php");
include_once($lib_dirname."/xml/xml_processor.php");

/**
 * Абстрактная библиотека
 *
 * Самостоятельно не применяется. Служит для группировки библиотек.
 * Все библиотеки подключаются в этом файле (кроме {@link bench}, которая подключается самостоятельно,
 * чтобы можно было замерить даже время подключения библиотек)
 *
 * @package    RBC_Contents_5_0
 * @subpackage lib
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
abstract class lib_abstract{

}
?>