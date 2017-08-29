{if $content}
<table>
{foreach from=$content item=row}
<tr>
	{foreach from=$row item=item}
	<td>
		<table>
		<tr>
			<td class="foto_img">
				<a target="_blank" href="{$item.URL}" onclick="popupWin = window.open(this.href,this.target, 'location=no,toolbar=no,menubar=no,status=no,resizable=yes,scrollbars=yes,left=50,top=50'); popupWin.focus(); return false;">
					<img src="{$item.IMG}" border="0" alt="" />
				</a>
			</td>
		</tr>
		<tr>
			<td align="center">
				<a target="_blank" href="{$item.URL}" onclick="popupWin = window.open(this.href,this.target, 'location=no,toolbar=no,menubar=no,status=no,resizable=yes,scrollbars=yes,left=50,top=50'); popupWin.focus(); return false;">{$item.TITLE}</a>
			</td>
		</tr>
		</table>
	</td>
	{/foreach}
</tr>
{/foreach}
</table>
{/if}

{if $navigation}
<div class="pager">{$navigation}</div>
{/if}