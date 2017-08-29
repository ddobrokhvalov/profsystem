<div id="slider" class="slider">
	<div class="flexslider">
		<ul class="slides">
			{foreach from=$content item=item}
				<li>
					<img src="{$item.IMG}" />
					<div class="flex-caption">
						<h2>{$item.TITLE}</h2>
						<p>{$item.BODY}</p>
						<div></div>
						<a href="{$item.LINK}"><div class="btn">Узнать подробнее</div></a>
					</div>
				</li>
			{/foreach}
		</ul>
	</div>
</div>