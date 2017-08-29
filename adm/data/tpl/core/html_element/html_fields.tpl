{foreach from=$record item=field name=field}
{if $field.vars.separator}
<tr id="{$field.name}_SEPARATOR"{if $field.vars.display} style="display: {$field.vars.display}"{/if}>
	<td colspan="2">
		<hr/>
	</td>
</tr>
{/if}
<tr id="{$field.name}"{if $field.vars.display} style="display: {$field.vars.display}"{/if}>
	<td class="td21" valign="top">
		{if $field.translate.IMAGE}<img style="vertical-align: bottom;" alt="" src="/common/adm/img/lang/{$field.translate.IMAGE}.gif"/>{/if} {if $field.editor && !$field.disabled}<a href="" onclick="open_editor( '{$field.name}' ); return false">{$field.title}</a>{else}{$field.title}{/if}{if $field.nonempty}&nbsp;<span class="err">*</span>{/if}:<br/>
	</td>
	<td class="td22" valign="bottom">
		{if $field.type=="textarea"}
			<textarea style="width: 100%; height: 103px;{if $field.disabled} background: #e1e5e3;{/if}" name="{$field.name}" cols="50" rows="{$field.rows}" lang="errors{$field.prefix}"{if $field.disabled} disabled="disabled"{/if}>{$field.value}</textarea>
		{elseif $field.type=="checkbox"}
			<input type="hidden" name="{$field.name}" value="0"/>
			<input style="height: 13px; width: 13px;" type="checkbox" name="{$field.name}" value="1"{if $field.value} checked="checked"{/if}{if $field.disabled} disabled="disabled"{/if}/>
		{elseif $field.type=="select1" || $field.type=="select2" || $field.type=="parent"}
			<select onfocus="Calendar.hide();" style="width: 350px;{if $field.disabled} background: #e1e5e3;{/if}" name="{$field.name}" lang="errors{$field.prefix}"{if $field.disabled} disabled="disabled"{/if}>
				<option value="">
				{foreach from=$field.value_list item=option name=option}
					<option value="{$option._VALUE|escape}"{if $option._VALUE==$field.value} selected="selected"{/if}{if $option._class} class="{$option._class}"{/if}>{if !is_null($option.TREE_DEEP)}&nbsp;&nbsp;{section name=offset start=0 loop=$option.TREE_DEEP}&nbsp;&nbsp;{/section}{/if}{$option._TITLE|escape}</option>
				{/foreach}
			</select>
		{if $field.vars.message}
		<div>
			<img src="/common/adm/img/messages/warning.gif" style="width: auto; height: auto; border: none" align="absmiddle"> {$field.vars.message.title|escape}
		</div>
		{/if}
		{elseif $field.type=="date"}
<table border="0" cellspacing="0" cellpadding="0">
<tr>
	<td><input style="width: 120px;{if $field.disabled} background: #e1e5e3;{/if}" type="text" name="{$field.name}" value="{$field.value}" lang="errors{$field.prefix}"{if $field.disabled} disabled="disabled"{/if}/></td>
{if !$field.disabled}
<td><a href="" onClick="Calendar.show( document.forms['{$form_name}']['{$field.name}'], this, 'short' ); return false"><img src="/common/adm/img/calendar.gif" width="16" height="16" border="0" alt="{$lang_calendar}" title="{$lang_calendar}" style="margin: 0px 0px 0px 4px; width: 16; height: 16; border: 0px solid;"></a></td>
{/if}
</tr>
</table>
		{elseif $field.type=="datetime"}
<table border="0" cellspacing="0" cellpadding="0">
<tr>
	<td><input style="width: 120px;{if $field.disabled} background: #e1e5e3;{/if}" type="text" name="{$field.name}" value="{$field.value}" lang="errors{$field.prefix}"{if $field.disabled} disabled="disabled"{/if}/></td>
{if !$field.disabled}
<td><a href="" onClick="Calendar.show( document.forms['{$form_name}']['{$field.name}'], this, 'long' ); return false"><img src="/common/adm/img/calendar.gif" width="16" height="16" border="0" alt="{$lang_calendar}" title="{$lang_calendar}" style="margin: 0px 0px 0px 4px; width: 16; height: 16; border: 0px solid;"></a></td>
{/if}
</tr>
</table>
		{elseif $field.type=="img"}
<table border="0" cellspacing="0" cellpadding="0" width="100%" style="padding: 0px 0px 10px 0px;">
<tr valign="top">
{if $field.value}
	<td style="padding: 0px 11px 10px 0px;"><img alt="" style="width: 150px;" width="150" src="{$field.value}"/></td>
{/if}
    <td width="100%">
            <input size="100%" style="width: 100%; margin: 0px 0px 4px 0px;{if $field.disabled} background: #e1e5e3;{/if}" type="file" name="{$field.name}_file"{if $field.disabled} disabled="disabled"{/if}/>
			<input style="width: 100%;{if $field.disabled} background: #e1e5e3;{/if}" type="text" name="{$field.name}" value="{$field.value}" lang="errors{$field.prefix}"{if $field.disabled} disabled="disabled"{/if}/>
    </td>
</tr>
</table>
        {elseif $field.type=="file"}
			<input size="100%" style="width: 100%; margin: 0px 0px 4px 0px;{if $field.disabled} background: #e1e5e3;{/if}" type="file" name="{$field.name}_file"{if $field.disabled} disabled="disabled"{/if}/>
			<input style="width: 100%;{if $field.disabled} background: #e1e5e3;{/if}" type="text" name="{$field.name}" value="{$field.value}" lang="errors{$field.prefix}"{if $field.disabled} disabled="disabled"{/if}/>
		{else}
        <input style="width: {if $field.type=="text"}100%{elseif $field.type=="int"}140px{elseif $field.type=="float"}140px{elseif $field.type=="order"}140px{/if};{if $field.disabled} background: #e1e5e3;{/if}" type="text" name="{$field.name}" value="{$field.value}" lang="errors{$field.prefix}"{if $field.vars.maxlength} maxlength="{$field.vars.maxlength}"{/if}{if $field.disabled} disabled="disabled"{/if}>
		{/if}
	</td>
</tr>
{/foreach}
