<div class="feedback">
	<div class="feedback_form">
		<h2 class="center" style="color: #373737;">{$form_name}</h2>
		<p>
		{if $result == 'ok'}
			{$sysw_form_question_result_ok}
		{else}
			{$sysw_form_question_result_error}
		{/if}
		</p>
		<p>
			<a href="{$smarty.server.SCRIPT_NAME}">Вернуться</a>
		</p>
	</div>
</div>