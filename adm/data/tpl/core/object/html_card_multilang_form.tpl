{literal}
<script type="text/javascript" languare="javascript">
	function show_and_hide_langs()
	{
		var oForm = document.{/literal}{$form_name}{literal};
		for( var i = 0; i < oForm.elements.length; i++ )
			if ( langMatch = oForm.elements[i].name.match( /^_lang_(\d+)$/ ) )
				document.getElementById( '_form_lang_' + langMatch[1] ).style.display =
					oForm.elements[i].checked ? '' : 'none';
	}
</script>
{/literal}
<tr>
	<td>
		<table border="0" width="100%">
{$top_additional_fields}
		</table>
	</td>
</tr>

{foreach from=$lang_fields item=fields name=fields}
<tr>
	<td>
		<div class="table_footer">
        {$fields.lang_name}
		<input type="hidden" name="_check_lang_{$fields.lang_id}" value="{$fields.lang_enabled}">
		<input style="height: 13px; width: 13px;" type="checkbox" name="_lang_{$fields.lang_id}" value="1"{if $smarty.foreach.fields.first} checked="checked"{/if} onclick="show_and_hide_langs()">
        </div>
	</td>
</tr>
<tr id="_form_lang_{$fields.lang_id}"{if !$smarty.foreach.fields.first} style="display: none"{/if}>
	<td>
		<table border="0" width="100%">
{if ($fields.blocked)}

<div class="spacer" style="width: 0px; height: 8px;"><img src="/common/adm/img/emp.gif" alt="" border="0" height="1" width="1"></div>
        <div class="errors-t"><div><img src="/common/adm/img/emp.gif" alt="" border="0" height="1" width="1"></div></div>
        <div class="errors-box">
        <div><div class="img"><img src="/common/adm/img/messages/warning.gif" alt=""></div>{$fields.blocked_info}</div></div>
        <div class="errors-b"><div><img src="/common/adm/img/emp.gif" alt="" border="0" height="1" width="1"></div></div>
        <div class="spacer" style="width: 0px; height: 15px;"><img src="/common/adm/img/emp.gif" alt="" border="0" height="1" width="1"></div>

<div class="spacer" style="width: 0px; height: 8px;"><img src="/common/adm/img/emp.gif" alt="" border="0" height="1" width="1"></div>

{* А если страничка взята из кэша, надо проверить! *}
{elseif ($fields.blocking)}
	<script>
		cur_lang = "{$fields.lang_id}";
{literal}
		var lock_Checker = {
			check: function( xmlResponse, oParam ) {
				if (xmlResponse.documentElement.firstChild.nodeValue != 'ok')
					location.reload();
			}
		}

		Manager.sendCommand( 'index.php', {'obj': get_current_obj(), 'action': 'service', 'command': 'check_block_record', 'params': escape(location.search+'&CUR_LANG='+cur_lang)}, lock_Checker, 'check', null, true );
{/literal}
	</script>
{/if}		
{$fields.html_fields}
		</table>
	</td>
</tr>
{/foreach}
<tr>
	<td>
		<table border="0" width="100%">
{$additional_fields}
		</table>
	</td>
</tr>
<script>
{if ($blocking)}
	// for ping interface.js
	if (self.pingInterval)
		clearInterval(pingInterval);
	self.pingInterval = setInterval( 'pingServer()', {$ping_time} );


	// if we are exiting from the page, we have to inform server about it because server must unlock record
	addListener(self, 'beforeunload', self.unblock_record);
	
	if (frm=document.forms['{$form_name}']) 
		addListener(frm, 'submit', self.remove_unblock_record);
{/if}
</script>