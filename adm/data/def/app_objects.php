<?php
/**
 * Специальные объекты
 *
 * @package    RBC_Contents_5_0
 * @subpackage app
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
$app_objects=array(
	// Специфичные поля разделов клиентской части (по умолчанию не заводятся)
//	"PAGE"=>array(
//		"fields"=>array(
//			"MENU_ACTIVE"=>			array("title"=>"Картинка выделенного пункта меню", "type"=>"img"),
//			"MENU_PASSIVE"=>		array("title"=>"Картинка невыделенного пункта меню", "type"=>"img"),
//		),
//	),

	// Простой текст
	"CONTENT_TEXT"=>array(
		"title"=>"lang_content_text",
		"type"=>"table",
		"class"=>"content_text",
		"only_one"=>1,
		"decorators"=>array("version", "lang", "block", "workflow"),
				"taxonomy"=>"1",
				"fields"=>array(
			"TITLE"=>		array("title"=>"lang_title", "type"=>"text", "show"=>1, "is_main"=>1, "sort"=>"asc"),
			"BODY"=>		array("title"=>"lang_contents", "type"=>"textarea", "editor"=>1),
		),
	),
	// Список
	"CONTENT_LIST"=>array(
		"title"=>"lang_content_list",
		"type"=>"table",
		"multiple_block"=>1,
		"decorators"=>array("version", "lang", "block", "workflow"),
		"workflow_scope"=>"table",
				"taxonomy"=>"1",
				"fields"=>array(
			"TITLE"=>		array("title"=>"lang_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ),
			"BODY"=>		array("title"=>"lang_text", "type"=>"textarea", "editor"=>1, "errors" => _nonempty_ ),
			"IMG"=>			array("title"=>"Изображение", "type"=>"img", "show"=>1, 'upload_dir' => 'upload/content_list_img'),
			"LINK"=>		array("title"=>"Ссылка", "type"=>"text", "show"=>1),
			"LIST_ORDER"=>	array("title"=>"lang_order", "type"=>"order", "show"=>1, "sort"=>"asc" ),
			// "SEND_IT"=>		array("title"=>"lang_send_it","type" => "checkbox" ),
		),
	),
	// Новости
	"NEWS"=>array(
		"title"=>"lang_news",
		"type"=>"table",
		"multiple_block"=>1,
		"decorators"=>array("version", "lang", "block", "workflow"),
				"taxonomy"=>"1",
				"fields"=>array(
			"TITLE"=>		array("title"=>"lang_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ),
			"ANNOUNCE"=>	array("title"=>"lang_news_announce", "type"=>"textarea", "editor"=>1, "rows" => 5 ),
			"IMG"		=>	array('title'=>'Главное изображение', 'type'=>'img','show'=>1, 'upload_dir' => 'upload/news_img', "errors" => _nonempty_ ),
			"BODY"=>		array("title"=>"lang_text", "type"=>"textarea", "editor"=>1, "rows" => 15, "errors" => _nonempty_ ),
			"NEWS_DATE"=>	array("title"=>"lang_news_date", "type"=>"datetime", "show"=>1, "sort"=>"desc", "errors" => _nonempty_, "filter"=>1),
			// "SEND_IT"=>		array("title"=>"lang_send_it","type" => "checkbox" ),
		),
	),
	// Статьи
	"ARTICLES"=>array(
		"title"=>"Статьи",
		"type"=>"table",
		"multiple_block"=>1,
		"decorators"=>array("version", "lang", "block", "workflow"),
				"taxonomy"=>"1",
				"fields"=>array(
			"TITLE"=>		array("title"=>"lang_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ),
			"ANNOUNCE"=>	array("title"=>"lang_news_announce", "type"=>"textarea", "editor"=>1, "rows" => 5 ),
			"IMG"		=>	array('title'=>'Главное изображение', 'type'=>'img','show'=>1, 'upload_dir' => 'upload/news_img', "errors" => _nonempty_ ),
			"BODY"=>		array("title"=>"lang_text", "type"=>"textarea", "editor"=>1, "rows" => 15, "errors" => _nonempty_ ),
		),
	),
	
	// Товары
	"CATALOG_ITEMS"=>array(
		"title"=>"Каталог товаров",
		"type"=>"table",
		"multiple_block"=>1,
		"decorators"=>array("version", "lang", "block", "workflow"),
		"taxonomy"=>"1",
		"fields"=>array(
			"SHOW_ON_MAIN"	=>	array("title"=>"Показывать на главной", "type"=>"checkbox", "show"=>1, "filter"=>"1" ),
			"TYPE"			=>	array("title"=>"Тип товара", "type"=>"select1", "show"=>1,
											"value_list" => array(
													array( "title" => "Профлист", "value" => "Профлист" ),
													array( "title" => "Металлочерепица", "value" => "Металлочерепица" ),
													array( "title" => "Пиролизный котел", "value" => "Пиролизный котел" ),
													array( "title" => "Детская площадка", "value" => "Детская площадка" ),
											),
										),
			"TITLE"			=>	array("title"=>"Наименование", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ),
			"ANNOUNCE"		=>	array("title"=>"Краткое описание", "type"=>"textarea", "editor"=>1, "rows" => 5 ),
			"PRICE"			=>	array("title"=>"Цена от", "type"=>"int", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ),
			"BODY"			=>	array("title"=>"Полное описание", "type"=>"textarea", "editor"=>1, "rows" => 15, "errors" => _nonempty_ ),
			"MAIN_IMG"		=>	array('title'=>'Главное изображение', 'type'=>'img','show'=>1, 'upload_dir' => 'upload/catalog_item_img', "is_main"=>1, "errors" => _nonempty_ ),
		),
		"links"=>array(
			"CATALOG_IMAGES" => array( "show"=>1, "on_delete_cascade"=>1),
			"CATALOG_PRICES" => array( "show"=>1, "on_delete_cascade"=>1),
			"CATALOG_VOTES" => array( "show"=>1, "on_delete_cascade"=>1),
			"CATALOG_FEEDBACK" => array( "show"=>1, "on_delete_cascade"=>1),
		),
	),
	
	"CATALOG_IMAGES"=>array(
		"title"=>"Изображения товара",
		"type"=>"table",
		"fields"=>array(
			"CATALOG_ITEMS_ID"	=>	array("title"=>"Товар", "type"=>"select2", "fk_table"=>"CATALOG_ITEMS", "errors"=>_nonempty_, "filter"=>1, "list_mode"=>array( "select_only"=>1 ), "show" => 1, "no_change"=>1, "disabled"=>1 ),
			"META_TITLE"		=>	array("title"=>"Аттрибут Title", "type"=>"text", "show"=>1),
			"META_ALT"			=>	array("title"=>"Аттрибут Alt", "type"=>"text", "show"=>1),
			"IMG"=> 			array('title'=>'Изображение', 'type'=>'img','show'=>1, 'upload_dir' => 'upload/catalog_item_img', "is_main"=>1, "errors"=>_nonempty_),
			"DESCRIPTION"		=>	array("title"=>"Описание", "type"=>"textarea", "editor"=>1, "rows" => 15),
		),
	),
	
	"CATALOG_PRICES"=>array(
		"title"=>"Цены товара",
		"type"=>"table",
		"fields"=>array(
			"CATALOG_ITEMS_ID"=>	array("title"=>"Товар", "type"=>"select2", "fk_table"=>"CATALOG_ITEMS", "errors"=>_nonempty_, "filter"=>1, "list_mode"=>array( "select_only"=>1 ), "show" => 1 , "no_change"=>1, "disabled"=>1 ),
			"PREFIX"=>				array("title"=>"Префикс цены", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_),
			"PRICE"			=>	array("title"=>"Цена от", "type"=>"int", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ),
		),
	),
	
	"CATALOG_VOTES"=>array(
		"title"=>"Оценки товара",
		"type"=>"table",
		"fields"=>array(
			"CATALOG_ITEMS_ID"=>	array("title"=>"Товар", "type"=>"select2", "fk_table"=>"CATALOG_ITEMS", "errors"=>_nonempty_, "filter"=>1, "list_mode"=>array( "select_only"=>1 ), "show" => 1, "no_change"=>1, "disabled"=>1  ),
			"VOTE_IP"=>			array("title"=>"IP пользователя", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_),
			"VOTE"=>			array("title"=>"Оценка", "type"=>"int", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ),
			"VOTE_DATE_TIME"=>	array("title"=>"Дата/время оценки", "type"=>"datetime", "show"=>1, "sort"=>"desc", "errors" => _nonempty_, "filter"=>1),
		),
	),
	"CATALOG_FEEDBACK"=>array(
		"title"=>"Отзывы о товаре",
		"type"=>"table",
		"decorators"=>array("version", "workflow"),
		"fields"=>array(
			"CATALOG_ITEMS_ID"=>	array("title"=>"Товар", "type"=>"select2", "fk_table"=>"CATALOG_ITEMS", "errors"=>_nonempty_, "filter"=>1, "list_mode"=>array( "select_only"=>1 ), "show" => 1, "no_change"=>1, "disabled"=>1  ),
			"FEEDBACK_IP"=>			array("title"=>"IP пользователя", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_),
			"FEEDBACK_TEXT"=>		array("title"=>"Оценка", "type"=>"textarea", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ),
			"FEEDBACK_DATE_TIME"=>	array("title"=>"Дата/время оценки", "type"=>"datetime", "show"=>1, "sort"=>"desc", "errors" => _nonempty_, "filter"=>1),
		),
	),
	
	// Альтернативное меню
	"ALT_MENU"=>array(
		"title"=>"lang_alt_menu",
		"type"=>"table",
		"class"=>"alt_menu",
		"parent_field"=>"PARENT_ID",
		"decorators"=>array("version", "block", "workflow"),
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_alt_menu_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ),
			"PARENT_ID"=>		array("title"=>"lang_alt_menu_parent", "type"=>"parent", "errors"=>_nonempty_),
			"PAGE_ID"=>			array("title"=>"lang_alt_menu_page_id", "type"=>"select2", "fk_table"=>"PAGE" ),
			"URL"=>				array("title"=>"lang_alt_menu_url", "type"=>"text" ),
			"IS_BLANK"=>		array("title"=>"lang_alt_menu_blank", "type"=>"checkbox" ),
			"MENU_ORDER"=>		array("title"=>"lang_alt_menu_order", "type"=>"order", "show"=>1, "sort"=>"asc", "group_by"=>array("PARENT_ID"), "errors" => _nonempty_ ),
			
//			"MENU_ACTIVE"=>		array("title"=>"Картинка выделенного пункта меню","type" => "img" ),
//			"MENU_PASSIVE"=>	array("title"=>"Картинка невыделенного пункта меню","type" => "img" ),
		),
	),
	// Форма обратной связи
	"FORM_QUESTION"=>array(
		"title"=>"lang_form_question",
		"class"=>"form_question",
		"type"=>"table",
		"decorators"=>array("version", "block", "workflow"),
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_form_question_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ),
			"QUESTION_ORDER"=>	array("title"=>"lang_order", "type"=>"order", "show"=>1, "sort"=>"asc" ),
			"IS_MANDATORY"=>	array("title"=>"lang_form_question_is_mandatory", "type"=>"checkbox"),
			"QUESTION_TYPE"=>	array("title"=>"lang_form_question_type", "type"=>"select1", "errors" => _nonempty_, "no_change"=>1, "disabled"=>1, "show"=>1,
				"value_list" => array(
					array( "title" => "lang_string", "value" => "string" ),
					array( "title" => "lang_int", "value" => "int" ),
					array( "title" => "lang_float", "value" => "float" ),
					array( "title" => "lang_email", "value" => "email" ),
					array( "title" => "lang_date", "value" => "date" ),
					array( "title" => "lang_text", "value" => "textarea" ),
					array( "title" => "lang_checkbox", "value" => "checkbox" ),
					array( "title" => "lang_form_question_select", "value" => "select" ),
					array( "title" => "lang_form_question_radio_group", "value" => "radio_group" ),
					array( "title" => "lang_form_question_radio_group_alt", "value" => "radio_group_alt" ),
					array( "title" => "lang_form_question_checkbox_group", "value" => "checkbox_group" ),
					array( "title" => "lang_form_question_checkbox_group_alt", "value" => "checkbox_group_alt" ),
					array( "title" => "lang_form_question_separator", "value" => "separator" ) ) ),
			"DEFAULT_VALUE"=>	array("title"=>"lang_default_value", "type"=>"text" ),
		),
		"links"=>array(
			"FORM_OPTIONS" => array( "show"=>1, "show_if" => array( "QUESTION_TYPE" =>
				array( "select", "radio_group", "radio_group_alt", "checkbox_group", "checkbox_group_alt" ) ) ),
		),
	),
	
	// Форма обратной связи: варианты ответов
	"FORM_OPTIONS"=>array(
		"title"=>"lang_field_values",
		"type"=>"table",
		"class"=>"form_options",
		"decorators"=>array("rights_inheritance"),
		"rights_inheritance"=>"FORM_QUESTION_ID",
		"fields"=>array(
			"TITLE"=>				array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ),
			"FORM_QUESTION_ID"=>	array("title"=>"lang_question", "type"=>"select2", "fk_table"=>"FORM_QUESTION", "errors"=>_nonempty_, "filter"=>1, "list_mode"=>array( "select_only"=>1 ), "show" => 1 ),
			"OPTIONS_ORDER"=>		array("title"=>"lang_order", "type"=>"order", "show"=>1, "sort"=>"asc", "errors" => _nonempty_, "group_by"=>array("FORM_QUESTION_ID") ),
			"IS_DEFAULT"=>			array("title"=>"lang_default_value", "type"=>"checkbox", "show"=>1 ),
		),
	),
	
	// Заполненные анкеты
	"FORM_ANSWER"=>array(
		"title"=>"lang_form_answer",
		"type"=>"table", "no_add" => 1,/* "no_change" => 1, */
		"decorators"=>array("rights_inheritance"),
		"rights_inheritance"=>"INF_BLOCK_ID",
		"fields"=>array(
			"IP"=>				array("title"=>"lang_form_answer_ip", "type"=>"text", "show"=>1, "is_main"=>1 ),
			"INF_BLOCK_ID"=>	array("title"=>"lang_inf_block", "type"=>"select2", "fk_table"=>"INF_BLOCK", "filter"=>1, "show"=>1, "list_mode" => array( "by_module_name" => "FORM_QUESTION" ) ),
			"ANSWER_TIME"=>		array("title"=>"lang_form_answer_time", "type"=>"datetime", "show"=>1, "sort"=>"desc" ),
			"COMPLETED"=>		array("title"=>"Обработано", "type"=>"checkbox", "show"=>1, "filter"=>1),
		),
		"links"=>array(
			"FORM_VALUES" => array( "show"=>1, "on_delete_cascade"=>1 ),
		),
	),
	
	// Содержимое анкет
	"FORM_VALUES"=>array(
		"title"=>"lang_form_values",
		"class"=>"form_values",
		"type"=>"table", "no_add" => 1, "no_change" => 1, "no_delete" => 1,
		"fields"=>array(
			"FORM_ANSWER_ID"=>			array("title"=>"lang_question", "type"=>"select2", "fk_table"=>"FORM_ANSWER", "filter"=>1 ),
			"FORM_QUESTION_ID"=>		array("title"=>"lang_question", "type"=>"select2", "fk_table"=>"FORM_QUESTION", "show"=>1, "is_main"=>1 ),
			"FORM_OPTIONS_ID"=>			array("title"=>"lang_answer_variants", "type"=>"select2", "fk_table"=>"FORM_OPTIONS", "show"=>1 ),
			"ANSWER_VALUE"=>			array("title"=>"lang_form_values_value", "type"=>"text", "show"=>1 ),
		),
	),
	// Опросы
	"VOTE"=>array(
		"title"=>"lang_vote",
		"type"=>"table",
		"class"=>"vote",
		"decorators"=>array("version", "block", "workflow"),
		"fields"=>array(
			"TITLE"=>		array("title"=>"lang_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ),
			"BODY"=>		array("title"=>"lang_vote_body", "type"=>"textarea", "editor"=>1, "errors" => _nonempty_),
			"VOTE_TYPE"=>	array("title"=>"lang_vote_type", "type"=>"select1", "errors" => _nonempty_, "value_list" => array(
				array( "title"=>"lang_vote_single_answer", "value"=>"single", "selected"=>1 ),
				array( "title"=>"lang_vote_multiple_answer", "value"=>"multiple" ) ) ),
			"VOTE_RESULT"=>	array("title"=>"lang_vote_result", "type"=>"select1", "errors" => _nonempty_, "value_list" => array(
				array( "title"=>"lang_vote_hide_result", "value"=>"hide" ),
				array( "title"=>"lang_vote_show_result", "value"=>"show", "selected"=>1 ),
				array( "title"=>"lang_vote_show_result_before", "value"=>"before" ) ) ),
			"START_DATE"=>	array("title"=>"lang_vote_start_date", "type"=>"datetime", "show"=>1, "sort"=>"desc", "errors" => _nonempty_ ),
			"END_DATE"=>	array("title"=>"lang_vote_end_date", "type"=>"datetime", "show"=>1, "errors" => _nonempty_ ),
			"FILL_COUNT" => array("title"=>"lang_vote_fill_count", "type" => "int", "errors" => _int_, "no_change"=>1, "no_add"=>1, "disabled"=>1, "show"=>1, "value"=>"0" ),
			"ACTIVE" => array("title" => "lang_vote_activity", "type" => "select1", "virtual" => 1, "filter" => 1,  "no_add"=>1, "no_change"=>1, "value_list" => array(
					array( "title" => "lang_vote_active", "value" => "active" ),
					array( "title" => "lang_vote_complete", "value" => "complete" ),
					array( "title" => "lang_vote_schedule", "value" => "schedule" ) ) ),
		),
		"links"=>array(
			"VOTE_ANSWER" => array( "show"=>1 ),
		),
	),
	
	// Опросы: варианты ответов
	"VOTE_ANSWER"=>array(
		"title"=>"lang_answer_variants",
		"type"=>"table",
		"class"=>"vote_answer",
		"decorators"=>array("rights_inheritance"),
		"rights_inheritance"=>"VOTE_ID",
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_vote_answer_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ),
			"VOTE_ID"=>			array("title"=>"lang_vote_answer_vote_id", "type"=>"select2", "fk_table"=>"VOTE", "errors"=>_nonempty_, "filter"=>1, "show" => 1 ),
			"ANSWER_COUNT" =>	array("title"=>"lang_vote_fill_count", "type" => "int", "errors" => _int_, "no_add"=>1, "no_change"=>1, "disabled"=>1, "show"=>1, "value"=>"0" ),
			"ANSWER_ORDER"=>	array("title"=>"lang_order", "type"=>"order", "show"=>1, "sort"=>"asc", "errors" => _nonempty_, "group_by"=>array("VOTE_ID") ),
		),
	),
	// Рекламный блок
	"PROMO_BLOCK"=>array(
		"title"=>"lang_promo_block",
		"type"=>"table",
		"lock_records"=>1,
		"multiple_block"=>1,
		"decorators"=>array("version", "lang", "block", "workflow"),
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_promo_block_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ),
			"BODY"=>			array("title"=>"lang_promo_block_body", "type"=>"textarea", "editor"=>1, "rows" => 15, "errors" => _nonempty_ ),
			"START_DATE"=>		array("title"=>"lang_promo_block_start_date", "type"=>"datetime", "show"=>1, "sort"=>"desc", "errors" => _nonempty_ ),
			"END_DATE"=>		array("title"=>"lang_promo_block_end_date", "type"=>"datetime", "show"=>1, "errors" => _nonempty_ ),
			"MAX_NUM_LOADS" =>	array("title"=>"lang_promo_block_max_num_loads", "type" => "int", "value"=>"0" ),
			"PRIORITY" =>		array("title"=>"lang_promo_block_priority", "type" => "int", "errors" => _nonempty_, "value"=>"1" ),
			"NUM_LOADS" =>		array("title"=>"lang_promo_block_num_loads", "type" => "int", "no_change"=>1, "no_add"=>1, "disabled"=>1, "value"=>"0" ),
			"NUM_CLICKS" =>		array("title"=>"lang_promo_block_num_clicks", "type" => "int", "no_change"=>1, "no_add"=>1, "disabled"=>1, "value"=>"0" ),
		),
	),
	// Фотогалерея
	"PHOTO_GALLERY"=>array(
		"title"=>"lang_photo_gallery",
		"type"=>"table",
		"lock_records"=>1,
		"multiple_block"=>0,
		"decorators"=>array("version","lang","block","workflow"),	
		"fields"=>array(
	  		"TITLE" 		=> array("title"=>"lang_photo_gallery_name", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_),
	  		"BODY" 			=> array("title"=>"lang_photo_gallery_description", "type"=>"textarea", "editor"=>1),
	  		"LIST_ORDER" 	=> array("title"=>"lang_order", "type"=>"order", "sort"=>"asc", "show"=>1),
	  		"IMG" 			=> array("title"=>"lang_photo_gallery_photo", "type"=>"img", "upload_dir"=>"upload/photo_gallery/preview/"),
	  		"IMG_BIG" 		=> array("title"=>"lang_photo_gallery_big_photo", "type"=>"img", "upload_dir"=>"upload/photo_gallery/"),
	  		"VOTE_COUNT"	=> array("title"=>"lang_photo_gallery_vote_count", "type"=>"int", "show"=>1, "no_change"=>1, "no_add"=>1, "disabled"=>1, "value"=>"0"),
		),
	),
	// Пользователи
	"CL_CLIENT"=>array(
		"title"=>"lang_client_table",
		"type"=>"table",
		"class"=>"cl_client",
		"fields"=>array(
			"SURNAME"=>			array("title"=>"lang_surname", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "sort"=>"asc"),
			"NAME"=>			array("title"=>"lang_pure_name", "type"=>"text"),
			"PATRONYMIC"=>		array("title"=>"lang_patronymic", "type"=>"text"),
			"CLIENT_TYPE"=>		array("title"=>"lang_client_type", "type"=>"select1", "value_list" => array(
				array( "title"=>"lang_client_natural_person", "value"=>"0", "selected"=>1 ),
				array( "title"=>"lang_client_legal_person", "value"=>"1" ) ), "errors"=>_nonempty_ ),
			"EMAIL"=>			array("title"=>"lang_email", "type"=>"text", "show"=>1, "errors"=>_nonempty_|_email_, "group_by"=>array()),
			"OPENID"=>			array("title"=>"lang_openid", "type"=>"text", "group_by"=>array()),
			"IS_OPENID"=>		array("title"=>"lang_openid", "type" => "checkbox", "virtual" => 1, "filter" => 1,  "no_add"=>1, "no_change"=>1 ),
			"TELEPHONE"=>		array("title"=>"lang_client_telephone", "type"=>"text"),
			"PASSWORD_MD5"=>	array("title"=>"lang_password", "type"=>"password_md5", "datatype_like"=>"text", "errors"=>_login_),
			"LEGAL_PERSON"=>	array("title"=>"lang_client_company_name", "type"=>"text"),
			"ADDRESS"=>	array("title"=>"lang_client_address", "type"=>"textarea"),
			"INN"=>	array("title"=>"lang_client_inn", "type"=>"text"),
			"R_ACCOUNT"=>	array("title"=>"lang_client_r_account", "type"=>"text"),
			"BANK_NAME"=>	array("title"=>"lang_client_bank_name", "type"=>"text"),
			"K_ACCOUNT"=>	array("title"=>"lang_client_k_account", "type"=>"text"),
			"BIK"=>	array("title"=>"lang_client_bik", "type"=>"text"),
			"CODE_OKPO"=>	array("title"=>"lang_client_code_okpo", "type"=>"text"),
			"CODE_OKVED"=>	array("title"=>"lang_client_code_okved", "type"=>"text"),
			"KPP"=>	array("title"=>"lang_client_kpp", "type"=>"text"),
			"FAX"=>	array("title"=>"lang_client_fax", "type"=>"text"),
		),
		"m2m"=>array(
								),
	),
	
		
	
	// Статистика
	"AWSTATS" => array (
		"title"=>"lang_awstats_tool",
		"type"=>"tool",
		"class"=>"awstats"
	),
	// Таблица тегов
	"TAG"=>array(
		"title"=>"lang_tags",
		"type"=>"table",
		"class"=>"tag",
		"fields"=>array(
			"TITLE"=>				array("title"=>"lang_name", "type"=>"text", "show"=>1, "sort"=>"asc", "is_main"=>1, "errors"=>_nonempty_),
			"SYSTEM_NAME"=>			array("title"=>"lang_tag_system_name", "type"=>"text", "no_add"=>1, "no_change"=>1, "disabled"=>1, "errors"=>_nonempty_),
			"DESCRIPTION"=>			array("title"=>"lang_description", "type"=>"textarea"),
			"NUM_LINKS"=>			array("title"=>"lang_tag_num_links", "type"=>"int", "show"=>1, "no_add"=>1, "no_change"=>1, "disabled"=>1, "filter"=>"1", "errors"=>_int_),
		),
		"links"=>array(
			"TAG_OBJECT" => array( "show"=>0, "on_delete_cascade"=>1 ),
		),
	),
	
	// Таблица связи между тегами и объектами
	"TAG_OBJECT"=>array(
		"title"=>"lang_tag_object_table",
		"type"=>"internal_table",
		"no_add"=>1,
		"no_change"=>1,
		"no_delete"=>1,
		"fields"=>array(
			"OBJECT_ID"=>		array("type"=>"int", "pk"=>1),
			"TAG_ID"=>			array("type"=>"int", "pk"=>1),
			"TE_OBJECT_ID"=>	array("type"=>"int", "pk"=>1),
		),
	),
	// Система почтовой рассылки
	
	// Подписчики
	"SUBSCRIBER"=>array(
		"title"=>"lang_subscriber_table",
		"type"=>"table",
		"fields"=>array(
			"FIO"=>				array("title"=>"lang_fio", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_, "sort"=>"asc"),
			"EMAIL"=>			array("title"=>"lang_email", "type"=>"text", "show"=>1, "errors"=>_nonempty_|_email_, "group_by"=>array()),
			"ORGANIZATION"=>	array("title"=>"lang_organization", "type"=>"text"),
			"PASSWORD"=>		array("title"=>"lang_password", "type"=>"text", "errors"=>_nonempty_),
			"ACTIVE"=>			array("title"=>"lang_active", "type"=>"checkbox", "show"=>1 ),
		),
		"m2m"=>array(
			"SUBSCRIBER_SUBSCRIBE_LIST"=>	array("secondary_table"=>"SUBSCRIBE_LIST" ),
		),
		"indexes"=>array(
			"IDX_EMAIL" =>		array ("fields" => array ("EMAIL" => array())),
			"IDX_ACTIVE" =>		array ("fields" => array ("ACTIVE" => array())),
		),
	),
	
	// Листы подписки
	"SUBSCRIBE_LIST"=>array(
		"title"=>"lang_subscribe_list_table",
		"type"=>"table",
		"class"=>"subscribe_list",
		"fields"=>array(
			"LIST_NAME"=>		array("title"=>"lang_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors"=>_nonempty_),
			"LANG_ID"=>			array("title"=>"lang_lang", "type"=>"select2", "errors"=>_nonempty_, "fk_table"=>"LANG"),
			"LIST_DESCRIPTION"=>array("title"=>"lang_description", "type"=>"textarea"),
			"SENDER_EMAIL"=>	array("title"=>"lang_sender_email", "type"=>"text", "errors"=>_nonempty_|_email_),
			"SENDER_NAME"=>		array("title"=>"lang_sender_name", "type"=>"text", "errors"=>_nonempty_),
			"EMAIL_SUBJECT"=>	array("title"=>"lang_email_subject", "type"=>"text", "errors"=>_nonempty_),
			"EMAIL_HEADER"=>	array("title"=>"lang_email_header", "type"=>"textarea"),
			"EMAIL_FOOTER"=>	array("title"=>"lang_email_footer", "type"=>"textarea"),
			"LIST_ORDER"=>		array("title"=>"lang_order", "type"=>"order", "show"=>1, "sort"=>"asc" ),
			"PERIOD"=>			array("title"=>"lang_period", "type"=>"select1", "errors"=>_nonempty_, "value_list"=>array(
				array("title"=>"lang_default", "value"=>"1", "selected"=>1),
				array("title"=>"lang_daily", "value"=>"2"),
				array("title"=>"lang_weekly", "value"=>"3"),
				array("title"=>"lang_monthly", "value"=>"4"))),
			"START_DATE"=>		array("title"=>"lang_date_first_dispatch", "type"=>"date"),
		),
		"m2m"=>array(
			"SUBSCRIBER_SUBSCRIBE_LIST"=>	array("secondary_table"=>"SUBSCRIBER" ),
			"SUBSCRIBE_LIST_INF_BLOCK"=>	array("secondary_table"=>"INF_BLOCK" ),
			"SUBSCRIBE_LIST_SITE"=>			array("secondary_table"=>"SITE" ),
		),
		"indexes"=>array(
			"IDX_LANG_ID" =>	array ("fields" => array ("LANG_ID" => array())),
			"IDX_PERIOD" =>		array ("fields" => array ("PERIOD" => array())),
			"IDX_START_DATE" =>	array ("fields" => array ("START_DATE" => array())),
		),
	),
	
	// Архив писем
	"SUBSCRIBE_ARCHIVE"=>array(
		"title"=>"lang_subscribe_archive_table",
		"type"=>"table",
		"class"=>"subscribe_archive",
		"view"=>1,
		"no_add"=>1,
		"no_change"=>1,
		"fields"=>array(
			"SUBSCRIBE_LIST_ID"=>	array("title"=>"lang_subscribe_list", "type"=>"select2", "fk_table"=>"SUBSCRIBE_LIST", "filter"=>1),
			"SITE_ID"=>				array("title"=>"lang_site", "type"=>"select2", "fk_table"=>"SITE"),
			"LANG_ID"=>				array("title"=>"lang_lang", "type"=>"select2", "fk_table"=>"LANG"),
			"AUTH_USER_ID"=>		array("title"=>"lang_administrator", "type"=>"select2", "fk_table"=>"AUTH_USER"),
			"EMAIL_SUBJECT"=>		array("title"=>"lang_email_subject", "type"=>"text", "is_main"=>1, "show"=>1, "errors"=>_nonempty_),
			"EMAIL_DATETIME"=>		array("title"=>"lang_time_dispatch", "type"=>"datetime", "show"=>1, "sort"=>"desc", "errors"=>_nonempty_),
			"EMAIL_BODY"=>			array("title"=>"lang_email_text", "type"=>"textarea", "errors"=>_nonempty_),
			"EMAIL_ATTACH"=>		array("title"=>"lang_email_attach", "type"=>"textarea"),
		),
		"indexes"=>array(
			"IDX_SL_ID" =>		array ("fields" => array ("SUBSCRIBE_LIST_ID" => array())),
			"IDX_AU_ID" =>		array ("fields" => array ("AUTH_USER_ID" => array())),
		),
	),
	
	// Привязка пользователей к листам подписки
	"SUBSCRIBER_SUBSCRIBE_LIST"=>array(
		"title"=>"lang_subscriber_subscribe_list_table",
		"type"=>"internal_table",
		"fields"=>array(
			"SUBSCRIBER_ID"=>	array("title"=>"", "type"=>"int", "pk"=>1),
			"SUBSCRIBE_LIST_ID"=>array("title"=>"", "type"=>"int", "pk"=>1),
		),
		"indexes"=>array(
			"IDX_SL_ID" =>		array ("fields" => array ("SUBSCRIBE_LIST_ID" => array())),
			"IDX_S_ID" =>		array ("fields" => array ("SUBSCRIBER_ID" => array())),
		),
	),
	
	// Привязка листов подписки к блокам
	"SUBSCRIBE_LIST_INF_BLOCK"=>array(
		"title"=>"lang_subscribe_list_inf_block_table",
		"type"=>"internal_table",
		"fields"=>array(
			"SUBSCRIBE_LIST_ID"=>	array("title"=>"", "type"=>"int", "pk"=>1),
			"INF_BLOCK_ID"=>		array("title"=>"", "type"=>"int", "pk"=>1),
		),
		"indexes"=>array(
			"IDX_SL_ID" =>		array ("fields" => array ("SUBSCRIBE_LIST_ID" => array())),
			"IDX_IB_ID" =>		array ("fields" => array ("INF_BLOCK_ID" => array())),
		),
	),
	
	// Привязка листов подписки к сайтам
	"SUBSCRIBE_LIST_SITE"=>array(
		"title"=>"lang_subscribe_list_site_table",
		"type"=>"internal_table",
		"fields"=>array(
			"SUBSCRIBE_LIST_ID"=>	array("title"=>"", "type"=>"int", "pk"=>1),
			"SITE_ID"=>				array("title"=>"", "type"=>"int", "pk"=>1),
		),
		"indexes"=>array(
			"IDX_SL_ID" =>		array ("fields" => array ("SUBSCRIBE_LIST_ID" => array())),
			"IDX_S_ID" =>		array ("fields" => array ("SITE_ID" => array())),
		),
	),
	
	// Отправленные письма
	"SUBSCRIBE_SENDED_CONTENT"=>array(
		"title"=>"lang_subscribe_sended_content_table",
		"type"=>"internal_table",
		"fields"=>array(
			"SUBSCRIBE_LIST_ID"=>	array("title"=>"", "type"=>"int", "pk"=>1),
			"CONTENT_ID"=>			array("title"=>"", "type"=>"int", "pk"=>1),
			"PRG_MODULE_ID"=>		array("title"=>"", "type"=>"int", "pk"=>1),
		),
		"indexes"=>array(
			"IDX_SL_ID" =>		array ("fields" => array ("SUBSCRIBE_LIST_ID" => array())),
			"IDX_C_ID" =>		array ("fields" => array ("CONTENT_ID" => array())),
			"IDX_PM_ID" =>		array ("fields" => array ("PRG_MODULE_ID" => array())),
		),
	),

	// -------------------------------------   Б  Л  О  Г  И   -----------------------------------------------
	// Блог
	"BLOG"=>array(
		"title"=>"lang_blog", // Блог
		"class"=>"blog",
		"type"=>"table",
		"fields"=>array(
			"BLOG_DATE"=>		array("title"=>"lang_blog_date", "type"=>"datetime", "show"=>1, "errors" => _nonempty_|_datetime_, "sort"=>"desc", "filter"=>"1" ), // Дата добавления
			"TITLE"=>			array("title"=>"lang_blog_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_|_login_ ), // Ник пользователя/название сообщества
			"NAME"=>			array("title"=>"lang_blog_name", "type"=>"text", "show"=>1 ), // Название
			"EMAIL"=>			array("title"=>"lang_blog_email", "type"=>"text", "show"=>1, "errors" => _email_ ), // E-mail
			"FIO"=>				array("title"=>"lang_blog_fio", "type"=>"text" ), // ФИО
			"ICQ"=>				array("title"=>"lang_blog_icq", "type"=>"text" ), // ICQ
			"SKYPE"=>			array("title"=>"lang_blog_skype", "type"=>"text" ), // Skype
			"PASSWORD_MD5"=>	array("title"=>"lang_blog_password", "type"=>"password_md5", "datatype_like"=>"text", "errors"=>_login_ ), // Пароль
			"BIRTHDATE"=>		array("title"=>"lang_blog_birthdate", "type"=>"date", "errors" => _date_ ), // Дата рождения
			"BIRTHDATE_FORMAT"=>array("title"=>"lang_blog_birthdate_format", "type"=>"select1", 
									"value_list" => array(
										array( "title"=>"lang_blog_ddmmyyyy", "value"=>"1" ), // ДД.ММ.ГГГГ
										array( "title"=>"lang_blog_ddmm", "value"=>"2" ), // ДД.ММ
										array( "title"=>"lang_blog_yyyy", "value"=>"3" ), // ГГГГ
									) 
								), // Формат отображения даты рождения
			"SEX"=>				array("title"=>"lang_blog_sex", "type"=>"select1", 
									"value_list" => array(
										array( "title"=>"lang_blog_sex_unisex", "value"=>"1" ), // не определён
										array( "title"=>"lang_blog_sex_male", "value"=>"2" ), // мужской
										array( "title"=>"lang_blog_sex_female", "value"=>"3" ), // женский
									) 
								), // Пол
			"BLOG_COUNTRY_ID"=>	array("title"=>"lang_blog_country", "type"=>"select2", "fk_table"=>"BLOG_COUNTRY", "filter"=>"1" ), // Страна
			"BLOG_CITY_ID"=>	array("title"=>"lang_blog_city", "type"=>"select2", "fk_table"=>"BLOG_CITY", "filter"=>"1"), // Город
			"HOMEPAGE"=>		array("title"=>"lang_blog_homepage", "type"=>"text" ), // Персональный web-сайт
			"ABOUT"=>			array("title"=>"lang_blog_about", "type"=>"textarea", "editor"=>1 ), // О себе
			"POSTS_ON_PAGE"=>	array("title"=>"lang_blog_posts_on_page", "type"=>"select1", 
									"value_list" => array(
										array( "title"=>"20", "value"=>"1" ), // 20
										array( "title"=>"30", "value"=>"2" ), // 30
										array( "title"=>"40", "value"=>"3" ), // 40
										array( "title"=>"50", "value"=>"4" ), // 50
									) 
								), // Количество записей на странице
			"IS_COMMUNITY"=>	array("title"=>"lang_blog_is_community", "type"=>"checkbox", "filter"=>"1" ), // Сообщество
			"MEMBERSHIP"=>		array("title"=>"lang_blog_membership", "type"=>"select1", 
									"value_list" => array(
										array( "title"=>"lang_blog_membership_free", "value"=>"1" ), // свободное
										array( "title"=>"lang_blog_membership_moderated", "value"=>"2" ), // модерируемое
									) 
								), // Условие вступления
			"POSTLEVEL"=>		array("title"=>"lang_blog_postlevel", "type"=>"select1", 
									"value_list" => array(
										array( "title"=>"lang_blog_postlevel_unqualified", "value"=>"1" ), // неограниченное
										array( "title"=>"lang_blog_postlevel_limited", "value"=>"2" ), // ограниченное
									) 
								), // Добавление записей
			"MODERATION"=>		array("title"=>"lang_blog_moderation", "type"=>"select1", 
									"value_list" => array(
										array( "title"=>"lang_blog_moderation_nomade", "value"=>"1" ), // не производится
										array( "title"=>"lang_blog_moderation_made", "value"=>"2" ), // производится
									) 
								), // Модерация записей
			"IS_ACTIVE"=>		array("title"=>"lang_blog_is_active", "type"=>"checkbox", "filter"=>"1" ), // Активный
		), 
		"m2m"=>array(
			"BLOG_SV_BLOG_INTEREST"=>	array("secondary_table"=>"BLOG_INTEREST" ),
			"BLOG_SV_BLOG_FIELD"=>		array("secondary_table"=>"BLOG_FIELD" ),
			"BLOG_SV_BLOG_INTEREST"=>	array("secondary_table"=>"BLOG_INTEREST" ),
		),
		"links"=>array(
			"BLOG_IMAGE"=>			array( "show"=>1, "on_delete_cascade"=>1 ),
			"BLOG_FRIENDGROUP"=>	array( "show"=>1, "on_delete_cascade"=>1 ),
			"BLOG_POST"=>			array( "show"=>1, "on_delete_cascade"=>1 ),
			"BLOG_TAG"=>			array( "show"=>1, "on_delete_cascade"=>1 ),
		),
		"indexes"=>array(
			"IDX_BBCO_ID" =>	array ("fields" => array ("BLOG_COUNTRY_ID" => array())),
			"IDX_BBCI_ID" =>	array ("fields" => array ("BLOG_CITY_ID" => array())),
			"IDX_BIC_ID" =>		array ("fields" => array ("IS_COMMUNITY" => array())),
			"IDX_BIA_ID" =>		array ("fields" => array ("IS_ACTIVE" => array())),
		),
	),
	// Страна
	"BLOG_COUNTRY"=>array(
		"title"=>"lang_blog_country", // Страна
		"type"=>"table",
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ), // Название
		),
		"links"=>array(
			"BLOG"=>			array( "show"=>1, "on_delete_cascade"=>1 ),
			"BLOG_CITY"=>		array( "show"=>1, "on_delete_cascade"=>1 ),
		),
	),
	// Город
	"BLOG_CITY"=>array(
		"title"=>"lang_blog_city", // Город
		"type"=>"table",
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ), // Название
			"BLOG_COUNTRY_ID"=>	array("title"=>"lang_blog_country", "type"=>"select2", "show"=>"1", "fk_table"=>"BLOG_COUNTRY", "filter"=>"1" ), // Страна
		),
		"links"=>array(
			"BLOG"=>			array( "show"=>1, "on_delete_cascade"=>1 ),
		),
		"indexes"=>array(
			"IDX_BCBCO_ID" =>	array ("fields" => array ("BLOG_COUNTRY_ID" => array())),
		),
	),
	// Настроение
	"BLOG_MOOD"=>array(
		"title"=>"lang_blog_mood", // Настроение
		"type"=>"table",
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ), // Название
	  		"IMAGE"=>			array("title"=>"lang_blog_mood_image", "type"=>"img", "upload_dir"=>"upload/blogs/mood/"), // Изображение
		),
	),
	// Интерес
	"BLOG_INTEREST"=>array(
		"title"=>"lang_blog_interest", // Интерес
		"type"=>"table",
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ), // Название
			"RATING"=>			array("title"=>"lang_blog_interest_rating", "type"=>"int", "show"=>"1", "errors" => _int_ ), // Количество людей, добавивших интерес
		),
	),
	// Связь интереса с блогом
	"BLOG_SV_BLOG_INTEREST"=>array(
		"title"=>"lang_blog_sv_blog_interest", // Связь интереса с блогом
		"type"=>"internal_table",
		"fields"=>array(
			"BLOG_ID"=>			array("title"=>"", "type"=>"int", "pk"=>1),
			"BLOG_INTEREST_ID"=>array("title"=>"", "type"=>"int", "pk"=>1),
		),
		"indexes"=>array(
			"IDX_BSBIB_ID" =>	array ("fields" => array ("BLOG_ID" => array())),
			"IDX_BSBIBI_ID" =>	array ("fields" => array ("BLOG_INTEREST_ID" => array())),
		),
	),
	// Изображение пользователя
	"BLOG_IMAGE"=>array(
		"title"=>"lang_blog_image", // Изображение пользователя
		"type"=>"table",
		"fields"=>array(
			"BLOG_ID"=>			array("title"=>"lang_blog", "type"=>"select2", "show"=>1, "fk_table"=>"BLOG", "errors" => _nonempty_, "filter"=>"1" ), // Блог
			"IMAGE_DATE"=>		array("title"=>"lang_blog_image_date", "type"=>"datetime", "show"=>1, "errors" => _datetime_, "sort"=>"desc" ), // Дата добавления
			"TITLE"=>			array("title"=>"lang_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ), // Название
	  		"IMG"=>				array("title"=>"lang_blog_image_image", "type"=>"img", "upload_dir"=>"upload/blogs/image/"), // Фотография
			"IS_DEFAULT"=>		array("title"=>"lang_blog_image_is_default", "type"=>"checkbox", "show"=>1, "filter"=>"1" ), // По умолчанию
		),
	),
	// Тег
	"BLOG_TAG"=>array(
		"title"=>"lang_blog_tag", // Тег
		"type"=>"table",
		"fields"=>array(
			"BLOG_ID"=>			array("title"=>"lang_blog", "type"=>"select2", "show"=>1, "fk_table"=>"BLOG", "errors" => _nonempty_, "filter"=>"1" ), // Блог
			"TITLE"=>			array("title"=>"lang_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ), // Название
			"RATING"=>			array("title"=>"lang_blog_tag_rating", "type"=>"int", "show"=>"1", "errors" => _int_ ), // Количество связанных тем
		),
	),
	// Группа друзей
	"BLOG_FRIENDGROUP"=>array(
		"title"=>"lang_blog_friendgroup", // Группа друзей
		"type"=>"table",
		"fields"=>array(
			"BLOG_ID"=>			array("title"=>"lang_blog", "type"=>"select2", "show"=>1, "fk_table"=>"BLOG", "errors" => _nonempty_, "filter"=>"1" ), // Блог
			"TITLE"=>			array("title"=>"lang_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ), // Название
			"LIST_ORDER"=>		array("title"=>"lang_order", "type"=>"order", "show"=>"1", "sort"=>"desc" ), // Порядок
		),
		"m2m"=>array(
			"BLOG_FRIEND_SV_BLOG_FRIENDGROUP"=>	array("secondary_table"=>"BLOG_FRIEND" ),
		),
	),
	// Друг
	"BLOG_FRIEND"=>array(
		"title"=>"lang_blog_friend", // Друг
		"type"=>"table",
		"fields"=>array(
			"ADDED_DATE"=>		array("title"=>"lang_blog_friend_date", "type"=>"datetime", "show"=>1, "errors" => _nonempty_|_datetime_, "sort"=>"desc" ), // Дата добавления
			"FRIEND_ID"=>		array("title"=>"lang_blog_friend", "type"=>"select2", "show"=>1, "is_main"=>1, "fk_table"=>"BLOG", "errors" => _nonempty_ ), // Друг
			"OWNER_ID"=>		array("title"=>"lang_blog_friend_owner", "type"=>"select2", "show"=>1, "fk_table"=>"BLOG", "errors" => _nonempty_, "filter"=>"1" ), // Владелец
			"INVITER_ID"=>		array("title"=>"lang_blog_friend_inviter", "type"=>"select2", "show"=>1, "fk_table"=>"BLOG", "errors" => _nonempty_, "filter"=>"1" ), // Пригласивший в сообщество
			"LEVEL"=>			array("title"=>"lang_blog_friend_level", "type"=>"select1", 
									"value_list" => array(
										array( "title"=>"lang_blog_friend_level_readonly", "value"=>"1" ), // только чтение
										array( "title"=>"lang_blog_friend_level_read_add", "value"=>"2" ), // чтение и добавление
									) 
								), // Ограничение на добавление записей
			"IS_CREATOR"=>		array("title"=>"lang_blog_friend_is_creator", "type"=>"checkbox" ), // Создатель сообщества
			"IS_MODERATOR"=>	array("title"=>"lang_blog_friend_is_moderator", "type"=>"checkbox" ), // Модератор
			"IS_INVITE"=>		array("title"=>"lang_blog_friend_is_invite", "type"=>"checkbox" ), // Приглашение
			"IS_INQUIRY"=>		array("title"=>"lang_blog_friend_is_inquiry", "type"=>"checkbox" ), // Запрос на вступление
		),
		"m2m"=>array(
			"BLOG_FRIEND_SV_BLOG_FRIENDGROUP"=>	array("secondary_table"=>"BLOG_FRIENDGROUP" ),
		),
	),
	// Связь друга с группой друзей
	"BLOG_FRIEND_SV_BLOG_FRIENDGROUP"=>array(
		"title"=>"lang_blog_friend_sv_blog_friendgroup", // Связь друга с группой друзей
		"type"=>"internal_table",
		"fields"=>array(
			"BLOG_FRIEND_ID"=>		array("title"=>"", "type"=>"int", "pk"=>1),
			"BLOG_FRIENDGROUP_ID"=>	array("title"=>"", "type"=>"int", "pk"=>1),
		),
		"indexes"=>array(
			"IDX_BFSBFGBF_ID" =>	array ("fields" => array ("BLOG_FRIEND_ID" => array())),
			"IDX_BFSBFGBFG_ID" =>	array ("fields" => array ("BLOG_FRIENDGROUP_ID" => array())),
		),
	),
	// Поле
	"BLOG_FIELD"=>array(
		"title"=>"lang_blog_field", // Поле
		"type"=>"table",
		"fields"=>array(
			"TITLE"=>			array("title"=>"lang_title", "type"=>"text", "show"=>1, "is_main"=>1, "errors" => _nonempty_ ), // Название
			"FIELD_NAME"=>		array("title"=>"lang_blog_field_name", "type"=>"text", "show"=>"1", "errors" => _nonempty_ ), // Имя атрибута
		),
	),
	// Связь поля с блогом
	"BLOG_SV_BLOG_FIELD"=>array(
		"title"=>"lang_blog_sv_blog_field", // Связь поля с блогом
		"type"=>"internal_table",
		"fields"=>array(
			"BLOG_ID"=>		array("title"=>"", "type"=>"int", "pk"=>1),
			"BLOG_FIELD_ID"=>	array("title"=>"", "type"=>"int", "pk"=>1),
		),
		"indexes"=>array(
			"IDX_BSBFB_ID" =>	array ("fields" => array ("BLOG_ID" => array())),
			"IDX_BSBFBF_ID" =>	array ("fields" => array ("BLOG_FIELD_ID" => array())),
		),
	),
	// Запись
	"BLOG_POST"=>array(
		"title"=>"lang_blog_post", // Запись
		"type"=>"table",
		"fields"=>array(
			"ADDED_DATE"=>		array("title"=>"lang_blog_post_date", "type"=>"datetime", "show"=>1, "errors" => _nonempty_|_datetime_, "sort"=>"desc", "filter"=>"1" ), // Дата добавления
			"CHANGE_DATE"=>		array("title"=>"lang_blog_post_change_date", "type"=>"datetime", "show"=>1, "errors" => _datetime_, "filter"=>"1" ), // Дата последнего изменения
			"AUTHOR_ID"=>		array("title"=>"lang_blog_post_author", "type"=>"select2", "show"=>1, "fk_table"=>"BLOG", "errors" => _nonempty_, "filter"=>"1" ), // Автор записи
			"BLOG_ID"=>			array("title"=>"lang_blog", "type"=>"select2", "show"=>1, "fk_table"=>"BLOG", "errors" => _nonempty_, "filter"=>"1" ), // Блог
			"BLOG_IMAGE_ID"=>	array("title"=>"lang_blog_post_image", "type"=>"select2", "fk_table"=>"BLOG_IMAGE" ), // Изображение пользователя
			"TITLE"=>			array("title"=>"lang_blog_post_title", "type"=>"text", "is_main"=>1 ), // Тема
			"BODY"=>			array("title"=>"lang_blog_post_body", "type"=>"textarea", "editor"=>1, "errors" => _nonempty_ ), // Текст записи
			"BLOG_MOOD_ID"=>	array("title"=>"lang_blog_post_mood", "type"=>"select2", "fk_table"=>"BLOG_MOOD", "filter"=>"1" ), // Настроение пользователя
			"CURRENT_MUSIC"=>	array("title"=>"lang_blog_post_music", "type"=>"text" ), // Текущая музыка
			"IS_PUBLIC"=>		array("title"=>"lang_blog_post_is_public", "type"=>"checkbox", "filter"=>"1" ), // Опубликована
			"ACCESS"=>			array("title"=>"lang_blog_post_access", "type"=>"select1", "filter"=>"1", 
									"value_list" => array(
										array( "title"=>"lang_blog_post_access_for_all", "value"=>"1" ), // для всех
										array( "title"=>"lang_blog_post_access_for_friend", "value"=>"2" ), // для друзей
										array( "title"=>"lang_blog_post_access_personal", "value"=>"3" ), // личное
										array( "title"=>"lang_blog_post_access_selectively", "value"=>"4" ), // выборочно
									) 
								), // Разрешение на чтение
			"IS_CONFIRM"=>		array("title"=>"lang_blog_post_is_confirm", "type"=>"checkbox" ), // На подтверждение модератором
			"IS_DISABLECOMMENT"=>array("title"=>"lang_blog_post_is_disablecomment", "type"=>"checkbox" ), // Скрыть комментарии
		),
		"m2m"=>array(
			"BLOG_POST_SV_BLOG_TAG"=>			array("secondary_table"=>"BLOG_TAG" ),
			"BLOG_POST_SV_BLOG_FRIENDGROUP"=>	array("secondary_table"=>"BLOG_FRIENDGROUP" ),
		),
		"links"=>array(
			"BLOG_COMMENT"=>			array( "show"=>1, "on_delete_cascade"=>1 ),
		),
		"indexes"=>array(
			"IDX_BPB_ID" =>		array ("fields" => array ("BLOG_ID" => array())),
			"IDX_BPA_ID" =>		array ("fields" => array ("AUTHOR_ID" => array())),
			"IDX_BPBI_ID" =>	array ("fields" => array ("BLOG_IMAGE_ID" => array())),
			"IDX_BPBM_ID" =>	array ("fields" => array ("BLOG_MOOD_ID" => array())),
			"IDX_BPIP_ID" =>	array ("fields" => array ("IS_PUBLIC" => array())),
			"IDX_BPACC_ID" =>	array ("fields" => array ("ACCESS" => array())),
		),
	),
	// Связь тега с записью
	"BLOG_POST_SV_BLOG_TAG"=>array(
		"title"=>"lang_blog_post_sv_blog_tag", // Связь тега с записью
		"type"=>"internal_table",
		"fields"=>array(
			"BLOG_POST_ID"=>	array("title"=>"", "type"=>"int", "pk"=>1),
			"BLOG_TAG_ID"=>		array("title"=>"", "type"=>"int", "pk"=>1),
		),
		"indexes"=>array(
			"IDX_BPSBTBP_ID" =>	array ("fields" => array ("BLOG_POST_ID" => array())),
			"IDX_BPSBTBT_ID" =>	array ("fields" => array ("BLOG_TAG_ID" => array())),
		),
	),
	// Связь групп друзей с записью
	"BLOG_POST_SV_BLOG_FRIENDGROUP"=>array(
		"title"=>"lang_blog_post_sv_blog_friendgroup", // Связь групп друзей с записью
		"type"=>"internal_table",
		"fields"=>array(
			"BLOG_POST_ID"=>		array("title"=>"", "type"=>"int", "pk"=>1),
			"BLOG_FRIENDGROUP_ID"=>	array("title"=>"", "type"=>"int", "pk"=>1),
		),
		"indexes"=>array(
			"IDX_BPSBFGBP_ID" =>	array ("fields" => array ("BLOG_POST_ID" => array())),
			"IDX_BPSBFGBFG_ID" =>	array ("fields" => array ("BLOG_FRIENDGROUP_ID" => array())),
		),
	),
	// Комментарий
	"BLOG_COMMENT"=>array(
		"title"=>"lang_blog_comment", // Комментарий
		"type"=>"table",
		"fields"=>array(
			"ADDED_DATE"=>		array("title"=>"lang_blog_comment_date", "type"=>"datetime", "show"=>1, "errors" => _nonempty_|_datetime_, "sort"=>"desc", "filter"=>"1" ), // Дата добавления
			"BLOG_POST_ID"=>	array("title"=>"lang_blog_post", "type"=>"select2", "fk_table"=>"BLOG_POST", "errors" => _nonempty_, "filter"=>"1" ), // Запись
			"AUTHOR_ID"=>		array("title"=>"lang_blog_comment_author", "type"=>"select2", "show"=>1, "fk_table"=>"BLOG", "errors" => _nonempty_, "filter"=>"1" ), // Автор комментария
			"PARENT_ID"=>		array("title"=>"lang_blog_comment_parent", "type"=>"select2", "fk_table"=>"BLOG_COMMENT" ), // Вышестоящий комментарий
			"BLOG_IMAGE_ID"=>	array("title"=>"lang_blog_comment_image", "type"=>"select2", "fk_table"=>"BLOG_IMAGE" ), // Изображение пользователя
			"TITLE"=>			array("title"=>"lang_blog_comment_title", "type"=>"text", "show"=>1, "is_main"=>1 ), // Тема комментария
			"BODY"=>			array("title"=>"lang_blog_comment_body", "type"=>"textarea", "editor"=>1 ), // Текст комментария
			"IS_PUBLIC"=>		array("title"=>"lang_blog_comment_is_public", "type"=>"checkbox", "filter"=>"1" ), // Опубликована
			"IS_DISABLE"=>		array("title"=>"lang_blog_comment_is_disable", "type"=>"checkbox" ), // Скрыть
		),
		"indexes"=>array(
			"IDX_BCBP_ID" =>	array ("fields" => array ("BLOG_POST_ID" => array())),
			"IDX_BCA_ID" =>		array ("fields" => array ("AUTHOR_ID" => array())),
			"IDX_BCP_ID" =>		array ("fields" => array ("PARENT_ID" => array())),
			"IDX_BCBI_ID" =>	array ("fields" => array ("BLOG_IMAGE_ID" => array())),
			"IDX_BCIP_ID" =>	array ("fields" => array ("IS_PUBLIC" => array())),
		),
	),
	// Параметры изображений
	"BLOG_IMAGE_SETTINGS"=>array(
		"title"=>"lang_blog_image_settings", // Параметры изображений
		"type"=>"table",
		"no_add"=>1,
		"no_delete"=>1,
		"fields"=>array(
			"SIZE"=>	array("title"=>"lang_blog_image_settings_size", "type"=>"int", "show"=>"1", "errors" => _int_ ), // Размер(кБ)
			"WIDTH"=>	array("title"=>"lang_blog_image_settings_width", "type"=>"int", "show"=>"1", "errors" => _int_ ), // Высота(пикс.)
			"HEIGHT"=>	array("title"=>"lang_blog_image_settings_height", "type"=>"int", "show"=>"1", "errors" => _int_ ), // Ширина(пикс.)
			"TOTAL"=>	array("title"=>"lang_blog_image_settings_total", "type"=>"int", "show"=>"1", "errors" => _int_ ), // Общее количество
		),
	),

);
?>