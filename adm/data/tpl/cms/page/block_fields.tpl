{foreach from=$record item=field name=field}
        <div id="{$field.name}" class="hr2">
			<div>{if $field.vars.template_url}<a href="{$field.vars.template_url}">{$field.title}</a>{else}{$field.title}{/if}{if $field.nonempty} <span class="err">*</span>{/if}:</div>
		{if $field.type=="textarea"}
			<textarea style="width: 230px; heigth: 103px;" name="{$field.name}" cols="50" rows="{$field.rows}" lang="errors{$field.prefix}"{if $field.disabled} disabled="disabled"{/if}>{$field.value}</textarea>
		{elseif $field.type=="checkbox"}
			<input type="hidden" name="{$field.name}" value="0"/>
			<input type="checkbox" name="{$field.name}" value="1"{if $field.value} checked="checked"{/if}{if $field.disabled} disabled="disabled"{/if}/>
		{elseif $field.type=="select1" || $field.type=="select2" || $field.type=="parent"}
			<select style="width: 230px;" name="{$field.name}" lang="errors{$field.prefix}"{if $field.disabled} disabled="disabled"{/if}>
				<option value="">
				{foreach from=$field.value_list item=option name=option}
					<option value="{$option._VALUE|escape}"{if $option._VALUE===$field.value} selected="selected"{/if}{if $option._class} class="{$option._class}"{/if}>{if !is_null($option.TREE_DEEP)}&nbsp;&nbsp;{section name=offset start=0 loop=$option.TREE_DEEP}&nbsp;&nbsp;{/section}{/if}{$option._TITLE|escape}</option>
				{/foreach}
			</select>
		{elseif $field.type=="date"}
<table border="0" cellspacing="0" cellpadding="0">
<tr>
	<td><input style="width: 230px;" type="text" name="{$field.name}" value="{$field.value}" lang="errors{$field.prefix}"{if $field.disabled} disabled="disabled"{/if}/></td>
{if !$field.disabled}
<td><a href="" onClick="getCalendar('cal_{$field.name}', 'document.{$form_name}.{$field.name}', document.{$form_name}.{$field.name}.value, 0); return false"><img src="/common/adm/img/calendar.gif" width="16" height="16" border="0" alt="{$lang_calendar}" title="{$lang_calendar}" style="margin: 0px 0px 0px 4px; width: 16; height: 16; border: 0px solid;"></a>
<div id="cal_{$field.name}" style="display: inline; z-index: 10; position: absolute; width: 175px; visibility: hidden; background-color: #FFFFFF; border: none"></div>
</td>
{/if}
</tr>
</table>
		{elseif $field.type=="datetime"}
<table border="0" cellspacing="0" cellpadding="0">
<tr>
	<td><input style="width: 230px;" type="text" name="{$field.name}" value="{$field.value}" lang="errors{$field.prefix}"{if $field.disabled} disabled="disabled"{/if}/></td>
{if !$field.disabled}
<td><a href="" onclick="getCalendar('cal_{$field.name}', 'document.{$form_name}.{$field.name}', document.{$form_name}.{$field.name}.value, 1); return false"><img src="/common/adm/img/calendar.gif" width="16" height="16" border="0" alt="{$lang_calendar}" title="{$lang_calendar}" style="margin: 0px 0px 0px 4px; width: 16; height: 16; border: 0px solid;"></a>
<div id="cal_{$field.name}" style="display: inline; z-index: 10; position: absolute; width: 175px; visibility: hidden; background-color: #FFFFFF; border: none"></div></td>
{/if}
</tr>
</table>
		{elseif $field.type=="img"}
<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr valign="top">
{if $field.value}
	<td style="padding: 0px 11px 10px 0px;"><img alt="" src="{$field.value}"/></td>
{/if}
    <td width="100%">
            <input style="width: 230px; margin: 0px 0px 4px 0px;" type="file" name="{$field.name}_file" lang="errors{$field.prefix}"{if $field.disabled} disabled="disabled"{/if}/>
			<input style="width: 230px;" type="text" name="{$field.name}" value="{$field.value}" lang="errors{$field.prefix}"{if $field.disabled} disabled="disabled"{/if}/>
    </td>
</tr>
</table>	
        {elseif $field.type=="file"}
			<input style="width: 230px; margin: 0px 0px 4px 0px;" type="file" name="{$field.name}_file" lang="errors{$field.prefix}"{if $field.disabled} disabled="disabled"{/if}/>
			<input style="width: 230px;" type="text" name="{$field.name}" value="{$field.value}" lang="errors{$field.prefix}"{if $field.disabled} disabled="disabled"{/if}/>
		{else}
			<input style="width: 230px;" type="text" name="{$field.name}" value="{$field.value}" lang="errors{$field.prefix}"{if $field.disabled} disabled="disabled"{/if}>
		{/if}</div>
{/foreach}
