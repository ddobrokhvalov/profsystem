<script type="text/javascript" src="/common/js/core/translate.js.php?lang={$env.lang_root_dir}"></script>
<script type="text/javascript" src="/common/js/core/check_form.js"></script>
{if $openid_mode}
{literal}
<script type="text/javascript">
	function switch_login_form()
	{
		var login_form = document.getElementById( 'login_form' );
		var openid_form = document.getElementById( 'openid_form' );
		
		if ( !login_form || !openid_form ) return false;
		
		login_form.style.display = login_form.style.display == 'none' ? 'block' : 'none';
		openid_form.style.display = openid_form.style.display == 'none' ? 'block' : 'none';
	}
</script>
{/literal}
{/if}
{if $is_registrated}
{if $surname || $name}
<b>{$surname} {$name}</b><br>
{else if $openid}
<b>{$openid}</b><br>
{/if}
<a href="index.php?exit_{$env.area_id}=1">{$sysw_client_exit}</a><br>
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
{if $openid_mode}
<form id="openid_form" action="" method="post" onsubmit="return CheckForm.validate( this )" style="display: {if $openid_mode && $openid}block{else}none{/if}">
	<input type="hidden" name="from_url_{$env.area_id}" value="{$from_url}">
	<table border="1">
		<tr>
			<td>
				{$sysw_client_openid}*:
			</td>
			<td>
				<input type="text" name="openid_{$env.area_id}" value="{$openid}" lang="errors_nonempty_">
			</td>
		</tr>
		<tr>
			<td>
				&nbsp;
			</td>
			<td>
				<input type="checkbox" id="openid_out_computer" name="out_computer_{$env.area_id}" value="1"> <label for="openid_out_computer">{$sysw_client_other}</label>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="submit" value="{$sysw_client_enter}" class="button">
			</td>
		</tr>
	</table>
	<a href="" onclick="switch_login_form(); return false;">{$sysw_client_login_enter}</a><br>
</form>
{/if}
<form id="login_form" action="" method="post" onsubmit="return CheckForm.validate( this )" style="display: {if $openid_mode && $openid}none{else}block{/if}">
	<input type="hidden" name="from_url_{$env.area_id}" value="{$from_url}">
	<table border="1">
		<tr>
			<td>
				{$sysw_client_email}*:
			</td>
			<td>
				<input type="text" name="email_{$env.area_id}" value="{$email}" lang="errors_nonempty__email_">
			</td>
		</tr>
		<tr>
			<td>
				{$sysw_client_password}*:
			</td>
			<td>
				<input type="password" name="password_{$env.area_id}" value="" lang="errors_nonempty_">
			</td>
		</tr>
		<tr>
			<td>
				&nbsp;
			</td>
			<td>
				<input type="checkbox" id="login_out_computer" name="out_computer_{$env.area_id}" value="1"> <label for="login_out_computer">{$sysw_client_other}</label>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="submit" value="{$sysw_client_enter}" class="button">
			</td>
		</tr>
	</table>
{if $path_to_reminder}
	<a href="{$path_to_reminder}">{$sysw_client_remember}</a><br>
{/if}
{if $openid_mode}
	<a href="" onclick="switch_login_form(); return false;">{$sysw_client_openid_enter}</a><br>
{/if}
</form>
{if $path_to_registration}
<a href="{$path_to_registration}">{$sysw_client_reg}</a><br>
{/if}
{/if}
