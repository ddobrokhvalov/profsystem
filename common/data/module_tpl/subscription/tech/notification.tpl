<br>
<b>{$sysw_sub_msg13}</b><br>
<br>
{$sysw_sub_msg14} -<br>
{$sysw_client_email}: {$EMAIL}<br>
{$SYSW_SUB_FIO}: {$FIO}<br>
{if $ORGANIZATION}{$sysw_sub_org}: {$ORGANIZATION}<br>{/if}
<br>
{if $SUBSCRIBE_LIST}
{$sysw_sub_msg5} -<br>
{foreach from=$SUBSCRIBE_LIST item=SUBSCRIBE_ITEM}
 * {$SUBSCRIBE_ITEM.LIST_NAME}<br>
{/foreach}
{else}
{$sysw_sub_msg6}<br>
{/if}
<br>
<p>{$sysw_sub_msg15} <br><a href="{$URL_SUBMIT}">{$URL_SUBMIT}</a>.
