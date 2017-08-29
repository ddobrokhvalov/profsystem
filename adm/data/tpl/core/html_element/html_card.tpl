<table cellspacing="0" cellpadding="0" style="width: 100%">
	<tr>
		<td align="left" valign="top">
			<h2 style="margin: 10px 5px 5px 5px">{$title|escape}</h2>
		</td>
		<td align="right" valign="top" rowspan="2" style="background: url(/common/adm/img/tabs/top/t-menu2-border.gif) repeat-x bottom">
{$header|default:"&nbsp;"}
			<img src="/common/adm/img/tabs/gray-tr1.gif" width="3" height="1" border="0" alt=""><br/>
		</td>
	</tr>
	<tr>
		<td align="left" valign="bottom" style="width: 100%; background: url(/common/adm/img/tabs/top/t-menu2-border.gif) repeat-x bottom">
{if $tabs}
			<table border="0" cellspacing="0" cellpadding="0" style="width: 100%">
				<tr>
					<td style="width: 100%">
			<div class="top_menu2" id="tab_scroll_div">
				<table border="0" cellspacing="0" cellpadding="0" id="tab_scroll_table">
					<tr>
{foreach from=$tabs item=tab}
						<td style="background: url(/common/adm/img/tabs/top/t-menu2-border.gif) repeat-x bottom;" onmousedown="{if $form_name}if ( FormState.confirmChange() ) {/if}redirect( event, {ldelim} 'url': '{$tab.url}' {rdelim} )"{if $tab.active} active="active"{/if}>
							<div class="bmark2-bg{if $tab.active}-s{/if}">
								<div class="bmark">
									<div title="{$tab.title}">
										<a href="{$tab.url}" onclick="return false">{$tab.title}</a>
									</div>
								</div>
							</div>
						</td>
{/foreach}
					</tr>
				</table>
			</div>
					</td>
					<td id="tab_scroll_image_left" class="tab_move_left_hidden" onmousedown="TabScroll.start( -1 )" onmouseup="TabScroll.stop()"><div/></td>
					<td id="tab_scroll_image_right" class="tab_move_right_hidden" onmousedown="TabScroll.start( +1 )" onmouseup="TabScroll.stop()"><div/></td>
				</tr>
			</table>
			<script>
				TabScroll.init( 'tab_scroll_div', 'tab_scroll_table', 'tab_scroll_image_left', 'tab_scroll_image_right' );
			</script>
{/if}
		</td>
	</tr>
{if $filter}
	<tr>
		<td align="right" valign="top" colspan="2" class="box2" style="padding: 5px">
{$filter}
		</td>
	</tr>
{/if}
	<tr>
		<td align="left" valign="top" colspan="2">
			<table border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr>
					<td class="box2">
						<table border="0" cellspacing="0" cellpadding="0" width="100%">
							<tr>
								<td class="pad2">
{if ($blocked)}

<div class="spacer" style="width: 0px; height: 8px;"><img src="/common/adm/img/emp.gif" alt="" border="0" height="1" width="1"></div>
        <div class="errors-t"><div><img src="/common/adm/img/emp.gif" alt="" border="0" height="1" width="1"></div></div>
        <div class="errors-box">
        <div><div class="img"><img src="/common/adm/img/messages/warning.gif" alt=""></div>{$blocked_info}</div></div>
        <div class="errors-b"><div><img src="/common/adm/img/emp.gif" alt="" border="0" height="1" width="1"></div></div>
        <div class="spacer" style="width: 0px; height: 15px;"><img src="/common/adm/img/emp.gif" alt="" border="0" height="1" width="1"></div>

<div class="spacer" style="width: 0px; height: 8px;"><img src="/common/adm/img/emp.gif" alt="" border="0" height="1" width="1"></div>

{* А если страничка взята из кэша, надо проверить! *}
{elseif ($blocking)}
	<script>
		var blocked_forms = new Array()
		var i=0
{section name=block loop=$block_forms}
		blocked_forms[i++] = "{$block_forms[block]}"
{sectionelse}
		blocked_forms[i++] = "{$form_name}"
{/section}
{literal}
		var lock_Checker = {
			check: function( xmlResponse, oParam ) {
				if (xmlResponse.documentElement.firstChild.nodeValue != 'ok')
					location.reload();
			}
		}

		Manager.sendCommand( 'index.php', {'obj': get_current_obj(), 'action': 'service', 'command': 'check_block_record', 'params': escape(location.search)}, lock_Checker, 'check', null, true );
{/literal}
	</script>
{/if}
{$form}

{if ($blocked)}
<script>
{section name=block loop=$block_forms}
disable_form_controls ("{$block_forms[block]}")
{sectionelse}
disable_form_controls ("{$form_name}")
{/section}
</script>

{/if}

								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
            <div class="box2-bline"><div class="box2-b"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div></div>
		    <div class="spacer" style="height: 9px; width: 0px;"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div>
		</td>
	</tr>
	<tr>
		<td align="{if $navigation}left{else}right{/if}" valign="top" colspan="{if $navigation}{else}2{/if}">
{$footer|default:"&nbsp;"}
		</td>
		{if $navigation}
        <td align="right" valign="top">
{$navigation|default:"&nbsp;"}
		</td>
        {/if}
	</tr>
</table>

{if $form_name && $tabs}
<script>
		FormState.init( '{$form_name}' );
</script>
{/if}

<script>
{if ($blocking)}
	// for ping interface.js
	if (self.pingInterval)
		clearInterval(pingInterval);
	self.pingInterval = setInterval( 'pingServer("&blocking=1")', {$ping_time} );

	// if we are exiting from the page, we have to inform server about it because server must unlock record
	addListener(self, 'beforeunload', self.unblock_record);
	
	if (frm=document.forms['{$form_name}']) 
		addListener(frm, 'submit', self.remove_unblock_record);
{/if}
</script>