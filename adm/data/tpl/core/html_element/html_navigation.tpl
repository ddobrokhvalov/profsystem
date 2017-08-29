<div>
    <div class="nav">
    {if $first_url}<img src="/common/adm/img/tabs/table_osn/n-ll-a.gif" width="9" height="7" border="0" alt="" style="margin: 0px 7px 0px 0px;"><a href="{$first_url}"{if $confirm_change} onclick="return FormState.confirmChange()"{/if}>{$lang_navigation_to_begin}</a>{else}{/if}&nbsp;
    {if $prev_url}<img src="/common/adm/img/tabs/table_osn/n-l-a.gif" width="4" height="7" border="0" alt="" style="margin: 0px 7px 0px 0px;"><a href="{$prev_url}"{if $confirm_change} onclick="return FormState.confirmChange()"{/if}>{$lang_navigation_to_prev}</a>{else}{/if}
    
{foreach from=$links item=link name=link}|{if $link.url}<a href="{$link.url}"{if $confirm_change} onclick="return FormState.confirmChange()"{/if}><span>{else}<span class="s">{/if}{$link.num}{if $link.url}</span></a>{else}</span>{/if}{if $smarty.foreach.link.last}|{else}{/if}{/foreach}
    
    {if $next_url}<a href="{$next_url}"{if $confirm_change} onclick="return FormState.confirmChange()"{/if}>{$lang_navigation_to_next}</a><img src="/common/adm/img/tabs/table_osn/n-r-a.gif" width="4" height="7" border="0" alt="" style="margin: 0px 0px 0px 6px;">{else}{/if}&nbsp;
    {if $last_url}<a href="{$last_url}"{if $confirm_change} onclick="return FormState.confirmChange()"{/if}>{$lang_navigation_to_end}</a><img src="/common/adm/img/tabs/table_osn/n-rr-a.gif" width="9" height="7" border="0" alt="" style="margin: 0px 0px 0px 6px;">{else}{/if}
    </div>
</div>
