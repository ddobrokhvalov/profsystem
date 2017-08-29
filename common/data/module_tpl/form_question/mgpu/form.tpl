<div class="container" style="padding:0 0 30px 0">

<div class="section clearfix">
<h1 style="padding: 30px 0px">{$form_name}</h1>
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
	<table cellpadding="5">
{foreach from=$fields item=item}
{if $item.QUESTION_TYPE == 'separator'}
		<tr>
			<td colspan="2">
				<hr>
			</td>
		</tr>
{else}
		<tr>
			<td>
				{$item.TITLE}{if $item.IS_MANDATORY} <span style="color: red">*</span>{/if}
			</td>
			<td>
{if $item.QUESTION_TYPE == 'string'}
				<input type="text" value="{$item.DEFAULT_VALUE}" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}" lang="errors{if $item.IS_MANDATORY}_nonempty_{/if}">
{elseif $item.QUESTION_TYPE == 'int'}
				<input type="text" value="{$item.DEFAULT_VALUE}" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}" lang="errors_int_{if $item.IS_MANDATORY}_nonempty_{/if}">
{elseif $item.QUESTION_TYPE == 'float'}
				<input type="text" value="{$item.DEFAULT_VALUE}" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}" lang="errors_float_{if $item.IS_MANDATORY}_nonempty_{/if}">
{elseif $item.QUESTION_TYPE == 'email'}
				<input type="text" value="{$item.DEFAULT_VALUE}" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}" lang="errors_email_{if $item.IS_MANDATORY}_nonempty_{/if}">
{elseif $item.QUESTION_TYPE == 'date'}
				<input type="text" value="{$item.DEFAULT_VALUE}" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}" lang="errors_date_{if $item.IS_MANDATORY}_nonempty_{/if}">
				<a href="" onclick="Calendar.show( document.forms['form_question']['f_{$item.FORM_QUESTION_ID}_{$env.area_id}'], this); return false">{$sysw_calendar}</a>
{elseif $item.QUESTION_TYPE == 'textarea'}
    			<textarea cols="7" rows="5" name="f_{$item.FORM_QUESTION_ID}_{$env.area_id}" lang="errors{if $item.IS_MANDATORY}_nonempty_{/if}">{$item.DEFAULT_VALUE}</textarea>
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
			</td>
		</tr>
{/if}
{/foreach}
{if $captcha_id}
		<tr>
			<td>
				{$sysw_captcha} <span style="color: red">*</span>
			</td>
			<td>
				<img src="/common/tool/getcaptcha.php?captcha_id={$captcha_id}"><br>
				<input type="hidden" name="captcha_id_{$env.area_id}" value="{$captcha_id}">
				<input type="text" name="captcha_value_{$env.area_id}" value="" lang="errors_nonempty_">
			</td>
		</tr>
{/if}
		<tr>
			<td>
				&nbsp;
			</td>
			<td>
				<input type="hidden" name="done_{$env.area_id}" value="1">
				<input type="submit" value="{$sysw_form_question_send}">
			</td>
		</tr>
	</table>
</form>
<div class="sub-links">{$sysw_mandatory}</div>
{/if}
</div>
</div>