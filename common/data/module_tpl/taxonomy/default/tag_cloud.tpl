<div style="text-align: center">
{foreach from=$tag_list item=tag_item}
{if $tag_item.URL}
	<a href="{$tag_item.URL}" style="white-space: nowrap; font-size: {$tag_item.FONT_SIZE}px">{$tag_item.TITLE}</a>
{else}
	<span style="white-space: nowrap; font-size: {$tag_item.FONT_SIZE}px">{$tag_item.TITLE}</span>
{/if}
{/foreach}
</div>
