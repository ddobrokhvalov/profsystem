{if $archives_form}
{$archives_form}
{/if}

{foreach from=$content item=item}
<h2>{$item.NEWS_DATE}. <a href="{$item.URL}">{$item.TITLE}</a></h2>
{if $item.ANNOUNCE}
{$item.ANNOUNCE}<br>
{/if}
<a href="{$item.URL}">{$sysw_news_more}</a><br>
{if $item.TAG_LIST}
<div>
{foreach from=$item.TAG_LIST item=tag_item}
	<a href="{$tag_item.URL}">{$tag_item.TITLE}</a>
{/foreach}
</div>
{/if}
{foreachelse}
<b>{$sysw_news_nonews}</b>
{/foreach}

{if $navigation}
<div class="sub-links">{$navigation}</div>
{/if}

{if $arch_url}
<div class="sub-links"><a href="{$arch_url}">{$sysw_arch_news}</a></div>
{/if}

{if $rss_url}
<div class="sub-links"><a href="{$rss_url}"><img src="/common/img/rss_icon.gif" alt="rss"></a></div>
{/if}

{if $print_url}
<div class="sub-links"><a href="{$print_url}" target="_blank">{$sysw_printver}</a></div>
{/if}
