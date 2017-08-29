<?php
// include the params file
include dirname(__FILE__).'/../../../data/config/params.php';
include_once(params::$params["adm_data_server"]["value"]."class/core/auth/auth.php");

session_start();
system_params::apply_global_params_from_db();

if ( !auth::is_auth() ) {
	$_SESSION["back_url"]=$_SERVER["SCRIPT_NAME"].($_SERVER["QUERY_STRING"]?"?".$_SERVER["QUERY_STRING"]:"");
	header( "Location: /index.php" );
	exit;
}

$spaw_default_lang = params::$params["default_interface_lang"]["value"];

// directory where spaw files are located
// виртуальный путь от корня сервера до папки с редактором
$spaw_dir = '/editor/';

// base url for images
// базовый URL для изображений; достаточно пути от корня адм. сервера
$spaw_base_url = params::$params["common_htdocs_http"]["value"];

// физический путь до папки редактора
if (!ereg('/$', params::$params["adm_htdocs_server"]["value"]))
  $spaw_root = params::$params["adm_htdocs_server"]["value"].$spaw_dir;
else
  $spaw_root = params::$params["adm_htdocs_server"]["value"].substr($spaw_dir,1,strlen($spaw_dir)-1);
  

$spaw_default_toolbars = 'default';
$spaw_default_theme = 'default';
$spaw_default_css_stylesheet = $spaw_dir.'wysiwyg.css';

// add javascript inline or via separate file
$spaw_inline_js = false;

// use active toolbar (reflecting current style) or static
$spaw_active_toolbar = true;

// default dropdown content
$spaw_dropdown_data['style']['default'] = 'Normal';

$spaw_dropdown_data['table_style']['default'] = 'Normal';

$spaw_dropdown_data['td_style']['default'] = 'Normal';

$spaw_dropdown_data['font']['Arial'] = 'Arial';
$spaw_dropdown_data['font']['Courier'] = 'Courier';
$spaw_dropdown_data['font']['Tahoma'] = 'Tahoma';
$spaw_dropdown_data['font']['Times New Roman'] = 'Times';
$spaw_dropdown_data['font']['Verdana'] = 'Verdana';

$spaw_dropdown_data['fontsize']['1'] = '1';
$spaw_dropdown_data['fontsize']['2'] = '2';
$spaw_dropdown_data['fontsize']['3'] = '3';
$spaw_dropdown_data['fontsize']['4'] = '4';
$spaw_dropdown_data['fontsize']['5'] = '5';
$spaw_dropdown_data['fontsize']['6'] = '6';

// in mozilla it works only with this settings, if you don't care
// about mozilla you can change <H1> to Heading 1 etc.
// this way it will be reflected in active toolbar
$spaw_dropdown_data['paragraph']['Normal'] = 'Normal';
$spaw_dropdown_data['paragraph']['<H1>'] = 'Heading 1';
$spaw_dropdown_data['paragraph']['<H2>'] = 'Heading 2';
$spaw_dropdown_data['paragraph']['<H3>'] = 'Heading 3';
$spaw_dropdown_data['paragraph']['<H4>'] = 'Heading 4';
$spaw_dropdown_data['paragraph']['<H5>'] = 'Heading 5';
$spaw_dropdown_data['paragraph']['<H6>'] = 'Heading 6';

// image library related config

// allowed extentions for uploaded image files
// разрешённые расширения для загружаемых/используемых графических файлов
$spaw_valid_imgs = array('gif', 'jpg', 'jpeg', 'png');

// allow upload in image library
// возможность загрузки изображений
$spaw_upload_allowed = true;

// allow delete in image library
// возможность удаления изображений
$spaw_img_delete_allowed = true;

// image libraries
// папки с изображениями (путь относительно базового содержится в переменной value, в text - произвольное название)
// для RBC Contents доступна только одна папка
$spaw_imglibs = array(
  array(
    'value'   => params::$params["upload_dir"]["value"],
    'text'    => 'Изображения',
  )
);

// file to include in img_library.php (useful for setting $spaw_imglibs dynamically
// путь до файла, подгружаемого в качестве библиотеки из основного скрипта библиотеки изображений
// $spaw_imglib_include = '';

// allow delete in file library
// возможность удаления файлов
$spaw_file_delete_allowed = true;

// file libraries
// папка с файлами
$spaw_filelibs = array(
  array(
    'value'   => params::$params["upload_dir"]["value"],
    'text'    => 'Файлы',
  )
);

// allowed hyperlink targets
$spaw_a_targets['_self'] = 'Self';
$spaw_a_targets['_blank'] = 'Blank';
$spaw_a_targets['_top'] = 'Top';
$spaw_a_targets['_parent'] = 'Parent';

// image popup script url
// pop-up скрипт для просмотра изображений
$spaw_img_popup_url = $spaw_dir.'img_popup.php';

// internal link script url
$spaw_internal_link_script = 'url to your internal link selection script';

// disables style related controls in dialogs when css class is selected
$spaw_disable_style_controls = true;

?>