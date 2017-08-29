{foreach from=$content item=item}
<h2>{if $item.ID}{$item.ID}. {/if}{$item.TITLE}</h2>
{$item.BODY}

{if $item.TAG_LIST}
<div>
{foreach from=$item.TAG_LIST item=tag_item}
	<a href="{$tag_item.URL}">{$tag_item.TITLE}</a>
{/foreach}
</div>
{/if}{/foreach}

{if $navigation}
<br><br>{$navigation}
{/if}

{if $print_url}
<div class="sub-links"><a href="{$print_url}" target="_blank">{$sysw_printver}</a></div>
{/if}
