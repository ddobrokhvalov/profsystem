<?php
/**
 * index.php для разделов с типом "folder"
 *
 * @package    RBC_Contents_5_0
 * @subpackage cms
 */
include_once("{{$params_path}}");
include_once(params::$params["common_data_server"]["value"]."module/module/module.php");

// Инициализация и исполнение модулей
$module_info=array();
$menu=module::factory("MENU");
$menu->go_to_next_level({{$page_id}}, {{$version}});
?>