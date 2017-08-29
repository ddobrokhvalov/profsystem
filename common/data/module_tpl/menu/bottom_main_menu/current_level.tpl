{foreach from=$ITEMS item="LEVEL1" name="LEVEL1"}
	<a href="{$LEVEL1.URL}">{if $LEVEL1.SELECTED}<b>{/if}{$LEVEL1.TITLE}{if $LEVEL1.SELECTED}</b>{/if}</a>
	{if !$smarty.foreach.LEVEL1.last}&nbsp;&nbsp;{/if}
{/foreach}