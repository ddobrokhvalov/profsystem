{if $toClient}

    {$SYSW_BLOG_MSG4} - {$NICK}/{$PASSWORD}

{else}

    {$SYSW_BLOG_MSG5}<br><br>

    {$SYSW_BLOG_NICK}: {$NICK}<br>
    {$SYSW_BLOG_MAILLOG}: <a href="mailto: {$EMAIL}">{$EMAIL}</a>
{/if}

<br>
<br>

{$SYSW_BLOG_SIGNATURE}