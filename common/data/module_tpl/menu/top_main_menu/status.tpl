<div class="breadcrambs">
{foreach from=$ITEMS item="LEVEL1" name="LEVEL1"}
	{if !$smarty.foreach.LEVEL1.last && $LEVEL1.URL}<a href="{$LEVEL1.URL}">{else}<span>{/if}{$LEVEL1.TITLE}{if !$smarty.foreach.LEVEL1.last && $LEVEL1.URL}</a> / {else}</span> /{/if}
{/foreach}
</div>