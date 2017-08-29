{if !$IS_MAIL}
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

function nonempty(form_name){ldelim}
    var _form = eval('document.'+form_name);
    if (_form.body_{$env.area_id}.value.replace(/(^\s*)|(\s*$)/g, "")==""){ldelim}
        alert ('Вы не заполнили поле "Текст комментария"');
        return false;
    {rdelim}
    return true;
{rdelim}

function change_image(form_name){ldelim}
    var _form = eval('document.'+form_name);
    var image_arr = Array();
    {if $IMAGES}
    {foreach from=$IMAGES item=item}
        image_arr["{$item.BLOG_IMAGE_ID}"] = "{$item.IMG}";
    {/foreach}
    {/if}
    var myDate = new Date();
	_form.comment_image_{$env.area_id}.src = image_arr[_form.blog_image_id_{$env.area_id}.value];
{rdelim}
</script>
{/if}

{if $IS_CARD}

    {if $IS_FRIENDTAPE}
        <center><b>{$BLOG_USER}&nbsp;:&nbsp;лента друзей</b></center>
        {if !$IS_MAIL}
            <a href="../profile{$INDEX_FILE}">{$SYSW_BLOG_PROFILE}</a>&nbsp;&nbsp;<a href="..{$INDEX_FILE}">{$SYSW_BLOG}</a>&nbsp;&nbsp;<b>{$SYSW_BLOG_FRIENDTAPE}</b>
        {/if}
    {else}
        <center><b>{if $THEME}{$THEME}{else}{$BLOG_USER}{/if}{if $BLOG_NAME}&nbsp;:&nbsp;{$BLOG_NAME}{/if}</b></center>
        {if !$IS_MAIL}
            <a href="./profile{$INDEX_FILE}">{$SYSW_BLOG_PROFILE}</a>&nbsp;&nbsp;<b>{$SYSW_BLOG}</b>&nbsp;&nbsp;<a href="./friend{$INDEX_FILE}">{$SYSW_BLOG_FRIENDTAPE}</a>
        {/if}
    {/if}

    <br>
    
    {foreach from=$POSTS item=item}
        {if $IS_MAIL}<a href="{$item.CARD_LINK}">Карточка</a>{/if}
    <table  width="100%">
    <tr>
        <td>
            {if $item.POST_IMAGE}<img src="{$item.POST_IMAGE}">{/if}
        </td>
        <td>
            <table border="1" width="100%">
            <tr>
                <td>{$item.AUTHOR_TITLE}</td>
                <td>{$item.TITLE}</td>
            </tr>
            <tr>
                <td colspan="2">{$item.ADDED_DATE}</td>
            </tr>
            {if $item.BLOG_MOOD_ID}
            <tr>
                <td>{$SYSW_BLOG_MOON} :</td>
                <td>{if $item.MOOD_IMAGE}<img src="{$item.MOOD_IMAGE}">{/if}{$item.MOOD_TITLE}</td>
            </tr>
            {/if}
            {if $item.CURRENT_MUSIC}
            <tr>
                <td>{$SYSW_BLOG_MUSIC} :</td>
                <td>{$item.CURRENT_MUSIC}</td>
            </tr>
            {/if}
            <tr>
                <td colspan="2">{$item.BODY}</td>
            </tr>
            {if $item.BLOG_TAGS}
            <tr>
                <td colspan="2">
                    {$SYSW_BLOG_TAGS}&nbsp;:&nbsp;
                    {foreach from=$item.BLOG_TAGS item=item_tag name=foreach_blog_tags}
                        {if !$smarty.foreach.foreach_blog_tags.first}, {/if}<a href="{$item_tag.PATH}">{$item_tag.TITLE}</a>
                    {/foreach}
                </td>
            </tr>
            {/if}
            <tr>
                <td colspan="2">
                    Разрешение на просмотр&nbsp;:&nbsp;{$item.ACCESS_TITLE}
                </td>
            </tr>
            {if !$IS_MAIL}
            <tr>
                <td>
                {if $SHOW_EDIT_LINK}
                    <a href="{$item.EDIT_LINK}">{$SYSW_BLOG_EDIT}</a>
                {/if}
                {if $SHOW_DELPOST_LINK}
                    <a href="?post_id_{$env.area_id}={$item.BLOG_POST_ID}&action_{$env.area_id}=del_post" onClick="javascript: if ( !confirm('Запись будет удалена. Удалить?')) return false;">{$SYSW_BLOG_DELETE}</a>
                {/if}
                </td>
                <td><a href="javascript: void(0);" onClick="showHide(document.getElementById('main_comment_{$env.area_id}'));">{$SYSW_BLOG_ADD_COMMENT}</a></td>
            </tr>
            {/if}
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <div id="main_comment_{$env.area_id}" style="visibility:hidden;display:none">{$item.FORM_COMMENT}</div>
            {if $item.IS_ADD_COMMENT}<script language="Javascript">showHide(document.getElementById('main_comment_{$env.area_id}'));</script>{/if}
        </td>
    </tr>
    <tr>
        <td colspan="2"><a name="comments_{$env.area_id}"></a>{$item.COMMENTS}</td>
    </tr>
    </table>
    {/foreach}



{else}



    {if $IS_FRIENDTAPE}
        <center><b>{if $BLOG_NAME}{$BLOG_NAME}{else}{$BLOG_USER}&nbsp;:&nbsp;лента друзей{/if}</b></center>
        <a href="../profile{$INDEX_FILE}">{$SYSW_BLOG_PROFILE}</a>&nbsp;&nbsp;<a href="..{$INDEX_FILE}">{$SYSW_BLOG}</a>&nbsp;&nbsp;<b>{$SYSW_BLOG_FRIENDTAPE}</b>
    {else}
        <center><b>{if $BLOG_NAME}{$BLOG_NAME}{else}{$BLOG_USER}&nbsp;:&nbsp;блог{/if}</b></center>
        <a href="./profile{$INDEX_FILE}">{$SYSW_BLOG_PROFILE}</a>&nbsp;&nbsp;<b>{$SYSW_BLOG}</b>&nbsp;&nbsp;<a href="./friend{$INDEX_FILE}">{$SYSW_BLOG_FRIENDTAPE}</a>
    {/if}

    <br>
    
    {if $SHOW_NOPUBLIC_LINK}
        {if $IS_PUBLIC}
            {if $IS_NEWPOST}
                <a href="?no_public_{$env.area_id}=1&newpost_{$env.area_id}=1">{$SYSW_BLOG_SHOW_NOPUBLIC_POST}</a>
            {else}
                <a href="?no_public_{$env.area_id}=1">{$SYSW_BLOG_SHOW_NOPUBLIC_POST}</a>
            {/if}
        {else}
            {if $IS_NEWPOST}
                <a href="?newpost_{$env.area_id}=1">{$SYSW_BLOG_SHOW_PUBLIC_POST}</a>
            {else}
                <a href="?no_public_{$env.area_id}=0">{$SYSW_BLOG_SHOW_PUBLIC_POST}</a>
            {/if}
        {/if}
    {/if}
    
    <br>
    
    {if $SHOW_NEWPOST_LINK}
        {if $IS_NEWPOST}
            {if $IS_PUBLIC}
                <a href="?no_public_{$env.area_id}=0">Показать проверенные записи</a>
            {else}
                <a href="?no_public_{$env.area_id}=1">Показать проверенные записи</a>
            {/if}
        {else}
            {if $IS_PUBLIC}
                <a href="?newpost_{$env.area_id}=1">Показать новые записи</a>
            {else}
                <a href="?no_public_{$env.area_id}=1&newpost_{$env.area_id}=1">Показать новые записи</a>
            {/if}
        {/if}
    {/if}


    <table width="100%">
    {if $BLOG_TAG_NAME}
    <tr>
        <td colspan="2"><center><b>Записи с фильтром {$BLOG_TAG_NAME}</b></center></td>
    </tr>
    {/if}
    
    {foreach from=$POSTS item=item}
    <tr>
        <td>
            {if $item.POST_IMAGE}
                {if $IS_COMMUNITY}
                    <a href="{$item.AUTHOR_PATH}"><img src="{$item.POST_IMAGE}"></a>
                {else}
                    {if $IS_FRIENDTAPE}
                        <a href="{$item.AUTHOR_PATH}"><img src="{$item.POST_IMAGE}"></a>
                    {else}
                        <img src="{$item.POST_IMAGE}">
                    {/if}
                {/if}
            {else}
                &nbsp;
            {/if}

            {if $IS_NEWPOST}
            <br>
            <FORM ACTION="" METHOD="POST" NAME="public_form_{$env.area_id}">
                <INPUT TYPE="HIDDEN" NAME="post_id_{$env.area_id}" VALUE="{$item.BLOG_POST_ID}">
                <INPUT TYPE="HIDDEN" NAME="action_{$env.area_id}" VALUE="public">
                <INPUT TYPE="SUBMIT" VALUE="Опубликовать">
            </FORM>
            {/if}
        </td>
        <td>
            <table border="1" width="100%">
            {if $IS_COMMUNITY}
                <tr>
                    <td colspan="3">Автор : <a href="{$item.AUTHOR_PATH}">{$item.AUTHOR_TITLE}</a></td>
                </tr>
            {else}
                {if $IS_FRIENDTAPE}
                    <tr>
                        <td colspan="3">Автор : <a href="{$item.AUTHOR_PATH}">{$item.AUTHOR_TITLE}</a></td>
                    </tr>
                    {if $item.COMMUNITY_PATH}
                        <tr>
                            <td colspan="3">В рамках сообщества : <a href="{$item.COMMUNITY_PATH}">{$item.COMMUNITY_TITLE}</a></td>
                        </tr>
                    {/if}
                {/if}
            {/if}
            
            <tr>
                <td colspan="2"><a href="?post_id_{$env.area_id}={$item.BLOG_POST_ID}">{$item.TITLE}</a></td>
                <td>{$item.ADDED_DATE}</td>
            </tr>
            <tr>
                <td colspan="3">{$item.BODY}</td>
            </tr>
            {if $item.BLOG_MOOD_ID}
            <tr>
                <td>{$SYSW_BLOG_MOON} :</td>
                <td colspan="2">{if $item.MOOD_IMAGE}<img src="{$item.MOOD_IMAGE}">{/if}{$item.MOOD_TITLE}</td>
            </tr>
            {/if}
            {if $item.CURRENT_MUSIC}
            <tr>
                <td>{$SYSW_BLOG_MUSIC} :</td>
                <td colspan="2">{$item.CURRENT_MUSIC}</td>
            </tr>
            {/if}
            {if $item.BLOG_TAGS}
            <tr>
                <td>Теги :</td>
                <td colspan="2">
                    {foreach from=$item.BLOG_TAGS item=item_tag name=foreach_blog_tags}
                        {if !$smarty.foreach.foreach_blog_tags.first}, {/if}<a href="{$item_tag.PATH}">{$item_tag.TITLE}</a>
                    {/foreach}
                </td>
            </tr>
            {/if}

            <tr>
                <td>{$item.ACCESS_TITLE}</td>
                <td><a href="?post_id_{$env.area_id}={$item.BLOG_POST_ID}#comments_{$env.area_id}">{$SYSW_BLOG_COMMENTS}{if $item.COUNT_COMMENTS}&nbsp;({$item.COUNT_COMMENTS})</a>{/if}</td>
                <td><a href="?post_id_{$env.area_id}={$item.BLOG_POST_ID}&is_add_comment_{$env.area_id}=1#add_comment_{$env.area_id}">{$SYSW_BLOG_ADD_COMMENT}</a></td>
            </tr>
            </table>
        </td>
    </tr>
    {/foreach}
    </table>
    
    {if $pager}{$pager}{/if}

    {if $BLOG_ALLTAGS}
    <table>
    <tr>
        <td>Теги пользователя:</td>
        <td>
            {foreach from=$BLOG_ALLTAGS item=item_tag name=foreach_blog_alltags}
                {if !$smarty.foreach.foreach_blog_alltags.first}, {/if}<a href="{$item_tag.PATH}">{$item_tag.TITLE}</a>
            {/foreach}
        </td>
    </tr>
    </table>
    {/if}



{/if}
