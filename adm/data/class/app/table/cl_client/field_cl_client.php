<?php
include_once( params::$params['adm_data_server']['value'] . 'class/te/table/auth_user/field_auth_user.php' );

/**
 * Класс работы с полями для Пользователей.
 *
 * Отличается от стандартного тем, что вводит новый тип поля password_md5, который никому кроме пользователей не нужен
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2006 RBC SOFT
 */

class field_cl_client extends field_auth_user{
}
?>