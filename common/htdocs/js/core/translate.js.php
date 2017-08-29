var Dictionary =
{
	aWords: new Array(),
	
	translate: function( sWord )
	{
		return this.aWords[ sWord ] ? this.aWords[ sWord ] : sWord;
	}
};

<?php
	include_once(dirname(__FILE__)."/../../../data/config/params.php");

	$lang_name = isset( $_GET['lang'] ) ? $_GET['lang'] : 'ru';
	$lang_name = preg_replace( '/[^A-z]/', '', $lang_name );
	
	// to do: переделать на метаданные, чтобы каждый раз не грузить
	$content = "";
	$jss=filesystem::ls_r("../lang/{$lang_name}");
	foreach($jss as $js)
		if(!$js["is_dir"] && preg_match('/\.js$/', $js['pure_name']))
			$content .= file_get_contents( $js["name"] );
	
	print $content;
?>
