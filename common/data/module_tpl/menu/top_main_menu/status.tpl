<div class="breadcrambs">
{foreach from=$ITEMS item="LEVEL1" name="LEVEL1"}
	{if !$smarty.foreach.LEVEL1.last && $LEVEL1.URL}
		<a href="{$LEVEL1.URL}">{$LEVEL1.TITLE}</a> /
	{else}
		{if $smarty.get.id_4}
			<a href="{$LEVEL1.URL}">{$LEVEL1.TITLE}</a> /
		{else}
			<span>{$LEVEL1.TITLE}</span> /
		{/if}
	{/if}
{/foreach}
</div>