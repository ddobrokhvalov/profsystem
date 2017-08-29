{if $TITLE}<h2>{$TITLE}</h2>{/if}
{$BODY}
{if $TAG_LIST}
<div>
{foreach from=$TAG_LIST item=tag_item}
	<a href="{$tag_item.URL}">{$tag_item.TITLE}</a>
{/foreach}
</div>
{/if}

{if $print_url}
<div class="sub-links"><a href="{$print_url}" target="_blank">{$sysw_printver}</a></div>
{/if}
