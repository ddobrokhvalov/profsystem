{if $ACTION_ADD}
    Пользователь {$USER_TITLE} приглашенный ранее в сообщество {$COMMUNITY_NAME} ( {$COMMUNITY_TITLE} ) принял приглашение.
{/if}

{if $ACTION_DEL}
    Пользователь {$USER_TITLE} приглашенный Вами в сообщество {$COMMUNITY_NAME} ( {$COMMUNITY_TITLE} ) отказался от приглашения.
{/if}

{if $ACTION_INQUIRY}
    Пользователь {$USER_TITLE} отправил запрос на вступление в сообщество {$COMMUNITY_NAME} ( {$COMMUNITY_TITLE} ).
    <br>
    <br>
    <br>
    Перейдите по ссылке для принятия его запроса или отказа : <a href="{$PATH_TO_COMMUNITYPAGE}">{$PATH_TO_INVITEPAGE}</a>
{/if}


{if $ACTION_CONFIRM}
    Была опубликована запись в "модерируемом" сообществе {$COMMUNITY_NAME} ( {$COMMUNITY_TITLE} ).
{/if}

<br>
<br>
<br>

{$SYSW_BLOG_SIGNATURE}