<div class="call_order_form_result call_order_form call_order_form_visible">
	<div class="call_order_form_close" rel="{$smarty.server.SCRIPT_NAME}"></div>
	<h3>{$form_name}</h3>
	{if $result == 'ok'}
		{$sysw_form_question_result_ok}
	{else}
		{$sysw_form_question_result_error}
	{/if}
</div>