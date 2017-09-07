<div class="items">
	{foreach from=$content item=item key=key}
		<div class="item">
			<div class="item-img">
				<img src="{$item.MAIN_IMG}">
				<a href='{$item.URL}' class="item-preview"></a>
			</div>
			<div class="item-type">{$item.TYPE}</div>
			<div class="item-name">{$item.TITLE}</div>
			<div class="item-rating rating_{$item.CATALOG_ITEMS_ID} {if !$item.ALREADY_VOTE}enabled_votes{/if}">
				<div class="item-rating__star {if $item.RATING.AVG_RATING >= 0.6}on{/if}" data_te_obj="CATALOG_ITEMS" data_id="{$item.CATALOG_ITEMS_ID}" rating="1"></div>	
				<div class="item-rating__star {if $item.RATING.AVG_RATING >= 1.6}on{/if}" data_te_obj="CATALOG_ITEMS" data_id="{$item.CATALOG_ITEMS_ID}" rating="2"></div>	
				<div class="item-rating__star {if $item.RATING.AVG_RATING >= 2.6}on{/if}" data_te_obj="CATALOG_ITEMS" data_id="{$item.CATALOG_ITEMS_ID}" rating="3"></div>	
				<div class="item-rating__star {if $item.RATING.AVG_RATING >= 3.6}on{/if}" data_te_obj="CATALOG_ITEMS" data_id="{$item.CATALOG_ITEMS_ID}" rating="4"></div>	
				<div class="item-rating__star {if $item.RATING.AVG_RATING >= 4.6}on{/if}" data_te_obj="CATALOG_ITEMS" data_id="{$item.CATALOG_ITEMS_ID}" rating="5"></div>	
			</div>
			<div class="item-price">
				<span class="normal">{$item.PRICE}</span>
			</div>
		</div>
	{/foreach}
</div>
<div class="clear"></div>
<div class="items-all">
	<a href="/{$env.lang_root_dir}/catalog/" class="items-all">Все товары</a>
</div>