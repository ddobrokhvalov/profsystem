<div class="call_order_form {if $captcha_error}call_order_form_visible{/if}">
	<div class="call_order_form_close"></div>
	<h3>{$form_name}</h3>
		{if $form_description}
			<p>{$form_description}</p>
		{/if}
	{if $captcha_error}
		<div class="error">
			{$sysw_captcha_error}
		</div>
	{/if}
	{if $fields}
		<script type="text/javascript" src="/common/js/core/translate.js.php?lang={$env.lang_root_dir}"></script>
		<script type="text/javascript" src="/common/js/core/check_form.js"></script>
		<script type="text/javascript" src="/common/js/core/calendar.js"></script>
		<form action="" method="post" name="form_question" onsubmit="return CheckForm.validate( this )">
				{foreach from=$fields item=item}
					{if $item.QUESTION_TYPE == 'separator'}
						<div class="call_order_form_field field_{$item.QUESTION_TYPE} field_{$item.QUESTION_TYPE}">
							<hr>
						</div>
					{else}
						<div class="call_order_form_field field_{$item.QUESTION_TYPE}">
								{if $item.QUESTION_TYPE != 'string' && $item.QUESTION_TYPE != 'textarea'}
								<label class="call_order_form_field_label">{$item.TITLE}{if $item.IS_MANDATORY} <span style="color: red">*</span>{/if}</label>
								{/if}
							
								{if $item.QUESTION_TYPE == 'string'}
									<input type="text" placeholder="{$item.TITLE}" value="{$item.DEFAULT_VALUE}" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}" lang="errors{if $item.IS_MANDATORY}_nonempty_{/if}">
								{elseif $item.QUESTION_TYPE == 'int'}
									<input type="text" placeholder="{$item.TITLE}" value="{$item.DEFAULT_VALUE}" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}" lang="errors_int_{if $item.IS_MANDATORY}_nonempty_{/if}">
								{elseif $item.QUESTION_TYPE == 'float'}
									<input type="text" placeholder="{$item.TITLE}" value="{$item.DEFAULT_VALUE}" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}" lang="errors_float_{if $item.IS_MANDATORY}_nonempty_{/if}">
								{elseif $item.QUESTION_TYPE == 'email'}
									<input type="text" placeholder="{$item.TITLE}" value="{$item.DEFAULT_VALUE}" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}" lang="errors_email_{if $item.IS_MANDATORY}_nonempty_{/if}">
								{elseif $item.QUESTION_TYPE == 'date'}
									<input type="text" placeholder="{$item.TITLE}" value="{$item.DEFAULT_VALUE}" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}" lang="errors_date_{if $item.IS_MANDATORY}_nonempty_{/if}">
									<a href="" onclick="Calendar.show( document.forms['form_question']['f_{$item.FORM_QUESTION_ID}_{$env.area_id}'], this); return false">{$sysw_calendar}</a>
								{elseif $item.QUESTION_TYPE == 'textarea'}
									<textarea placeholder="{$item.TITLE}" cols="7" rows="5" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}" lang="errors{if $item.IS_MANDATORY}_nonempty_{/if}">{$item.DEFAULT_VALUE}</textarea>
								{elseif $item.QUESTION_TYPE == 'checkbox'}
									<input type="hidden" value="0" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}">
									<input type="checkbox" value="1" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}"{if $item.DEFAULT_VALUE} checked="checked"{/if} lang="errors{if $item.IS_MANDATORY}_nonempty_{/if}">
								{elseif $item.QUESTION_TYPE == 'select'}
									<select name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}" lang="errors{if $item.IS_MANDATORY}_nonempty_{/if}">
										<option value=""></option>
										{foreach from=$item.OPTIONS item=option}
										<option value="{$option.FORM_OPTIONS_ID}"{if $option.IS_DEFAULT} selected="selected"{/if}>{$option.TITLE}</option>
										{/foreach}
									</select>
								{elseif $item.QUESTION_TYPE == 'radio_group'}
									{foreach from=$item.OPTIONS item=option}
										<input type="radio" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}" value="{$option.FORM_OPTIONS_ID}"{if $option.IS_DEFAULT} checked="checked"{/if} lang="errors{if $item.IS_MANDATORY}_radio_{/if}"> {$option.TITLE}<br>
									{/foreach}
								{elseif $item.QUESTION_TYPE == 'radio_group_alt'}
									{foreach from=$item.OPTIONS item=option}
										<input type="radio" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}" value="{$option.FORM_OPTIONS_ID}"{if $option.IS_DEFAULT} checked="checked"{/if} lang="errors{if $item.IS_MANDATORY}_radioalt_{/if}"> {$option.TITLE}<br>
									{/foreach}
										<input type="radio" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}" value="_alt_"{if $item.IS_DEFAULT} checked="checked"{/if} lang="errors{if $item.IS_MANDATORY}_radioalt_{/if}"> {$sysw_form_question_other}
										<input type="text" value="{$item.DEFAULT_VALUE}" name="alt_f_{$item.FORM_QUESTION_ID}_{$env.area_id}">
								{elseif $item.QUESTION_TYPE == 'checkbox_group'}
									{foreach from=$item.OPTIONS item=option}
										<input type="checkbox" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}[]" value="{$option.FORM_OPTIONS_ID}"{if $option.IS_DEFAULT} checked="checked"{/if} lang="errors{if $item.IS_MANDATORY}_checkboxgroup_{/if}"> {$option.TITLE}<br>
									{/foreach}
								{elseif $item.QUESTION_TYPE == 'checkbox_group_alt'}
									{foreach from=$item.OPTIONS item=option}
										<input type="checkbox" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}[]" value="{$option.FORM_OPTIONS_ID}"{if $option.IS_DEFAULT} checked="checked"{/if} lang="errors{if $item.IS_MANDATORY}_checkboxgroupalt_{/if}"> {$option.TITLE}<br>
									{/foreach}
										<input type="checkbox" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}[]" value="_alt_"{if $item.IS_DEFAULT} checked="checked"{/if} lang="errors{if $item.IS_MANDATORY}_checkboxgroupalt_{/if}"> {$sysw_form_question_other}
										<input type="text" value="{$item.DEFAULT_VALUE}" name="alt_f_{$item.FORM_QUESTION_ID}_{$env.area_id}[]">
								{else}
									&nbsp;
								{/if}
						</div>
					{/if}
				{/foreach}
				{if $captcha_id}
					<div class="call_order_form_captcha">
							<img src="/common/tool/getcaptcha.php?captcha_id={$captcha_id}">
							<input type="hidden" name="captcha_id_{$env.area_id}" value="{$captcha_id}">
							<input placeholder="{$sysw_captcha}" type="text" name="captcha_value_{$env.area_id}" value="" lang="errors_nonempty_">
					</div>
				{/if}
				<div class="call_order_form__checkbox_label">
					<input type="checkbox" id="feedback_checkbox" name="im_ok" lang="errors_nonempty_">
					<label for="feedback_checkbox">
						Принимаю условия <a href="#" target="_blank">Соглашения по обработке персональных данных</a>
					</label>
				</div>
				<div class="call_order_form_buttons">
					<input type="hidden" name="done_{$env.area_id}" value="1">
					<input type="submit" value="{$sysw_form_question_send}">
				</div>
		</form>
		<div class="sub-links">{* $sysw_mandatory *}</div>
	{/if}
</div>