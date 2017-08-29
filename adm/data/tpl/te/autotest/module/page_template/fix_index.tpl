{$lang_autotest_test_page_template_template_for_page} {$TITLE}<br>
<form method="POST" action="{$fix_action_url}">
{if (sizeof($templates))}
	{html_options name="TEMPLATE_ID" options=$templates}
{/if}
<br><br>
<input type="hidden" name="PAGE_ID" value="{$PAGE_ID}">
<input type="hidden" name="VERSION" value="{$VERSION}">
<input type="hidden" name="SYSTEM_NAME" value="{$SYSTEM_NAME}">
<input type="submit" value="{$lang_autotest_test_page_template_set_template}">
</form>