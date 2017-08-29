<form action="" method="get" name="tag_form">
	<table>
		<tr>
			<td>
				{$sysw_tag_search_title}
			</td>
			<td>
				<input type="text" name="search_{$env.area_id}" value="{$search}">
			</td>
		</tr>
		<tr>
			<td>
				{$sysw_tag_search_module}
			</td>
			<td>
				<select name="view_module_{$env.area_id}">
{foreach from=$view_module_list item=view_module_item}
					<option value="{$view_module_item.VALUE}"{if $view_module_item.selected} selected="selected"{/if}>{$view_module_item.TITLE}</option>
{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="hidden" name="view_mode_{$env.area_id}" value="tag_search">
				<input type="submit" value="{$sysw_search}" class="button">
			</td>
		</tr>
	</table>
</form>

<hr>

{foreach from=$anchor_list key=anchor_key item=anchor_item}
	<a href="#{$anchor_key}">{$anchor_item}</a>
{/foreach}
	<br>
{foreach from=$result_list key=result_key item=result_item}
	<a id="{$result_key}" name="{$result_key}"></a>
	
	<b>{$result_item.module_title} ({$result_item.result_count})</b>

	{$result_item.result}

{if $result_item.navigation}
	{$result_item.navigation} 
{/if}
{if $result_item.search_url}
	<a href="{$result_item.search_url}">{$sysw_tag_search_more}</a>
{/if}
	<br>
{/foreach}
