<table cellspacing="0" cellpadding="0" style="width: 100%">
	<tr>
		<td align="left" valign="top">
			<h2 style="margin: 10px 5px 5px 5px">{$title|escape}</h2>
		</td>
		<td align="right" valign="top" rowspan="2">
{$filter|default:"&nbsp;"}
		</td>
	</tr>
	<tr>
		<td align="left" valign="bottom">
{$header|default:"&nbsp;"}
		</td>
	</tr>
	<tr>
		<td align="left" valign="top" colspan="2">
{$table|default:"&nbsp;"}
		</td>
	</tr>
	<tr>
		<td align="left" valign="top">
{$footer|default:"&nbsp;"}
		</td>
		<td align="right" valign="top">
{$navigation|default:"&nbsp;"}
		</td>
	</tr>
</table>