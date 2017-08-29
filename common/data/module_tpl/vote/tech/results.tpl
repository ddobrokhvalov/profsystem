{if $TITLE}
<h3>{$TITLE}</h3>
{$BODY}
<br><br>

<b>{$sysw_vote_voted}:</b> {$ANSWER_SUM}<br><br>
{if $ANSWER_LIST}
<table cellpadding="3" cellspacing="0">
{foreach from=$ANSWER_LIST item=item name=fields}
	<tr>
		<td>
			<b>{$item.TITLE}</b>
		</td>
		<td>
			{$item.ANSWER_COUNT} ({$item.PERCENT} %)
		</td>
		<td>
			<div style="width: {$item.WIDTH}px; height: 10px; background-color: #cdf1cd;">&nbsp;</div>
		</td>
	</tr>
{/foreach}
</table>
{else}
<b>{$sysw_vote_no_answers}</b>
{/if}
{else}
<b>{$sysw_vote_not_available}</b>
{/if}

{if $archives_link}
<div class="sub-links"><a href="{$archives_link}">{$sysw_vote_archives}</a></div>
{/if}