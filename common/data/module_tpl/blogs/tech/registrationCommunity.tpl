<script type="text/javascript" src="/common/js/core/translate.js.php?lang={$env.lang_root_dir}"></script>
<script type="text/javascript" src="/common/js/core/check_form.js"></script>

<script language="JavaScript">
function checkNick(form_name){ldelim}
    var _form = eval('document.'+form_name);
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
    <b>Сообщество успешно было создано!</b>
{else}
    {if $_is_error}
        <div align="center">
        <font color="red">
        {if $_error1}
            Уважаемый посетитель, заполните, пожалуйста, все обязательные поля формы<br>
        {/if}
        {if $_error2}
            Уважаемый посетитель, сообщество с введенным названием уже существует. Пожалуйста, выберите другое название<br>
        {/if}
        </font>
        </div>
    {/if}
    <form action="index.php" method="post" name="change_{$env.area_id}" onSubmit="if ( !checkNick('change_{$env.area_id}') ) return false; else return CheckForm.validate( this );">
    <table align="center" border="1">
    <tr>
        <td align="center" colspan="2"><b>Создание сообщества</b></td>
    </tr>
    <tr>
        <td align="right">Название сообщества <font color="red">*</font></td>
        <td><input type="text" name="NICK_{$env.area_id}" value="{$NICK}" lang="errors_nonempty_"></td>
    </tr>
    <tr>
        <td align="right">Заголовок сообщества</td>
        <td><input type="text" name="NAME_{$env.area_id}" value="{$NAME}"></td>
    </tr>
    <tr>
        <td align="right">Условие вступления <font color="red">*</font>:</td>
        <td>
            <input type="radio" name="MEMBERSHIP_{$env.area_id}" value="1" lang="errors_nonempty__radio_" {if !$MEMBERSHIP2} checked{/if}> свободное<br>
            <input type="radio" name="MEMBERSHIP_{$env.area_id}" value="2" lang="errors_nonempty__radio_" {if $MEMBERSHIP2} checked{/if}> модерируемое
        </td>
    </tr>
    <tr>
        <td align="right">Добавление записей <font color="red">*</font>:</td>
        <td>
            <input type="radio" name="POSTLEVEL_{$env.area_id}" value="1" lang="errors_nonempty__radio_" {if !$POSTLEVEL2} checked{/if}> неограниченное<br>
            <input type="radio" name="POSTLEVEL_{$env.area_id}" value="2" lang="errors_nonempty__radio_" {if $POSTLEVEL2} checked{/if}> ограниченное
        </td>
    </tr>
    <tr>
        <td align="right">Модерация записей <font color="red">*</font>:</td>
        <td>
            <input type="radio" name="MODERATION_{$env.area_id}" value="1" lang="errors_nonempty__radio_" {if !$MODERATION2} checked{/if}> не производится<br>
            <input type="radio" name="MODERATION_{$env.area_id}" value="2" lang="errors_nonempty__radio_" {if $MODERATION2} checked{/if}> производится
        </td>
    </tr>
    <tr>
        <td align="center" colspan="2"><input type="submit" value="Создать"></td>
    </tr>
    </table>
    </form>
{/if}
