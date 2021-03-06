<h1>{$content_item.TITLE}</h1>
{literal}
	<style type="text/css">
		.select-view{
			display: none;
		}
	</style>
{/literal}
{if $content_item}
		<div class="share42init" data-url="" data-title="{$content_item.TITLE}" data-image="http://{$smarty.server.HTTP_HOST}{$content_item.MAIN_IMG}"></div>
	{literal}
		<script type="text/javascript" src="/common/js/share42.js"></script>
	{/literal}
	<div class="card">
<a id="fancy_image" href="{$content_item.MAIN_IMG}">
		<div class="card-img-b" style="background-image: url({$content_item.MAIN_IMG});">
			{*<img src="{$content_item.MAIN_IMG}">*}
		</div>
</a>
		<div class="card-img-container">
			<ul class="card-img-container-u">
				<li class="card-img-next"></li>
				<li class="card-img-prev"></li>
			</ul>
			<div class="card-img">
				<div class="card-img-s">
					<img src="{$content_item.MAIN_IMG}">
				</div>
				{foreach from=$content_item.content_images item=content_image}
					<div class="card-img-s">
						<img src="{$content_image.IMG}" title="{$content_image.META_TITLE}" alt="{$content_image.META_ALT}">
					</div>
				{/foreach}
			</div>
		</div>
	</div>
	<div class="clear"></div>
	{if $content_item.content_prices}
        {if !$view_param.show_price || $view_param.show_price == 'Y'}
	<div class="size-and-price">
		<div class="size-and-price-h">
			Размеры и цены
		</div>
		{foreach from=$content_item.content_prices item=content_price}
			<div class="size-and-price-item">
				{$content_price.PREFIX} - от {$content_price.PRICE} руб.
			</div>
		{/foreach}
	</div>
	{/if}
        {/if}
	<div class="clear"></div>
	<div class="rating"  style="padding-top: 40px;padding-bottom: 20px;">
		<div class="card-rating rating_{$content_item.CATALOG_ITEMS_ID} {if !$content_item.ALREADY_VOTE}enabled_votes{/if}">
			<div class="card-rating__star {if $content_item.RATING.AVG_RATING >= 0.6}on{/if}" data_te_obj="CATALOG_ITEMS" data_id="{$content_item.CATALOG_ITEMS_ID}" rating="1"></div>	
			<div class="card-rating__star {if $content_item.RATING.AVG_RATING >= 1.6}on{/if}" data_te_obj="CATALOG_ITEMS" data_id="{$content_item.CATALOG_ITEMS_ID}" rating="2"></div>	
			<div class="card-rating__star {if $content_item.RATING.AVG_RATING >= 2.6}on{/if}" data_te_obj="CATALOG_ITEMS" data_id="{$content_item.CATALOG_ITEMS_ID}" rating="3"></div>	
			<div class="card-rating__star {if $content_item.RATING.AVG_RATING >= 3.6}on{/if}" data_te_obj="CATALOG_ITEMS" data_id="{$content_item.CATALOG_ITEMS_ID}" rating="4"></div>	
			<div class="card-rating__star {if $content_item.RATING.AVG_RATING >= 4.6}on{/if}" data_te_obj="CATALOG_ITEMS" data_id="{$content_item.CATALOG_ITEMS_ID}" rating="5"></div>	
		</div>
	</div>
	<div class="price">
		<div class="card-price">
{if !$view_param.show_price || $view_param.show_price == 'Y'}
			<div class="card-price__normal">
				<p>от {$content_item.PRICE}</p>
			</div>
{/if}
			{*<div class="card-price__old">
				<p>60680</p>
			</div>
			<div class="card-price__new">
				<p>55900</p>
			</div>*}
		</div>
		<div class="clear"></div>
		<div class="card-price-about">
			{if !$view_param.show_price || $view_param.show_price == 'Y'}Более точную информацию {else}Информацию {/if}о цене Вы можете узнать по телефону +7 (919) 061-44-80 или по электронной почте <a href="mailto:Prof.Systema@mail.ru"><span class='mail_to'>Prof.Systema@mail.ru</span></a>
		</div>
		<div class="card-description">
			<div class="size-and-price-h">Описание товара:</div>
			<p>{$content_item.BODY}</p>
		</div>
	</div>
{else}
{/if}