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
					{literal}<?if(!$_GET["id_4"]):?>{/literal}
						<h1>{$page.TITLE}</h1>
					{literal}<?endif;?>{/literal}
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
						{$areas.footer_4}
						{literal}
						<div class="y_counter">
							<!-- Yandex.Metrika informer -->
							<a href="https://metrika.yandex.ru/stat/?id=46375131&amp;from=informer"
							target="_blank" rel="nofollow"><img src="https://informer.yandex.ru/informer/46375131/3_0_484848FF_282828FF_1_pageviews"
							style="width:88px; height:31px; border:0;" alt="Яндекс.Метрика" title="Яндекс.Метрика: данные за сегодня (просмотры, визиты и уникальные посетители)" /></a>
							<!-- /Yandex.Metrika informer -->

							<!-- Yandex.Metrika counter -->
							<script type="text/javascript" >
								(function (d, w, c) {
									(w[c] = w[c] || []).push(function() {
										try {
											w.yaCounter46375131 = new Ya.Metrika({
												id:46375131,
												clickmap:true,
												trackLinks:true,
												accurateTrackBounce:true,
												webvisor:true
											});
										} catch(e) { }
									});

									var n = d.getElementsByTagName("script")[0],
										s = d.createElement("script"),
										f = function () { n.parentNode.insertBefore(s, n); };
									s.type = "text/javascript";
									s.async = true;
									s.src = "https://mc.yandex.ru/metrika/watch.js";

									if (w.opera == "[object Opera]") {
										d.addEventListener("DOMContentLoaded", f, false);
									} else { f(); }
								})(document, window, "yandex_metrika_callbacks");
							</script>
							<noscript><div><img src="https://mc.yandex.ru/watch/46375131" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
							<!-- /Yandex.Metrika counter -->
						</div>
						<!-- Global site tag (gtag.js) - Google Analytics -->
						<script async src="https://www.googletagmanager.com/gtag/js?id=UA-108501116-1"></script>
						<script>
						  window.dataLayer = window.dataLayer || [];
						  function gtag(){dataLayer.push(arguments);}
						  gtag('js', new Date());

						  gtag('config', 'UA-108501116-1');
						</script>
						{/literal}
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