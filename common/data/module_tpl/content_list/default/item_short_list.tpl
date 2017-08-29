<h2>{$TITLE}</h2>
{$BODY}

{if $TAG_LIST}
<div>
{foreach from=$TAG_LIST item=tag_item}
	<a href="{$tag_item.URL}">{$tag_item.TITLE}</a>
{/foreach}
</div>
{/if}

<div class="sub-links">
{if $back_url}
<a href="{$back_url}">{$sysw_back_list}</a>
{/if}

{if $print_url}
<br><a href="{$print_url}" target="_blank">{$sysw_printver}</a>
{/if}
</div>