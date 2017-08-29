<h2 style="margin: 10px 0px 11px 0px;">{$test_name}</h2>

{if ($report_html!='')}

	{$report_html}
    
	{if ($global_fix_link)}
		<div style="margin: 8px 0px 15px 0px; clear: both;">
        
<span class="left-black"><span class="right-black"><span class="button-black"><a href="{$global_fix_link}" target="{$iframe_name_for_fix}" {if ($global_fix_confirm)}onClick="return confirm('{$global_fix_confirm}')"{/if}>{$lang_autotest_fix}</a></span></span></span>
        <div style="clear: both; font-size: 0px;"> </div>

        </div>
	{/if}
{else}
    <div class="noerrors">{$lang_autotest_no_error}</div>
{/if}
