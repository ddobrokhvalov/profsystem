{$text_before}
<form name="checkbox_form" method="post" {if $header._group}onsubmit="return CheckFill()"{/if}>
{$html_hidden}
<table cellspacing="0" cellpadding="0" class="table_osn" border="0">
{if ($caption)}
	<caption>{$caption}</caption>
{/if}
	<tr class="th">
{foreach from=$header item=head name=head}
{if $smarty.foreach.head.first}
		<th align="left" valign="top" style="width: 0%; height: 7px;"><img src="/common/adm/img/tabs/table_osn/t-tl2.gif" width="6" height="7" border="0" alt=""><br/></th>
		<th rowspan="2" {if $head.width}width="{$head.width}"{elseif $head.is_main}width="100%"{/if}>
{else}
		<th rowspan="2"{if $head.sort_ord=="asc"} class="sort"{elseif $head.sort_ord=="desc"} class="sort"{/if}{if $head.width} style="width: {$head.width};"{elseif $head.is_main} style="width: 100%;"{/if}>
{/if}
{if $head.type=="_group"} {* Для группового чекбокса *}
			<div class="top2">
				<div class="pad" style="vertical-align: middle;">
					<input style="vertical-align: middle;" id="check_all{$head.column}" type="checkbox" value="1" onclick="CheckAllBoxes( '{$head.column}', this )"/>{if $head.title}<span style="font-size: 10px; font-weight: bold; color: #ffffff; margin: 0px 0px 0px 3px;">{$head.title}</span>{/if}
				</div>
			</div>
{else}
			<div class="top">
{if $head.sort_url}
				<a href="{$head.sort_url}">{/if}{$head.title}{if $head.sort_url}</a>
{/if}
{if $head.sort_ord=="asc"}
				<img src="/common/adm/img/tabs/table_osn/down.gif" border="0" alt=""/>
{elseif $head.sort_ord=="desc"}
				<img src="/common/adm/img/tabs/table_osn/up.gif" border="0" alt=""/>
{/if}
			</div>
{/if}
{if $smarty.foreach.head.last}
		</th>
		<th align="right" valign="top" style="text-align: right; width: 0px; padding: 0px 0px 0px 0px;"><img src="/common/adm/img/tabs/table_osn/t-tr2.gif" alt=""></th>
{else}
		</th>
{/if}
{/foreach}
	</tr>
	<tr class="th2">
{foreach from=$header item=head name=head}
{if $smarty.foreach.head.first && $smarty.foreach.head.last}
		<th align="left" valign="bottom"><img src="/common/adm/img/tabs/table_osn/t-bl.gif" width="6" height="5" border="0" alt=""><br></th>
		<th align="right" valign="bottom" style="text-align: right; width: 0px; padding: 0px 0px 0px 0px;"><img src="/common/adm/img/tabs/table_osn/t-br.gif" width="6" height="5" border="0" alt=""><br></th>
{elseif $smarty.foreach.head.first}
		<th valign="bottom"><img src="/common/adm/img/tabs/table_osn/t-bl.gif" width="6" height="5" border="0" alt=""><br></th>
{elseif $smarty.foreach.head.last}

		<th align="right" valign="bottom" style="text-align: right; width: 0px; padding: 0px 0px 0px 0px;"><img src="/common/adm/img/tabs/table_osn/t-br.gif" width="6" height="5" border="0" alt=""><br></th>
{/if}
{/foreach}
	</tr>
{foreach from=$list rowkey=rowkey item=row name=row}
	<tr id=tr_list_{$smarty.foreach.row.iteration} bgcolor="{if $smarty.foreach.row.iteration is odd}#ffffff{else}#edefee{/if}" onmouseover="this.style.backgroundColor = '#f7f0cc';" onmouseout="this.style.backgroundColor = '{if $smarty.foreach.row.iteration is odd}#ffffff{else}#edefee{/if}';">
{foreach from=$header key=key item=head name=head} {* Итерируемся по заголовку, чтобы выводились только те поля, что есть в заголовке и в нужном порядке *}
		<td{if $head.type=="_ops"} style="width: 10%;"{/if}{if $smarty.foreach.head.first && $smarty.foreach.head.last} colspan="3"{elseif $smarty.foreach.head.first || $smarty.foreach.head.last} colspan="2"{/if}{if $head.align} align="right"{elseif $key == "_number"} align="center"{/if}{if $head.type == '_group' && $row.class} class="{$row.class}"{/if}{if $head.type=='_group' && $row[$key].id && !$row[$key].disabled} onclick="CheckBoxCellClick( 'group_id_{$row[$key].id}' )"{/if}><table border="0" cellspacing="0" cellpadding="0">
<tr>
	<td>
{if $head.is_main}
			{section name=offset start=0 loop=$row.TREE_DEEP}<div style="margin: 0px 0px 0px 20px">{/section}
{/if}
			<div class="pad"{if $head.is_main && $row._icon} style="background: url(/common/adm/img/menu/icons/{$row._icon}.gif) no-repeat 0% 50%; padding: 3px 0px 1px 22px;"{/if}>
				{if $head.is_main}<span class="normal{if $row.SELECTED} normal_select{/if}">{/if}
				{if $head.is_main}{if $row._hier_url}<a href="{$row._hier_url}">{/if}{/if}
				{* Перечисление типов полей, которые должны выводиться особым образом *}
{if $head.type=="checkbox"}
					{if $row[$key]}<img src="/common/adm/img/messages/success.gif" width="16" height="16" border="0" alt="">{else}&nbsp;{/if}
{elseif $head.type=="_group"}{* Специальный тип поля для чекбоксов групповых операций и м2м *}
					{if $row[$key].hidden && !$row[$key].disabled}<input type="hidden" name="group_id_{$row[$key].id}" value="0">{/if}
					{if $row[$key].id}<input type="checkbox" column="{$header[$key].column}" name="group_id_{$row[$key].id}" value="1" {if $row[$key].checked}checked="checked"{/if} {if $row[$key].disabled}disabled="disabled"{/if} onclick="stopEventCascade( event )">{/if}
{elseif $head.type=="_link"}{* Специальный тип поля для кнопок связей 1:M и M:M *}
					<div class="nowrap">
						{if isset($row[$key])}{if isset($row[$key].url) && $row[$key].url}<a href="{$row[$key].url}">{if isset($row[$key].title)}{if $head.escape}{$row[$key].title|escape}{else}{$row[$key].title}{/if}{else}{$lang_go}{/if}</a>{else}{if isset($row[$key].title)}{if $head.escape}{$row[$key].title|escape}{else}{$row[$key].title}{/if}{/if}{/if}{else}&nbsp;{/if}
					</div>
{elseif $head.type=="_ops"}{* Специальный тип поля для кнопок стандартных операций *}
					<table border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td><div id="Menu{$smarty.foreach.row.iteration}"></div></td>
						</tr>
					</table>
					<script>
						var Menu{$smarty.foreach.row.iteration} = new Menu(); Menu{$smarty.foreach.row.iteration}.init( 'Menu{$smarty.foreach.row.iteration}', [ {$row[$key]} ] );
					</script>
{elseif $head.type=="_list"}{* Специальный тип поля для списков значений *}
					<div class="nowrap">
{foreach from=$row[$key] item=list_element}
						{if isset($list_element.url) && $list_element.url}<a href="{$list_element.url}">{if isset($list_element.title)}{if $head.escape}{$list_element.title|escape}{else}{$list_element.title}{/if}{else}{$lang_go}{/if}</a>{else}{if isset($list_element.title)}{if $head.escape}{$list_element.title|escape}{else}{$list_element.title}{/if}{/if}{/if}<br/>
{/foreach}
					</div>
{elseif $head.type=="order"}{* Специальный тип поля отображения порядка записей *}
<table border="0" cellspacing="0" cellpadding="0">
<tr>
	<td style="width: 27px;">{$row[$key].order}</td>
    <td style="width: 19px;">{if $row[$key].up}<a href="{$row[$key].up}"><img src="/common/adm/img/move_up.gif" alt="{$lang_move_up}" title="{$lang_move_up}"/></a>{/if}</td>
    <td style="width: 11px;">{if $row[$key].down}<a href="{$row[$key].down}"><img src="/common/adm/img/move_down.gif" alt="{$lang_move_down}" title="{$lang_move_down}"/></a>{/if}</td>
</tr>
</table>	
{else}{* Поля всех типов кроме вышеперечисленных, а также поля без типа *}
{if is_null($row[$key]) || $row[$key]===""}{* Поле без значения *}
						&nbsp;
{else}
						{assign var="value" value=$row[$key]}
						{if $head.escape}{assign var="value" value=$value|escape}{/if}
						{if $head.nl2br}{assign var="value" value=$value|nl2br}{/if}
						{$value|replace:"\t":"&nbsp;&nbsp;"}
{/if}
{/if}
				{if $head.is_main}{if $row._hier_url}</a>{/if}{/if}
                {if $head.is_main}</span>{/if}
				{if $head.is_main && isset($row._path)}
				<div>
{if is_array($row._path)}
					<div class="status_line2">{foreach from=$row._path item=item name=path}{if !$smarty.foreach.path.first} / {/if}<a href="{$item._URL}">{$item._TITLE|escape}</a>{/foreach}</div>
{else}
					<span style="color: red">{$lang_path_build_failed}</span>
{/if}
				</div>
				{/if}
			</div>
{if $head.is_main}
			{section name=offset start=0 loop=$row.TREE_DEEP}</div>{/section}
{/if}
		</td>
        <td><img src="/common/adm/img/emp.gif" width="0" height="26" border="0" alt=""><br></td>
</tr>
</table></td>
{/foreach}
	</tr>
{/foreach}
</table>
</form>
{if isset( $counter ) && $counter !== ''}
<div class="table_footer">
	{$lang_total_records}: <b>{$counter}</b>
</div>
{/if}
{$text_after}
