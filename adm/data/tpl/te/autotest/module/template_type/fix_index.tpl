{$lang_autotest_test_template_type_type_for_template} {$TITLE}<br>
<form method="POST" action="{$fix_action_url}">
{if (sizeof($template_types))}
	{html_options name="TEMPLATE_TYPE_ID" options=$template_types}
{/if}
<br><br>
<input type="hidden" name="TEMPLATE_ID" value="{$TEMPLATE_ID}">
<input type="hidden" name="SYSTEM_NAME" value="{$SYSTEM_NAME}">
<input type="submit" value="{$lang_autotest_test_template_type_setup_template_type}">
</form>