<form action="index.php?{foreach from=$get_params key=key item=param}{$key}={$param}&{/foreach}" method="POST">
{foreach from=$post_params key=key item=param}
<input type="hidden" name="{$key}" value="{$param}">
{/foreach}
<div class="spacer" style="width: 0px; height: 8px;"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div>
<div class="errors-t"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div>
<div class="errors-box">
	<div><img src="/common/adm/img/messages/warning.gif" alt="" align="absmiddle"/>{$lang_warning}:</div>
    <div>{$message}</div>
<div>
	<table border="0">
	<tr>
	<td>
	<span class="left-black" style="margin: 11px 0px 0px 0px;">
	 <span class="right-black">
	 	<input class="button-black"  type="submit" value="{$lang_yes}" name="ca_Yes">
	 </span>
	</span>
	</td>
	<td>
	<span class="left-black" style="margin: 11px 0px 0px 0px;">
	 <span class="right-black">
	 	<input  class="button-black"  type="submit" value="{$lang_no}" name="ca_No">
	 </span>
	</span>
	</td>
	</tr>
	</table>
</div>
</div>
<div class="errors-b"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div>
<div class="spacer" style="width: 0px; height: 5px;"><img src="/common/adm/img/emp.gif" width="1" height="15" border="0" alt=""></div>
</form>