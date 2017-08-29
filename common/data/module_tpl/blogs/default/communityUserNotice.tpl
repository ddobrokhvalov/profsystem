{if $ACTION_INVITE}
    Вы были приглашены в сообщество : {$COMMUNITY_NAME} ( {$COMMUNITY_TITLE} )
    <br>
    <br>
    <br>
    Перейдите по ссылке для принятия предожения или отказа : <a href="{$PATH_TO_INVITEPAGE}">{$PATH_TO_INVITEPAGE}</a>
{/if}

{if $ACTION_DEL}
    Вы были удалены из сообщества : {$COMMUNITY_NAME} ( {$COMMUNITY_TITLE} )
{/if}

{if $ACTION_CHANGE}
    Были изменены ваши данные как участника сообщества : {$COMMUNITY_NAME} ( {$COMMUNITY_TITLE} )
{/if}

<br>
<br>
<br>

{$SYSW_BLOG_SIGNATURE}