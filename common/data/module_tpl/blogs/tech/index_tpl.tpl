{$PHP_START}

include_once('{$PATH_TO_BLOG_PARAMS}');

$module_info=array();
$blog_site=module::factory('BLOGS');
$blog_site->init(
  array(
    'page_id'=>$page_id,
    'version'=>$version,
    'lang_id'=>$lang_id,
    'lang_root_dir'=>$lang_root_dir,
    'site_id'=>$site_id,
    'area_id'=>$tarea_id,
    'block_id'=>$block_id,
    'cache_time'=>$cache_time,
    'is_main'=>$is_main,
    'is_print'=>$is_print
  ),
  array(
    'view_mode'=>'{$VIEW_MODE}',
    'submode'=>'{$SUBMODE}',
    'template'=>$template,
    'blog_id'=>'{$BLOG_ID}'
  ),
  &$module_info
);
echo $blog_site->get_body();

{$PHP_END}