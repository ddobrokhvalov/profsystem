<form action="{$search_page_url}">
<table>
<tr>
<td><input type="Text" style="width: 150px;" {if ($q_param.q)}value="{$q_param.q}"{else}value="{$sysw_search_what}" onfocus="javascript:this.value=''"{/if} name="q_{$tarea_id}"></td>
<td><input type="submit" border="0" value="{$sysw_search}" title="{$sysw_search}" class="button"></td>
</tr>
</table>
</form>

{if ($q_param.q)}
<br>
<h1>{$sysw_search_results_title}</h1>
{$search_result}
{/if}