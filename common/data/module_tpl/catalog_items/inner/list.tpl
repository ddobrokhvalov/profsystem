<div class="items" style="margin-left: -20px;margin-right: -20px;">
	{foreach from=$content item=item key=key}
		<div class="item">
			<div class="item-img">
				<img src="{$item.MAIN_IMG}">
				<a href='{$item.URL}' class="item-preview"></a>
			</div>
			<div class="item-type">{$item.TYPE}</div>
			<div class="item-name">{$item.TITLE}</div>
			<div class="item-rating rating_{$item.CATALOG_ITEMS_ID}">
				<div class="item-rating__star on" data_id="{$item.CATALOG_ITEMS_ID}" rating="1"></div>	
				<div class="item-rating__star on" data_id="{$item.CATALOG_ITEMS_ID}" rating="2"></div>	
				<div class="item-rating__star on" data_id="{$item.CATALOG_ITEMS_ID}" rating="3"></div>	
				<div class="item-rating__star" data_id="{$item.CATALOG_ITEMS_ID}" rating="4"></div>	
				<div class="item-rating__star" data_id="{$item.CATALOG_ITEMS_ID}" rating="5"></div>	
			</div>
			<div class="item-price">
				<span class="normal">{$item.PRICE}</span>
			</div>
		</div>
	{/foreach}
</div>
<div class="clear"></div>