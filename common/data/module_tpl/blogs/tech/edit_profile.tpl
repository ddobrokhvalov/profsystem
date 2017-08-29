<script type="text/javascript" src="/common/js/core/translate.js.php?lang={$env.lang_root_dir}"></script>
<script type="text/javascript" src="/common/js/core/check_form.js"></script>

<!-- собираем заголовок -->
<center>
<b>
    {$BLOG_USER}&nbsp;:&nbsp;
    {if $MODE_PERSONDATA}редактирование&nbsp;профиля{/if}
    {if $MODE_PWD}изменение&nbsp;пароля{/if}
    {if $MODE_IMAGES}управление&nbsp;изображениями{/if}
    {if $MODE_FRIENDGROUPS}управление&nbsp;группами&nbsp;друзей{/if}
    {if $MODE_FRIENDS}управление&nbsp;списком&nbsp;друзей{/if}
    {if $MODE_TAGS}управление&nbsp;списком&nbsp;тегов{/if}
    {if $MODE_OFFERS}приглашения{/if}
</b>
</center>
<!-- /собираем заголовок -->


<!-- фомируем ссылки по закладкам -->
{if $MODE_PERSONDATA}<b>Личная информация</b>{else}<a href="index.php">Личная информация</a>{/if}
{if !$PASSWORD_READONLY}
    &nbsp;&nbsp;
    {if $MODE_PWD}<b>Пароль</b>{else}<a href="index.php?submode_{$env.area_id}=pwd">Пароль</a>{/if}
{/if}
&nbsp;&nbsp;
{if $MODE_IMAGES}<b>Изображения</b>{else}<a href="index.php?submode_{$env.area_id}=images">Изображения</a>{/if}
&nbsp;&nbsp;
{if $MODE_FRIENDGROUPS}<b>Группы друзей</b>{else}<a href="index.php?submode_{$env.area_id}=friendgroups">Группы друзей</a>{/if}
&nbsp;&nbsp;
{if $MODE_FRIENDS}<b>Друзья</b>{else}<a href="index.php?submode_{$env.area_id}=friends">Друзья</a>{/if}
&nbsp;&nbsp;
{if $MODE_TAGS}<b>Теги</b>{else}<a href="index.php?submode_{$env.area_id}=tags">Теги</a>{/if}
&nbsp;&nbsp;
{if $MODE_OFFERS}<b>Приглашения</b>{else}<a href="index.php?submode_{$env.area_id}=offers">Приглашения{if $_countOffer} ({$_countOffer}){/if}</a>{/if}
<!-- /фомируем ссылки по закладкам -->

<br>

<!-- общий скрипт для скрытия/отображения элементов формы -->
<script language="JavaScript">
function hideElement(e){ldelim}
    if (e){ldelim}
        if (e.style && e.style.visibility != 'hidden'){ldelim}
            e.style.visibility = 'hidden';
            e.style.display = 'none';
        {rdelim}
    {rdelim}
{rdelim}

function showElement(e){ldelim}
    if (e){ldelim}
        if (e.style && e.style.visibility != 'visible'){ldelim}
            e.style.visibility = 'visible';
            e.style.display = '';
        {rdelim}
    {rdelim}
{rdelim}

function showHide(e){ldelim}
    if (e){ldelim}
        if (e.style && e.style.visibility != 'visible'){ldelim}
            showElement(e);
        {rdelim}else{ldelim}
            hideElement(e)
        {rdelim}
    {rdelim}
{rdelim}
</script>
<!-- /общий скрипт для скрытия/отображения элементов формы -->





<!-- отображаем закладки в зависимости от выбранного варианта -->


{if $MODE_PERSONDATA}
<!-- ПРОФИЛЬ (ЛИЧНАЯ ИНФОРМАЦИЯ) -->

<script type="text/javascript" src="/common/js/core/calendar.js"></script>
<script language="JavaScript">
function change_country(form_name){ldelim}
    var _form = eval('document.'+form_name);
    var cities = new Array();
    {if $BLOG_COUNTRY_BLOG_COUNTRY_ID}
    {foreach from=$BLOG_COUNTRY_BLOG_COUNTRY_ID item=item}
        cities["{$item.BLOG_COUNTRY_ID}"] = new Array();
    {/foreach}
    {/if}
    {if $BLOG_CITY_BLOG_CITY_ID}
    {foreach from=$BLOG_CITY_BLOG_CITY_ID item=item}
        cities["{$item.BLOG_COUNTRY_ID}"].push( new Array('{$item.BLOG_CITY_ID}', '{$item.BLOG_CITY_NAME}') );
    {/foreach}
    {/if}
    var country = _form.BLOG_COUNTRY_ID_{$env.area_id}.value;
    _form.BLOG_CITY_ID_{$env.area_id}.options.length = 0;
    _form.BLOG_CITY_ID_{$env.area_id}.options[0] = new Option('', '');
    for ( i=0; i<cities[country].length; i++ ){ldelim}
        _form.BLOG_CITY_ID_{$env.area_id}.options[i+1] = new Option(cities[country][i][1], cities[country][i][0]);
    {rdelim}
{rdelim}
</script>

<center>
<FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="change_{$env.area_id}" ONSUBMIT="return CheckForm.validate( this )">
<table width="90%" border="0" cellspacing="0" cellpadding="0">
<tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td align="center">отображать поле</td>
</tr>

<tr>
    <td>Заголовок блога</td>
    <td><INPUT TYPE="TEXT" NAME="NAME_{$env.area_id}" VALUE="{$NAME}" style="width:400px;" {if $NAME_READONLY} readonly{/if}></td>
    <td align="center"><INPUT TYPE="checkbox" NAME="SHOW_FIELDS_{$env.area_id}[]" VALUE="{$FIELD_NAME_ID}" {if $FIELD_NAME_CHECKED} checked="checked"{/if}></td>
</tr>
		
<tr>
    <td>E-mail *</td>
    <td><INPUT TYPE="TEXT" NAME="EMAIL_{$env.area_id}" VALUE="{$EMAIL}" lang="errors_nonempty__email_" style="width:400px;" {if $EMAIL_READONLY} readonly{/if}></td>
    <td align="center"><INPUT TYPE="checkbox" NAME="SHOW_FIELDS_{$env.area_id}[]" VALUE="{$FIELD_EMAIL_ID}" {if $FIELD_EMAIL_CHECKED} checked="checked"{/if}></td>
</tr>

<tr>
    <td>ФИО</td>
    <td><INPUT TYPE="TEXT" NAME="FIO_{$env.area_id}" VALUE="{$FIO}" style="width:400px;" {if $FIO_READONLY} readonly{/if}></td>
    <td align="center"><INPUT TYPE="checkbox" NAME="SHOW_FIELDS_{$env.area_id}[]" VALUE="{$FIELD_FIO_ID}" {if $FIELD_FIO_CHECKED} checked="checked"{/if}></td>
</tr>

<tr>
    <td>ICQ</td>
    <td><INPUT TYPE="TEXT" NAME="ICQ_{$env.area_id}" VALUE="{$ICQ}" style="width:400px;" {if $ICQ_READONLY} readonly{/if}></td>
    <td align="center"><INPUT TYPE="checkbox" NAME="SHOW_FIELDS_{$env.area_id}[]" VALUE="{$FIELD_ICQ_ID}" {if $FIELD_ICQ_CHECKED} checked="checked"{/if}></td>
</tr>

<tr>
    <td>Skype</td>
    <td><INPUT TYPE="TEXT" NAME="SKYPE_{$env.area_id}" VALUE="{$SKYPE}" style="width:400px;" {if $SKYPE_READONLY} readonly{/if}></td>
    <td align="center"><INPUT TYPE="checkbox" NAME="SHOW_FIELDS_{$env.area_id}[]" VALUE="{$FIELD_SKYPE_ID}" {if $FIELD_SKYPE_CHECKED} checked="checked"{/if}></td>
</tr>

<tr>
    <td>Дата рождения</td>
    <td>
        <INPUT TYPE="TEXT" NAME="BIRTHDATE_{$env.area_id}" VALUE="{$BIRTHDATE}" lang="errors_date_" style="width:80px;" {if $BIRTHDATE_READONLY} readonly{/if}>
        <a href="" onclick="Calendar.show( document.change_{$env.area_id}.BIRTHDATE_{$env.area_id}, this ); return false">{$SYSW_CALENDAR}</a>
        отображать в виде:
        <SELECT NAME="BIRTHDATE_FORMAT_{$env.area_id}" {if $BIRTHDATE_FORMAT_READONLY} disabled{/if}>
            <OPTION VALUE="">
            {foreach from=$BIRTHDATE_FORMAT item=item} 
                <OPTION VALUE="{$item.BIRTHDATE_FORMAT_VALUE}" {$item.SELECTED} >{$item.BIRTHDATE_FORMAT_NAME}</OPTION>
            {/foreach}
        </SELECT>
    </td>
    <td align="center"><INPUT TYPE="checkbox" NAME="SHOW_FIELDS_{$env.area_id}[]" VALUE="{$FIELD_BIRTHDATE_ID}" {if $FIELD_BIRTHDATE_CHECKED} checked="checked"{/if}></td>
</tr>

<tr>
    <td>Пол</td>
    <td>
        <SELECT NAME="SEX_{$env.area_id}" {if $SEX_READONLY} disabled{/if}>
            {foreach from=$SEX item=item} 
                <OPTION VALUE="{$item.SEX_VALUE}" {$item.SELECTED} >{$item.SEX_NAME}</OPTION>
            {/foreach}
        </SELECT>
    </td>
    <td align="center"><INPUT TYPE="checkbox" NAME="SHOW_FIELDS_{$env.area_id}[]" VALUE="{$FIELD_SEX_ID}" {if $FIELD_SEX_CHECKED} checked="checked"{/if}></td>
</tr>

<tr>
    <td>Страна</td>
    <td>
        <SELECT NAME="BLOG_COUNTRY_ID_{$env.area_id}"  onChange="change_country('change_{$env.area_id}');" {if $BLOG_COUNTRY_ID_READONLY} disabled{/if}>
            <OPTION VALUE="">
            {foreach from=$BLOG_COUNTRY_BLOG_COUNTRY_ID item=item} 
                <OPTION VALUE="{$item.BLOG_COUNTRY_ID}" {$item.SELECTED} >{$item.BLOG_COUNTRY_NAME}</OPTION>
            {/foreach}
        </SELECT>
    </td>
    <td align="center"><INPUT TYPE="checkbox" NAME="SHOW_FIELDS_{$env.area_id}[]" VALUE="{$FIELD_BLOG_COUNTRY_ID_ID}" {if $FIELD_BLOG_COUNTRY_ID_CHECKED} checked="checked"{/if}></td>
</tr>

<tr>
    <td>Город</td>
    <td>
        <SELECT NAME="BLOG_CITY_ID_{$env.area_id}" {if $BLOG_CITY_ID_READONLY} disabled{/if}>
            <OPTION VALUE="">
            {foreach from=$BLOG_CITY_BLOG_CITY_ID item=item}
                {if $item.SHOW} 
                    <OPTION VALUE="{$item.BLOG_CITY_ID}" {$item.SELECTED} >{$item.BLOG_CITY_NAME}</OPTION>
                {/if} 
            {/foreach}
        </SELECT>
    </td>
    <td align="center"><INPUT TYPE="checkbox" NAME="SHOW_FIELDS_{$env.area_id}[]" VALUE="{$FIELD_BLOG_CITY_ID_ID}" {if $FIELD_BLOG_CITY_ID_CHECKED} checked="checked"{/if}></td>
</tr>

<tr>
    <td>Web-сайт</td>
    <td><INPUT TYPE="TEXT" NAME="HOMEPAGE_{$env.area_id}" VALUE="{$HOMEPAGE}" style="width:400px;" {if $HOMEPAGE_READONLY} readonly{/if}></td>
    <td align="center"><INPUT TYPE="checkbox" NAME="SHOW_FIELDS_{$env.area_id}[]" VALUE="{$FIELD_HOMEPAGE_ID}" {if $FIELD_HOMEPAGE_CHECKED} checked="checked"{/if}></td>
</tr>

<tr>
    <td>О себе</td>
    <td><TEXTAREA  ROWS="5" STYLE="width:400px;" WRAP="soft" NAME="ABOUT_{$env.area_id}" {if $ABOUT_READONLY} readonly{/if}>{$ABOUT}</TEXTAREA></td>
    <td align="center"><INPUT TYPE="checkbox" NAME="SHOW_FIELDS_{$env.area_id}[]" VALUE="{$FIELD_ABOUT_ID}" {if $FIELD_ABOUT_CHECKED} checked="checked"{/if}></td>
</tr>

<tr>
    <td>Интересы</td>
    <td>
        Список слов/фраз, разделённых запятой.
        <br><TEXTAREA  ROWS="5" STYLE="width:400px;" WRAP="soft" NAME="INTEREST_{$env.area_id}" {if $INTEREST_READONLY} readonly{/if}>{$INTEREST}</TEXTAREA>
    </td>
    <td align="center"><INPUT TYPE="checkbox" NAME="SHOW_FIELDS_{$env.area_id}[]" VALUE="{$FIELD_INTEREST_ID}" {if $FIELD_INTEREST_CHECKED} checked="checked"{/if}></td>
</tr>

<tr>
    <td>В блоге и ленте друзей </td>
    <td colspan="2">
        <SELECT NAME="POSTS_ON_PAGE_{$env.area_id}" {if $POSTS_ON_PAGE_READONLY} disabled{/if}>
            {foreach from=$POSTS_ON_PAGE item=item}
                <OPTION VALUE="{$item.POSTS_ON_PAGE_VALUE}" {$item.SELECTED} >{$item.POSTS_ON_PAGE_NAME}</OPTION>
            {/foreach}
        </SELECT>
        записей на странице
    </td>
</tr>
<tr>
    <td colspan="3"><center><INPUT TYPE="SUBMIT" NAME="save_{$env.area_id}" VALUE="Сохранить"></center></td>
</tr>
</table>
<INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
</FORM>
</center>

<!-- /ПРОФИЛЬ (ЛИЧНАЯ ИНФОРМАЦИЯ) -->
{/if}





{if $MODE_PWD}
<!-- ПАРОЛЬ -->

<center>
<FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="change_{$env.area_id}" ONSUBMIT="return CheckForm.validate( this )">
<table width="90%" border="0" cellspacing="0" cellpadding="0">
{if $ERROR_DIFF_PASSWORD}
<tr>
    <td colspan="2"><font color="EE3333">Уважаемый пользователь,будьте внимательны! Не совпадают поля "Новый пароль" и "Подтверждение нового пароля".</font></td>
</tr>
{/if}

{if $ERROR_OLD_PASSWORD}
<tr>
    <td colspan="2"><font color="EE3333">Уважаемый пользователь,будьте внимательны! Ваш текущий пароль не совпадает с полем "Старый пароль".</font></td>
</tr>
{/if}

<tr>
    <td>Новый пароль *</td>
    <td><INPUT TYPE="PASSWORD" NAME="PASSWORD_{$env.area_id}" VALUE="" lang="errors_nonempty_" style="width:400px;"></td>
</tr>
		
<tr>
    <td>Подтверждение нового пароля *</td>
    <td><INPUT TYPE="PASSWORD" NAME="PASSWORD2_{$env.area_id}" VALUE="" lang="errors_nonempty_" style="width:400px;"></td>
</tr>

<tr>
    <td>Старый пароль *</td>
    <td><INPUT TYPE="PASSWORD" NAME="OLD_PASSWORD_{$env.area_id}" VALUE="" lang="errors_nonempty_" style="width:400px;"></td>
</tr>

<tr>
    <td colspan="2"><center><INPUT TYPE="SUBMIT" NAME="save_{$env.area_id}" VALUE="Сохранить"></center></td>
</tr>
</table>
<INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
</FORM>
</center>

<!-- /ПАРОЛЬ -->
{/if}




{if $MODE_IMAGES}
<!-- ИЗОБРАЖЕНИЯ -->

<script language="JavaScript">
function del_image(form_name, id_image){ldelim}
    var _form = eval('document.'+form_name);
    _form.action_{$env.area_id}.value = 'del';
    _form.BLOG_IMAGE_ID_{$env.area_id}.value = id_image;
    _form.submit();
{rdelim}
function change_status_image(_form, _field){ldelim}
    CheckForm.oForm = _form;
    if ( !( CheckForm[CheckForm.aCheckHandlers['_nonempty_']['method']](_field) ) ){ldelim}
        alert( Dictionary.translate( CheckForm.aCheckHandlers['_nonempty_']['message'] ) );
        try {ldelim} _field.focus() {rdelim} catch (e) {ldelim}{rdelim};
    {rdelim}else{ldelim}
        return true;
    {rdelim}
{rdelim}
function save_image(form_name, id_image){ldelim}
    var _form = eval('document.'+form_name);
    var _img_file = eval('document.'+form_name+'.IMG'+id_image+'IMG_{$env.area_id}_file');
    var _title = eval('document.'+form_name+'.TITLE'+id_image+'TITLE_{$env.area_id}');
    if ( change_status_image(_form, _title) ){ldelim}
        _form.action_{$env.area_id}.value = 'save';
        _form.BLOG_IMAGE_ID_{$env.area_id}.value = id_image;
        _form.submit();
    {rdelim}
{rdelim}
function add_image(form_name){ldelim}
    var _form = eval('document.'+form_name);
    var _img_file = eval('document.'+form_name+'.IMG_{$env.area_id}_file');
    var _title = eval('document.'+form_name+'.TITLE_{$env.area_id}');
    if ( change_status_image(_form, _img_file) ){ldelim}
        if ( change_status_image(_form, _title) ){ldelim}
            _form.action_{$env.area_id}.value = 'add';
            _form.submit();
        {rdelim}
    {rdelim}
{rdelim}
</script>

<center>
<FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="change_{$env.area_id}">
<table width="90%" border="1" cellspacing="0" cellpadding="0">
{if $ERROR_IMAGE_EMPTY} 
<tr>
    <td colspan="2"><center><font color="EE3333">Поле Изображение не заполнено.</font></center></td>
</tr>
{/if} 
{if $ERROR_IMAGE_1} 
<tr>
    <td colspan="2"><center><font color="EE3333">Закачиваемый файл не является файлом изображения.</font></center></td>
</tr>
{/if} 
{if $ERROR_IMAGE_2} 
<tr>
    <td colspan="2"><center><font color="EE3333">Не поддерживаемый формат изображения (gif,&nbsp;jpeg,&nbsp;png).</font></center></td>
</tr>
{/if} 
{if $ERROR_IMAGE_3} 
<tr>
    <td colspan="2"><center><font color="EE3333">Число ваших изображений достигло максимума.</font></center></td>
</tr>
{/if} 
{if $ERROR_IMAGE_4} 
<tr>
    <td colspan="2"><center><font color="EE3333">Превышен размер файла, разрешённый администратором.</font></center></td>
</tr>
{/if} 
{if $ERROR_IMAGE_5} 
<tr>
    <td colspan="2"><center><font color="EE3333">Ширина изображения больше разрешённой.</font></center></td>
</tr>
{/if} 
{if $ERROR_IMAGE_6} 
<tr>
    <td colspan="2"><center><font color="EE3333">Высота изображения больше разрешённой.</font></center></td>
</tr>
{/if} 
{if $ERROR_IMAGE_7} 
<tr>
    <td colspan="2"><center><font color="EE3333">Размер файла больше, чем позволено настройками системы.</font></center></td>
</tr>
{/if} 
{if $ERROR_IMAGE_8} 
<tr>
    <td colspan="2"><center><font color="EE3333">Файл был загружен неполностью.</font></center></td>
</tr>
{/if} 
{if $ERROR_IMAGE_9} 
<tr>
    <td colspan="2"><center><font color="EE3333">Загруженный файл отсутствует.</font></center></td>
</tr>
{/if} 

{if $BLOG_IMAGES} 
{foreach from=$BLOG_IMAGES item=item}
<tr>
    <td width="10%"><img src="{$item.IMG}"></td>
    <td>
        <table>
        <tr>
            <td>Изображение (gif,&nbsp;jpeg,&nbsp;png)</td>
            <td><INPUT TYPE="FILE" NAME="IMG{$item.BLOG_IMAGE_ID}IMG_{$env.area_id}_file"></td>
        </tr>
        <tr>
            <td>Название</td>
            <td><INPUT TYPE="TEXT" NAME="TITLE{$item.BLOG_IMAGE_ID}TITLE_{$env.area_id}" VALUE="{$item.TITLE}" style="width:250px;"></td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td><INPUT TYPE="radio" NAME="IS_DEFAULT_{$env.area_id}" VALUE="{$item.BLOG_IMAGE_ID}" {if $item.IS_DEFAULT} checked{/if}>по умолчанию</td>
        </tr>
        <tr>
            <td colspan="2">
                <center>
                    <INPUT TYPE="BUTTON" VALUE="Сохранить" onClick="save_image('change_{$env.area_id}',{$item.BLOG_IMAGE_ID});">
                    <INPUT TYPE="BUTTON" VALUE="Удалить" onClick="javascript: if ( !confirm('Изображение будет удалено. Удалить?')) return false; else del_image('change_{$env.area_id}',{$item.BLOG_IMAGE_ID});">
                </center>
            </td>
        </tr>
        </table>
    </td>
</tr>
{/foreach}
{/if}

<tr>
    <td width="10%"><center>Добавление изображения</center></td>
    <td>
        <table>
        <tr>
            <td>Изображение (gif,&nbsp;jpeg,&nbsp;png)</td>
            <td><INPUT TYPE="FILE" NAME="IMG_{$env.area_id}_file"></td>
        </tr>
        <tr>
            <td>Название</td>
            <td><INPUT TYPE="TEXT" NAME="TITLE_{$env.area_id}" VALUE="" style="width:250px;"></td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td><INPUT TYPE="radio" NAME="IS_DEFAULT_{$env.area_id}" VALUE="0" {if !$BLOG_IMAGES} checked{/if}>по умолчанию</td>
        </tr>
        <tr>
            <td colspan="2">
                <center>
                    <INPUT TYPE="BUTTON" VALUE="Добавить" onClick="add_image('change_{$env.area_id}');">
                </center>
            </td>
        </tr>
        </table>
    </td>
</tr>

</table>

<INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="">
<INPUT TYPE="HIDDEN" NAME="BLOG_IMAGE_ID_{$env.area_id}" VALUE="">
<INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
</FORM>
</center>

<!-- /ИЗОБРАЖЕНИЯ -->
{/if}




{if $MODE_FRIENDGROUPS}
<!-- ГРУППЫ ДРУЗЕЙ -->

<script language="JavaScript">
function added_friend_to_group(form_name, friend_id){ldelim}
    hideElement(document.getElementById('div_out_'+friend_id+'__{$env.area_id}'));
    showElement(document.getElementById('div_in_'+friend_id+'__{$env.area_id}'));
    document.getElementById('check_'+friend_id+'__{$env.area_id}').checked = true;
{rdelim}

function del_friend_from_group(form_name, friend_id){ldelim}
    hideElement(document.getElementById('div_in_'+friend_id+'__{$env.area_id}'));
    showElement(document.getElementById('div_out_'+friend_id+'__{$env.area_id}'));
    document.getElementById('check_'+friend_id+'__{$env.area_id}').checked = false;
{rdelim}

function change_group(form_name){ldelim}
    var _form = eval('document.'+form_name);
    var group_id = _form.BLOG_FRIENDGROUP_ID_{$env.area_id}.value;
    var str = new String(document.location);
    var pos = str.lastIndexOf('blog_friendgroup_id_{$env.area_id}=');
    if (pos==-1){ldelim}
        document.location = document.location + '&blog_friendgroup_id_{$env.area_id}=' + group_id;
    {rdelim}else{ldelim}
        document.location = str.substr(0,pos) + 'blog_friendgroup_id_{$env.area_id}=' + group_id;
    {rdelim}
{rdelim}
</script>

<center>
<table width="90%" border="0" cellspacing="0" cellpadding="0">

{if $BLOG_FRIENDGROUP} 
<FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="view_{$env.area_id}">
<tr>
    <td>
        Выбрать группу:
        <SELECT NAME="BLOG_FRIENDGROUP_ID_{$env.area_id}" >
            {foreach from=$BLOG_FRIENDGROUP item=item}
                <OPTION VALUE="{$item.BLOG_FRIENDGROUP_ID}" {if $item.SELECTED} selected{/if} >{$item.TITLE}</OPTION>
            {/foreach}
        </SELECT>
        <INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
        <INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="del">
        <INPUT TYPE="BUTTON" VALUE="Показать" onClick="javascript: change_group('view_{$env.area_id}');">
        <INPUT TYPE="SUBMIT" VALUE="Удалить" onClick="javascript: if ( !confirm('Группа будет удалена. Удалить?')) return false;">
    </td>
</tr>
</FORM>
{/if}

{if $BLOG_FRIENDGROUP_ID} 
<FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="change_{$env.area_id}">
<tr>
    <td>
        Название
        <INPUT TYPE="TEXT" NAME="TITLE_{$env.area_id}" VALUE="{$TITLE}">
        Порядок
        <INPUT TYPE="TEXT" NAME="LIST_ORDER_{$env.area_id}" VALUE="{$LIST_ORDER}">
    </td>
</tr>
<tr>
    <td>
        <table>
        <tr>
            <td>Состав группы:</td>
            <td>Не в группе</td>
            <td>В группе</td>
        </tr>
        <tr>
            <td>
                &nbsp;
                {if $BLOG_FRIENDS}
                {foreach from=$BLOG_FRIENDS item=item}
                    <INPUT id="check_{$item.BLOG_FRIEND_ID}__{$env.area_id}" TYPE="checkbox" NAME="BLOG_FRIENDS_IN_{$env.area_id}[]" VALUE="{$item.BLOG_FRIEND_ID}" {if $item.SELECTED} checked="checked"{/if}  style="visibility:hidden;display:none">
                {/foreach}
                {/if}
            </td>
            <td>
                <table border="1">
                <tr>
                    <td>
                        {if $BLOG_FRIENDS}
                        {foreach from=$BLOG_FRIENDS item=item}
                            <div id="div_out_{$item.BLOG_FRIEND_ID}__{$env.area_id}" style="{if $item.SELECTED}visibility:hidden;display:none{else}visibility:visible;display:{/if}">
                                <a href="{$item.PATH}">{$item.TITLE}</a> <a href="javascript:void(0);" onClick="added_friend_to_group(change_{$env.area_id}, {$item.BLOG_FRIEND_ID});">[Добавить в группу]</a>
                            </div>
                        {/foreach}
                        {/if}
                        </td>
                    </tr>
                </table>
            </td>
            <td>
                <table border="1">
                <tr>
                    <td>
                        {if $BLOG_FRIENDS} 
                        {foreach from=$BLOG_FRIENDS item=item} 
                            <div id="div_in_{$item.BLOG_FRIEND_ID}__{$env.area_id}" style="{if $item.SELECTED}visibility:visible;display:{else}visibility:hidden;display:none{/if}">
                                <a href="{$item.PATH}">{$item.TITLE}</a> <a href="javascript:void(0);" onClick="del_friend_from_group(change_{$env.area_id}, {$item.BLOG_FRIEND_ID});">[Удалить из группы]</a>
                            </div>
                        {/foreach}
                        {/if}
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        </table>
    </td>
</tr>
<tr>
    <td>
        <center>
        <INPUT TYPE="HIDDEN" NAME="BLOG_FRIENDGROUP_ID_{$env.area_id}" VALUE="{$BLOG_FRIENDGROUP_ID}">
        <INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
        <INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="save">
        <INPUT TYPE="SUBMIT" VALUE="Сохранить">
        </center>
    </td>
</tr>
</FORM>
{/if}

</table>

<br>

<FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="added_{$env.area_id}">
<table width="90%" border="1" cellspacing="0" cellpadding="0">
<tr>
    <td>
        Добавление группы<br>
        Название
        <INPUT TYPE="TEXT" NAME="TITLE_{$env.area_id}" VALUE="" style="width:200px;">
        Порядок
        <INPUT TYPE="TEXT" NAME="LIST_ORDER_{$env.area_id}" VALUE="" style="width:50px;">
        <INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
        <INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="add">
        <INPUT TYPE="SUBMIT" VALUE="Добавить">
    </td>
</tr>
</table>
</FORM>
</center>

<!-- /ГРУППЫ ДРУЗЕЙ -->
{/if}




{if $MODE_FRIENDS}
<!-- ДРУЗЬЯ -->

<script language="JavaScript">
function del_friend(form_name, friend_id){ldelim}
    var _form = eval('document.'+form_name);
    _form.BLOG_FRIEND_ID_{$env.area_id}.value = friend_id;
    _form.submit();
{rdelim}

function change_group(form_name){ldelim}
    var _form = eval('document.'+form_name);
    var group_id = _form.BLOG_FRIENDGROUP_ID_{$env.area_id}.value;
    var str = new String(document.location);
    var pos = str.lastIndexOf('blog_friendgroup_id_{$env.area_id}=');
    if (pos==-1){ldelim}
        document.location = document.location + '&blog_friendgroup_id_{$env.area_id}=' + group_id;
    {rdelim}else{ldelim}
        document.location = str.substr(0,pos) + 'blog_friendgroup_id_{$env.area_id}=' + group_id;
    {rdelim}
{rdelim}
</script>

<center>
<table width="90%" border="0" cellspacing="0" cellpadding="0">

{if $ERROR_1}
<tr>
    <td colspan="2"><font color="EE3333">Уважаемый пользователь,будьте внимательны! Пользователя с указанным ником не существует.</font></td>
</tr>
{/if}

{if $ERROR_2}
<tr>
    <td colspan="2"><font color="EE3333">Уважаемый пользователь,будьте внимательны! Вы указали свой собственный ник.</font></td>
</tr>
{/if}

{if $ERROR_3}
<tr>
    <td colspan="2"><font color="EE3333">Уважаемый пользователь,будьте внимательны! Пользователь с указанным ником уже является вашим другом.</font></td>
</tr>
{/if}

{if $ERROR_4}
<tr>
    <td colspan="2"><font color="EE3333">Уважаемый пользователь,будьте внимательны! Вы уже являетесь членом указанного сообщества.</font></td>
</tr>
{/if}

{if $BLOG_FRIENDGROUP} 
<FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="view_{$env.area_id}">
<tr>
    <td>Группа:</td>
    <td>
        <SELECT NAME="BLOG_FRIENDGROUP_ID_{$env.area_id}" >
            <OPTION VALUE="">все</OPTION>
            {foreach from=$BLOG_FRIENDGROUP item=item}
                <OPTION VALUE="{$item.BLOG_FRIENDGROUP_ID}" {if $item.SELECTED} selected{/if} >{$item.TITLE}</OPTION>
            {/foreach}
        </SELECT>
        <INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
        <INPUT TYPE="BUTTON" VALUE="Обновить" onClick="javascript: change_group('view_{$env.area_id}');">
    </td>
</tr>
</FORM>
{/if}

<FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="change_{$env.area_id}">
<INPUT TYPE="HIDDEN" NAME="BLOG_FRIEND_ID_{$env.area_id}" VALUE="">
<INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="del">
<INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
<tr>
    <td valign="top">Друзья:</td>
    <td>
        <table border="1">
        <tr>
            <td>ник</td>
            <td>группа</td>
            <td>&nbsp;</td>
        </tr>
        {if $BLOG_FRIENDS}
        {foreach from=$BLOG_FRIENDS item=item}
        <tr>
            <td><a href="{$item.PATH}">{$item.TITLE}</a></td>
            <td>
                {if $item.GROUPS}
                {foreach from=$item.GROUPS item=item_group name=foreach_groups} 
                    {if !$smarty.foreach.foreach_groups.first}<br>{/if}{$item_group.TITLE}
                {/foreach}
                {/if}
            </td>
            <td>
                <a href="javascript: void(0);" onClick="javascript: if ( !confirm('Пользователь {$item.TITLE} будет удален из списка друзей. Удалить?')){ldelim} return false; {rdelim}else{ldelim} del_friend('change_{$env.area_id}', {$item.BLOG_FRIEND_ID}); {rdelim}">Удалить</a>
            </td>
        </tr>
        {/foreach}
        {/if}
        </table>
    </td>
</tr>
</FORM>

</table>

<br>

<FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="added_{$env.area_id}">
<table width="90%" border="1" cellspacing="0" cellpadding="0">
<tr>
    <td>
        Добавление друга: 
        <INPUT TYPE="TEXT" NAME="NICK_{$env.area_id}" VALUE="{$NICK}" style="width:250px;">
        <INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
        <INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="add">
        <INPUT TYPE="SUBMIT" VALUE="Добавить">
    </td>
</tr>
</table>
</FORM>
</center>

<!-- /ДРУЗЬЯ -->
{/if}




{if $MODE_TAGS}
<!-- ТЕГИ -->

<script language="JavaScript">
function editTag(form_name, tad_id){ldelim}
    showHide(document.getElementById('div_title'+tad_id+'_{$env.area_id}'));
    showHide(document.getElementById('div_title_edit'+tad_id+'_{$env.area_id}'));
{rdelim}

function delTag(form_name, tad_id){ldelim}
    var _form = eval('document.'+form_name);
    _form.action_{$env.area_id}.value = "del";
    _form.submit();
{rdelim}

</script>

<center>
<table width="90%" border="1" cellspacing="0" cellpadding="0">
{if $BLOG_TAGS} 
<tr>
    <td><a href="index.php?submode_{$env.area_id}=tags&ord_{$env.area_id}=t{if $ORD_TA}d{else}a{/if}">Тег</a></td>
    <td><center><a href="index.php?submode_{$env.area_id}=tags&ord_{$env.area_id}=r{if $ORD_RA}d{else}a{/if}">Количество записей</a></center></td>
    <td>&nbsp;</td>
</tr>
{foreach from=$BLOG_TAGS item=item}
<FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="change{$item.BLOG_TAG_ID}_{$env.area_id}">
<tr>
    <td>
        <div id="div_title{$item.BLOG_TAG_ID}_{$env.area_id}" style="visibility:visible;display:">
            <a href="{$PATH_TO_BLOG}?blog_tag_id={$item.BLOG_TAG_ID}">{$item.TITLE}</a>
        </div>
        <div id="div_title_edit{$item.BLOG_TAG_ID}_{$env.area_id}" style="visibility:hidden;display:none">
            <INPUT TYPE="TEXT" NAME="TITLE_{$env.area_id}" VALUE="{$item.TITLE}">
            <INPUT TYPE="SUBMIT" VALUE="Сохранить">
        </div>
    </td>
    <td><center>{$item.RATING}</center></td>
    <td>
        <a href="javascript:void(0);" onClick="editTag('change{$item.BLOG_TAG_ID}_{$env.area_id}', {$item.BLOG_TAG_ID});">Переименовать</a>
        &nbsp;&nbsp;<a href="javascript:void(0);" onClick="javascript: if ( !confirm('Тег будет удален. Удалить?')){ldelim} return false; {rdelim}else{ldelim} delTag('change{$item.BLOG_TAG_ID}_{$env.area_id}', {$item.BLOG_TAG_ID});{rdelim}">Удалить</a>
    </td>
</tr>
<INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="save">
<INPUT TYPE="HIDDEN" NAME="BLOG_TAG_ID_{$env.area_id}" VALUE="{$item.BLOG_TAG_ID}">
<INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
</FORM>
{/foreach}
{/if}
</table>

<br>

<FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="added_{$env.area_id}">
<table width="90%" border="1" cellspacing="0" cellpadding="0">
<tr>
    <td>
        Добавление тега<br>
        Название
        <INPUT TYPE="TEXT" NAME="TITLE_{$env.area_id}" VALUE="" style="width:250px;">
        <INPUT TYPE="SUBMIT" VALUE="Добавить">
    </td>
</tr>
</table>
<INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="add">
<INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
</FORM>
</center>

<!-- /ТЕГИ -->
{/if}




{if $MODE_OFFERS}
<!-- ПРИГЛАШЕНИЯ -->

<script language="JavaScript">
function change_status(form_name, friend_id, community_id, action){ldelim}
    var _form = eval('document.'+form_name);
    _form.BLOG_FRIEND_ID_{$env.area_id}.value = friend_id;
    _form.COMMUNITY_ID_{$env.area_id}.value = community_id;
    _form.action_{$env.area_id}.value = action;
    _form.submit();
{rdelim}

</script>

<center>
<table width="90%" border="1" cellspacing="0" cellpadding="0">

<FORM ACTION="" METHOD="POST" NAME="change_{$env.area_id}">
<INPUT TYPE="HIDDEN" NAME="BLOG_FRIEND_ID_{$env.area_id}" VALUE="">
<INPUT TYPE="HIDDEN" NAME="COMMUNITY_ID_{$env.area_id}" VALUE="">
<INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="">
<INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
<tr>
    <td><center>Создано</center></td>
    <td><center>Название&nbsp;сообщества</center></td>
    <td><center>Пригласил</center></td>
    <td><center>&nbsp;</center></td>
</tr>
{if $BLOG_FRIENDS}
{foreach from=$BLOG_FRIENDS item=item}
<tr>
    <td><center>{$item.ADDED_DATE}</center></td>
    <td><center><a href="{$item.COMMUNITY_PATH}">{$item.COMMUNITY_TITLE}</a></center></td>
    <td><center><a href="{$item.INVITER_PATH}">{$item.INVITER_TITLE}</a></center></td>
    <td>
        <center>
            <a href="javascript: void(0);" onClick="javascript: if ( !confirm('Вы действительно хотите вступить в сообщество?')) return false; else change_status('change_{$env.area_id}', {$item.BLOG_FRIEND_ID}, {$item.COMMUNITY_ID}, 'add');">Вступить</a>
            <a href="javascript: void(0);" onClick="javascript: if ( !confirm('Вы действительно не хотите вступить в сообщество?')) return false; else change_status('change_{$env.area_id}', {$item.BLOG_FRIEND_ID}, {$item.COMMUNITY_ID}, 'del');">Отказаться</a>
        </center>
    </td>
</tr>
{/foreach}
{/if}
</FORM>

</table>
</center>

<!-- /ПРИГЛАШЕНИЯ -->
{/if}
