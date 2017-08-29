<?php
/**
 * Объекты Content Management System (CMS)
 *
 * @package    RBC_Contents_5_0
 * @subpackage cms
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
$cms_objects=array(
	// Модули
	"PRG_MODULE"=>array(
		"title"=>"lang_prg_module_table",
		"type"=>"table",
		"class"=>"prg_module",
		"decorators"=>array("translate"),
		"fields"=>array(
			"TITLE"=>				array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "sort"=>"asc", "translate"=>1 ),
			"SYSTEM_NAME"=>			array("title"=>"lang_system_name", "type"=>"text", "show"=>1, "errors"=>_dirname_|_nonempty_, "group_by"=>array()),
			"IS_ELEMENTS"=>			array("title"=>"lang_prg_module_is_elements", "type"=>"checkbox", "show"=>1 ),
			"ELEMENT_NAME"=>		array("title"=>"lang_prg_module_element_name", "type"=>"text", "show"=>1, "translate"=>1 ),
		),
		"links"=>array(
			"MODULE_PARAM"=>array("show"=>1),
		),
	),
	// Информационные блоки дополняются полями, специфичными для CMS, а также непозволением удалять привязанный блок
	"INF_BLOCK"=>array(
		"fields"=>array(
			"PRG_MODULE_ID"=>		array("title"=>"lang_module", "type"=>"select2", "errors"=>_nonempty_, "show"=>1, "show_short"=>1, "filter"=>1, "filter_short"=>1, "fk_table"=>"PRG_MODULE", "no_change"=>1),
			"CACHE_TIME"=>			array("title"=>"lang_uncheckable_default_cache_time", "type"=>"text", "errors"=>_int_, "value"=>900),
			"SITE_ID"=>				array("title"=>"lang_site", "type"=>"select2", "fk_table"=>"SITE", "disabled"=>1, "errors"=>_nonempty_),
		),
		"links"=>array(
			"PAGE_AREA"=>array(),
		),
	),
	// Сайты
	"SITE"=>array(
		"title"=>"lang_sites",
		"type"=>"table",
		"class"=>"site",
		"fields"=>array(
			"TITLE"=>				array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "sort"=>"asc"),
			"HOST"=>				array("title"=>"lang_site_host", "type"=>"text", "show"=>1, "errors"=>_nonempty_, "group_by"=>array()),
			"PATH"=>				array("title"=>"lang_site_path", "type"=>"text", "errors"=>_nonempty_),
			"TEST_HOST"=>			array("title"=>"lang_site_test_host", "type"=>"text", "errors"=>_nonempty_, "group_by"=>array()),
			"TEST_PATH"=>			array("title"=>"lang_site_test_path", "type"=>"text", "errors"=>_nonempty_),
			"TITLE_SEPARATOR" =>	array("title"=>"lang_uncheckable_title_separator", "type"=>"text"),
		),
		"links"=>array(
			"AUTH_ACL"=>array("secondary_field"=>"OBJECT_ID", "on_delete_ignore"=>1),
		),
	),
	// Разделы клиентской части
	// Многие поля здесь не имеют префикса _nonempty_, так как эти поля могут менять обязательность заполнения в зависимости от типа страницы. Обязательность выставляется в классе
	"PAGE"=>array(
		"title"=>"lang_page_table",
		"type"=>"table",
		"decorators"=>array("version"),
		"class"=>"page",
		"parent_field"=>"PARENT_ID",
		"copy"=>1,
		"fields"=>array(
			"PAGE_TYPE"=>			array("title"=>"lang_section_type", "type"=>"select1", "errors"=>_nonempty_, "no_add"=>1, "no_change"=>1, "value_list"=>array(array("title"=>"lang_page_type_page", "value"=>"page", "selected"=>1), array("title"=>"lang_link", "value"=>"link"), array("title"=>"lang_page_type_folder", "value"=>"folder"))),
			"SITE_ID"=>				array("title"=>"lang_site", "type"=>"select2", "errors"=>_nonempty_, "fk_table"=>"SITE", "filter"=>1, "filter_short"=>1),
			"TEMPLATE_ID"=>			array("title"=>"lang_template", "type"=>"select2", "fk_table"=>"TEMPLATE", "errors"=>_nonempty_),
			"PARENT_ID"=>			array("title"=>"lang_page_parent", "type"=>"parent", "errors"=>_nonempty_),
			"TITLE"=>				array("title"=>"lang_page_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_),
			"DIR_NAME"=>			array("title"=>"lang_dir_name", "type"=>"text", "errors"=>_dirname_|_nonempty_, "group_by"=>array("PARENT_ID"), "show"=>1),
			"LANG_ID"=>				array("title"=>"lang_lang", "type"=>"select2", "errors"=>_nonempty_, "fk_table"=>"LANG"),
			"PAGE_ORDER"=>			array("title"=>"lang_order", "type"=>"order", "show"=>1, "group_by"=>array("PARENT_ID"), "sort"=>"asc"),
			"URL"=>					array("title"=>"lang_url", "type"=>"text", "errors"=>_nonempty_),
			"PAGE_IMG"=> 			array('title'=>'Изображение', 'type'=>'img','show'=>1, 'upload_dir' => 'upload/page_img', "is_main"=>1),
			"DESCRIPTION"=>			array("title"=>"Описание раздела", "type"=>"textarea", "editor"=>1, "rows" => 15),
			"IS_MENU_PUBLISHED"=>	array("title"=>"lang_page_is_menu_published", "type"=>"checkbox"),
			"IS_TITLE_SHOWED"=>		array("title"=>"lang_page_is_title_showed", "type"=>"checkbox"),
			"IS_PROTECTED"=>		array("title"=>"lang_page_is_protected", "type"=>"checkbox"),
			"META_TITLE"=>			array("title"=>"lang_page_meta_title", "type"=>"textarea", "no_add"=>1, "no_change"=>1),
			"META_KEYWORDS"=>		array("title"=>"lang_page_meta_keywords", "type"=>"textarea", "no_add"=>1, "no_change"=>1),
			"META_DESCRIPTION"=>	array("title"=>"lang_page_meta_description", "type"=>"textarea", "no_add"=>1, "no_change"=>1),
		),
		"links"=>array(
			"AUTH_ACL"=>array("secondary_field"=>"OBJECT_ID", "on_delete_ignore"=>1),
		),
	),
	// Экземпляры областей разделов клиентской части
	"PAGE_AREA"=>array(
		"title"=>"lang_page_area",
		"type"=>"internal_table",
		"fields"=>array(
			"PAGE_ID"=>				array("type"=>"int", "pk"=>1),
			"VERSION"=>				array("type"=>"int", "pk"=>1),
			"TEMPLATE_AREA_ID"=>	array("type"=>"int", "pk"=>1),
			"INF_BLOCK_ID"=>		array("type"=>"int"),
		),
		"indexes"=>array(
			"PA_IB_IDX" => array ("fields" => array ("INF_BLOCK_ID" => array())),
		),
	),
	// Параметры модулей в экземплярах областей разделов клиентской части
	"PAGE_AREA_PARAM"=>array(
		"title"=>"lang_page_area_param",
		"type"=>"internal_table",
		"fields"=>array(
			"PAGE_ID"=>				array("type"=>"int", "pk"=>1),
			"VERSION"=>				array("type"=>"int", "pk"=>1),
			"TEMPLATE_AREA_ID"=>	array("type"=>"int", "pk"=>1),
			"MODULE_PARAM_ID"=>		array("type"=>"int", "pk"=>1),
			"VALUE"=>				array("type"=>"text", "datatype_like"=>"text"),
		),
	),
	// Системные роли дополняются ссылкой на раздачу прав на разделы и сайты
	"AUTH_SYSTEM_ROLE"=>array(
		"m2m"=>array(
			"ACL_PAGE"=>array("m2m_table"=>"AUTH_ACL", "title"=>"lang_page_table", "secondary_table"=>"PAGE", "secondary_m2m_field"=>"OBJECT_ID", "tertiary_table"=>"AUTH_PRIVILEGE", "list_mode"=>array("by_auth_object"=>"PAGE")),
			"ACL_SITE"=>array("m2m_table"=>"AUTH_ACL", "title"=>"lang_sites", "secondary_table"=>"SITE", "secondary_m2m_field"=>"OBJECT_ID", "tertiary_table"=>"AUTH_PRIVILEGE", "list_mode"=>array("by_auth_object"=>"SITE")),
		),
	),	
	
	// Параметры модулей
	"MODULE_PARAM"=>array(
		"title"=>"lang_module_param_table",
		"type"=>"table",
		"decorators"=>array("translate"),
		"class"=>"module_param",
		"fields"=>array(
			"TITLE"=>				array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "translate"=>1 ),
			"PRG_MODULE_ID"=>		array("title"=>"lang_module", "type"=>"select2", "errors"=>_nonempty_, "fk_table"=>"PRG_MODULE", "filter"=>1, "show"=>1 ),
			"SYSTEM_NAME"=>			array("title"=>"lang_system_name", "type"=>"text", "errors"=>_nonempty_|_login_, "group_by"=>array("PRG_MODULE_ID"), "show"=>1),
			"PARAM_TYPE"=>			array("title"=>"lang_module_param_type", "type"=>"select1", "value_list"=>array( array('title'=>'lang_int', value=>'int'),
																													 array('title'=>'lang_float', value=>'float'),
																													 array('title'=>'lang_string', value=>'varchar'),
																													 array('title'=>'lang_list', value=>'select'), 
																													 array('title'=>'lang_template', value=>'template'), 
																													 array('title'=>'lang_page', value=>'page'), 
																													 array('title'=>'lang_text', value=>'textarea'), 
																													 array('title'=>'lang_table', value=>'table') ), "errors"=>_nonempty_, "no_change"=>1, "disabled"=>1, "show"=>1),
			"DEFAULT_VALUE"=>		array("title"=>"lang_default_value", "type"=>"text" ),
			"TABLE_NAME"=>			array("title"=>"lang_module_param_table_name", "type"=>"text", "errors"=>_nonempty_),
			"FIELD_NAME"=>			array("title"=>"lang_module_param_field_name", "type"=>"text", "errors"=>_nonempty_),
			"IS_LANG"=>				array("title"=>"lang_module_param_is_lang", "type"=>"checkbox" ),
		),
		"links"=>array(
			"PARAM_VALUE"=>array("show"=>1, "show_if"=>array("PARAM_TYPE"=>array("select", "template"))),
		),
	),
	
	// Значения параметров модулей
	"PARAM_VALUE"=>array(
		"title"=>"lang_param_value_table",
		"type"=>"table",
		"decorators"=>array("translate"),
		"class"=>"param_value",
		"copy"=>1,
		"fields"=>array(
			"TITLE"=>				array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "translate"=>1 ),
			"MODULE_PARAM_ID"=>		array("title"=>"lang_param_value_link_name", "type"=>"select2", "errors"=>_nonempty_, "fk_table"=>"MODULE_PARAM", "filter"=>1, "show"=>1, "no_change"=>1, "disabled"=>1, "list_mode"=>array("select_only"=>1)),
			"VALUE"=>				array("title"=>"lang_Value", "type"=>"text", "show"=>1, "errors"=>_nonempty_, "group_by"=>array("MODULE_PARAM_ID") ),
			"IS_DEFAULT"=>			array("title"=>"lang_default_value", "type"=>"checkbox", "show"=>1, "group_by"=>array("MODULE_PARAM_ID")),
		),
	),
	
	// Типы шаблонов
	"TEMPLATE_TYPE"=>array(
		"title"=>"lang_template_type_table",
		"type"=>"table",
		"decorators"=>array("translate"),
		"class"=>"template_type",
		"copy"=>1,
		"fields"=>array(
			"TITLE"=>				array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "translate"=>1 ),
			"HTML_MAP"=>			array("title"=>"lang_template_type_html_map", "type"=>"textarea", "rows"=>10, "errors"=>_nonempty_ ),
		),
		"links"=>array(
			"TEMPLATE"=>			array("show"=>1),
		),
		"m2m"=>array(
			"AREA_MAP"=>			array("m2m_table"=>"TEMPLATE_AREA_MAP", "title"=>"lang_template_area", "secondary_table"=>"TEMPLATE_AREA", "secondary_m2m_field"=>"TEMPLATE_AREA_ID" ),
		),
	),
	
	// Области шаблонов
	"TEMPLATE_AREA"=>array(
		"title"=>"lang_template_area",
		"type"=>"table",
		"class"=>"template_area",
		"decorators"=>array("translate"),
		"fields"=>array(
			"TEMPLATE_TYPE_ID"=>	array("title"=>"lang_template_type_link_name", "type"=>"select2", "virtual"=>1, "fk_table"=>"TEMPLATE_TYPE", "no_add"=>1, "no_change"=>1, "filter"=>1, "filter_short"=>1),
			"TITLE"=>				array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "translate"=>1 ),
			"SYSTEM_NAME"=>			array("title"=>"lang_system_name", "type"=>"text", "show"=>1, "show_short"=>1, "errors"=>_nonempty_|_login_, "group_by"=>array() ),
			"DESCRIPTION"=>			array("title"=>"lang_description", "type"=>"textarea", "rows"=>10, "translate"=>1 ),
			"IS_MAIN"=>				array("title"=>"lang_template_area_main", "type"=>"checkbox", "show"=>1, "show_short"=>1 ),
			"AREA_ORDER"=>			array("title"=>"lang_order", "type"=>"order", "show"=>1, "sort"=>"asc" ),
		),
	),
	// Привязка областей шаблонов к типам шаблонов
	"TEMPLATE_AREA_MAP"=>array(
		"title"=>"lang_template_area_map",
		"type"=>"internal_table",
		"fields"=>array(
			"TEMPLATE_AREA_ID"=>	array("type"=>"int", "pk"=>1),
			"TEMPLATE_TYPE_ID"=>	array("type"=>"int", "pk"=>1),
		),
	),	
	// Шаблоны
	"TEMPLATE"=>array(
		"title"=>"lang_template_table",
		"type"=>"table",
		"class"=>"template",
		"decorators"=>array("translate"),
		"copy"=>1,
		"fields"=>array(
			"TITLE"=>				array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "translate"=>1 ),
			"TEMPLATE_TYPE_ID"=>	array("title"=>"lang_template_type_link_name", "type"=>"select2", "errors"=>_nonempty_, "fk_table"=>"TEMPLATE_TYPE", "show"=>1, "filter"=>1 ),
			"TEMPLATE_DIR"=>		array("title"=>"lang_template_dir_name", "type"=>"text", "errors"=>_dirname_|_nonempty_, "group_by"=>array(), "show"=>1),
			"LANG_ID"=>				array("title"=>"lang_lang", "type"=>"select2", "errors"=>_nonempty_, "fk_table"=>"LANG", "show"=>1, "filter"=>1),
			"DOCTYPE"=>				array("title"=>"lang_template_doctype", "type"=>"textarea"),
		),
	),
	
	// Справочник системных слов
	"SYSTEM_WORD"=>array(
		"title"=>"lang_system_word_table",
		"type"=>"table",
		"decorators"=>array("lang"),
		"class"=>"system_word",
		"fields"=>array(
			"SYSTEM_NAME"=>			array("title"=>"lang_system_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_login_|_nonempty_, "width"=>"20%", "no_change"=>1, "disabled"=>1, "group_by"=>array("PRG_MODULE_ID", "LANG_ID") ),
			"VALUE"=>				array("title"=>"lang_Value", "type"=>"text", "errors"=>_nonempty_, "width"=>"60%" ),
			"PRG_MODULE_ID"=>		array("title"=>"lang_module", "type"=>"select2", "fk_table"=>"PRG_MODULE", "show"=>1, "filter"=>1, "no_change"=>1, "disabled"=>1, "width"=>"20%" ),
		),
	),
	
	"EXPORT_STRUCTURE" => array (
		"title" => "lang_export_structure",
		"type" => "tool",
		"class" => "export_structure",
	),
	
	"IMPORT_STRUCTURE" => array (
		"title" => "lang_import_structure",
		"type" => "tool",
		"class" => "import_structure",
	),
	
	// Журнал импорта для отката
	"IMPORT_LOG" => array (
		"title" => "lang_import_log",
		"type" => "internal_table",
		"fields" => array (
			"IMPORT_LOG_ID" =>	array ("type" =>  "int", "pk" => 1, "auto_increment" => 1),
			"CONTENT_ID" => array ("type" => "int"),
			"TE_OBJECT_ID" => array ("type" => "int"),
		)
	),
	
);
?>
