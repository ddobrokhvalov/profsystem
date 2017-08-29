{foreach from=$result_list item=result_item}
<div>
	<b>{if $result_item.URL}<a href="{$result_item.URL}">{$result_item.TITLE}</a>{else}{$result_item.TITLE}{/if}</b><br>
	{$result_item.BODY}
</div>
<br>
{/foreach}
