<div class="spacer" style="width: 0px; height: 8px;"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div>
<div class="errors-t"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div>
<div class="errors-box">
	<div><div class="img"><img src="/common/adm/img/messages/error.gif" alt=""></div>{$lang_user_error_message}:</div>
    <div style="padding: 5px 0px 5px 24px;">{$msg}</div>
{if $file}
    <div style="padding: 5px 0px 5px 24px;">{$lang_file}: {$file}</div>
{/if}
{if $line}
    <div style="padding: 5px 0px 5px 24px;">{$lang_string}: {$line}</div>
{/if}
{if $debug}
    <div style="padding: 5px 0px 5px 24px;">{$lang_debug_info}: {$debug}</div>
{/if}
{if $trace}
    <div style="padding: 5px 0px 5px 24px;">{$lang_backtrace}: {$trace}</div>
{/if}
</div>
<div class="errors-b"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div>
<div class="spacer" style="width: 0px; height: 5px;"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div>
{if $back_url}
<div><a href="{$back_url}">{$lang_back}</a></div>
{/if}

