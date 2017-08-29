<script language="JavaScript">
function show_info(lnk, param){ldelim}
    runt = "toolbar=0,location=0,directories=0,status=0,menubar=0,resizable=1,scrollbars=1," + param;
    var iwin = window.open(lnk, 'info1', runt);
    iwin.focus();
{rdelim}
</script>

<center><b>{$BLOG_USER}&nbsp;:&nbsp;профиль</b></center>

<b>{$SYSW_BLOG_PROFILE}</b>&nbsp;&nbsp;<a href="..{$INDEX_FILE}">{$SYSW_BLOG}</a>&nbsp;&nbsp;<a href="../friend{$INDEX_FILE}">{$SYSW_BLOG_FRIENDTAPE}</a>
<br>

<table border="1" width="100%">
    {if $DEFAULT_IMAGE}
    <tr>
        <td colspan="2">
            <img src="{$DEFAULT_IMAGE}"><br>
            <a href="" onClick="javascript:show_info('{$ALLIMAGE_LINK}');">{$SYSW_BLOG_SHOWALLIMAGE}</a>
        </td>
    </tr>
    {/if}

    {if $IS_COMMUNITY}
    <tr>
        <td colspan="2">
            <center>
                {if $IS_FRIEND_CONNUNITY}
                    <a href="?action_{$env.area_id}=del&community_id_{$env.area_id}={$BLOG_ID}&user_id_{$env.area_id}={$USER_ID}" onClick="javascript: if ( !confirm('Вы действительно желаете покинуть сообщество?')) return false;">Покинуть сообщество</a>
                {else}
                    <a href="?action_{$env.area_id}=add&community_id_{$env.area_id}={$BLOG_ID}&user_id_{$env.area_id}={$USER_ID}" onClick="javascript: if ( !confirm('Вы действительно желаете вступить в сообщество?')) return false;">Вступить в сообщество</a>
                {/if}
            </center>
        </td>
    </tr>
    {/if}

    {if $SHOW_NICK}
    <tr>
        <td>{if $IS_COMMUNITY}Название{else}{$SYSW_BLOG_NICK}{/if}</td>
        <td>{$NICK}</td>
    </tr>
    {/if}
    {if $SHOW_CREATER}
    {if $CREATER}
    <tr>
        <td>Создатель</td>
        <td><a href="{$CREATER_PATH}">{$CREATER}</a></td>
    </tr>
    {/if}
    {/if}
    {if $SHOW_MODERATOR}
    {if $MODERATORS}
    <tr>
        <td>Модераторы</td>
        <td>
            {foreach from=$MODERATORS item=item name=foreach_moderators}
                {if !$smarty.foreach.foreach_moderators.first}, {/if}<a href="{$item.PATH_TO_PROFILE}">{$item.TITLE}</a>
            {/foreach}
        </td>
    </tr>
    {/if}
    {/if}
    {if $SHOW_EMAIL}
    {if $EMAIL}
    <tr>
        <td>{$SYSW_BLOG_EMAIL}</td>
        <td>{$EMAIL}</td>
    </tr>
    {/if}
    {/if}
    {if $SHOW_ICQ}
    {if $ICQ}
    <tr>
        <td>{$SYSW_BLOG_ICQ}</td>
        <td>{$ICQ}</td>
    </tr>
    {/if}
    {/if}
    {if $SHOW_SKYPE}
    {if $SKYPE}
    <tr>
        <td>{$SYSW_BLOG_SKYPE}</td>
        <td>{$SKYPE}</td>
    </tr>
    {/if}
    {/if}
    {if $SHOW_FIO}
    {if $FIO}
    <tr>
        <td>{$SYSW_BLOG_FIO}</td>
        <td>{$FIO}</td>
    </tr>
    {/if}
    {/if}
    {if $SHOW_BIRTHDATE}
    {if $BIRTHDATE}
    <tr>
        <td>{$SYSW_BLOG_BIRTHDATE}</td>
        <td>{$BIRTHDATE}</td>
    </tr>
    {/if}
    {/if}
    {if $SHOW_SEX}
    {if $SEX}
    <tr>
        <td>{$SYSW_BLOG_SEX}</td>
        <td>{$SEX}</td>
    </tr>
    {/if}
    {/if}
    {if $SHOW_BLOG_COUNTRY}
    {if $BLOG_COUNTRY}
    <tr>
        <td>{$SYSW_BLOG_COUNTRY}</td>
        <td>{$BLOG_COUNTRY}</td>
    </tr>
    {/if}
    {/if}
    {if $SHOW_BLOG_CITY}
    {if $BLOG_CITY}
    <tr>
        <td>{$SYSW_BLOG_CITY}</td>
        <td>{$BLOG_CITY}</td>
    </tr>
    {/if}
    {/if}
    {if $SHOW_HOMEPAGE}
    {if $HOMEPAGE}
    <tr>
        <td>{$SYSW_BLOG_HOMEPAGE}</td>
        <td>{$HOMEPAGE}</td>
    </tr>
    {/if}
    {/if}
    {if $SHOW_ABOUT}
    {if $ABOUT}
    <tr>
        <td>{if $IS_COMMUNITY}О сообществе{else}{$SYSW_BLOG_ABOUT}{/if}</td>
        <td>{$ABOUT}</td>
    </tr>
    {/if}
    {/if}
    {if $SHOW_MEMBERSHIP}
    <tr>
        <td>Условие вступления</td>
        <td>{if $MEMBERSHIP1}свободное{else}модерируемое{/if}</td>
    </tr>
    {/if}
    {if $SHOW_POSTLEVEL}
    <tr>
        <td>Добавление записей</td>
        <td>{if $POSTLEVEL1}неограниченное{else}ограниченное{/if}</td>
    </tr>
    {/if}
    {if $SHOW_MODERATION}
    <tr>
        <td>Модерация записей</td>
        <td>{if $MODERATION1}не производится{else}производится{/if}</td>
    </tr>
    {/if}
    {if $SHOW_INTEREST}
    {if $INTERESTS}
    <tr>
        <td>{$SYSW_BLOG_INTERESTS}</td>
        <td>{$INTERESTS}</td>
    </tr>
    {/if}
    {/if}
    {if $FRIENDS}
    <tr>
        <td>{if $IS_COMMUNITY}Участники{else}{$SYSW_BLOG_FRIENDS}{/if}</td>
        <td>
            {foreach from=$FRIENDS item=item name=foreach_friends}
                {if !$smarty.foreach.foreach_friends.first}, {/if}<a href="{$item.PATH_TO_PROFILE}">{$item.TITLE}</a>
            {/foreach}
        </td>
    </tr>
    {/if}
    {if $SHOW_COMMUNITY}
    {if $COMMUNITY}
    <tr>
        <td>{$SYSW_BLOG_COMMUNITY}</td>
        <td>
            {foreach from=$COMMUNITY item=item name=foreach_community}
                {if !$smarty.foreach.foreach_community.first}, {/if}<a href="{$item.PATH_TO_PROFILE}">{$item.TITLE}</a>
            {/foreach}
        </td>
    </tr>
    {/if}
    {/if}

    {if $SHOW_EDIT_LINK}
    <tr>
        <td colspan="2" align="right"><a href="{$EDIT_LINK}">{$SYSW_BLOG_EDIT}</a></td>
    </tr>
    {/if}
</table>