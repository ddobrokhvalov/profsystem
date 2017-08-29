<?php
/**
 * Заголовок раздела клиентской части
 *
 * @package    RBC_Contents_5_0
 * @subpackage cms
 */
include_once("{{$params_path}}");
include_once(params::$params["common_data_server"]["value"]."module/module/module.php");

{{if $page.IS_PROTECTED}}
include_once( params::$params['common_data_server']['value'] . 'cms_class/client_access.php' );
if ( !client_access::check_autorization() || !client_access::check_rights_on_page( {{$page.PAGE_ID}} ) )
	client_access::permission_denied( '{{$page.ROOT_DIR}}' );
{{/if}}

{{if !$page.META_TITLE}}
// Общие для страницы компоненты заголовка
$title[]="{{$site_title|escape}}";
{{if $page.IS_TITLE_SHOWED}}
$title[]="{{$page.TITLE|escape}}";
{{/if}}
{{/if}}

$keywords="{{$page.META_KEYWORDS|escape}}";
$description="{{$page.META_DESCRIPTION|escape}}";

// Инициализация и исполнение модулей
$module_info=array();
{{section loop=$areas name=area}}
// Блок '{{$areas[area].INF_BLOCK_TITLE|escape}}' ({{$areas[area].INF_BLOCK_ID}}) модуля '{{$areas[area].PRG_MODULE_TITLE|escape}}' в области '{{$areas[area].TITLE|escape}}' ({{$areas[area].SYSTEM_NAME}}, {{$areas[area].TEMPLATE_AREA_ID}})
${{$areas[area].instance_name}}=module::factory("{{$areas[area].PRG_MODULE_SYSTEM_NAME}}");
${{$areas[area].instance_name}}->init(
	{{$areas[area].env}}+array("is_print"=>(bool)$_GET["print"]),
	{{$areas[area].view_param}},
	$module_info
);
{{if $areas[area].IS_MAIN}}
$title[]=htmlspecialchars(${{$areas[area].instance_name}}->get_title(), ENT_QUOTES);

$main_keywords=htmlspecialchars(${{$areas[area].instance_name}}->get_keywords(), ENT_QUOTES);
$main_description=htmlspecialchars(${{$areas[area].instance_name}}->get_description(), ENT_QUOTES);
if ( $main_keywords ) $keywords = $main_keywords;
if ( $main_description ) $description = $main_description;
{{/if}}

$bench->register(bench::bencher("all_parts"), "Block '<b>{{$areas[area].INF_BLOCK_TITLE|escape}}</b>' ({{$areas[area].INF_BLOCK_ID}}) of module '<i>{{$areas[area].PRG_MODULE_TITLE|escape}}</i>' in the area '<i>{{$areas[area].TITLE|escape}}</i>' ({{$areas[area].SYSTEM_NAME}}, {{$areas[area].TEMPLATE_AREA_ID}})");//
{{/section}}

{{if !$page.META_TITLE}}
// Сбор заголовка страницы
foreach($title as $key=>$item){
	if(!$item){
		unset($title[$key]);
	}
}
$title=join("{{$title_separator}}", $title);
{{else}}
$title="{{$page.META_TITLE|escape}}";
{{/if}}

{{if $page.DOCTYPE}}
// Доктайп
echo"{{$page.DOCTYPE|escape:"javascript"}}\n";
{{/if}}

//echo"<!--{{$TOOLBAR_INFO}}-->\n";
//echo"<!--{{$TOOLBAR_PAGE_INFO}}-->\n";

// Репорт о времени выполнения выводим только в том случае, если об этом просят
if($_GET["debug"]){
	$bench->echo_report(true);//
}
?>