<div class="inner_news">
	{foreach from=$content item=item}
		<div class="anons clear">
			<div class="anons-data">
				{$item.NEWS_DATE}
			</div>	
			<div class="anons-title">
				<a href="{$item.URL}">{$item.TITLE}</a>
			</div>	
		</div>
	{foreachelse}
	<b>{$sysw_news_nonews}</b>
	{/foreach}
</div>
{if $navigation}
	<div class="sub-links">{$navigation}</div>
{/if}