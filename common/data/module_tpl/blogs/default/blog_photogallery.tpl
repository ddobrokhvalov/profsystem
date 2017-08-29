<center><b>{$BLOG_USER}&nbsp;:&nbsp;изображения пользователя</b></center>

<center>
<table>
{foreach from=$IMAGES item=item}
<tr>
    {foreach from=$item.COLS item=item_cols}
    <td>
    <table {if $item_cols.S_DEFAULT} border="1"{/if}>
    <tr>
        <td>
            <center>
                <table width="100%">
                <tr><td>{if $item_cols.IMG}<img src="{$item_cols.IMG}">{/if}</td></tr>
                <tr><td>{$item_cols.TITLE}</td></tr>
                </table>
            </center>
        </td>
    </tr>
    </table>
    </td>
    {/foreach}
</tr>
{/foreach}
</table>
</center>

<center><input type="button" onClick="javascript:self.close()" value="{$SYSW_BLOG_CLOSE}"></center>
