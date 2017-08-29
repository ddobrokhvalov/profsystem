<?php
/**
 * Объекты Table Engine (TE)
 *
 * @package		RBC_Contents_5_0
 * @subpackage te
 * @copyright	Copyright (c) 2006 RBC SOFT
 * @todo Придумать как обойти проблему с выставлением ширины в коротких списках (если ширина выставлялась по полю, которое в коротком списке не выводится)
 * @todo Подумать над фичей мультисортировки по умолчанию, то есть, если указано у нескольких полей "sort", то сортировать последовательно по всем ним в порядке расположения в деф-файле. Непонятно правда, как отображать такое направление сортировки в админке, а еще это направление будет теряться, если покликать по колонкам
 * @todo Нужно ли красиво (через языковые константы) именовать поля внутренних таблиц, равно как и сами таблицы? Сейчас туда на всякий случай помещаются затычки, на которые будет ругаться автотест
 * @todo Наверно у параметров нужно классы переименовать также как объекты ТЕ, которые их описывают
 */
$te_objects=array(
	// Администраторы
	"AUTH_USER"=>array(
		"title"=>"lang_administrators",
		"type"=>"table",
		"class"=>"auth_user",
		"fields"=>array(
			"SURNAME"=>			array("title"=>"lang_surname", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "sort"=>"asc"),
			"NAME"=>			array("title"=>"lang_pure_name", "type"=>"text"),
			"PATRONYMIC"=>		array("title"=>"lang_patronymic", "type"=>"text"),
			"LOGIN"=>			array("title"=>"lang_login", "type"=>"text", "errors"=>_nonempty_|_login_, "show"=>1, "group_by"=>array()),
			"PASSWORD_MD5"=>	array("title"=>"lang_password", "type"=>"password_md5", "datatype_like"=>"text", "errors"=>_login_), // Специальный тип поля, введенный в field_auth_user.php
			"EMAIL"=>			array("title"=>"lang_email", "type"=>"text", "errors"=>_email_),
			"IS_LOCKED"=>		array("title"=>"lang_auth_user_locked", "type"=>"checkbox"),
			"BAD_PASSWORD_MAX_COUNT" => array("title"=>"lang_bad_password_max_count", "type"=>"int", "is_null"=>1),
			"BAD_PASSWORD_COUNT"=>array("title"=>"lang_bad_password_count", "type"=>"int", "no_change"=>1, "no_add"=>1),
			"CL_CLIENT_ID"=>	array("type"=>"int", "no_add"=>1, "no_change"=>1),
		),
		"m2m"=>array(
			"AUTH_USER_SYSTEM_ROLE"=>array("secondary_table"=>"AUTH_SYSTEM_ROLE"),
		),
		"links"=>array(
			// Роли от пользователя отвязываются кастомным методом
			"AUTH_SYSTEM_ROLE"=>array("on_delete_ignore"=>1),
			"FAVOURITE"=>array("on_delete_cascade"=>1),
		),
	),
	// AUTH_USER_SYSTEM_ROLE - привязка пользователей системы к системным ролям
	"AUTH_USER_SYSTEM_ROLE"=>array(
		"title"=>"lang_auth_user_system_role",
		"type"=>"internal_table",
		"fields"=>array(
			"AUTH_USER_ID"=>			array("type"=>"int", "pk"=>1),
			"AUTH_SYSTEM_ROLE_ID"=>		array("type"=>"int", "pk"=>1),
		),
	),
	// Типы объектов разделения доступа - блоки, разделы, система и т.д.
	"AUTH_OBJECT_TYPE"=>array(
		"title"=>"lang_auth_object_type_table",
		"type"=>"table",
		"decorators"=>array("translate"),
		"fields"=>array(
			"TITLE"=>				array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "sort"=>"asc","translate"=>1),
			"SYSTEM_NAME"=>			array("title"=>"lang_system_name", "type"=>"text", "errors"=>_nonempty_|_login, "show"=>1, "group_by"=>array()),
		),
		"links"=>array(
			"AUTH_PRIVILEGE"=>array("show"=>1),
			"AUTH_OBJECT_TYPE_TABLE"=>array("show"=>1),
		),
	),
	// Таблицы, которые содержат записи, охватываемые типами разделения доступа
	"AUTH_OBJECT_TYPE_TABLE"=>array(
		"title"=>"lang_auth_object_type_table_table",
		"type"=>"table",
		"fields"=>array(
			"SYSTEM_NAME"=>			array("title"=>"lang_system_name", "type"=>"text", "errors"=>_nonempty_|_login, "is_main"=>1, "group_by"=>array("AUTH_OBJECT_TYPE_ID")),
			"AUTH_OBJECT_TYPE_ID"=>	array("title"=>"lang_auth_privilege_auth_object_type_id", "type"=>"select2", "errors"=>_nonempty_, "show"=>1, "filter"=>1, "fk_table"=>"AUTH_OBJECT_TYPE"),
		),
	),
	// Права на объекты системы
	"AUTH_PRIVILEGE"=>array(
		"title"=>"lang_auth_privilege_table",
		"type"=>"table",
		"class"=>"auth_privilege",
		"decorators"=>array("translate"),
		"fields"=>array(
			"TITLE"=>				array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "sort"=>"asc", "translate"=>1),
			"SYSTEM_NAME"=>			array("title"=>"lang_system_name", "type"=>"text", "errors"=>_nonempty_|_login, "show"=>1, "group_by"=>array("AUTH_OBJECT_TYPE_ID")),
			"DESCRIPTION"=>			array("title"=>"lang_description", "type"=>"textarea", "show"=>1, "width"=>"100%", "translate"=>1),
			"AUTH_OBJECT_TYPE_ID"=>	array("title"=>"lang_auth_privilege_auth_object_type_id", "type"=>"select2", "errors"=>_nonempty_, "show"=>1, "filter"=>1, "fk_table"=>"AUTH_OBJECT_TYPE"),
		),
	),
	// Системные роли
	"AUTH_SYSTEM_ROLE"=>array(
		"title"=>"lang_auth_system_role_table",
		"type"=>"table",
		"parent_field"=>"PARENT_ID",
		"class"=>"auth_system_role",
		"decorators"=>array("translate"),
		"fields"=>array(
			"TITLE"=>		array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "sort"=>"asc", "translate"=>1),
			"PARENT_ID"=>	array("title"=>"lang_parent_record", "type"=>"parent", "errors"=>_nonempty_),
			"SYSTEM_NAME"=>	array("title"=>"lang_system_name", "type"=>"text", "no_add"=>1, "no_change"=>1),
			"AUTH_USER_ID"=>array("title"=>"lang_administrator", "type"=>"select2", "fk_table"=>"AUTH_USER", "no_add"=>1, "no_change"=>1, "disabled"=>1),
		),
		"m2m"=>array(
			"ACL_IB"=>	array("m2m_table"=>"AUTH_ACL", "title"=>"lang_inf_block_table", "secondary_table"=>"INF_BLOCK", "secondary_m2m_field"=>"OBJECT_ID", "tertiary_table"=>"AUTH_PRIVILEGE", "list_mode"=>array("by_auth_object"=>"inf_block")),
			"ACL_IBWF"=>array("m2m_table"=>"AUTH_ACL", "title"=>"lang_auth_acl_ibwf", "secondary_table"=>"INF_BLOCK", "secondary_m2m_field"=>"OBJECT_ID", "tertiary_table"=>"AUTH_PRIVILEGE", "list_mode"=>array("by_auth_object"=>"workflow")),
			"ACL_SS"=>	array("m2m_table"=>"AUTH_ACL", "title"=>"lang_auth_system_section_table", "secondary_table"=>"AUTH_SYSTEM_SECTION", "secondary_m2m_field"=>"OBJECT_ID", "tertiary_table"=>"AUTH_PRIVILEGE", "list_mode"=>array("by_auth_object"=>"auth_system_section")),
			"ACL_SSWF"=>array("m2m_table"=>"AUTH_ACL", "title"=>"lang_auth_acl_sswf", "secondary_table"=>"AUTH_SYSTEM_SECTION", "secondary_m2m_field"=>"OBJECT_ID", "tertiary_table"=>"AUTH_PRIVILEGE", "list_mode"=>array("by_auth_object"=>"workflow")),
			"ACL_SP"=>	array("m2m_table"=>"AUTH_ACL", "title"=>"lang_auth_acl_sp", "secondary_table"=>"AUTH_PRIVILEGE", "tertiary_table"=>"AUTH_SYSTEM", "tertiary_m2m_field"=>"OBJECT_ID", "list_mode"=>array("by_auth_object"=>"system")),
		),
	),
	// AUTH_ACL - access control list - м2м-таблица со всеми назначенными правами
	"AUTH_ACL"=>array(
		"title"=>"lang_auth_acl",
		"type"=>"internal_table",
		"fields"=>array(
			"OBJECT_ID"=>					array("type"=>"int", "pk"=>1),
			"AUTH_PRIVILEGE_ID"=>			array("type"=>"int", "pk"=>1),
			"AUTH_SYSTEM_ROLE_ID"=>			array("type"=>"int", "pk"=>1),
			"AUTH_OBJECT_TYPE_TABLE_ID"=>	array("type"=>"int", "pk"=>1),
		),
	),

	// Системные разделы
	"AUTH_SYSTEM_SECTION"=>array(
		"title"=>"lang_auth_system_section_table",
		"type"=>"table",
		"decorators"=>array("translate"),
		"parent_field"=>"PARENT_ID",
		"class"=>"auth_system_section",
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "translate"=>1),
			"PARENT_ID"=>		array("title"=>"lang_parent_record", "type"=>"parent", "errors"=>_nonempty_),
			"TE_OBJECT_ID"=>	array("title"=>"lang_auth_system_section_object_name", "type"=>"select2", "show"=>1, "fk_table"=>"TE_OBJECT"),
			"OBJECT_PARAM"=>	array("title"=>"lang_auth_system_section_object_param", "type"=>"text"),
			"FILTER_PRESET"=>	array("title"=>"lang_auth_system_section_filter_preset", "type"=>"text"),
			"SECTION_TYPE"=>	array("title"=>"lang_section_type", "type"=>"select1", "value_list"=>array(
				array( 'title'=>'lang_auth_system_settings', value=>'settings' ),
				array( 'title'=>'lang_auth_system_content', value=>'content' ),
 				array( 'title'=>'lang_auth_system_utility', value=>'utility' ) ), "errors"=>_nonempty_ ),
			"SECTION_ORDER"=>	array("title"=>"lang_order", "type"=>"order", "show"=>1, "sort"=>"asc", "group_by"=>array("PARENT_ID")),
		),
		"links"=>array(
			"AUTH_ACL"=>			array("secondary_field"=>"OBJECT_ID", "on_delete_ignore"=>1),
		),
	),
	// Утилита отображения системных разделов
	"AUTH_SYSTEM_SECTION_TOOL" => array (
		"title" => "lang_auth_system_section_tool",
		"type" => "tool",
 		"class" => "auth_system_section_tool"
	),
	
	// Система - немного искусственная табличка, призванная удобно вписать тип разделения доступа "система" в существующий механизм разделения доступа
	"AUTH_SYSTEM"=>array(
		"title"=>"lang_auth_system_table",
		"type"=>"table",
		"decorators"=>array("translate"),
		"no_add"=>1,
		"no_change"=>1,
		"no_delete"=>1,
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "sort"=>"asc", "translate"=>1),
		),
	),
	
	// разрешенные IP
	"AUTH_IP_FILTER" => array (
		"title" => "lang_auth_ip_filter",
		"type" => "table",
		"decorators"=>array("translate"),
		"class" => "auth_ip_filter",
		"fields" => array (
			"AUTH_IP_FILTER_ID" => array ("title"=>"lang_IP", "type"=>"int"),
			"TITLE" => array ("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "translate"=>1),
			"START_IP" => array ("title"=>"lang_auth_ip_filter_start_ip", "type"=>"ip", "show"=>1),
			"FINISH_IP" => array ("title"=>"lang_auth_ip_filter_finish_ip", "type"=>"ip", "show"=>1),
		),
	),
	
	
	// Объекты табличного движка - хранит идентификаторы, автоматически выдаваемые объектам 
	"TE_OBJECT"=>array(
		"title"=>"lang_te_object_table",
		"type"=>"table",
		"decorators"=>array("translate"),
		"no_add"=>1,
		"no_delete"=>1,
		"class"=>"te_object",
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "sort"=>"asc", "translate"=>1, "no_change"=>1, "disabled"=>1),
			"SYSTEM_NAME"=>		array("title"=>"lang_system_name", "type"=>"text", "no_add"=>1, "no_change"=>1, "show"=>1, "show_short"=>1, "no_change"=>1, "disabled"=>1),
			"WF_WORKFLOW_ID"=>	array("title"=>"lang_wf_state_workflow", "type"=>"select2", "fk_table"=>"WF_WORKFLOW", "no_add"=>1, "show"=>1, "filter"=>1, "errors"=>_nonempty_ ),
			"TAXONOMY_ALLOWED"=>array("title"=>"lang_taxonomy_allowed", "type"=>"checkbox", "no_add"=>1, "no_change"=>1),
			"IS_UNBLOCKABLE" => array("title"=>"lang_unblockable_table", "type"=>"checkbox"),
		),
	),
	// Языки
	"LANG"=>array(
		"title"=>"lang_lang_table",
		"type"=>"table",
		"class" => "lang",
		"decorators"=>array("translate"),
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "translate"=>1, "errors"=>_nonempty_),
			"ORIGINAL_NAME" =>	array("title"=>"lang_original_name", "type"=>"text", "errors"=>_nonempty_, "show"=>1),
			"PRIORITY"=>		array("title"=>"lang_lang_priority", "type"=>"checkbox", "show"=>1, "group_by"=>array()),
			"ROOT_DIR"=>		array("title"=>"lang_lang_root_dir", "type"=>"text", "errors"=>_nonempty_|_dirname_, "show"=>1, "show_short"=>1, "group_by" => array()),
			"IN_ADMIN" => array ("title"=>"lang_lang_in_admin", "type"=>"checkbox"),
			"IN_CONTENT" => array ("title"=>"lang_lang_content", "type"=>"checkbox"),
		),
	),
	// Данные переводимых таблиц
	"TABLE_TRANSLATE"=>array(
		"title"=>"lang_table_translate_table",
		"type"=>"internal_table",
		"fields"=>array(
			"TE_OBJECT_ID"=>	array("type"=>"int"),
			"LANG_ID"=>			array("type"=>"int"),
			"CONTENT_ID"=>		array("type"=>"int"),
			"FIELD_NAME"=>		array("type"=>"text"),
			"VALUE"=>			array("type"=>"text"),
		),
		"indexes"=>array(
			"TT_TLC_IDX" => array ("fields" => array ("TE_OBJECT_ID" => array(), "LANG_ID" => array(), "CONTENT_ID" => array())),
			"TT_TLC_IDX_UNIQ" => array ("unique"=>1, "fields" => array ("TE_OBJECT_ID" => array(), "LANG_ID" => array(), "CONTENT_ID" => array(), "FIELD_NAME" => array())),
		),
	),
	// Информационные блоки (или просто блоки)
	"INF_BLOCK"=>array(
		"title"=>"lang_inf_block_table",
		"type"=>"table",
		"class"=>"inf_block",
		"fields"=>array(
			"TITLE"=>				array("title"=>"lang_name", "type"=>"text", "show"=>1, "show_short"=>1, "is_main"=>1, "sort"=>"asc", "errors"=>_nonempty_ ),
			"TE_OBJECT_ID"=>		array("title"=>"lang_table", "type"=>"select2", "errors"=>_nonempty_, "fk_table"=>"TE_OBJECT", "no_change"=>1, "no_add"=>1 ),
			"WF_WORKFLOW_ID"=>		array("title"=>"lang_wf_state_workflow", "type"=>"select2", "fk_table"=>"WF_WORKFLOW", "filter"=>1, "errors"=>_nonempty_ ),
		),
		"links"=>array(
			"CONTENT_MAP"=>			array(),
			"AUTH_ACL"=>			array("secondary_field"=>"OBJECT_ID", "on_delete_ignore"=>1),
		),
	),
	// CONTENT_MAP - привязка контента к блокам
	"CONTENT_MAP"=>array(
		"title"=>"lang_content_map_table",
		"type"=>"internal_table",
		"fields"=>array(
			"CONTENT_ID"=>			array("type"=>"int", "pk"=>1),
			"INF_BLOCK_ID"=>		array("type"=>"int", "pk"=>1),
			"IS_MAIN"=>				array("type"=>"int"),
		),
		"indexes"=>array(
			"CM_IB_IDX"=>array("fields"=>array("INF_BLOCK_ID"=>array())),
		),
	),
//////////////////////////////////////////////////////////////////////////////////////////////// Workflow
	// Workflow
	"WF_WORKFLOW"=>array(
		"title"=>"lang_wf_workflow_table",
		"type"=>"table",
		"class"=>"wf_workflow",
		"decorators"=>array("translate"),
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "sort"=>"asc", "translate"=>1, "errors"=>_nonempty_),
			"IS_DEFAULT"=>		array("title"=>"lang_wf_workflow_is_default", "type"=>"checkbox", "show"=>1, "group_by"=>array()),
			"WORKFLOW_TYPE"=>	array("title"=>"lang_wf_workflow_workflow_type", "type"=>"select1", "value_list"=>array(array( 'title'=>'lang_wf_workflow_use_versions', value=>'use_versions' ), array( 'title'=>'lang_wf_workflow_dont_use_versions', value=>'dont_use_versions' )), "errors"=>_nonempty_, "show"=>1, "filter"=>1, "no_change"=>1, "disabled"=>1),
		),
		"links"=>array(
			"WF_STATE"=>array("show"=>1),
			"WF_RESOLUTION"=>array("show"=>1, "on_delete_ignore"=>1),
		),
	),
	// Состояния для Workflow
	"WF_STATE"=>array(
		"title"=>"lang_wf_state_table",
		"type"=>"table",
		"class" => "wf_state",
		"decorators"=>array("translate"),
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "sort"=>"asc", "translate"=>1, "errors"=>_nonempty_),
			"SYSTEM_NAME"=>		array("title"=>"lang_system_name", "type"=>"text", "no_add"=>1, "no_change"=>1),
			"WF_WORKFLOW_ID"=>	array("title"=>"lang_wf_state_workflow", "type"=>"select2", "errors"=>_nonempty_, "fk_table"=>"WF_WORKFLOW", "show"=>1, "filter"=>1, "no_change"=>1, "disabled"=>1 ),
			"VERSIONS"=>		array("title"=>"lang_wf_state_versions", "type"=>"select1", "value_list"=>array(array( 'title'=>'lang_wf_state_record_exists', value=>'one_version'), array( 'title'=>'lang_test_version', value=>'test_version' ), array( 'title'=>'lang_wf_workflow_test_and_work_version', value=>'two_versions' ), array( 'title'=>'lang_wf_workflow_record_deleted', value=>'no_version' )), "errors"=>_nonempty_, "show"=>1, "filter"=>1),
		),
		"links"=>array(
			"WF_EDGE_STATE"=>array("on_delete_cascade"=>1),
		),
	),
	// Резолюции для Workflow
	"WF_RESOLUTION"=>array(
		"title"=>"lang_resolutions",
		"type"=>"table",
		"class"=>"wf_resolution",
		"decorators"=>array("translate"),
		"fields"=>array(
			"TITLE"=>				array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "sort"=>"asc", "translate"=>1),
			"WF_WORKFLOW_ID"=>		array("title"=>"lang_wf_state_workflow", "type"=>"select2", "errors"=>_nonempty_, "fk_table"=>"WF_WORKFLOW", "filter"=>1, "virtual"=>1, "no_change"=>1, "disabled"=>1),
			"FIRST_STATE_ID"=>		array("title"=>"lang_wf_resolution_first_state", "type"=>"select2", "errors"=>_nonempty_, "fk_table"=>"WF_STATE", "show"=>1, "filter"=>1 ),
			"LAST_STATE_ID"=>		array("title"=>"lang_wf_resolution_last_state", "type"=>"select2", "errors"=>_nonempty_, "fk_table"=>"WF_STATE", "show"=>1, "filter"=>1 ),
			"MAIN_VERSION"=>		array("title"=>"lang_wf_resolution_main_version", "type"=>"select1", "value_list"=>array(
																												array( 'title'=>'lang_work_version', value=>'0' ),
																												array( 'title'=>'lang_test_version', value=>'1' ),
																												array( 'title'=>'lang_wf_resolution_no_version', value=>'2' ) ), "errors"=>_nonempty_, "datatype_like"=>"int"),
			"LANG_ID"=>				array("title"=>"lang_wf_resolution_lang", "type"=>"select2", "fk_table"=>"LANG", "show"=>1, "filter"=>1 ),
			"QUORUM" =>				array("title"=>"lang_wf_quorum", "type"=>"int", "errors"=>_int_),
		),
		"links"=>array(
			"WF_PRIVILEGE_RESOLUTION"=>array("on_delete_cascade"=>1),
		),
	),
	// Граничные состояния
	"WF_EDGE_STATE"=>array(
		"title"=>"lang_wf_edge_state",
		"type"=>"internal_table",
		"fields"=>array(
			"WF_STATE_ID"=>					array("type"=>"int", "pk"=>1),
			"LANG_ID"=>						array("type"=>"int", "pk"=>1),
			"EDGE_TYPE"=>					array("type"=>"text",),
			"EDGE_TYPE"=>					array("type"=>"select1", "value_list"=>array(array( value=>'new'), array( value=>'deleted' ))),
		),
	),
	// Привязка резолюций к правам
	"WF_PRIVILEGE_RESOLUTION"=>array(
		"title"=>"lang_wf_privilege_resolution",
		"type"=>"internal_table",
		"fields"=>array(
			"AUTH_PRIVILEGE_ID"=>			array("type"=>"int", "pk"=>1),
			"WF_RESOLUTION_ID"=>			array("type"=>"int", "pk"=>1),
		),
	),
	// Учет проголосовавших прав в параллельном наложении резолюций
	"WF_APPROVED"=>array(
		"title"=>"lang_wf_approved",
		"type"=>"internal_table",
		"fields"=>array(
			"AUTH_PRIVILEGE_ID"=>		array("type"=>"int", "pk"=>1),
			"CONTENT_ID"=>				array("type"=>"int", "pk"=>1),
			"LANG_ID"=>					array("type"=>"int", "pk"=>1),
			"TE_OBJECT_ID"=>			array("type"=>"int", "pk"=>1),
			"AUTH_USER_ID"=>			array("type"=>"int"),
			"COMMENTS"=>				array("type"=>"text"),
			"OPERATION_DATE"=>			array("type"=>"datetime"),
		),
	),
	// Сохранение пользователей, которых нужно оповещать о переходе, в параллельном наложении резолюций
	"WF_NOTIFY"=>array(
		"title"=>"lang_wf_notify",
		"type"=>"internal_table",
		"fields"=>array(
			"AUTH_USER_ID"=>			array("type"=>"int", "pk"=>1),
			"CONTENT_ID"=>				array("type"=>"int", "pk"=>1),
			"LANG_ID"=>					array("type"=>"int", "pk"=>1),
			"TE_OBJECT_ID"=>			array("type"=>"int", "pk"=>1),
			"OPERATION_DATE"=>			array("type"=>"datetime"),
		),
	),
////////////////////////////////////////////////////////////////////////////////////////////////
	// Системные URL
	"SYSTEM_URL"=>array(
		"title"=>"lang_system_url_table",
		"type"=>"table",
		"decorators"=>array("lang"),
		"class"=>"system_url",
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "width"=>"30%", "errors"=>_nonempty_, "no_change"=>1, "disabled"=>1 ),
			"URL"=>				array("title"=>"lang_url", "type"=>"text", "errors"=>_nonempty_ ),
			"SITE_ID"=>			array("title"=>"lang_site", "type"=>"select2", "errors"=>_nonempty_, "fk_table"=>"SITE", "no_change"=>1, "disabled"=>1 ),
			"SYSTEM_URL_TYPE"=>	array("title"=>"lang_system_url_type", "type"=>"select1", "value_list"=>array(	array('title'=>'lang_authentication', value=>'1'),
																												array('title'=>'lang_access_denied', value=>'2') ), "errors"=>_nonempty_, "no_change"=>1, "disabled"=>1 ),
		)
	),
	// Файловый менеджер
	"FM"=>array(
		"title"=>"lang_fm_tool",
		"type"=>"tool",
		"class"=>"fm",
	),
	// Корневые каталоги (используются файловым менеджером как точки входа на файловую систему)
	"ROOT_DIR"=>array(
		"title"=>"lang_root_dir_table",
		"type"=>"table",
		"decorators"=>array("translate"),
		"class"=>"root_dir",
		"fields"=>array(
			"TITLE"=>				array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "translate"=>1 ),
			"ALIAS"=>				array("title"=>"lang_root_dir_alias", "type"=>"text", "show"=>1, "errors"=>_nonempty_| _dirname_, "group_by"=>array() ),
			"ROOT_DIR_TYPE"=>		array("title"=>"lang_root_dir_type", "type"=>"select1", "value_list"=>array(	array('title'=>'lang_root_dir_adm_data', value=>'1'),
																													array('title'=>'lang_root_dir_common_data', value=>'2'),
																													array('title'=>'lang_root_dir_adm_htdocs', value=>'3'),
																													array('title'=>'lang_root_dir_common_htdocs', value=>'4'),
																													array('title'=>'lang_root_dir_abs', value=>'5') ), "errors"=>_nonempty_ ),
			"ROOT_DIR_VALUE"=>		array("title"=>"lang_root_dir_value", "type"=>"text", "show"=>1, "errors"=>_nonempty_, "group_by"=>array("ROOT_DIR_TYPE") ) )
	),
	
	// Избранное
	"FAVOURITE"=>array(
		"title"=>"lang_favourite",
		"type"=>"table",
		"class"=>"favourite",
		"fields"=>array(
			"AUTH_USER_ID"=>		array("title"=>"lang_administrator", "type"=>"select2", "fk_table"=>"AUTH_USER", "no_change"=>1, "no_add"=>1, "errors" => _nonempty_ ),
			"TITLE"=>				array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_, "no_escape"=>1 ),
			"URL"=>					array("title"=>"lang_link", "type"=>"text", "show"=>1, "errors" => _nonempty_, "group_by" => array('AUTH_USER_ID') ),
			"FAVOURITE_ORDER"=>		array("title"=>"lang_order", "type"=>"order", "show"=>1, "sort"=>"asc", "group_by"=>array("AUTH_USER_ID"), "errors" => _nonempty_ ),
		),
	),
	
	// глобальные параметры системы
	"SYSTEM_GLOBAL_PARAMS_TOOL"=>array(
		"title" => "lang_system_global_params_tool",
		"type" => "tool",
		"class" => "system_global_params",
	),
	// таблица глобальных параметров системы
	"SYSTEM_GLOBAL_PARAMS"=>array(
		"title"=>"lang_system_global_params",
		"type"=>"internal_table",
		"fields"=>array(
			"SYSTEM_GLOBAL_PARAMS_ID"=>	array("type"=>"int", "pk"=>1, "auto_increment"=>1),
			"SYSTEM_NAME"=>				array("type"=>"text"),
			"VALUE"=>					array("type"=>"text"),
		),
	),

	// параметры системы, зависимые от пользователя
	"SYSTEM_AUTH_USER_PARAMS_TOOL" => array (
		"title" => "lang_system_auth_user_params_tool",
		"type" => "tool",
		"class" => "system_auth_user_params",
	),
	// таблица пользовательских параметров системы
	"SYSTEM_AUTH_USER_PARAMS"=>array(
		"title"=>"lang_system_auth_user_params",
		"type"=>"internal_table",
		"fields"=>array(
			"SYSTEM_AUTH_USER_PARAMS_ID"=>	array("type"=>"int", "pk"=>1, "auto_increment"=>1),
			"AUTH_USER_ID"=>			array("type"=>"int",),
			"SYSTEM_NAME"=>				array("type"=>"text"),
			"VALUE"=>					array("type"=>"text"),
		),
	),

	// автотесты
	"AUTOTEST" => array (
		"title" => "lang_autotest_tool",
		"type" => "tool",
		"class" => "autotest",
	),
	
	// журналы
	"LOG_TYPE" => array (
		"no_add"=>1,
		"no_delete"=>1,
		"title" => "lang_log_type",
		"type" => "table",
		"decorators"=>array("translate"),
		"fields"=>array(
			"TITLE"=>		array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "width"=>"40%", "sort"=>"asc", "translate"=>1, "no_change"=>1, "disabled"=>1),
			"SYSTEM_NAME"=>	array("title"=>"lang_system_name", "type"=>"text", "errors"=>_nonempty_, "show"=>1, "group_by"=>array(), "no_change"=>1, "disabled"=>1),
			"IS_ERASABLE"=>	array("title"=>"lang_log_type_is_erasable", "type"=>"checkbox", "show"=>1, "width"=>"20%", "filter"=>1),
			"IS_ENABLED"=>	array("title"=>"lang_log_type_is_enabled", "type"=>"checkbox", "show"=>1, "width"=>"20%", "filter"=>1),
			"LOG_TYPE_ORDER"=>	array("title"=>"lang_order", "type"=>"order", "sort"=>"asc", "no_change"=>1),
		),
		"links"=>array(
			"LOG_OPERATION"=>array("secondary_field"=>"LOG_TYPE_ID"),
		),
		"class" => "log_type",
	),
	
	// операции в журналах
	"LOG_OPERATION" => array (
		"no_add"=>1,
		"no_change"=>1,
		"no_delete"=>1,
		"title" => "lang_log_operation_types",
		"type" => "table",
		"decorators"=>array("translate"),
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "width"=>"40%", "sort"=>"asc", "translate"=>1),
			"SYSTEM_NAME"=>	array("title"=>"lang_system_name", "type"=>"text", "errors"=>_nonempty_, "show"=>1, "group_by"=>array()),
			"LOG_TYPE_ID"=>	array("title"=>"lang_log_type_id", "type"=>"select2", "fk_table"=>"LOG_TYPE", "errors"=>_nonempty_, "filter"=>1, "show" => 1 ),
		),
		"indexes"=>array(
			"LO_TYPE"=>array("fields"=>array("LOG_TYPE_ID"=>array())),
		),
		"class" => "log_operation",
	),
	
	"LOG_EXTENDED_INFO" => array (
		"title" => "lang_log_extended_info",
		"type" => "internal_table",
		"fields" => array (
			"LOG_EXTENDED_INFO_ID" => array ("type"=>"int", "pk"=>1, "auto_increment"=>1),
			"LOG_RECORD_ID" => array ("type"=>"select2", "fk_table"=>"LOG_RECORD"),
			"EXTENDED_INFO" => array ("type"=>"textarea"),
		),
	),
	
	// записи
	"LOG_RECORD" => array (
		"title" => "lang_log_record",
		"type" => "table",
		"no_add"=>1,
		"no_change"=>1,
		"no_delete"=>1,
		"view" => 1,
		"class" => "log_show",
		"fields" => array (
			"LOG_OPERATION_ID" => array ("title"=>"lang_log_operation", "type"=>"select2", "fk_table"=>"LOG_OPERATION", "show"=>1),
			"AUTH_USER_ID" => array ("title"=>"lang_administrator", "type"=>"select2", "fk_table"=>"AUTH_USER", "show"=>1),
			"TE_OBJECT_ID" => array ("title"=>"lang_object", "type"=>"select2", "show"=>1, "fk_table"=>"TE_OBJECT"),
			"LANG_ID" => array ("title"=>"lang_lang", "type"=>"select2", "show"=>1, "fk_table"=>"LANG", "is_null"=>1),
			"IP" => array ("title"=>"lang_IP", "type"=>"text", "show"=>1),
			"OPERATION_DATE" => array ("title"=>"lang_date", "type"=>"datetime", "show"=>1, "sort"=>"desc", "view_type"=>"full"),
			"OBJECT_ID" => array ("title"=>"lang_object", "show"=>1, 'type'=>'int', "is_null"=>1),
			"VERSION" => array ("title"=>"lang_version", "show"=>1, 'type'=>'int', "is_null"=>1),
			"LOG_INFO" => array ("title"=>"lang_log_info", "show"=>1, 'type'=>'textarea'),
			"IS_ERASABLE" => array ("title"=>"lang_is_eracable", "show"=>1, 'type'=>'checkbox'),
			"DENORMALIZED_INFO" => array ("title" => "lang_denormalized_info", "show"=>1, 'type'=>'textarea'),
		),
		"indexes"=>array(
			"LR_FR_IDXS"=>array("fields"=>array("LOG_OPERATION_ID"=>array(), "AUTH_USER_ID"=>array(), "TE_OBJECT_ID"=>array(), "LANG_ID"=>array())),
			"LR_OD"=>array("fields"=>array("OPERATION_DATE"=>array())),
		),
	),
	
	// обновление
	"SYSTEM_UPDATE" => array (
		"title" => "lang_system_update",
		"type" => "table",
		"class" => "system_update",
		"no_change" => 1,
		"fields" => array (
			"SU_DATE" => array ("title"=>"lang_system_update_date", "show"=>1, "type"=>"date",	"sort"=>"desc", "no_add"=>1),
			"TITLE" => array ("title"=>"lang_name", "show"=>1, "type"=>"text", "no_add"=>1),
			"DESCR" => array ("title"=>"lang_description", "show"=>1, "type"=>"textarea", "is_main"=>1, "no_add"=>1, "no_escape"=>1),
			"SU_STATE" => array ("title"=>"lang_state", "show"=>1, "type"=>"select1",	"filter"=>1, "value_list"=>array(array("title"=>"lang_system_update_installed", "value"=>"installed"), array("title"=>"lang_system_update_registered", "value"=>"registered"), array("title"=>"lang_system_update_install_failed", "value"=>"install_failed"), array("title"=>"lang_system_update_uninstall_failed", "value"=>"uninstall_failed")), "no_add"=>1),
			"PREVIOUS_UPDATE" => array ("type"=>"date", "no_add"=>1),
			"NOT_UNINSTALLABLE" => array ("type"=>"checkbox", "no_add"=>1),
			"UPLOAD_FIELD" => array('title'=>'lang_system_update_file', 'virtual'=>1, 'type'=>'file')
		)
	),
	
	// очередь действий обновления
	"SYSTEM_UPDATE_ACTION_QUEUE" => array (
		"title" => "lang_system_update_action",
		"type" => "internal_table",
		"fields" => array (
			"SYSTEM_UPDATE_ACTION_QUEUE_ID" => array ("type"=>"int", "pk"=>1, "auto_increment"=>1),
			"SYSTEM_UPDATE_ID" => array ("type"=>"select2", "fk_table"=>"SYSTEM_UPDATE"),
			"ACTION" => array("type"=>"text"),
			"REVERT_ACTION" => array("type"=>"text"),
			"FILE_ELEMENT" => array("type"=>"text"),
			"STATUS" => array("type"=>"select1", "value_list"=>array(array("value"=>"done"), array("value"=>"pending"))),
		),
	),
	
	// тулса подтверждения выбора
	"CONFIRM_ACTION" => array (
		"title" => "lang_confirm_action",
		"type" => "tool",
		"class" => "confirm_action",
	),
	
	// Контекстная помощь
	"CONTEXT_HELP"=>array(
		"title"=>"lang_context_help",
		"type"=>"table",
		"decorators"=>array("translate"),
		"fields"=>array(
			"TE_OBJECT_ID"=>	array("title"=>"lang_auth_system_section_object_name", "type"=>"select2", "show"=>1, "fk_table"=>"TE_OBJECT", "errors"=>_nonempty_),
			"OBJECT_PARAM"=>	array("title"=>"lang_auth_system_section_object_param", "type"=>"text"),
			"OBJECT_ACTION"=>	array("title"=>"lang_context_help_action", "type"=>"select1", "value_list"=>array(
				array( 'title'=>'lang_context_help_action_index', value=>'index' ),
				array( 'title'=>'lang_context_help_action_add', value=>'add' ),
				array( 'title'=>'lang_context_help_action_copy', value=>'copy' ),
				array( 'title'=>'lang_context_help_action_change', value=>'change' ),
				array( 'title'=>'lang_context_help_action_translate', value=>'translate' ),
				array( 'title'=>'lang_context_help_action_group_move', value=>'group_move' ),
				array( 'title'=>'lang_context_help_action_distributed', value=>'distributed' ),
				array( 'title'=>'lang_context_help_action_resolution', value=>'resolution' ),
				array( 'title'=>'lang_context_help_action_m2m', value=>'m2m' ),
				array( 'title'=>'lang_context_help_action_tree', value=>'tree' ),
				array( 'title'=>'lang_context_help_action_block', value=>'block' ),
				array( 'title'=>'lang_context_help_action_block_add', value=>'block_add' ),
				array( 'title'=>'lang_context_help_action_block_copy', value=>'block_copy' ),
				array( 'title'=>'lang_context_help_action_content', value=>'content' ),
				array( 'title'=>'lang_context_help_action_privilege', value=>'privilege' ) ), "filter" => 1, "errors"=>_nonempty_ ),
			"BODY"=>			array("title"=>"lang_context_help_body", "type"=>"textarea", "editor"=>1, "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "translate"=>1, "no_escape"=>1 ),
		),
	),
	
	// заблокированные записи
	"LOCKED_RECORD"=>array (
		"title"=>"lang_locked_record_table",
		"type"=>"internal_table",
		"fields"=>array (
			"TE_OBJECT_ID"=> array("type"=>"int", "pk"=>1),
			"CONTENT_ID" => array ("type"=>"int", "pk"=>1),
			"LANG_ID" => array ("type"=>"int", "pk"=>1),
			"AUTH_USER_ID" => array ("type"=>"int"),
			"LOCK_STARTED" => array ("type"=>"datetime"),
			"LOCK_LAST_ACCESSED" => array ("type"=>"datetime"),
		),
		"indexes"=>array(
			"LR_LLA" => array ("fields" => array ("LOCK_LAST_ACCESSED" => array())),
		),
	),
	
	// Изменение структуры
	"METADATA_CHANGE"=>array (
		"title" => "lang_metadata_changer_tool",
		"type" => "tool",
		"class" => "metadata_change"
	),
	
	// Утилита для редактирования личных данных
	"PERSONAL_INFO_TOOL" => array (
		"title" => "lang_personal_info_tool",
		"type" => "tool",
 		"class" => "personal_info_tool"
	),
	
	// Cron-утилита
	"SHEDULER" => array (
		"title"=>"lang_sheduler_table",
		"type"=>"table",
		"class"=>"sheduler",
		"decorators"=>array("translate"),
		"fields"=>array(
			"TITLE"=>				array("title"=>"lang_name", "type"=>"text", "show"=>1, "sort"=>"asc", "is_main"=>1, "errors"=>_nonempty_,"translate"=>1),
			"TE_OBJECT_ID"=>		array("title"=>"lang_object", "type"=>"select2", "show"=>1, "fk_table"=>"TE_OBJECT", "errors"=>_nonempty_),
			"CLASS_NAME"=>			array("title"=>"lang_sheduler_task_class", "type"=>"text", "show"=>1, "errors"=>_nonempty_|_dirname_),
			"IS_ACTIVE"=>			array("title"=>"lang_sheduler_task_active", "type"=>"checkbox", "show"=>1),
		),
	),
);
?>