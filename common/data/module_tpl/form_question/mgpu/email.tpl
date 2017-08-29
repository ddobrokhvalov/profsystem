<html>
<body>
<div class="container" style="padding:0 0 30px 0">

<div class="section clearfix">
<h1>{$form_name}</h1>
<table border="1">
{foreach from=$fields item=item}
{if $item.QUESTION_TYPE == 'separator'}
	<tr>
		<td colspan="2">
			<hr/>
		</td>
	</tr>
{else}
	<tr>
		<td>
			<b>{$item.TITLE}</b>
		</td>
		<td>
{if $item.QUESTION_TYPE == 'string'}
			{$item.VALUE}
{elseif $item.QUESTION_TYPE == 'int'}
			{$item.VALUE}
{elseif $item.QUESTION_TYPE == 'float'}
			{$item.VALUE}
{elseif $item.QUESTION_TYPE == 'email'}
			{$item.VALUE}
{elseif $item.QUESTION_TYPE == 'date'}
			{$item.VALUE}
{elseif $item.QUESTION_TYPE == 'textarea'}
			{$item.VALUE}
{elseif $item.QUESTION_TYPE == 'checkbox'}
			{$item.VALUE}
{elseif $item.QUESTION_TYPE == 'select'}
			{$item.VALUE}
{elseif $item.QUESTION_TYPE == 'radio_group'}
			{$item.VALUE}
{elseif $item.QUESTION_TYPE == 'radio_group_alt'}
			{$item.VALUE}
{elseif $item.QUESTION_TYPE == 'checkbox_group'}
{foreach from=$item.VALUE item=option}
			{$option}<br>
{/foreach}
{elseif $item.QUESTION_TYPE == 'checkbox_group_alt'}
{foreach from=$item.VALUE item=option}
			{$option}<br>
{/foreach}
{else}
			&nbsp;
{/if}
		</td>
	</tr>
{/if}
{/foreach}
</table>
</div>


</div>
</body>
</html>
