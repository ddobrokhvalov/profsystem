<!--UdmComment-->
{if $first_url}<a href="{$first_url}">&lt;&lt;</a>{else}&lt;&lt;{/if}&nbsp;
{if $prev_url}<a href="{$prev_url}">&lt;</a>{else}&lt;{/if}

{foreach from=$links item=link name=link}
	{if $link.url}<a href="{$link.url}">{/if}{$link.num}{if $link.url}</a>{/if}
{/foreach}

{if $next_url}<a href="{$next_url}">&gt;</a>{else}&gt;{/if}&nbsp;
{if $last_url}<a href="{$last_url}">&gt;&gt;</a>{else}&gt;&gt;{/if}
<!--/UdmComment-->