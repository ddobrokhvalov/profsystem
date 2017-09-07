<!--UdmComment-->
<div class="pager">
	<div class="pager-items">
		{if $first_url}<a href="{$first_url}"><div class="pager-items__item_f">&lt;&lt;</div></a>{else}<div class="pager-items__item_f">&lt;&lt;</div>{/if}&nbsp;
		{if $prev_url}<a href="{$prev_url}"><div class="pager-items__item_f">&lt;</div></a>{else}<div class="pager-items__item_f">&lt;</div>{/if}

		{foreach from=$links item=link name=link}
			{if $link.url}<a href="{$link.url}">{/if}<div class="pager-items__item_f">{$link.num}</div>{if $link.url}</a>{/if}
		{/foreach}

		{if $next_url}<a href="{$next_url}"><div class="pager-items__item_f">&gt;</div></a>{else}<div class="pager-items__item_f">&gt;</div>{/if}&nbsp;
		{if $last_url}<a href="{$last_url}"><div class="pager-items__item_f">&gt;&gt;</div></a>{else}<div class="pager-items__item_f">&gt;&gt;</div>{/if}
	</div>
</div>
<!--/UdmComment-->