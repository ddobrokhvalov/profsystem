{$header}
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	
    <meta name="keywords" content="{$keywords}">
    <meta name="description" content="{$description}">
	<title>{$title}</title>
	<link rel="stylesheet" type="text/css" href="/common/css/style.css" media="all">
	<link rel="stylesheet" type="text/css" href="/common/css/styles.css" media="all">
	<link rel="stylesheet" type="text/css" href="/common/css/fix.css" media="all">
	<link rel="stylesheet" type="text/css" href="/common/css/calendar.css">
	<script type="text/javascript" src="/common/js/jquery.min.js"></script>
	<script type="text/javascript" src="/common/js/jquery.flexslider-min.js"></script>
	{literal}
	<script type="text/javascript" charset="utf-8">
	    var $ = jQuery.noConflict();
	    $(document).ready(function() {
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
				<div class="contacts">
					<form class="search-form" name="search-form-top">
						 <input type="text" class="search-form_input" name="search" placeholder="поиск...">
						 <div class="search-form__icon-phone"></div>
					</form>	
				</div>
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
		<div id="katalog-s" class="container"  style="margin-top: 20px;">
			{if $areas.left_col_1 || $areas.left_col_2 || $areas.left_col_3 || $areas.left_col_4 || $areas.left_col_5}
			<div class="left-side">
				{$areas.left_col_1}				
				{$areas.left_col_2}
				{$areas.left_col_3}
				{$areas.left_col_4}
				{$areas.left_col_5}
			</div>
			{/if}
			<div class="{if $areas.left_col_1 || $areas.left_col_2 || $areas.left_col_3 || $areas.left_col_4 || $areas.left_col_5}content{/if}">
				{$areas.status}
				{if $page.TITLE}
					<h1>{$page.TITLE}</h1>
				{/if}
				{$areas.inner_body_1}
				{$areas.inner_body_2}
				{$areas.body}
				{$areas.inner_body_3}
				{$areas.inner_body_4}
			</div>
		</div>
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
						<div class="contacts">
							<form class="search-form" name="search-form-bottom">
								 <input type="text" class="search-form_input" name="search" placeholder="поиск...">
								 <div class="search-form__icon-phone"></div>
							</form>	
						</div>
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