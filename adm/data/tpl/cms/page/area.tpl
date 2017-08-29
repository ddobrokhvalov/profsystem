{$lang_area}: <b>{$area.TITLE|escape}</b> ({$area.SYSTEM_NAME|escape})<br/>
{if $is_content}
	<br/>
	{$lang_assigned_block}: {if $content_url}<a href="{$content_url}"><b>{$area.INF_BLOCK_TITLE|escape}</b></a>{else}<b>{$area.INF_BLOCK_TITLE|escape}</b>{/if}
{if $add_url}
	<br/><br/>
	<b><img src="/common/adm/img/blocks/plus.gif" width="12" height="12" border="0" alt="" style="vertical-align: middle; margin: 0px 5px 0px 0px;"><a href="{$add_url}">{$lang_add} {$area.ELEMENT_NAME|escape}</a></b>
{/if}
{/if}
