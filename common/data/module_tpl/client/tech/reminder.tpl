{if $is_reminded}
<b>{$sysw_client_msg2}</b><br>
{elseif $is_updated}
<b>{$sysw_client_msg6}</b><br>
{else}
{if $errors}
<div class="error">
	{$sysw_client_caution}<br>
{foreach from=$errors item=error}
	{$error}<br>
{/foreach}
	{$sysw_client_again}
</div>
{/if}
<script type="text/javascript" src="/common/js/core/translate.js.php?lang={$env.lang_root_dir}"></script>
<script type="text/javascript" src="/common/js/core/check_form.js"></script>
{if $is_change_form}
<form action="" method="post" onsubmit="return CheckForm.validate( this )">
	<table border="1">
		<tr>
			<td colspan="2">
				<b>{$sysw_client_reminder}</b>
			</td>
		</tr>
		<tr>
			<td>
				{$sysw_client_password} <span style="color: red">*</span>:
			</td>
			<td>
				<input type="password" name="PASSWORD_{$env.area_id}" value="" lang="errors_nonempty_">
			</td>
		</tr>
		<tr>
			<td>
				{$sysw_client_password2} <span style="color: red">*</span>:
			</td>
			<td>
				<input type="password" name="PASSWORD2_{$env.area_id}" value="" lang="errors_nonempty_">
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="submit" value="{$sysw_client_save}">
			</td>
		</tr>
	</table>
</form>
{else}
<form method="post" onsubmit="return CheckForm.validate( this )">
	<table border="1">
		<tr>
			<td colspan="2">
				<b>{$sysw_client_reminder}</b>
			</td>
		</tr>
		<tr>
			<td>
				{$sysw_client_email} <span class="red">*</span>:
			</td>
			<td>
				<input type="text" name="EMAIL_{$env.area_id}" value="" lang="errors_nonempty__email_">
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="submit" class="button" value="{$sysw_client_continue}">
			</td>
		</tr>
	</table>
</form>
{/if}
{/if}