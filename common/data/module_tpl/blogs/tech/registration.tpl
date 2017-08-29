<script type="text/javascript" src="/common/js/core/translate.js.php?lang={$env.lang_root_dir}"></script>
<script type="text/javascript" src="/common/js/core/check_form.js"></script>

<script language="JavaScript">
function checkNick_{$env.area_id}(_form){ldelim}
    var nick_value = _form.NICK_{$env.area_id}.value;
    if (!nick_value.match("^[a-zA-Z0-9]+$")){ldelim}
        alert('{$SYSW_BLOG_CHECK_MSG1}');
        _form.NICK_{$env.area_id}.focus();
        return false;
    {rdelim}
    return true;
{rdelim}
</script>

{if $_is_registrated}
    <b>{$SYSW_BLOG_MSG1}</b>
{else}
    {if $_is_error}
        <div align="center">
        <font color="red">
        {$SYSW_BLOG_CAUTION}<br>
        {if $_error1}
            {$SYSW_BLOG_ERR1}<br>
        {/if}
        {if $_error2}
            {$SYSW_BLOG_ERR2}<br>
        {/if}
        {if $_error3}
            {$SYSW_BLOG_ERR3}<br>
        {/if}
        {if $_error31}
            {$SYSW_BLOG_ERR31}<br>
        {/if}
        {if $_error4}
            {$SYSW_BLOG_ERR4}<br>
        {/if}
        {if $_error5}
            {$SYSW_BLOG_ERR5}<br>
        {/if}
        {$SYSW_BLOG_AGAIN}<br>
        </font>
        </div>
    {/if}
    <form action="index.php" method="post" name="change_{$env.area_id}" onSubmit="if ( !checkNick_{$env.area_id}( this ) ){ldelim}return false{rdelim}else{ldelim}return CheckForm.validate( this ){rdelim}">
    <table align="center" border="1">
    <tr>
        <td align="center" colspan="2"><b>{$SYSW_BLOG_REG_BLOG}</b></td>
    </tr>
    <tr>
        <td align="right">{$SYSW_BLOG_NICK}: <font color="red">*</font></td>
        <td><input type="text" name="NICK_{$env.area_id}" value="{$NICK}" lang="errors_nonempty__login_" {if $NICK_READONLY} readonly{/if}></td>
    </tr>
    <tr>
        <td align="right">{$SYSW_BLOG_EMAIL} <font color="red">*</font>:</td>
        <td><input type="text" name="EMAIL_{$env.area_id}" value="{$EMAIL}" lang="errors_nonempty__email_" {if $EMAIL_READONLY} readonly{/if}></td>
    </tr>
    <tr>
        <td align="right">{$SYSW_BLOG_PASSWORD} <font color="red">*</font>:</td>
        <td><input type="password" name="PASSWORD_{$env.area_id}" value="{$PASSWORD}" lang="errors_nonempty__login_"></td>
    </tr>
    <tr>
        <td align="right">{$SYSW_BLOG_PASSWORD2} <font color="red">*</font>:</td>
        <td><input type="password" name="PASSWORD2_{$env.area_id}" value="{$PASSWORD2}" lang="errors_nonempty__login_"></td>
    </tr>
    <tr>
        <td align="right">{$SYSW_BLOG_CAPTCHA} <font color="red">*</font></td>
        <td>
            <img src="/common/tool/getcaptcha.php?captcha_id={$captcha_id}"><br>
            <input type="hidden" name="captcha_id_{$env.area_id}" value="{$captcha_id}">
            <input type="text" name="captcha_value_{$env.area_id}" value="" lang="errors_nonempty_">
        </td>
    </tr>
    <tr>
        <td align="center" colspan="2"><input type="submit" value="{$SYSW_BLOG_CREATE}"></td>
    </tr>
    </table>
    </form>
{/if}