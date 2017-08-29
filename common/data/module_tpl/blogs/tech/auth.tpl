<script type="text/javascript" src="/common/js/core/translate.js.php?lang={$env.lang_root_dir}"></script>
<script type="text/javascript" src="/common/js/core/check_form.js"></script>


{if $_is_registrated}

    {if $_image_default}<a href="{$_goToBlog}"><img src="{$_image_default}"></a><br>{/if}
    <a href="{$_goToBlog}">{$_nick}</a><br><br>
    <a href="{$_goToPage}?exit_{$env.area_id}=1">{$SYSW_BLOG_EXIT}</a>
    <br>
    <br><b>Личный блог</b>
    {if $_goToBlog}<br><a href="{$_goToBlog}/">список записей</a>{/if}
    {if $_goToAddPost}<br><a href="{$_goToAddPost}">написать</a>{/if}
    {if $_goToProfile}<br><a href="{$_goToProfile}">редактировать профиль</a>{/if}
    {if $_goToProfilePwd}<br><a href="{$_goToProfilePwd}">изменить пароль</a>{/if}
    {if $_goToProfileImage}<br><a href="{$_goToProfileImage}">изображения</a>{/if}
    {if $_goToProfileTag}<br><a href="{$_goToProfileTag}">управление тегами</a>{/if}
    <br><b>Друзья</b>
    {if $_goToBlog}<br><a href="{$_goToBlog}/friend/">лента друзей</a>{/if}
    {if $_goToProfileFriend}<br><a href="{$_goToProfileFriend}">управление друзьями</a>{/if}
    {if $_goToProfileFriendgroup}<br><a href="{$_goToProfileFriendgroup}">управление группами</a>{/if}
    <br><b>Сообщества</b>
    {if $_goToAddPost}<br><a href="{$_goToAddPost}">написать</a>{/if}
    {if $_goToProfileComManager}<br><a href="{$_goToProfileComManager}">управление</a>{/if}
    {if $_goToRegCommunity}<br><a href="{$_goToRegCommunity}">создать новое</a>{/if}
    {if $_goToProfileOffer}<br><a href="{$_goToProfileOffer}">приглашения{if $_countOffer} ({$_countOffer}){/if}</a>{/if}

{else}

	{if $_is_error}
	   <div align="center">
	   <font color="red">
	   {$SYSW_BLOG_CAUTION}<br>
	   {$SYSW_BLOG_ERR7} <a href="{$_goToReminder}?mode_{$env.area_id}=reminder">{$SYSW_BLOG_REMEMBER}</a><br>	
	   {$SYSW_BLOG_AGAIN}<br>
	   </font>
	   </div>
	{/if}

	<form action="{$_goToPage}" method="post" name="change_{$env.area_id}" onSubmit="return CheckForm.validate( this )">
	<input type="hidden" name="from_url_{$env.area_id}" value="{$from_url}">
	<table align="center" border="1">
	  <tr>
		<td align="right">{$SYSW_BLOG_LOGIN}:</td>
		<td><input type="text" name="login_{$env.area_id}" value="{$_login}" lang="errors_nonempty__login_"></td>
	  </tr>
	  <tr>
		<td align="right">{$SYSW_BLOG_PASSWORD}:</td>
		<td><input type="password" name="password_{$env.area_id}" value="{$_password}" lang="errors_nonempty__login_"></td>
	  </tr>
	  <tr>
		<td align="right"></td>
		<td><input type="checkbox" name="outComputer_{$env.area_id}" value="1"> {$SYSW_BLOG_OTHER}</td>
	  </tr>
	
	  <tr align="center">
		<td colspan="2"><input type="submit" value="{$SYSW_BLOG_ENTER}"></td>
	  </tr>
	</table>
	</form>
	{if $_goToReminder}<a href="{$_goToReminder}">{$SYSW_BLOG_REMINDER}</a>{/if}
	<br>
	{if $_goToRegistration}<a href="{$_goToRegistration}">{$SYSW_BLOG_REG}</a>{/if}
	
{/if}