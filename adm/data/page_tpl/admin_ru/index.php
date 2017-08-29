{$header}
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="keywords" content="{$keywords}">
    <meta name="description" content="{$description}">
	<title>{$title}</title>
	<link rel="stylesheet" type="text/css" href="/common/css/default.css" media="all">
	<link rel="stylesheet" type="text/css" href="/common/css/print.css" media="print">
	<link rel="stylesheet" type="text/css" href="/common/css/calendar.css">
	<link rel="stylesheet" type="text/css" href="/common/adm/css/rbccontents.css"/>
	<link rel="stylesheet" type="text/css" href="/common/adm/css/rbccontents-middle.css"/>
	
	<script type="text/javascript" src="/common/js/core/check_form.js"></script>
	<script type="text/javascript" src="/common/js/core/calendar.js"></script>
	
	<script type="text/javascript" src="/common/adm/js/core/check.js"></script>
	<script type="text/javascript" src="/common/adm/js/core/manager.js"></script>
	<script type="text/javascript" src="/common/adm/js/core/interface.js"></script>
</head>
<body>
<table id="main-tab" border="0" cellpadding="0" cellspacing="0">
	<tr>
	<td id="left">
		<div id="logo"><a href="/ru/"><img src="/common/img/logo.gif" width="195" height="38" border="0" alt="Templates" title="Templates"></a>

</div>
		{$areas.menu}
	</td>
	<td id="right">
		<div class="grey-line"><DIV class="spacer" style="height: 10px;">&nbsp;</DIV></div>
		<div id="extra" style="float: left;"><a href="/ru/"><img src="/common/img/home.gif" width="22" height="21" border="0" title="Главная страница" alt="Главная страница"></a><span class="spacer" style="padding: 3px;">&nbsp;</span><a href="/ru/test/test_sitemap/"><img src="/common/img/sitemap.gif" width="22" height="21" border="0" title="Карта сайта" alt="Карта сайта"></a><span class="spacer" style="padding: 3px;">&nbsp;</span><a href="/ru/test/test_form_question/"><img src="/common/img/letter.gif" width="22" height="21" border="0" title="Обратная связь" alt="Обратная связь"></a><span class="spacer" style="padding: 3px;">&nbsp;</span><a href="/en/"><img src="/common/img/eng.gif" width="32" height="21" border="0" title="English" alt="English"></a>

</div>		<DIV class="spacer" style="clear: both; height: 20px;">&nbsp;</DIV>
		{if $areas.status}
		<div class="status">
			{$areas.status}
		</div>
		{/if}
		{if $page.TITLE}
		<H1>{$page.TITLE}</H1>
		{/if}
		{if $areas.body_top}
		<div class="body_top">
			{$areas.body_top}
		</div>
		{/if}
		{if $areas.body}
		<div class="body">
			{$areas.body}
		</div>
		{/if}
		{if $areas.body_bottom}
		<div class="body_bottom">
			{$areas.body_bottom}
		</div>
		{/if}
	</td>
	</tr>
	<tr>
		<td colspan="3" id="footer">
			<div id="copyrights"></div>
		</td>
	</tr>
</table>
</body>
</html>