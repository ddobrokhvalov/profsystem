<div style="overflow: hidden;">
	<h2 style="background: url(/common/adm/img/tabs/icons/{$section_icon}) no-repeat 0px 0px; padding: 10px 0px 14px 42px; margin: 0px 0px 8px 0px;">{$section_title}</h2>
</div>
<div id="TreeMenu" style="margin: 0px 0px 0px 0px;"></div>
<script>
	var TreeMenu = new Tree(); TreeMenu.init( 'TreeMenu', [ {$tree_list} ], {$tree_param} );
</script>
