<script type="text/javascript" src="/common/js/core/translate.js.php?lang={$env.lang_root_dir}"></script>
<script type="text/javascript" src="/common/js/core/check_form.js"></script>
<script type="text/javascript" src="/common/js/core/calendar.js"></script>

<script langiage="JavaScript">
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

function change_image(form_name){ldelim}
    var _form = eval('document.'+form_name);
    var image_arr = Array();
    {if $BLOG_IMAGE_BLOG_IMAGE_ID}
    {foreach from=$BLOG_IMAGE_BLOG_IMAGE_ID item=item}
        image_arr["{$item.BLOG_IMAGE_ID}"] = "{$item.IMG}";
    {/foreach}
    {/if}
    var image_value = _form.BLOG_IMAGE_ID_{$env.area_id}.value;
    if (image_value != ""){ldelim}
        showElement(document.getElementById('div_image_{$env.area_id}'));
        document.getElementById('id_image_{$env.area_id}').src = image_arr[image_value];
    {rdelim}else{ldelim}
        hideElement(document.getElementById('div_image_{$env.area_id}'));
    {rdelim}
{rdelim}

function change_image_mood(form_name){ldelim}
    var _form = eval('document.'+form_name);
    var image_arr = Array();
    {if $BLOG_MOOD_BLOG_MOOD_ID}
    {foreach from=$BLOG_MOOD_BLOG_MOOD_ID item=item}
        image_arr["{$item.BLOG_MOOD_ID}"] = "{$item.IMAGE}";
    {/foreach}
    {/if}
    var image_value = _form.BLOG_MOOD_ID_{$env.area_id}.value;
    if (image_value != ""){ldelim}
        showElement(document.getElementById('div_image_mood_{$env.area_id}'));
        document.getElementById('id_image_mood_{$env.area_id}').src = image_arr[image_value];
    {rdelim}else{ldelim}
        hideElement(document.getElementById('div_image_mood_{$env.area_id}'));
    {rdelim}
{rdelim}

function change_access(form_name){ldelim}
    var _form = eval('document.'+form_name);
    var access_value = _form.ACCESS_{$env.area_id}.value;
    if (access_value == 4){ldelim}
        showElement(document.getElementById('div_access_group_{$env.area_id}'));
    {rdelim}else{ldelim}
        hideElement(document.getElementById('div_access_group_{$env.area_id}'));
    {rdelim}
{rdelim}

function change_blog(form_name){ldelim}
    var _form = eval('document.'+form_name);
    var blog_id = _form.BLOG_ID_{$env.area_id}.value;
    _form.ACCESS_{$env.area_id}.options.length = 0;
    if ( blog_id == {$BLOG_ID_FOR_JS} ){ldelim}
        _form.ACCESS_{$env.area_id}.options[0] = new Option('для всех', '1');
        _form.ACCESS_{$env.area_id}.options[1] = new Option('для друзей', '2');
        _form.ACCESS_{$env.area_id}.options[2] = new Option('личное', '3');
        _form.ACCESS_{$env.area_id}.options[3] = new Option('выборочно', '4');
    {rdelim}else{ldelim}
        _form.ACCESS_{$env.area_id}.options[0] = new Option('для всех', '1');
        _form.ACCESS_{$env.area_id}.options[1] = new Option('для друзей', '2');
    {rdelim}
    change_access(form_name);
    
    var blog_arr = new Array();
    {if $BLOG_BLOG_ID} 
    {foreach from=$BLOG_BLOG_ID item=item}
        blog_arr.push({$item.BLOG_ID});
    {/foreach}
    {/if}
    for ( i=0; i<blog_arr.length; i++){ldelim}
        hideElement(document.getElementById('div_tags'+blog_arr[i]+'__{$env.area_id}'));
    {rdelim}
    showElement(document.getElementById('div_tags'+blog_id+'__{$env.area_id}'));
{rdelim}

</script>

<center><b>{$BLOG_USER}&nbsp;:&nbsp;{if $EDIT}редактирование{else}добавление{/if}&nbsp;записи</b></center>

<br>

<table  width="100%">
<FORM ACTION="" METHOD="POST" ENCTYPE="multipart/form-data" NAME="change_{$env.area_id}" ONSUBMIT="return CheckForm.validate( this )">
<tr>
    <td>Дата добавления</td>
    <td>
        <INPUT TYPE="TEXT" NAME="ADDED_DATE_{$env.area_id}" VALUE="{$ADDED_DATE}" lang="errors_nonempty__datetime_" style="width:110px;">
        <a href="" onclick="Calendar.show( document.change_{$env.area_id}.ADDED_DATE_{$env.area_id}, this, 'long' ); return false">{$SYSW_CALENDAR}</a>
    </td>
</tr>

<tr>
    <td>Опубликовать в:</td>
    <td>
        <SELECT NAME="BLOG_ID_{$env.area_id}" onChange="change_blog('change_{$env.area_id}');">
            {if $BLOG_BLOG_ID} 
            {foreach from=$BLOG_BLOG_ID item=item}
                <OPTION VALUE="{$item.BLOG_ID}" {$item.SELECTED} >{$item.TITLE}</OPTION>
            {/foreach}
            {/if}
        </SELECT>
    </td>
</tr>

<tr>
    <td>Изображение</td>
    <td>
        <SELECT NAME="BLOG_IMAGE_ID_{$env.area_id}" onChange="change_image('change_{$env.area_id}');">
            <OPTION VALUE="">
            {foreach from=$BLOG_IMAGE_BLOG_IMAGE_ID item=item}
                <OPTION VALUE="{$item.BLOG_IMAGE_ID}" {$item.SELECTED} >{$item.BLOG_IMAGE_NAME}</OPTION>
            {/foreach}
        </SELECT>
        <div id="div_image_{$env.area_id}" style="visibility:hidden;display:none"><img id="id_image_{$env.area_id}" name="image_{$env.area_id}" src=""></div>
        {foreach from=$BLOG_IMAGE_BLOG_IMAGE_ID item=item}
            {if $item.SELECTED}
                <script langiage="JavaScript">
                    showElement(document.getElementById('div_image_{$env.area_id}'));
                    document.getElementById('id_image_{$env.area_id}').src = '{$item.IMG}';
                </script>
            {/if}
        {/foreach}
    </td>
</tr>

<tr>
    <td>Тема записи</td>
    <td><INPUT TYPE="TEXT" NAME="TITLE_{$env.area_id}" VALUE="{$TITLE}"></td>
</tr>

<tr>
    <td>
        Текст
        <!-- a href="javascript: void(0);" onClick="open_BODY();" title="редактировать в визуальном редакторе">Текст</a>
        <script language="javascript">
            <!--
            function open_BODY () {ldelim}
                window.open('',"window_BODY","scrollbars=1,status=1,statusbar=1,resizable=1,resize");
                document.editorForm.target = 'window_BODY';
                document.editorForm.object_name.value = 'BODY_{$env.area_id}';
                document.editorForm.object_value.value = document.change_{$env.area_id}.BODY_{$env.area_id}.value;
                document.editorForm.submit();
            {rdelim}
            //-->
        </script -->
    </td>
    <td><TEXTAREA  ROWS="10" WRAP="soft" NAME="BODY_{$env.area_id}" lang="errors_nonempty_">{$BODY}</TEXTAREA></td>
</tr>


<tr>
    <td>Текущая музыка</td>
    <td><INPUT TYPE="TEXT" NAME="CURRENT_MUSIC_{$env.area_id}" VALUE="{$CURRENT_MUSIC}"></td>
</tr>

<tr>
    <td>Настроение</td>
    <td>
        <SELECT NAME="BLOG_MOOD_ID_{$env.area_id}" onChange="change_image_mood('change_{$env.area_id}');">
            <OPTION VALUE="">
            {foreach from=$BLOG_MOOD_BLOG_MOOD_ID item=item}
                <OPTION VALUE="{$item.BLOG_MOOD_ID}" {$item.SELECTED} >{$item.BLOG_MOOD_NAME}</OPTION>
            {/foreach}
        </SELECT>
        <div id="div_image_mood_{$env.area_id}" style="visibility:hidden;display:none"><img id="id_image_mood_{$env.area_id}" name="image_mood_{$env.area_id}" src=""></div>
        {foreach from=$BLOG_MOOD_BLOG_MOOD_ID item=item}
            {if $item.SELECTED}
                <script langiage="JavaScript">
                    showElement(document.getElementById('div_image_mood_{$env.area_id}'));
                    document.getElementById('id_image_mood_{$env.area_id}').src = '{$item.IMAGE}';
                </script>
            {/if}
        {/foreach}
    </td>
</tr>

<tr>
    <td>Разрешение на просмотр</td>
    <td>
        <SELECT NAME="ACCESS_{$env.area_id}"  onChange="change_access('change_{$env.area_id}');">
            <OPTION VALUE="">
            {foreach from=$ACCESS item=item}
                <OPTION VALUE="{$item.ACCESS_VALUE}" {$item.SELECTED} >{$item.ACCESS_NAME}</OPTION>
            {/foreach}
        </SELECT>
        <div id="div_access_group_{$env.area_id}" style="visibility:hidden;display:none">
            <table>
                <tr><td>Группы:</td></tr>
                {foreach from=$BLOG_FRIENDGROUP_BLOG_FRIENDGROUP_ID item=item}
                    <tr><td><input name="access_group_{$env.area_id}[]" value="{$item.BLOG_FRIENDGROUP_ID}" type="checkbox"{if $item.CHECKED} checked="checked"{/if}>{$item.BLOG_FRIENDGROUP_NAME}</td></tr>
                {/foreach}
            </table>
        </div>
        <script langiage="JavaScript">
            change_blog('change_{$env.area_id}');
            change_access('change_{$env.area_id}');
        </script>
    </td>
</tr>

<tr>
    <td>Теги</td>
    <td>
        {if $BLOG_BLOG_ID} 
        {foreach from=$BLOG_BLOG_ID item=item}
        <div id="div_tags{$item.BLOG_ID}__{$env.area_id}" style="visibility:hidden;display:none">
            {if $item.BLOG_TAGS} 
            <table>
                {foreach from=$item.BLOG_TAGS item=item_tag}
                    <tr><td><input name="tags{$item_tag.BLOG_ID}__{$env.area_id}[]" value="{$item_tag.BLOG_TAG_ID}" type="checkbox"{if $item_tag.CHECKED} checked="checked"{/if}>{$item_tag.BLOG_TAG_NAME}</td></tr>
                {/foreach}
            </table>
            {/if}
        </div>
        {/foreach}
        {/if}
    </td>
</tr>
<tr>
    <td>Новые теги (разделенные запятой)</td>
    <td><INPUT TYPE="TEXT" NAME="NEW_TAGS_{$env.area_id}" VALUE=""></td>
</tr>
<tr>
    <td>&nbsp;</td>
    <td><INPUT TYPE="checkbox" NAME="IS_DISABLECOMMENT_{$env.area_id}" VALUE="1" {if $IS_DISABLECOMMENT} checked{/if}>не показывать комментарии другим пользователям</td>
</tr>


<tr>
    <td colspan="2">
        <INPUT TYPE="SUBMIT" name="save_{$env.area_id}" VALUE="Сохранить">
        <INPUT TYPE="SUBMIT" name="public_{$env.area_id}" VALUE="Опубликовать">
	    <INPUT TYPE="HIDDEN" NAME="BLOG_POST_ID_{$env.area_id}" VALUE="{$BLOG_POST_ID}">
        <INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="{if $EDIT}edit{else}add{/if}">
    </td>
</tr>
</FORM>
</table>

<!-- form name="editorForm" method="post" action="/editor3/index.php">
<input type="hidden" name="object_name">
<input type="hidden" name="object_value">
</form -->
<script langiage="JavaScript">
    change_blog('change_{$env.area_id}');
</script>
