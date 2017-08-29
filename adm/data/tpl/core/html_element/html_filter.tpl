<table border="0" cellspacing="0" cellpadding="0" style="margin: 2px 0px 8px 0px;">
	<tr>
{if $fields_count > 1}
		<td>
			<table border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
				<tr>
					<td style="padding-top: 34px;">
						<div class="box-tline"><div class="box-t"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div>
					</td>
					<td align="right" style="background: url(/common/adm/img/filter/bg-1.gif) no-repeat 100% 0%; width: 197px;">
{if $fields_count}
						<table cellspacing="0" cellpadding="0">
							<tr>
								<td>
									<div style="padding: 7px 10px 12px 8px"><img src="/common/adm/img/filter/glass.gif" width="25" height="19" border="0" alt=""/></div>
								</td>
								<td class="filter">
									<select onfocus="Calendar.hide();" style="width: 127px;" onchange="Filter.selectFilter( this )">
										<option value="" selected="selected"/>{$lang_add_filter}</option>
{foreach from=$fields item=field name=field}
										<option value="{$field.name|escape}">{$field.title|escape}</option>
{/foreach}
									</select>
								</td>
								<td>
									<div style="padding: 0px 7px 0px 5px"><img id="findicator" width="9" height="9" border="0" alt="" style="cursor: pointer"/></div>
								</td>
							</tr>
						</table>
{/if}
					</td>
				</tr>
			</table>
		</td>
{else}
		<td>
			<div class="box-tline"><div class="box-t"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div></div>
		</td>
{/if}
	</tr>
	<tr>
		<td style="background: #f0f2f1; padding: 6px 0px 6px 0px; border-left: 1px solid #e1e5e3; border-right: 1px solid #e1e5e3;">
			<form name="{$form_name}" action="index.php" method="get" onsubmit="return Filter.submitFilter()">
{foreach from=$fields item=field name=field}
				<div id="{$field.name}" title="{$field.title}" style="display: {if $field.display}block{else}none{/if}">
					<table style="width: 100%">
						<tr>
							<td class="td1" style="width: 98%">
								{$field.title}:
							</td>
							<td class="filter" style="width: 1%">
{if $field.type=="checkbox"}
{if $field.view_type=="flag"}
								<div style="width: 214px; vertical-align: middle">
									<input type="checkbox" name="{$field.name}" value="1"{if $field.value} checked="checked"{/if} style="height: 13px; width: 13px; margin: 0px; padding: 0px;"/>
								</div>
{else}
								<select onfocus="Calendar.hide();" name="{$field.name}" style="width: 212px;">
									<option value=""/>
									<option value="1"{if $field.value === "1"} selected="selected"{/if}>{$lang_yes}</option>
									<option value="0"{if $field.value === "0"} selected="selected"{/if}>{$lang_no}</option>
								</select>
{/if}
{elseif $field.type=="select1" || $field.type=="select2"}
								<select onfocus="Calendar.hide();" name="{$field.name}" style="width: 214px;">
									<option value=""/>
{foreach from=$field.value_list item=option name=option}
									<option value="{$option._VALUE|escape}" {if $option._VALUE === $field.value} selected="selected"{/if}>{$option._TITLE|escape}</option>
{/foreach}
								</select>
{elseif $field.type=="date" || $field.type=="datetime"}
								<table border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td>
											<input style="width: 85px;" type="text" name="{$field.name}_from" value="{$field.value.from}" lang="errors{$field.prefix}"/>
										</td>
										<td style="padding: 0px 4px 0px 0px;">
											<a href="" onClick="Calendar.show( document.forms['{$form_name}']['{$field.name}_from'], this, '{if $field.type=="datetime"}long{else}short{/if}' ); return false"><img src="/common/adm/img/calendar.gif" width="16" height="16" border="0" alt="{$lang_calendar}" title="{$lang_calendar}" style="margin: 0px 0px 0px 4px; width: 16; height: 16; border: 0px solid;"></a>
										</td>
										<td style="width: 10px"/>
										<td>
											<input style="width: 85px;" type="text" name="{$field.name}_to" value="{$field.value.to}" lang="errors{$field.prefix}"/>
										</td>
										<td>
											<a href="" onClick="Calendar.show( document.forms['{$form_name}']['{$field.name}_to'], this, '{if $field.type=="datetime"}long{else}short{/if}' ); return false"><img src="/common/adm/img/calendar.gif" width="16" height="16" border="0" alt="{$lang_calendar}" title="{$lang_calendar}" style="margin: 0px 0px 0px 4px; width: 16; height: 16; border: 0px solid;"></a>
										</td>
									</tr>
								</table>
{else}
								<input type="text" name="{$field.name}" value="{$field.value}" style="width: 214px;"/>
{/if}
							</td>
							<td class="td2" style="width: 1%">
{if $fields_count > 1}
								<img src="/common/adm/img/filter/up2.gif" style="width: 9px; height: 9px; cursor: pointer" onmousedown="Filter.dropFilter( '{$field.name}' )"/>
{else}
<img src="/common/adm/img/emp.gif" width="9" height="1" border="0" alt="">
{/if}
							</td>
						</tr>
					</table>
				</div>
{/foreach}
				<div id="search">
					<table border="0" style="width: 100%">
						<tr>
							<td class="td1" style="padding-left: 15px;">
								{if (!$no_search_text)}
									{$lang_search}:
								{/if}
							</td>
							<td style="width: 231px;">
                                <table border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td class="filter" style="width: 147px;">
									{if (!$no_search_text)}
										<input type="text" name="search" value="{$search}" style="width: 147px"/>
									{/if}
                                    </td>
                                    <td class="td2" align="right" style="width: 52px;">
                                    <span class="left-black"><span class="right-black"><input class="button-black" type="submit" value="{$lang_search}" /></span></span>
                                    </td>
                                    <td><div class="spacer" style="height: 0px; width: 24px;"></div></td>
                                </tr>
                                </table>
                                
                   			</td>
						</tr>
					</table>
                 </div>   
				
{$html_hidden}
				<input type="hidden" name="display_fields" value=""/>
			</form>
		</td>
	</tr>
	<tr>
		<td>
			<div class="box-bline"><div class="box-b"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div></div>
		</td>
	</tr>
    <tr>
        <td style="font-size: 0px;"><img src="/common/adm/img/emp.gif" width="295" height="1" border="0" alt="" style="font-size: 0px;"></td>
    </tr>
</table>
<script>
	Filter.init( '{$form_name}', {$fields_count} );
</script>
