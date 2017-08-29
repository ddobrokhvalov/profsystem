<script type="text/javascript" src="/common/js/core/translate.js.php?lang={$env.lang_root_dir}"></script>
<script type="text/javascript" src="/common/js/core/check_form.js"></script>

<!-- собираем заголовок -->
<center>
<b>
    {if $MODE_LIST}
        Список&nbsp;сообществ&nbsp;пользователя&nbsp;{$BLOG_USER}
    {else}
        {$TITLE}&nbsp;:&nbsp;
        {if $MODE_PERSONDATA}редактирование&nbsp;профиля{/if}
        {if $MODE_IMAGES}управление&nbsp;изображениями{/if}
        {if $MODE_PARTY}управление&nbsp;участниками{/if}
        {if $MODE_TAGS}управление&nbsp;списком&nbsp;тегов{/if}
    {/if}
</b>
</center>
<!-- /собираем заголовок -->


<!-- фомируем ссылки по закладкам -->
{if !$MODE_LIST}
{if $MODE_PERSONDATA}<b>Описание</b>{else}<a href="index.php?submode_{$env.area_id}=profile&community_id_{$env.area_id}={$COMMUNITY_ID}">Описание</a>{/if}
&nbsp;&nbsp;
{if $MODE_IMAGES}<b>Изображения</b>{else}<a href="index.php?submode_{$env.area_id}=images&community_id_{$env.area_id}={$COMMUNITY_ID}">Изображения</a>{/if}
&nbsp;&nbsp;
{if $MODE_PARTY}<b>Участники</b>{else}<a href="index.php?submode_{$env.area_id}=party&community_id_{$env.area_id}={$COMMUNITY_ID}">Участники</a>{/if}
&nbsp;&nbsp;
{if $MODE_TAGS}<b>Теги</b>{else}<a href="index.php?submode_{$env.area_id}=tags&community_id_{$env.area_id}={$COMMUNITY_ID}">Теги</a>{/if}
{/if}
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


{if $MODE_LIST}
<!-- СПИСОК -->

<script language="JavaScript">
function del_community(form_name, community_id){ldelim}
    var _form = eval('document.'+form_name);
    _form.COMMUNITY_ID_{$env.area_id}.value = community_id;
    _form.action_{$env.area_id}.value = 'del';
    _form.submit();
{rdelim}
</script>

<center>
<FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="change_{$env.area_id}">
<INPUT TYPE="HIDDEN" NAME="COMMUNITY_ID_{$env.area_id}" VALUE="">
<INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="">
<INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
</FORM>

<table width="90%" border="1" cellspacing="0" cellpadding="0">
<tr>
    <td>Название</td>
    <td>Заголовок</td>
    <td>&nbsp;</td>
</tr>
{if $COMMUNITIES} 
{foreach from=$COMMUNITIES item=item}
<tr>
    <td><a href="{$item.PATH}">{$item.TITLE}</a></td>
    <td>{$item.NAME}</td>
    <td>
        <a href="index.php?submode_{$env.area_id}=profile&community_id_{$env.area_id}={$item.BLOG_ID}">Редактировать</a>
        <a href="javascript: void(0);" onClick="javascript: if ( !confirm('Сообщество будет удалено. Удалить?')) return false; else del_community('change_{$env.area_id}', {$item.BLOG_ID});">Удалить</a>
    </td>
</tr>
{/foreach}
{/if}
</table>
</center>

<!-- /СПИСОК -->
{/if}





{if $MODE_PERSONDATA}
<!-- ОПИСАНИЕ -->

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
    <td>Заголовок сообщества</td>
    <td><INPUT TYPE="TEXT" NAME="NAME_{$env.area_id}" VALUE="{$NAME}" style="width:400px;"></td>
    <td align="center"><INPUT TYPE="checkbox" NAME="SHOW_FIELDS_{$env.area_id}[]" VALUE="{$FIELD_NAME_ID}" {if $FIELD_NAME_CHECKED} checked="checked"{/if}></td>
</tr>

<tr>
    <td>Страна</td>
    <td>
        <SELECT NAME="BLOG_COUNTRY_ID_{$env.area_id}"  onChange="change_country('change_{$env.area_id}');" >
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
        <SELECT NAME="BLOG_CITY_ID_{$env.area_id}" >
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
    <td><INPUT TYPE="TEXT" NAME="HOMEPAGE_{$env.area_id}" VALUE="{$HOMEPAGE}" style="width:400px;"></td>
    <td align="center"><INPUT TYPE="checkbox" NAME="SHOW_FIELDS_{$env.area_id}[]" VALUE="{$FIELD_HOMEPAGE_ID}" {if $FIELD_HOMEPAGE_CHECKED} checked="checked"{/if}></td>
</tr>

<tr>
    <td>О себе</td>
    <td><TEXTAREA  ROWS="5" STYLE="width:400px;" WRAP="soft" NAME="ABOUT_{$env.area_id}" >{$ABOUT}</TEXTAREA></td>
    <td align="center"><INPUT TYPE="checkbox" NAME="SHOW_FIELDS_{$env.area_id}[]" VALUE="{$FIELD_ABOUT_ID}" {if $FIELD_ABOUT_CHECKED} checked="checked"{/if}></td>
</tr>

<tr>
    <td>Интересы</td>
    <td>
        Список слов/фраз, разделённых запятой.
        <br><TEXTAREA  ROWS="5" STYLE="width:400px;" WRAP="soft" NAME="INTEREST_{$env.area_id}" >{$INTEREST}</TEXTAREA>
    </td>
    <td align="center"><INPUT TYPE="checkbox" NAME="SHOW_FIELDS_{$env.area_id}[]" VALUE="{$FIELD_INTEREST_ID}" {if $FIELD_INTEREST_CHECKED} checked="checked"{/if}></td>
</tr>

<tr>
    <td>В блоге и ленте участников </td>
    <td colspan="2">
        <SELECT NAME="POSTS_ON_PAGE_{$env.area_id}" >
            {foreach from=$POSTS_ON_PAGE item=item}
                <OPTION VALUE="{$item.POSTS_ON_PAGE_VALUE}" {$item.SELECTED} >{$item.POSTS_ON_PAGE_NAME}</OPTION>
            {/foreach}
        </SELECT>
        записей на странице
    </td>
</tr>

<tr>
    <td>Условие вступления *:</td>
    <td colspan="2">
        <input type="radio" name="MEMBERSHIP_{$env.area_id}" value="1" lang="errors_nonempty__radio_" {if $MEMBERSHIP1} checked{/if}> свободное<br>
        <input type="radio" name="MEMBERSHIP_{$env.area_id}" value="2" lang="errors_nonempty__radio_" {if $MEMBERSHIP2} checked{/if}> модерируемое
    </td>
</tr>
<tr>
    <td>Добавление записей *:</td>
    <td colspan="2">
        <input type="radio" name="POSTLEVEL_{$env.area_id}" value="1" lang="errors_nonempty__radio_" {if $POSTLEVEL1} checked{/if}> неограниченное<br>
        <input type="radio" name="POSTLEVEL_{$env.area_id}" value="2" lang="errors_nonempty__radio_" {if $POSTLEVEL2} checked{/if}> ограниченное
    </td>
</tr>
<tr>
    <td>Модерация записей *:</td>
    <td colspan="2">
        <input type="radio" name="MODERATION_{$env.area_id}" value="1" lang="errors_nonempty__radio_" {if $MODERATION1} checked{/if}> не производится<br>
        <input type="radio" name="MODERATION_{$env.area_id}" value="2" lang="errors_nonempty__radio_" {if $MODERATION2} checked{/if}> производится
    </td>
</tr>

 <tr>
    <td colspan="3"><center><INPUT TYPE="SUBMIT" NAME="save_{$env.area_id}" VALUE="Сохранить"></center></td>
</tr>
</table>
<INPUT TYPE="HIDDEN" NAME="submode_{$env.area_id}" VALUE="{$SUBMODE}">
<INPUT TYPE="HIDDEN" NAME="COMMUNITY_ID_{$env.area_id}" VALUE="{$COMMUNITY_ID}">
<INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
</FORM>
</center>

<!-- /ОПИСАНИЕ -->
{/if}





{if $MODE_IMAGES}
<!-- ИЗОБРАЖЕНИЯ -->

<script language="JavaScript">
function change_status_image(form_name, id_image, _img_file, _title, action){ldelim}
    var _form = eval('document.'+form_name);
    CheckForm.oForm = _form;
    if ( !( CheckForm[CheckForm.aCheckHandlers['_nonempty_']['method']](_img_file) ) ){ldelim}
        alert( Dictionary.translate( CheckForm.aCheckHandlers['_nonempty_']['message'] ) );
        try {ldelim} _img_file.focus() {rdelim} catch (e) {ldelim}{rdelim};
    {rdelim}else{ldelim}
        if ( !( CheckForm[CheckForm.aCheckHandlers['_nonempty_']['method']](_title) ) ){ldelim}
            alert( Dictionary.translate( CheckForm.aCheckHandlers['_nonempty_']['message'] ) );
            try {ldelim} _title.focus() {rdelim} catch (e) {ldelim}{rdelim};
        {rdelim}else{ldelim}
            _form.action_{$env.area_id}.value = action;
            _form.BLOG_IMAGE_ID_{$env.area_id}.value = id_image;
            _form.submit();
        {rdelim}
    {rdelim}
{rdelim}
function save_image(form_name, id_image){ldelim}
    var _img_file = eval('document.'+form_name+'.IMG'+id_image+'IMG_{$env.area_id}_file');
    var _title = eval('document.'+form_name+'.TITLE'+id_image+'TITLE_{$env.area_id}');
    change_status_image(form_name, id_image, _img_file, _title, 'save');
{rdelim}
function del_image(form_name, id_image){ldelim}
    var _form = eval('document.'+form_name);
    _form.action_{$env.area_id}.value = 'del';
    _form.BLOG_IMAGE_ID_{$env.area_id}.value = id_image;
    _form.submit();
{rdelim}
function add_image(form_name){ldelim}
    var _img_file = eval('document.'+form_name+'.IMG_{$env.area_id}_file');
    var _title = eval('document.'+form_name+'.TITLE_{$env.area_id}');
    change_status_image(form_name, '', _img_file, _title, 'add');
{rdelim}
</script>

<center>
<FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="change_{$env.area_id}">
<table width="90%" border="1" cellspacing="0" cellpadding="0">
{if $ERROR_IMAGE_1} 
<tr>
    <td colspan="2"><center><font color="EE3333">Превышен размер файла, разрешёный администратором.</font></center></td>
</tr>
{/if} 
{if $ERROR_IMAGE_2} 
<tr>
    <td colspan="2"><center><font color="EE3333">Число ваших изображений достигло максимума.</font></center></td>
</tr>
{/if} 
{if $ERROR_IMAGE_3} 
<tr>
    <td colspan="2"><center><font color="EE3333">Ширина изображения больше разрешённой.</font></center></td>
</tr>
{/if} 
{if $ERROR_IMAGE_4} 
<tr>
    <td colspan="2"><center><font color="EE3333">Высота изображения больше разрешённой.</font></center></td>
</tr>
{/if} 

{if $BLOG_IMAGES} 
{foreach from=$BLOG_IMAGES item=item}
<tr>
    <td width="10%"><img src="{$item.IMG}"></td>
    <td>
        <table>
        <tr>
            <td>Изображение</td>
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
                    <INPUT TYPE="BUTTON" VALUE="Удалить" onClick="del_image('change_{$env.area_id}',{$item.BLOG_IMAGE_ID});">
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
            <td>Изображение</td>
            <td><INPUT TYPE="FILE" NAME="IMG_{$env.area_id}_file"></td>
        </tr>
        <tr>
            <td>Название</td>
            <td><INPUT TYPE="TEXT" NAME="TITLE_{$env.area_id}" VALUE="" style="width:250px;"></td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td><INPUT TYPE="radio" NAME="IS_DEFAULT_{$env.area_id}" VALUE="0">по умолчанию</td>
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
<INPUT TYPE="HIDDEN" NAME="submode_{$env.area_id}" VALUE="{$SUBMODE}">
<INPUT TYPE="HIDDEN" NAME="COMMUNITY_ID_{$env.area_id}" VALUE="{$COMMUNITY_ID}">
<INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
</FORM>
</center>

<!-- /ИЗОБРАЖЕНИЯ -->
{/if}





{if $MODE_PARTY}
<!-- УЧАСТНИКИ -->

<script type="text/javascript" src="/common/js/core/calendar.js"></script>
<script language="JavaScript">
function change_status_friend(form_name, blog_friend_id, friend_id, _action){ldelim}
    var _form = eval('document.'+form_name);
    _form.BLOG_FRIEND_ID_{$env.area_id}.value = blog_friend_id;
    _form.FRIEND_ID_{$env.area_id}.value = friend_id;
    _form.action_{$env.area_id}.value = _action;
    _form.submit();
{rdelim}

function del_friend(form_name, blog_friend_id, friend_id){ldelim}
    change_status_friend(form_name, blog_friend_id, friend_id, 'del');
{rdelim}

function save_friend(form_name, blog_friend_id, friend_id){ldelim}
    change_status_friend(form_name, blog_friend_id, friend_id, 'save');
{rdelim}

function del_new_friend(form_name, blog_friend_id, friend_id){ldelim}
    change_status_friend(form_name, blog_friend_id, friend_id, 'del_inquiry');
{rdelim}

function add_new_friend(form_name, blog_friend_id, friend_id){ldelim}
    change_status_friend(form_name, blog_friend_id, friend_id, 'add_inquiry');
{rdelim}

function del_invite(form_name, blog_friend_id, friend_id){ldelim}
    change_status_friend(form_name, blog_friend_id, friend_id, 'del_invite');
{rdelim}

function replace_filter(str, name, value){ldelim}
    var pos = str.lastIndexOf(name);
    if ( pos == -1 )
        return str + '&' + name + '=' + value;
    pos1 = str.indexOf('&', pos);
    if ( pos1 == -1 )
        return str.substr(0,pos) + name + '=' + value;
    return str.substr(0,pos) + name + '=' + value + str.substr(pos1);
{rdelim}
function change_filter_f(form_name){ldelim}
    var _form = eval('document.'+form_name);
    var ff_ds = _form.ff_ds_{$env.area_id}.value;
    var ff_de = _form.ff_de_{$env.area_id}.value;
    var ff_n = _form.ff_n_{$env.area_id}.value;
    var ff_l = _form.ff_l_{$env.area_id}.value;
    var ff_m = _form.ff_m_{$env.area_id}.value;
    var ff_c = _form.ff_c_{$env.area_id}.value;
    
    var str = new String(document.location);
    str = replace_filter(str, 'ff_ds_{$env.area_id}', ff_ds);
    str = replace_filter(str, 'ff_de_{$env.area_id}', ff_de);
    str = replace_filter(str, 'ff_n_{$env.area_id}', ff_n);
    str = replace_filter(str, 'ff_l_{$env.area_id}', ff_l);
    str = replace_filter(str, 'ff_m_{$env.area_id}', ff_m);
    str = replace_filter(str, 'ff_c_{$env.area_id}', ff_c);
    
    document.location = str;
{rdelim}
function change_filter_i(form_name){ldelim}
    var _form = eval('document.'+form_name);
    var fi_ds = _form.fi_ds_{$env.area_id}.value;
    var fi_de = _form.fi_de_{$env.area_id}.value;
    var fi_n = _form.fi_n_{$env.area_id}.value;
    
    var str = new String(document.location);
    str = replace_filter(str, 'fi_ds_{$env.area_id}', fi_ds);
    str = replace_filter(str, 'fi_de_{$env.area_id}', fi_de);
    str = replace_filter(str, 'fi_n_{$env.area_id}', fi_n);
    
    document.location = str;
{rdelim}
function change_filter_q(form_name){ldelim}
    var _form = eval('document.'+form_name);
    var fq_ds = _form.fq_ds_{$env.area_id}.value;
    var fq_de = _form.fq_de_{$env.area_id}.value;
    var fq_n = _form.fq_n_{$env.area_id}.value;
    
    var str = new String(document.location);
    str = replace_filter(str, 'fq_ds_{$env.area_id}', fq_ds);
    str = replace_filter(str, 'fq_de_{$env.area_id}', fq_de);
    str = replace_filter(str, 'fq_n_{$env.area_id}', fq_n);
    
    document.location = str;
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
    <td colspan="2"><font color="EE3333">Уважаемый пользователь,будьте внимательны! Пользователь с указанным ником уже состоит в сообществе.</font></td>
</tr>
{/if}

{if $ERROR_3}
<tr>
    <td colspan="2"><font color="EE3333">Уважаемый пользователь,будьте внимательны! Пользователь с указанным ником уже приглашен в сообщество.</font></td>
</tr>
{/if}

{if $ERROR_4}
<tr>
    <td colspan="2"><font color="EE3333">Уважаемый пользователь! Вы не можете изменять информацию о себе и удалить себя тоже не можете.</font></td>
</tr>
{/if}

<tr>
    <td><b>Участники</b></td>
</tr>
<tr>
    <td>
        <table width="100%" border="1" cellspacing="0" cellpadding="0">
        <FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="view_friends_{$env.area_id}" ONSUBMIT="return CheckForm.validate( this )">
        <tr>
            <td>
                с:&nbsp;<INPUT TYPE="TEXT" NAME="ff_ds_{$env.area_id}" VALUE="{$ff_ds}" lang="errors_date_" style="width:80px;">&nbsp;<a href="" onclick="Calendar.show( document.view_friends_{$env.area_id}.ff_ds_{$env.area_id}, this ); return false">{$SYSW_CALENDAR}</a>
                <br>
                по:&nbsp;<INPUT TYPE="TEXT" NAME="ff_de_{$env.area_id}" VALUE="{$ff_de}" lang="errors_date_" style="width:80px;">&nbsp;<a href="" onclick="Calendar.show( document.view_friends_{$env.area_id}.ff_de_{$env.area_id}, this ); return false">{$SYSW_CALENDAR}</a>
            </td>
            <td><INPUT TYPE="TEXT" NAME="ff_n_{$env.area_id}" VALUE="{$ff_n}"></td>
            <td align="center">
                <select name="ff_l_{$env.area_id}">
                    <option value="">все</option>
                    <option value="2" {if $ff_l2} selected{/if}>добавление</option>
                    <option value="1" {if $ff_l1} selected{/if}>только чтение</option>
                </select>
            </td>
            <td align="center">
                <select name="ff_m_{$env.area_id}">
                    <option value="">все</option>
                    <option value="1" {if $ff_m1} selected{/if}>медераторы</option>
                    <option value="0" {if $ff_m0} selected{/if}>не модераторы</option>
                </select>
            </td>
            <td align="center">
                <select name="ff_c_{$env.area_id}">
                    <option value="">все</option>
                    <option value="1" {if $ff_c1} selected{/if}>создатель</option>
                    <option value="0" {if $ff_c0} selected{/if}>не создатели</option>
                </select>
            </td>
            <td align="center">
                <INPUT TYPE="BUTTON" VALUE="Обновить" onClick="javascript: change_filter_f('view_friends_{$env.area_id}');">
            </td>
        </tr>
        </FORM>
        <tr>
            <td>Дата&nbsp;добавления</td>
            <td>Ник</td>
            <td align="center">Добавление&nbsp;записей</td>
            <td align="center">Модератор</td>
            <td align="center">Создатель</td>
            <td align="center">&nbsp;</td>
        </tr>
        
        <FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="change_friends_{$env.area_id}">
        <INPUT TYPE="HIDDEN" NAME="BLOG_FRIEND_ID_{$env.area_id}" VALUE="">
        <INPUT TYPE="HIDDEN" NAME="FRIEND_ID_{$env.area_id}" VALUE="">
        <INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="">
        <INPUT TYPE="HIDDEN" NAME="COMMUNITY_ID_{$env.area_id}" VALUE="{$COMMUNITY_ID}">
        <INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
        {if $BLOG_FRIENDS}
        {foreach from=$BLOG_FRIENDS item=item}
        <tr>
            <td>{$item.ADDED_DATE}</td>
            <td><a href="{$item.PATH}">{$item.TITLE}</a></td>
            <td align="center">
                <INPUT TYPE="radio" NAME="LEVEL{$item.BLOG_FRIEND_ID}__{$env.area_id}" VALUE="2" {if $item.LEVEL2} checked{/if}>да
                <INPUT TYPE="radio" NAME="LEVEL{$item.BLOG_FRIEND_ID}__{$env.area_id}" VALUE="1" {if $item.LEVEL1} checked{/if}>нет
            </td>
            <td align="center">
                <INPUT TYPE="checkbox" NAME="IS_MODERATOR{$item.BLOG_FRIEND_ID}__{$env.area_id}" VALUE="1" {if $item.IS_MODERATOR} checked="checked"{/if}>
            </td>
            <td align="center">
                <INPUT TYPE="radio" NAME="IS_CREATOR_{$env.area_id}" VALUE="{$item.BLOG_FRIEND_ID}" {if $item.IS_CREATOR} checked{/if}>
            </td>
            <td align="center">
                <a href="javascript: void(0);" onClick="javascript: save_friend('change_friends_{$env.area_id}', {$item.BLOG_FRIEND_ID}, {$item.FRIEND_ID});">Сохранить</a>
                <a href="javascript: void(0);" onClick="javascript: if ( !confirm('Пользователь {$item.TITLE} будет удален из сообщества. Удалить?')) return false; else del_friend('change_friends_{$env.area_id}', {$item.BLOG_FRIEND_ID}, {$item.FRIEND_ID});">Удалить</a>
            </td>
        </tr>
        {/foreach}
        {/if}
        </FORM>
        </table>
    </td>
</tr>
</table>
<br>

<table width="90%" border="0" cellspacing="0" cellpadding="0">
<tr>
    <td><b>Запросы на вступление</b></td>
</tr>
<tr>
    <td>
        <table width="100%" border="1" cellspacing="0" cellpadding="0">
        <FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="view_friends_inquiry_{$env.area_id}" ONSUBMIT="return CheckForm.validate( this )">
        <tr>
            <td>
                с:&nbsp;<INPUT TYPE="TEXT" NAME="fq_ds_{$env.area_id}" VALUE="{$fq_ds}" lang="errors_date_" style="width:80px;">&nbsp;<a href="" onclick="Calendar.show( document.view_friends_inquiry_{$env.area_id}.fq_ds_{$env.area_id}, this ); return false">{$SYSW_CALENDAR}</a>
                <br>
                по:&nbsp;<INPUT TYPE="TEXT" NAME="fq_de_{$env.area_id}" VALUE="{$fq_de}" lang="errors_date_" style="width:80px;">&nbsp;<a href="" onclick="Calendar.show( document.view_friends_inquiry_{$env.area_id}.fq_de_{$env.area_id}, this ); return false">{$SYSW_CALENDAR}</a>
            </td>
            <td><INPUT TYPE="TEXT" NAME="fq_n_{$env.area_id}" VALUE="{$fq_n}"></td>
            <td align="center">
                <INPUT TYPE="BUTTON" VALUE="Обновить" onClick="javascript: change_filter_q('view_friends_inquiry_{$env.area_id}');">
            </td>
        </tr>
        </FORM>
        <tr>
            <td>Дата&nbsp;добавления</td>
            <td>Ник</td>
            <td align="center">&nbsp;</td>
        </tr>
        
        <FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="change_friends_inquiry_{$env.area_id}">
        <INPUT TYPE="HIDDEN" NAME="BLOG_FRIEND_ID_{$env.area_id}" VALUE="">
        <INPUT TYPE="HIDDEN" NAME="FRIEND_ID_{$env.area_id}" VALUE="">
        <INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="">
        <INPUT TYPE="HIDDEN" NAME="COMMUNITY_ID_{$env.area_id}" VALUE="{$COMMUNITY_ID}">
        <INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
        {if $BLOG_FRIENDS_INQUIRY}
        {foreach from=$BLOG_FRIENDS_INQUIRY item=item}
        <tr>
            <td>{$item.ADDED_DATE}</td>
            <td><a href="{$item.PATH}">{$item.TITLE}</a></td>
            <td align="center">
                <a href="javascript: void(0);" onClick="javascript: add_new_friend('change_friends_inquiry_{$env.area_id}', {$item.BLOG_FRIEND_ID}, {$item.FRIEND_ID});">Принять</a>
                <a href="javascript: void(0);" onClick="javascript: if ( !confirm('Отказать в запросе на вступление?')) return false; else del_new_friend('change_friends_inquiry_{$env.area_id}', {$item.BLOG_FRIEND_ID}, {$item.FRIEND_ID});">Отказать</a>
            </td>
        </tr>
        {/foreach}
        {/if}
        </FORM>
        </table>
    </td>
</tr>
</table>
<br>


<table width="90%" border="0" cellspacing="0" cellpadding="0">
<tr>
    <td><b>Приглашенные</b></td>
</tr>
<tr>
    <td>
        <table width="100%" border="1" cellspacing="0" cellpadding="0">
        <FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="view_friends_invite_{$env.area_id}" ONSUBMIT="return CheckForm.validate( this )">
        <tr>
            <td>
                с:&nbsp;<INPUT TYPE="TEXT" NAME="fi_ds_{$env.area_id}" VALUE="{$fi_ds}" lang="errors_date_" style="width:80px;">&nbsp;<a href="" onclick="Calendar.show( document.view_friends_invite_{$env.area_id}.fi_ds_{$env.area_id}, this ); return false">{$SYSW_CALENDAR}</a>
                <br>
                по:&nbsp;<INPUT TYPE="TEXT" NAME="fi_de_{$env.area_id}" VALUE="{$fi_de}" lang="errors_date_" style="width:80px;">&nbsp;<a href="" onclick="Calendar.show( document.view_friends_invite_{$env.area_id}.fi_de_{$env.area_id}, this ); return false">{$SYSW_CALENDAR}</a>
            </td>
            <td><INPUT TYPE="TEXT" NAME="fi_n_{$env.area_id}" VALUE="{$fi_n}"></td>
            <td align="center">
                <INPUT TYPE="BUTTON" VALUE="Обновить" onClick="javascript: change_filter_i('view_friends_invite_{$env.area_id}');">
            </td>
        </tr>
        </FORM>
        <tr>
            <td>Дата&nbsp;добавления</td>
            <td>Ник</td>
            <td align="center">&nbsp;</td>
        </tr>
        
        <FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="change_friends_invite_{$env.area_id}">
        <INPUT TYPE="HIDDEN" NAME="BLOG_FRIEND_ID_{$env.area_id}" VALUE="">
        <INPUT TYPE="HIDDEN" NAME="FRIEND_ID_{$env.area_id}" VALUE="">
        <INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="">
        <INPUT TYPE="HIDDEN" NAME="COMMUNITY_ID_{$env.area_id}" VALUE="{$COMMUNITY_ID}">
        <INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
        {if $BLOG_FRIENDS_INVITE}
        {foreach from=$BLOG_FRIENDS_INVITE item=item}
        <tr>
            <td>{$item.ADDED_DATE}</td>
            <td><a href="{$item.PATH}">{$item.TITLE}</a></td>
            <td align="center">
                <a href="javascript: void(0);" onClick="javascript: if ( !confirm('Приглашение пользователя {$item.TITLE} будет удалено. Удалить?')) return false; else del_invite('change_friends_{$env.area_id}', {$item.BLOG_FRIEND_ID}, {$item.FRIEND_ID});">Удалить</a>
            </td>
        </tr>
        {/foreach}
        {/if}
        </FORM>
        </table>
    </td>
</tr>
</table>
<br>


<br>

<table width="90%" border="1" cellspacing="0" cellpadding="0">
<FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="added_{$env.area_id}">
<tr>
    <td>
        Приглашение участника: 
    </td>
</tr>
<tr>
    <td>
        Ник: 
        <INPUT TYPE="TEXT" NAME="NICK_{$env.area_id}" VALUE="{$NICK}" style="width:250px;">
        <INPUT TYPE="HIDDEN" NAME="COMMUNITY_ID_{$env.area_id}" VALUE="{$COMMUNITY_ID}">
        <INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
        <INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="add">
        <INPUT TYPE="SUBMIT" VALUE="Пригласить">
    </td>
</tr>
</table>
</FORM>
</center>

<!-- /УЧАСТНИКИ -->
{/if}





{if $MODE_TAGS}
<!-- ТЕГИ -->

<script language="JavaScript">
function editTag(form_name, tad_id){ldelim}
    showHide(document.getElementById('div_title'+tad_id+'__{$env.area_id}'));
    showHide(document.getElementById('div_title_edit'+tad_id+'__{$env.area_id}'));
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
    <td><a href="index.php?submode_{$env.area_id}=tags&community_id_{$env.area_id}={$COMMUNITY_ID}&ord_{$env.area_id}=t{if $ORD_TA}d{else}a{/if}">Тег</a></td>
    <td><center><a href="index.php?submode_{$env.area_id}=tags&community_id_{$env.area_id}={$COMMUNITY_ID}&ord_{$env.area_id}=r{if $ORD_RA}d{else}a{/if}">Количество записей</a></center></td>
    <td>&nbsp;</td>
</tr>
{foreach from=$BLOG_TAGS item=item}
<TMPL_LOOP NAME="BLOG_TAGS"> 
<FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="change{$item.BLOG_TAG_ID}__{$env.area_id}">
<tr>
    <td>
        <div id="div_title{$item.BLOG_TAG_ID}__{$env.area_id}" style="visibility:visible;display:">
            <a href="{$PATH_TO_BLOG}?blog_tag_id={$item.BLOG_TAG_ID}">{$item.TITLE}</a>
        </div>
        <div id="div_title_edit{$item.BLOG_TAG_ID}__{$env.area_id}" style="visibility:hidden;display:none">
            <INPUT TYPE="TEXT" NAME="TITLE_{$env.area_id}" VALUE="{$item.TITLE}">
            <INPUT TYPE="SUBMIT" VALUE="Сохранить">
        </div>
    </td>
    <td><center>{$item.RATING}</center></td>
    <td>
        <a href="javascript:void(0);" onClick="editTag('change{$item.BLOG_TAG_ID}__{$env.area_id}', {$item.BLOG_TAG_ID});">Переименовать</a>
        &nbsp;&nbsp;<a href="javascript:void(0);" onClick="javascript: if ( !confirm('Тег будет удален. Удалить?')) return false; else delTag('change{$item.BLOG_TAG_ID}__{$env.area_id}', {$item.BLOG_TAG_ID});">Удалить</a>
    </td>
</tr>
<INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="save">
<INPUT TYPE="HIDDEN" NAME="BLOG_TAG_ID_{$env.area_id}" VALUE="{$item.BLOG_TAG_ID}">
<INPUT TYPE="HIDDEN" NAME="submode_{$env.area_id}" VALUE="{$SUBMODE}">
<INPUT TYPE="HIDDEN" NAME="COMMUNITY_ID_{$env.area_id}" VALUE="{$COMMUNITY_ID}">
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
<INPUT TYPE="HIDDEN" NAME="submode_{$env.area_id}" VALUE="{$SUBMODE}">
<INPUT TYPE="HIDDEN" NAME="COMMUNITY_ID_{$env.area_id}" VALUE="{$COMMUNITY_ID}">
<INPUT TYPE="HIDDEN" NAME="BLOG_ID_{$env.area_id}" VALUE="{$BLOG_ID}">
</FORM>
</center>

<!-- /ТЕГИ -->
{/if}
