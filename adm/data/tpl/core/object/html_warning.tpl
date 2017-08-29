<div class="spacer" style="width: 0px; height: 8px;"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div>
<div class="errors-t"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div>
<div class="errors-box">
	<div><div class="img"><img src="/common/adm/img/messages/warning.gif" alt=""></div>{$lang_warning}:</div>
    <div style="padding: 5px 0px 5px 24px;">{$msg}</div>
{if (sizeof($buttons))}
<div style="padding: 5px 0px 5px 24px;">
	<table border="0">
	<form action="index.php" method="POST" name="{$form_name}" enctype="multipart/form-data" onsubmit="return CheckForm.validate( this )">
		{$html_fields}
		{$html_hidden}
	<tr>
	{foreach from=$buttons key=msg item=link}
	<td>
	<span class="left-black" style="margin: 11px 0px 0px 0px;">
	 <span class="right-black">
		<input class="button-black" type="button" value="{$msg}" onClick="top.location.assign('{$link}')" />
	 </span>
	</span>
	</td>
	{/foreach}
	</tr>
	</form>
	</table>
</div>
{/if}    
</div>
<div class="errors-b"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div>
<div class="spacer" style="width: 0px; height: 5px;"><img src="/common/adm/img/emp.gif" width="1" height="15" border="0" alt=""></div>
{if $back_url}
<div><a href="{$back_url}">{$lang_back}</a></div>
{/if}
