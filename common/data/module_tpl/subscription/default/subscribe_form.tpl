{if $SUBSCRIBED}
	{$sysw_sub_msg10}
{elseif $CHANGED}
	{$sysw_sub_msg11}
{elseif $CANT_CHANGED}
	{$sysw_sub_msg12}
{elseif $MESSAGES}
	{foreach from=$MESSAGES item=MESSAGE}
		{$MESSAGE.MESSAGE}<br>
	{/foreach}
	<p><a href="./index.php">{$sysw_sub_back}</a>
{else}
<script type="text/javascript" src="/common/js/core/translate.js.php?lang={$env.lang_root_dir}"></script>
<script type="text/javascript" src="/common/js/core/check_form.js"></script>
<form action="./index.php" method="post" name="subscribe" onsubmit="return CheckForm.validate( this )">
<table>
<tr>
	<td>{$sysw_sub_email}<span class="red">*</span></td>
	<td><input type="text" name="email_{$env.area_id}" value="{$EMAIL|escape}" lang="errors_nonempty__email_"></td>
</tr>
<tr>
	<td>{$sysw_sub_fio}<span class="red">*</span></td>
	<td><input type="text" name="fio_{$env.area_id}" value="{$FIO|escape}" lang="errors_nonempty_"></td>
</tr>
<tr>
	<td>{$sysw_sub_org}</td>
	<td><input type="text" name="organization_{$env.area_id}" value="{$ORGANIZATION|escape}"></td>
</tr>
</table>
<div class="grey-line" style="margin: 20px 0 20px 0; width: 30%;"><DIV class="spacer" style="height: 1px;">&nbsp;</DIV></div>
{foreach from=$SUBSCRIBE_LIST item=SUBSCRIBE_ITEM name=SUBSCRIBE_LIST}
	{if $smarty.foreach.SUBSCRIBE_LIST.first}
		<table>
	{/if}
			<tr>
				<td>
					<input type="checkbox" name="list_{$env.area_id}[]" value="{$SUBSCRIBE_ITEM.ID}" {$SUBSCRIBE_ITEM.CHECKED}> {$SUBSCRIBE_ITEM.LIST_NAME}
				</td>
			</tr>
	{if $FORM_FORMAT == 2}
		{if $SUBSCRIBE_ITEM.LIST_DESCRIPTION}
			<tr>
				<td>
					{$SUBSCRIBE_ITEM.LIST_DESCRIPTION}
				</td>
			</tr>
		{/if}
	{/if}
	{if $smarty.foreach.SUBSCRIBE_LIST.last}
		</table>
		<div class="grey-line" style="margin: 20px 0 20px 0; width: 30%;"><DIV class="spacer" style="height: 1px;">&nbsp;</DIV></div>
	{/if}
{/foreach}
<input type="hidden" name="action_{$env.area_id}" value="subscribe">
<input type="submit" value="{$sysw_sub_subscribe}" class="button">
</form>
<div class="sub-links">{$sysw_mandatory}</div>
{/if}
