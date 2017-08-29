<table border="1" bordercolor="#000000" cellspacing="0" cellpadding="2">
	<tr>
		<td bgcolor='#cccccc'>Название</td>
		<td bgcolor='#cccccc'>Интервал, с</td>
		<td bgcolor='#cccccc'>Доля, %</td>
	</tr>
	{foreach from=$report item=item name=item}
	<tr>
		<td bgcolor='#ffffff'>{$item.message}</td>
		<td bgcolor='#ffffff'>{$item.interval}</td>
		<td bgcolor='{$item.color}'>{$item.percent}</td>
	</tr>
	{/foreach}
	<tr>
		<td bgcolor='#ccffcc'>Сумма</td>
		<td bgcolor='#ccffcc'>{$sum}</td>
		<td bgcolor='#ccffcc'>100</td>
	</tr>
</table>