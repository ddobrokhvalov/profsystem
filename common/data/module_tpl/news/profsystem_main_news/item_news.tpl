<h2>{$NEWS_DATE}. {$TITLE}</h2>
{$BODY}
{if $TAG_LIST}
<div>
{foreach from=$TAG_LIST item=tag_item}
	<a href="{$tag_item.URL}">{$tag_item.TITLE}</a>
{/foreach}
</div>
{/if}
<div class="sub-links">
{if !$env.is_print}
{if $back_url}
<a href="{$back_url}">{$sysw_back_news}</a><br>
{/if}

{if $arch_url}
<a href="{$arch_url}">{$sysw_arch_news}</a><br>
{/if}
{/if}
{if $print_url}
<a href="{$print_url}" target="_blank">{$sysw_printver}</a>
{/if}
 
</div>