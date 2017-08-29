<table border="1" bordercolor="#000000" cellspacing="0" cellpadding="2">
	<tr>
		<td bgcolor='#cccccc'>Название</td>
		{foreach from=$report item=item name=item}
		<td bgcolor='#ffffff'>{$item.message}</td>
		{/foreach}
		<td bgcolor='#ccffcc'>Сумма</td>
	</tr>
	<tr>
		<td bgcolor='#cccccc'>Интервал, с</td>
		{foreach from=$report item=item name=item}
		<td bgcolor='#ffffff'>{$item.interval}</td>
		{/foreach}
		<td bgcolor='#ccffcc'>{$sum}</td>
	</tr>
	<tr>
		<td bgcolor='#cccccc'>Доля, %</td>
		{foreach from=$report item=item name=item}
		<td bgcolor='{$item.color}'>{$item.percent}</td>
		{/foreach}
		<td bgcolor='#ccffcc'>100</td>
	</tr>
</table>