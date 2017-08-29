<a name="add_comment_{$env.area_id}"></a>

<form method="post" name="add{$BLOG_COMMENT_ID}__{$env.area_id}" onSubmit="return(nonempty('add{$BLOG_COMMENT_ID}__{$env.area_id}'));">
<table width="100%">
<tr>
    <td colspan="2"><center><b>Добавление комментария</b></center></td>
</tr>
<tr>
    <td>
        {if $IS_AUTH}
            <INPUT TYPE="radio" NAME="is_auth_user_{$env.area_id}" VALUE="1" checked onClick="showElement(document.getElementById('image{$BLOG_COMMENT_ID}__{$env.area_id}'));">{if $PATH_TO_BLOG}<a href="{$PATH_TO_BLOG}">{$NICK}</a>{else}{$NICK}{/if}<br>
            <INPUT TYPE="radio" NAME="is_auth_user_{$env.area_id}" VALUE="0" onClick="hideElement(document.getElementById('image{$BLOG_COMMENT_ID}__{$env.area_id}'));">{$SYSW_BLOG_ANONYMOUS}
        {else}
            {$SYSW_BLOG_ANONYMOUS}
            {if $PATH_TO_AUTH}<br>[<a href="{$PATH_TO_AUTH}">{$SYSW_BLOG_TOAUTHFORM}</a>]{/if}
        {/if}

        {if $IS_AUTH}
        <div id="image{$BLOG_COMMENT_ID}__{$env.area_id}" style="visibility:visible;display:">
        <br>
        <table width="100%">
        <tr>
            <td>
                {foreach from=$IMAGES item=item name=foreach_images1}
                    {if $smarty.foreach.foreach_images1.first}<img name="comment_image_{$env.area_id}" src="{$item.IMG}">{/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td>
                <select name="blog_image_id_{$env.area_id}" onChange="change_image('add{$BLOG_COMMENT_ID}__{$env.area_id}');">
                    <option value="0"></option>
                    {foreach from=$IMAGES item=item name=foreach_images2}
                        <option value="{$item.BLOG_IMAGE_ID}"{if $smarty.foreach.foreach_images2.first} selected{/if}>{$item.TITLE}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        </table>
        </div>
        {/if}
    </td>
    <td>
        <table width="100%">
        <tr>
            <td>Тема</td>
        </tr>
        <tr>
            <td><input type="text" name="title_{$env.area_id}"></td>
        </tr>
        <tr>
            <td>Текст комментария <span class="star">*</span></td>
        </tr>
        <tr>
            <td><textarea name="body_{$env.area_id}" rows="5"></textarea></td>
        </tr>
        <tr>
            <td><input type="checkbox" name="is_disable_{$env.area_id}" value="1"> скрыть комментарий</td>
        </tr>
        </table>
    </td>
</tr>
<tr>
    <td colspan="2">
        <input type="hidden" name="blog_post_id_{$env.area_id}" value="{$BLOG_POST_ID}">
        <input type="hidden" name="parent_id_{$env.area_id}" value="{$BLOG_COMMENT_ID}">
        <center><input type="submit" name="add_comment_{$env.area_id}" value="Сохранить"></center>
    </td>
</tr>
</table>
</form>
