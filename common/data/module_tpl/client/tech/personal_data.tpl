{if $is_updated}
<b>{$sysw_client_msg6}<b><br>
{/if}
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
<form method="post" action="index.php" onsubmit="return CheckForm.validate( this )">
	<table border="1">
		<tr>
			<td colspan="2">
				<b>{$sysw_client_personaldata}</b>
			</td>
		</tr>
		<tr>
			<td>
				{$sysw_client_surname}:{if !$is_openid_person} <span style="color: red">*</span>{/if}
			</td>
			<td>
				<input type="text" name="SURNAME_{$env.area_id}" value="{$SURNAME}"{if !$is_openid_person} lang="errors_nonempty_"{/if}>
			</td>
		</tr>
		<tr>
			<td>
				{$sysw_client_firstname}:
			</td>
			<td>
				<input type="text" name="NAME_{$env.area_id}" value="{$NAME}">
			</td>
		</tr>
		<tr>
			<td>
				{$sysw_client_patronymic}:
			</td>
			<td>
				<input type="text" name="PATRONYMIC_{$env.area_id}" value="{$PATRONYMIC}">
			</td>
		</tr>
		<tr>
			<td>
				{$sysw_client_email}:{if !$is_openid_person} <span class="red">*</span>{/if}
			</td>
			<td>
				<input type="text" name="EMAIL_{$env.area_id}" value="{$EMAIL}" lang="errors{if !$is_openid_person}_nonempty_{/if}_email_">
			</td>
		</tr>
		<tr>
			<td>
				{$sysw_client_phone}:
			</td>
			<td>
				<input type="text" name="TELEPHONE_{$env.area_id}" value="{$TELEPHONE}">
			</td>
		</tr>
{if $is_legal_person}
		<tr>
			<td>
				{$sysw_client_fax}:
			</td>
			<td>
				<input type="text" name="FAX_{$env.area_id}" value="{$FAX}">
				<input type="hidden" name="CLIENT_TYPE_{$env.area_id}" value="1">
			</td>
		</tr>
{/if}
{if !$is_openid_person}
		<tr>
			<td colspan="2">
				{$sysw_client_passwd_change_note} 
			</td>
		</tr>
		<tr>
			<td>
				{$sysw_client_password}:
			</td>
			<td>
				<input type="password" name="PASSWORD_{$env.area_id}" value="">
			</td>
		</tr>
		<tr>
			<td>
				{$sysw_client_password2}:
			</td>
			<td>
				<input type="password" name="PASSWORD2_{$env.area_id}" value="">
			</td>
		</tr>
{/if}
{if $is_legal_person}
		<tr>
			<td>
				{$sysw_client_organization} <span style="color: red">*</span>:
			</td>
			<td>
				<input type="text" name="LEGAL_PERSON_{$env.area_id}" value="{$LEGAL_PERSON}" lang="errors_nonempty_">
			</td>
		</tr>
		<tr>
			<td>
				{$sysw_client_legal_address} <span style="color: red">*</span>:
			</td>
			<td><input type="text" name="ADDRESS_{$env.area_id}" value="{$ADDRESS}" lang="errors_nonempty_"></td>
		</tr>
		<tr>
			<td>
				{$sysw_client_inn} <span style="color: red">*</span>:
			</td>
			<td><input type="text" name="INN_{$env.area_id}" value="{$INN}" lang="errors_nonempty__int_"></td>
		</tr>
		<tr>
			<td>
				{$sysw_client_kpp} <span style="color: red">*</span>:
			</td>
			<td><input type="text" name="KPP_{$env.area_id}" value="{$KPP}" lang="errors_nonempty__int_"></td>
		</tr>
		<tr>
			<td>
				{$sysw_client_settlement_account} <span style="color: red">*</span>:
			</td>
			<td><input type="text" name="R_ACCOUNT_{$env.area_id}" value="{$R_ACCOUNT}" lang="errors_nonempty__int_"></td>
		</tr>
		<tr>
			<td>
				{$sysw_client_bank} <span style="color: red">*</span>:
			</td>
			<td><input type="text" name="BANK_NAME_{$env.area_id}" value="{$BANK_NAME}" lang="errors_nonempty_"></td>
		</tr>
		<tr>
			<td>
				{$sysw_client_correspondent_account} <span style="color: red">*</span>:
			</td>
			<td><input type="text" name="K_ACCOUNT_{$env.area_id}" value="{$K_ACCOUNT}" lang="errors_nonempty__int_"></td>
		</tr>
		<tr>
			<td>
				{$sysw_client_bik} <span style="color: red">*</span>:
			</td>
			<td><input type="text" name="BIK_{$env.area_id}" value="{$BIK}" lang="errors_nonempty_"></td>
		</tr>
		<tr>
			<td>
				{$sysw_client_okpo} <span style="color: red">*</span>:
			</td>
			<td><input type="text" name="CODE_OKPO_{$env.area_id}" value="{$CODE_OKPO}" lang="errors_nonempty__int_"></td>
		</tr>
		<tr>
			<td>
				{$sysw_client_okved} <span style="color: red">*</span>:
			</td>
			<td><input type="text" name="CODE_OKVED_{$env.area_id}" value="{$CODE_OKVED}" lang="errors_nonempty__int"></td>
		</tr>
{/if}
		<tr>
			<td colspan="2">
				<input type="submit" value="{$sysw_client_save}">
			</td>
		</tr>
	</table>
</form>
