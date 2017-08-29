{if $site_list}
<br>
<br>
{$sysw_subscription_for_refusal} {foreach from=$site_list item=site_item name=site_list}{if !$smarty.foreach.site_list.first}{$sysw_subscription_or} {/if}{$sysw_subscription_site} <a href="{$site_item.URL}">{$site_item.HOST}</a> {/foreach}
{/if}
