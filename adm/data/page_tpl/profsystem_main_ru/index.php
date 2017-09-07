{$header}
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link href="/common/img/profsystem_style/favicon.png" rel="shortcut icon">
    <meta name="keywords" content="{$keywords}">
    <meta name="description" content="{$description}">
	<title>{$title}</title>
	<link rel="stylesheet" type="text/css" href="/common/css/style.css" media="all">
	<link rel="stylesheet" type="text/css" href="/common/css/styles.css" media="all">
	<link rel="stylesheet" type="text/css" href="/common/css/fix.css" media="all">
	<link rel="stylesheet" type="text/css" href="/common/css/calendar.css">
	<script type="text/javascript" src="/common/js/jquery.min.js"></script>
	<script type="text/javascript" src="/common/js/jquery.flexslider-min.js"></script>
	<script type="text/javascript" src="/common/js/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
	<link rel="stylesheet" href="/common/js/fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen">
	{literal}
	<script type="text/javascript">
	    var $ = jQuery.noConflict();
	    $(document).ready(function() {
		    $('.flexslider').flexslider({
		    	directionNav: false
		    });
		});
	</script>
	{/literal}
</head>
<body>
	<header>
		<div id="header">
			<div class="container header">
				{$areas.header_1}
				{$areas.header_2}
				{$areas.header_3}
				{$areas.header_4}
				{$areas.header_5}
			</div>
			<div class="clear"></div>
		</div>
		<div id="top-menu">
			<div class="container">
				{$areas.logo}
				{$areas.top_main_menu}
			</div>
		</div>
	</header>
	<section>
		{$areas.slider}
	</section>
	<section>
		<div id="icons" class="container">
			{$areas.top_main_menu_2}
		</div>
	</section>
	<section>
		<div id="katalog">
			<div class="container">
				{$areas.main_body_1}
				{$areas.body}
				{$areas.main_body_2}
				{$areas.main_body_3}
			</div>
		</div>
	</section>
	<section>
		<div id="news-articles" class="container">
			<div class="news">
				{if $areas.main_body_4}
					{$areas.main_body_4}
				{/if}
			</div>
			<div class="articles">
				{if $areas.main_body_5}
					{$areas.main_body_5}
				{/if}
			</div>
		</div>
		{$areas.main_body_6}
		{$areas.main_body_7}
		<div class="clear"></div>
	</section>
	<footer>
		{if $areas.bottom_menu}
			{$areas.bottom_menu}
		{/if}
		<div class="clear"></div>
		<div id="foother">
			<div class="container-foother">
				<div class="foother">
					<div style="display: inline-block;">
						{$areas.footer_1}
					</div>
					
					<div style="display: inline-block;vertical-align: top;">
						{$areas.footer_2}
					</div>

					<div style="display: inline-block; vertical-align: top; width: 290px;">
						{$areas.footer_3}
					</div>

					<div style="display: inline-block; vertical-align: top; padding-top: 5px;">
						{$areas.footer_4}
					</div>

					<div class="right">
						{$areas.footer_5}
						{$areas.footer_6}
					</div>
				</div>
			</div>
		</div>
	</footer>
	<script type="text/javascript" src="/common/js/common.js"></script>
	<script type="text/javascript" src="/common/js/script.js"></script>
</body>
</html>