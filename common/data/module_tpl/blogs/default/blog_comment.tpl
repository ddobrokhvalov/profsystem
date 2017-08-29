{if $COMMENTS}
{foreach from=$COMMENTS item=item}
    <a name="comment{$item.BLOG_COMMENT_ID}__{$env.area_id}"></a>
    <table width="100%">
    <tr>
        <td width="1%" valign="top">
            <table width="100%">
        	<tr>
                <td align="center">
                    {if $item.AUTHOR_NICK}
                        <a href="{$item.AUTHOR_BLOG}">{$item.AUTHOR_NICK}</a>
                    {else}
                        {$SYSW_BLOG_ANONYMOUS}
                    {/if}
                </td>
            </tr>
            <tr>
                <td>{if $item.IMG}<a href="{$item.AUTHOR_PROFILE}"><img align="left" src="{$item.IMG}"></a>{/if}</td>
            </tr>
            </table>
        </td>
        <td valign="top">
            <table border="1" width="100%">
        	<tr>
                <td>&nbsp;{$item.TITLE}</td>
                <td>&nbsp;{$item.ADDED_DATE}</td>
            </tr>
        	<tr>
                <td colspan="2">{if !$item.COMMENT_IS_DEL}<pre>{$item.BODY}</pre>{else}{$SYSW_BLOG_COMMENT_IS_DEL}{/if}</td>
            </tr>
            {if !$IS_MAIL}
            {if !$item.COMMENT_IS_DEL}
            <tr>
                <td>&nbsp;
                {if $item.SHOW_DELETE_LINK}
                    <a href="index.php?post_id_{$env.area_id}={$item.BLOG_POST_ID}&comment_id_{$env.area_id}={$item.BLOG_COMMENT_ID}&action_{$env.area_id}=del_comment" onClick="javascript: if ( !confirm('Комментарий будет удалён. Удалить?')) return false;">{$SYSW_BLOG_DELETE}</a>
                {/if}
                </td>
                <td><a href="javascript: void(0);" onClick="showHide(document.getElementById('main_comment{$item.BLOG_COMMENT_ID}__{$env.area_id}'));">{$SYSW_BLOG_ANSWER}</a></td>
            </tr>
            {/if}
            {/if}
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="2"><div id="main_comment{$item.BLOG_COMMENT_ID}__{$env.area_id}" style="visibility:hidden;display:none">{$item.FORM_COMMENT}</div></td>
    </tr>
    {if $item.CHILD}
    <tr>
        <td colspan="2" style="padding-left: 20px">{$item.CHILD}</td>
    </tr>
    {/if}
    </table>
{/foreach}
{/if}
