{$header}
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	
    <meta name="keywords" content="{$keywords}">
    <meta name="description" content="{$description}">
	<title>{$title}</title>
	<link rel="stylesheet" type="text/css" href="/common/css/style.css" media="all">
	<link rel="stylesheet" type="text/css" href="/common/css/styles.css" media="all">
	<link rel="stylesheet" type="text/css" href="/common/css/calendar.css">
	<script type="text/javascript" src="/common/js/jquery.min.js"></script>
	<script type="text/javascript" src="/common/js/jquery.flexslider-min.js"></script>
	{literal}
	<script type="text/javascript" charset="utf-8">
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
				<div class="contacts" style="width: 285px;">
					<div class="contacts__icon-address"></div>
					<div class="contacts__text">Тверь, ул.Паши Савельевой, офис 506</div>
				</div>
				<div class="contacts" style="width: 260px;">
					<div class="contacts__icon-email"></div>
					<div class="contacts__text">email@gmail.com</div>
				</div>
				<div class="contacts">
					<form class="search-form" name="search-form-top">
						 <input type="text" class="search-form_input" name="search" placeholder="поиск...">
						 <div class="search-form__icon-phone"></div>
					</form>	
				</div>
				<div class="contacts right">
					<div class="contacts__call">ЗАКАЗАТЬ ЗВОНОК</div>
				</div>
				<div class="contacts right">
					<div class="contacts__icon-phone"></div>
					<div class="contacts__text_c-w">8(900) 800-80-80</div>
				</div>
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
		<div id="katalog" class="container">
			{$areas.main_body_1}
			{$areas.body}
			{$areas.main_body_2}
			{$areas.main_body_3}
		</div>
	</section>
	<section>
		<div id="news-articles" class="container">
			<div class="news">
				{if $areas.main_body_4}
					{$areas.main_body_4}
				{else}
				<h2 class="center">Новости</h2>
				<div class="anons clear">
					<div class="anons-data">
						<div class="number">01</div>
						<div class="month">мар.</div>
					</div>	
					<div class="anons-title">
						<a href="#">Открылся новый магазин в Тверской области.</a>
					</div>	
				</div>
				<div class="anons clear">
					<div class="anons-data">
						<div class="number">05</div>
						<div class="month">мар.</div>
					</div>	
					<div class="anons-title">
						<a href="#">Открылся новый магазин в Тверской области.</a>
					</div>	
				</div>
				<div class="anons clear">
					<div class="anons-data">
						<div class="number">07</div>
						<div class="month">мар.</div>
					</div>	
					<div class="anons-title">
						<a href="#">Открылся новый магазин в Тверской области.</a>
					</div>	
				</div>
				<div class="anons clear">
					<div class="anons-data">
						<div class="number">09</div>
						<div class="month">мар.</div>
					</div>	
					<div class="anons-title">
						<a href="#">На трассе Моска-Рига Балтия в Тверской области открылся новый магазин.</a>
					</div>	
				</div>
				<div class="anons clear">
					<div class="anons-data">
						<div class="number">16</div>
						<div class="month">мар.</div>
					</div>	
					<div class="anons-title">
						<a href="#">Открылся новый магазин в Тверской области.</a>
					</div>	
				</div>
				{/if}
			</div>
			<div class="articles">
				{if $areas.main_body_5}
				{$areas.main_body_5}
				{else}
				<div>
					<div class="articles__nav">
						<div class="articles__next"></div>
						<div class="articles__prev"></div>
					</div>
					<h2 class="center">Статьи</h2>
				</div>
				<ul class="mp-articles">
					<li>
						<div class="main-page-article">
							<img src="/common/img/profsystem_style/article1.jpg">
							<h3 class="main-page-article__article-title">Что нужно знать при выборе металлочерепицы и как не ошибиться?</h3>
							<p>Приемлемая стоимость металлочерепицы, легкость монтажа и функциональность часто являются самыми главными критериями при выборе <a href="#">Подробнее</a></p>
						</div>
					</li>
					<li>
						<div class="main-page-article">
							<img src="/common/img/profsystem_style/slider/slide2.jpg">
							<h3 class="main-page-article__article-title">Статья 2</h3>
							<p>Приемлемая стоимость металлочерепицы, легкость монтажа и функциональность часто являются самыми главными критериями при выборе <a href="#">Подробнее</a></p>
						</div>
					</li>
					<li>
						<div class="main-page-article">
							<img src="/common/img/profsystem_style/slider/slide3.jpg">
							<h3 class="main-page-article__article-title">Статья 3</h3>
							<p>Приемлемая стоимость металлочерепицы, легкость монтажа и функциональность часто являются самыми главными критериями при выборе <a href="#">Подробнее</a></p>
						</div>
					</li>
				</ul>
				{/if}
			</div>
		</div>
		<div class="news-articles-links container">
			<div class="all-news"><a class="baselink" href="/ru/news/">Все новости</a></div>
			<div class="all-articles"><a class="baselink" href="/ru/articles/">Все статьи</a></div>
		</div>
		<div class="clear"></div>
	</section>
	<footer>
		{if $areas.bottom_menu}
			{$areas.bottom_menu}
		{else}
			<div id="bottom-menu">
				<div class="container-foother">
						<ul class="bottom-menu">
							<li>
								<a href="#">О производстве</a>
							</li>
							<li>
								<a href="#">Каталог</a>
							</li>
							<li>
								<a href="#">Доставка и оплата</a>
							</li>
							<li>
								<a href="#">Новости</a>
							</li>
							<li>
								<a href="#">Статьи</a>
							</li>
							<li>
								<a href="#">О компании</a>
							</li>
							<li>
								<a href="#">Контакты</a>
							</li>
						</ul>
				</div>
			</div>
		{/if}
		<div class="clear"></div>
		<div id="foother">
			<div class="container-foother">
				<div class="foother">
					<div style="display: inline-block;">
						<div>
							<div class="contacts">
								<div class="contacts__text_c-w">Профессиональная компания &laquo;ProfSystem&raquo;</div>
							</div>
							<div class="contacts">
								<div class="contacts__icon-social-vk"></div>
							</div>
							<div class="contacts">
								<div class="contacts__icon-social-fb"></div>
							</div>
							<div class="contacts">
								<div class="contacts__icon-social-ok"></div>
							</div>
						</div>
						<div>
							<div class="contacts">
								<div class="contacts__text_c-w">&#9400; 2017 Все права защищены</div>
							</div>
						</div>
					</div>

					<div style="display: inline-block; vertical-align: top; width: 290px;">
						<div>
							<div class="contacts">
								<div class="contacts__icon-address"></div>
								<div class="contacts__text_c-w">Тверь, ул.Паши Савельевой, офис 506</div>
							</div>
						</div>
						<div>
							<div class="contacts">
								<div class="contacts__icon-email"></div>
								<div class="contacts__text_c-w">email@gmail.com</div>
							</div>
						</div>
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
						<div style="height: 35px;">
							<div class="contacts right">
								<div class="contacts__icon-phone-b"></div>
								<div class="contacts__text_c-w-fs16">8(900) 800-80-80</div>
							</div>
						</div>
						<div>
							<div class="contacts right">
								<div class="contacts__call_big">ЗАКАЗАТЬ ЗВОНОК</div>
							</div>
						</div>

					</div>
				</div>
			</div>
		</div>
	</footer>
	<script type="text/javascript" src="/common/js/common.js"></script>
</body>
</html>