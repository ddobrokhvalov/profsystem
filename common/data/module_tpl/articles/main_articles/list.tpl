<div>
	<div class="articles__nav">
		<div class="articles__next"></div>
		<div class="articles__prev"></div>
	</div>
	<h2 class="center">Статьи</h2>
</div>
<ul class="mp-articles">
	{foreach from=$content item=article key=key}
	<li>
		<div class="main-page-article">
			<img src="{$article.IMG}">
			<h3 class="main-page-article__article-title">{$article.TITLE}</h3>
			<p>{$article.ANNOUNCE} <a href="{$article.URL}">Подробнее</a></p>
		</div>
	</li>
	{/foreach}
	
</ul>