<ul class="l-side-menu">
	{foreach from=$menu_array item=item}
		<li>
			<a href="{$item.URL}"{if $item.IS_BLANK} target="_blank"{/if}>
				<div class="left-side-menu">
					{$item.TITLE}
				</div>
			</a>
			{if $item.CURRENT || $item.SELECTED}
				{foreach from=$item.CHILDREN item=item}
					<div style="margin-left: 20px">
						<a href="{$item.URL}"{if $item.IS_BLANK} target="_blank"{/if}>{$item.TITLE}</a>
						{if $item.CURRENT || $item.SELECTED}
							{foreach from=$item.CHILDREN item=item}
								<div style="margin-left: 20px">
									<a href="{$item.URL}"{if $item.IS_BLANK} target="_blank"{/if}>{$item.TITLE}</a>
								</div>
							{/foreach}
						{/if}
					</div>
				{/foreach}
			{/if}
		</li>
	{/foreach}
</ul>