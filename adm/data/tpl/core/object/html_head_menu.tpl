<table border="0" cellspacing="0" cellpadding="0">
	<tr>
{foreach from=$head_menu item=item}
		<td>
			<div class="bmark-bg{if $item.SELECTED}-s{/if}" onmousedown="redirect( event, {ldelim} 'url': '{$item.URL}' {rdelim} )">
				<div class="bmark">
					<div title="{$item.TITLE}">
						<a href="{$item.URL}" onclick="return false">{$item.TITLE}</a>
					</div>
				</div>
			</div>
		</td>
{/foreach}
	</tr>
</table>
