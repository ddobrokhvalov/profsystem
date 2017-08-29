{foreach from=$content item=item}
<h3>{$item.TITLE}</h3>
{$item.BODY}<br>
{$item.START_DATE} - {$item.END_DATE}<br>
<a href="{$item.URL}">{$sysw_vote_results}</a><br>
{foreachelse}
<b>{$sysw_vote_empty_archives}</b>
{/foreach}

{if $navigation}
<br><br>{$navigation}
{/if}