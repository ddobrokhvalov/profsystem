<a name="top"></a>
{foreach from=$content item=item}

{if $smarty.foreach.item.first}
    {if $item.ID}
        
    {else}
     <ul>   
    {/if}
{/if}
{if $item.ID}<div class="list"><span class="num">{$item.ID}.</span> {else}<li>{/if}<a href="#{$item.CONTENT_LIST_ID}">{$item.TITLE}</a>{if $item.ID}</div>{else}</li>{/if}
{if $smarty.foreach.item.last}
    {if $item.ID}
        
    {else}
    </ul>    
    {/if}
{/if}


{/foreach}

{foreach from=$content item=item}
<a name="{$item.CONTENT_LIST_ID}"></a><h2>{if $item.ID}{$item.ID}. {/if}{$item.TITLE}</h2>
{$item.BODY}

{if $item.TAG_LIST}
<div>
{foreach from=$item.TAG_LIST item=tag_item}
	<a href="{$tag_item.URL}">{$tag_item.TITLE}</a>
{/foreach}
</div>
{/if}

<br><a href="#top">{$sysw_top_list}</a>

{/foreach}

{if $print_url}
<div class="sub-links"><a href="{$print_url}" target="_blank">{$sysw_printver}</a></div>
{/if}
