{if $_is_reminded}
    <div align="center">{$SYSW_BLOG_MSG2}</div>
{elseif $_is_updated}
    <div align="center">{$SYSW_BLOG_MSG6}</div>
{else}
    <script type="text/javascript" src="/common/js/core/translate.js.php?lang={$env.lang_root_dir}"></script>
    <script type="text/javascript" src="/common/js/core/check_form.js"></script>

	<h3 align="center">{$SYSW_BLOG_REMINDER}</h3>

	<!-- Сообщение об ошибке-->
	{if $_is_error}
		<font color="red">
            {$sysw_client_caution}
            {if $_error6}
            <br>{$SYSW_BLOG_ERR6}
            {/if}
            {if $_error4}
            <br>{$SYSW_BLOG_ERR4}
            {/if}
            <br>{$sysw_client_again}
        </font>
        <br><br>
	{/if}
	<!-- /Сообщение об ошибке-->

    {if $_is_change_form}
     	<form action="" method="post" name="change_{$env.area_id}" onSubmit="return CheckForm.validate( this )">
    	<table align="center" border="1">
    	  <tr>
    	  	<td align="right">Новый пароль <font color="red">*</font>:</b></td>
    	  	<td><input type="password" name="PASSWORD_{$env.area_id}" value="" lang="errors_nonempty_"></td>
    	  </tr>
    	  <tr>
    	  	<td align="right">Подтверждение нового пароля <font color="red">*</font>:</b></td>
    	  	<td><input type="password" name="PASSWORD2_{$env.area_id}" value="" lang="errors_nonempty_"></td>
    	  </tr>
    	  <tr>
    	  	<td colspan="2" align="center">
    	  		<input type="hidden" name="mode_{$env.area_id}" value="reminder">
    	  		<input type="submit" value="{$SYSW_BLOG_SAVE}">
    	  	</td>
    	  </tr>
    	</table>
    	</form>
    {else}
     	<form action="" method="post" name="change_{$env.area_id}" onSubmit="return CheckForm.validate( this )">
    	<table align="center" border="1">
    	  <tr>
    	  	<td align="right"><b>{$SYSW_BLOG_EMAIL} <font color="red">*</font>:</b></td>
    	  	<td><input type="text" name="EMAIL_{$env.area_id}" value="{$EMAIL}" lang="errors_nonempty__email_"></td>
    	  </tr>
    	  <tr>
    	  	<td colspan="2" align="center">
    	  		<input type="hidden" name="mode_{$env.area_id}" value="reminder">
    	  		<input type="submit" value="{$SYSW_BLOG_CONTINUE}">
    	  	</td>
    	  </tr>
    	</table>
    	</form>
    {/if}
{/if}