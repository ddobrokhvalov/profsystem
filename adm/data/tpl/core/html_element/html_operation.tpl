<div style="margin: 8px 0px 6px 0px">
	<table cellspacing="0" cellpadding="0">
		<tr>
{foreach from=$operations item=op name=op}
			<td>
{if $op.menu}
				<div id="MenuHeader{$op.name}{$label}"></div>
				<script>
					var MenuHeader{$op.name}{$label} = new Menu(); MenuHeader{$op.name}{$label}.init( 'MenuHeader{$op.name}{$label}', [ {$op.menu} ], 'big_image' );
				</script>
{else}
                <div class="menuh" onClick="{$op.onClick}" style="background: url(/common/adm/img/buttons/{$op.name}.gif) no-repeat center 0px;" title="{$op.alt}"><a href="{$op.url}"><span>{$op.alt}</span></a></div>
{/if}
			</td>
{if !$smarty.foreach.op.last}
			<td><img src="/common/adm/img/f-hr3.gif" width="1" height="36" border="0" style="margin: 8px 8px 8px 8px" alt=""></td>
{/if}
{/foreach}
		</tr>
	</table>
</div>
