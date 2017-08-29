<br>
<b>{$sysw_sub_msg1}</b><br>
<br>
{$sysw_sub_msg2} -<br>
{$sysw_client_email}: {$EMAIL}<br>
{$sysw_sub_fio}: {$FIO}<br>
{if $ORGANIZATION}{$sysw_sub_org}: {$ORGANIZATION}<br>{/if}
<br>
{if $SUBSCRIBE_LIST}
{$sysw_sub_subscribe_list} -<br>
{foreach from=$SUBSCRIBE_LIST item=SUBSCRIBE_ITEM}
 * {$SUBSCRIBE_ITEM.LIST_NAME}<br>
{/foreach}
{/if}
<br>
<p>{$sysw_sub_msg3} <br><a href="{$URL_SUBMIT}">{$URL_SUBMIT}</a>.
<p>{$sysw_sub_msg4} <br><a href="{$URL_CHANGE}">{$URL_CHANGE}</a>.
