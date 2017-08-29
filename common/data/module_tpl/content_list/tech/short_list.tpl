{foreach from=$content item=item}

{if $smarty.foreach.item.first}
    {if $item.ID}
        
    {else}
     <ul>   
    {/if}
{/if}
{if $item.ID}<div class="list"><span class="num">{$item.ID}.</span> {else}<li>{/if}<a href="{$item.URL}">{$item.TITLE}</a>
{if $item.TAG_LIST}
<div>
{foreach from=$item.TAG_LIST item=tag_item}
	<a href="{$tag_item.URL}">{$tag_item.TITLE}</a>
{/foreach}
</div>
{/if}
{if $item.ID}</div>{else}</li>{/if}
{if $smarty.foreach.item.last}
    {if $item.ID}
        
    {else}
    </ul>    
    {/if}
{/if}

{/foreach}

{if $navigation}
<br><br>{$navigation}
{/if}

{if $print_url}
<div class="sub-links"><a href="{$print_url}" target="_blank">{$sysw_printver}</a></div>
{/if}
