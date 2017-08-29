{foreach from=$menu_array item=item}
<div>
{if $item.SELECTED || $item.CURRENT}<b>{/if}{if $item.CURRENT}{$item.TITLE}{elseif $item.URL}<a href="{$item.URL}"{if $item.IS_BLANK} target="_blank"{/if}>{$item.TITLE}</a>{else}{$item.TITLE}{/if}{if $item.SELECTED || $item.CURRENT}</b>{/if}

{if $item.CURRENT || $item.SELECTED}
{foreach from=$item.CHILDREN item=item}
	<div style="margin-left: 20px">
{if $item.SELECTED || $item.CURRENT}<b>{/if}{if $item.CURRENT}{$item.TITLE}{elseif $item.URL}<a href="{$item.URL}"{if $item.IS_BLANK} target="_blank"{/if}>{$item.TITLE}</a>{else}{$item.TITLE}{/if}{if $item.SELECTED || $item.CURRENT}</b>{/if}

{if $item.CURRENT || $item.SELECTED}
{foreach from=$item.CHILDREN item=item}
		<div style="margin-left: 20px">
{if $item.SELECTED || $item.CURRENT}<b>{/if}{if $item.CURRENT}{$item.TITLE}{elseif $item.URL}<a href="{$item.URL}"{if $item.IS_BLANK} target="_blank"{/if}>{$item.TITLE}</a>{else}{$item.TITLE}{/if}{if $item.SELECTED || $item.CURRENT}</b>{/if}
		</div>
{/foreach}
{/if}
	</div>
{/foreach}
{/if}
</div>
{/foreach}