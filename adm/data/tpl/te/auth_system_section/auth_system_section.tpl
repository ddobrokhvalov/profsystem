<h2 style="background: url(/common/adm/img/tabs/icons/{$icon}) no-repeat 0px 0px; padding: 10px 0px 14px 40px; margin: 11px 0px 8px 4px;">{$title}</h2>

<table style="width: 100%;">
	<tr>
{foreach from=$section_columns item=column name=column}
		<td align="left" valign="top" style="padding: 0px 10px 0px 29px; width: {$column_width}%{if !$smarty.foreach.column.last};{/if}">
{foreach from=$column.COLUMN item=row name=row}
			{if $row.TREE_DEEP == 0}<div style="margin: 0px 0px 8px 0px;" class="title1"><h2>{/if}
{section name=offset start=0 loop=$row.TREE_DEEP}
				<div style="background: url(/common/adm/img/bul-all.gif) no-repeat 1px 5px; padding: 2px 0px 6px 17px; font-weight: bold; font-family: Arial, Helvetica, sans-serif; color: #0465a4;{if $row.LAST_NODE} margin: 0px 0px 22px 12px;{else} margin: 0px 0px 0px 12px; border-bottom: 1px solid #e2e5e4;{/if}">
{/section}
    {if $row.URL}
    	<a href="{$row.URL}">{$row.TITLE|escape}</a>
    {else}
    	{$row.TITLE|escape}
    {/if}
{section name=offset start=0 loop=$row.TREE_DEEP}
				</div>
{/section}
			{if $row.TREE_DEEP == 0}</h2></div>{/if}
{/foreach}
		</td>
{/foreach}
	</tr>
</table>
