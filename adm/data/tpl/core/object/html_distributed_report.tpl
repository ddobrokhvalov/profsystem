{if ($status.change_report_on)}
{$status.change_report_on}
{else}
<div class="spacer" style="width: 0px; height: 8px;"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div>
{if $status.complete_message && !$error_log_url}
<div class="errors-t"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div>
    <div class="errors-box">
       	{$status.complete_message}
    </div>
{else}
<div class="errors-t"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div>
    <div class="errors-box">
        {if !$status.complete_message}
        	<div><div class="img"><img src="/common/adm/img/messages/success.gif" alt="" style="margin-right: 2px"></div>{$status.success_message}: {$success_count}</div>
        {/if}
        {if $error_log_url}
        	<div style="padding: 5px 0px 5px 24px;">{$lang_about_errors_info} <a href="{$error_log_url}">{$lang_about_errors_here}</a></div>
        {foreach from=$report item=item}
            {if $full_report || $item.error}
            <div>
                <div class="img">{if $item.error == 'error'}<img src="/common/adm/img/messages/error.gif" alt="">{elseif $item.error == 'warning'}<img src="/common/adm/img/messages/warning.gif" alt="">{else}<img src="/common/adm/img/messages/success.gif" alt="" style="margin-right: 2px">{/if}</div>{$item.message}
            </div>
            {/if}
        {/foreach}
        {/if}
    </div>
{/if}
<div class="errors-b" style="font-size: 0px;"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div>
<div class="spacer" style="width: 0px; height: 5px;"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div>
<div><a href="{$status.back_url}">{$lang_back}</a><br/></div>
{/if}
