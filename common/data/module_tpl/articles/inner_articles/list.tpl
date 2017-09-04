<div class="inner_articles">
	{foreach from=$content item=article}
		<div class="article">
			<div class="article_img">
				<img src="{$article.IMG}">
			</div>
			<div class="article_text">
				<div class="article_title">{$article.TITLE}</div>
				<div class="article_announce">{$article.ANNOUNCE} <a href="{$article.URL}">Подробнее</a></div>
			</div>
		</div>
	{/foreach}
	{if $navigation}
		<div class="sub-links navigation">{$navigation}</div>
	{/if}
</div>