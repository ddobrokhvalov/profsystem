{* <ul id="main-menu">
{foreach from=$ITEMS item="LEVEL1" name="LEVEL1"}
	<li{if $LEVEL1.SELECTED} class="selected"{/if}>
	{if $smarty.foreach.LEVEL1.first}{/if}
	<a href="{$LEVEL1.URL}">{$LEVEL1.TITLE}</a>

{/foreach}
</ul> *}

<div class="items" style="margin-left: -20px;margin-right: -20px;">
	{foreach from=$ITEMS item="LEVEL1" name="LEVEL1"}
		<div class="item">
			<div class="item-img">
				<img src="{$LEVEL1.PAGE_IMG}">
				<a href='{$LEVEL1.URL}' class="item-preview"></a>
			</div>
			<div class="item-type">{$LEVEL1.TITLE}</div>
			<div class="item-name"></div>
			<div class="item-rating rating_1">
				
			</div>
			<div class="item-price">
				
			</div>
		</div>
	{/foreach}
</div>