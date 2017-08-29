<div class="gray full breadcrumbs ">
				<div class="container"><div class="section clearfix">
    <div class="sixteen columns navigation no-margin">
		<ul>
{foreach from=$ITEMS item="LEVEL1" name="LEVEL1"}
	<li>{if !$smarty.foreach.LEVEL1.last && $LEVEL1.URL}<a href="{$LEVEL1.URL}">{/if}{$LEVEL1.TITLE}{if !$smarty.foreach.LEVEL1.last && $LEVEL1.URL}</a></li>{else}</li>{/if}
<li><img src="/common/upload/img/elem/nav-arrow.png"></li>
{/foreach}
</ul>
	</div>
</div>
</div>
			</div>