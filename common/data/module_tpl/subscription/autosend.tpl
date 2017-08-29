{foreach from=$NEWS item=item}
<h2>{$item.NEWS_DATE}. {$item.TITLE}</h2>
{if $item.ANNOUNCE}
{$item.ANNOUNCE}<br>
{/if}
<a href="{$item.URL}">{$sysw_news_more}</a><br>
{/foreach}

{foreach from=$CONTENT_LIST item=item}
<h2>{$item.TITLE}</h2>
{if $item.BODY}
{$item.BODY}<br>
{/if}
{/foreach}
