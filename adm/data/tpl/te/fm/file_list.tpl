<h2 style="margin: 10px 0px 11px 0px;">{$lang_fm_tool}</h2>

<script>
	var sBaseUrl = '{$base_url}';
	
{literal}
	function fnCreateDir( path ) {
		var name = prompt( '{$lang_fm_create_dir}: ', 'New_folder' );
		if ( name )
			document.location.href = fnMakeLink( 'create', path, name, 'dir' );
	}
	
	function fnCreateFile( path ) {
		var name = prompt( '{$lang_fm_create_file}: ', 'New_file' );
		if ( name )
			document.location.href = fnMakeLink( 'create', path, name, 'file' );
	}
	
	function fnRename( path, name ) {
		var new_name = prompt( '{$lang_fm_new_name}: ', unescape( name ) );
		if ( new_name && name != new_name )
			document.location.href = fnMakeLink( 'rename', path, name, new_name );
	}
	
	function fnDelete( path, name, no_empty )	{
		if ( !confirm( no_empty ? '{$lang_fm_confirm_delete_no_empty}' : '{$lang_fm_confirm_delete}' ) ) return;
		document.location.href = fnMakeLink( 'delete', path, name );
	}
	
	function fnEdit( path, name ) {
		document.location.href = fnMakeLink( 'edit', path, name );
	}
	
	function fnDownload( path, name ) {
		document.location.href = fnMakeLink( 'download', path, name );
	}
	
	function fnUpload( path ) {
		document.location.href = fnMakeLink( 'upload', path );
	}
	
	function fnHome() {
		document.location.href = fnMakeLink();
	}
	
	function fnMakeLink( action, path, name, new_name )
	{
		var link = sBaseUrl;
		if ( action )
			link += '&action=' + action;
		if ( path )
			link += '&path=' + path; 
		if ( name )
			link += '&name=' + name;
		if ( new_name )
			link += '&new_name=' + new_name;
		return link;
	}
{/literal}
</script>

<table border="0" width="100%">
	<tr>
		<td width="100%" class="status_line" style="padding-left: 5px;">
			<img src="/common/adm/img/home.gif" width="11" height="10" border="0" align="absmiddle" alt="{$lang_home}" title="{$lang_home}" onclick="fnHome()" style="cursor: pointer;"/>
            {foreach from=$status_line item=item name=item}<img src="/common/adm/img/f-hr1.gif" width="2" height="3" border="0" alt="" align="absmiddle" style="margin: 0px 6px 0px 6px;">{if $smarty.foreach.item.last}{$item.name}<img src="/common/adm/img/f-hr2.gif" width="5" height="5" border="0" alt="" align="absmiddle" style="margin: 0px 6px 0px 6px;">{else}<a href="index.php?obj=FM&path={$item.link}&sort={$sort}&order={$order}" class="status">{$item.name}</a>{/if}{foreachelse}&nbsp;{/foreach}
		</td>
		<td align="left" class="curhand" style="white-space: nowrap;">
{if $current_path != '/'}
			<img src="/common/adm/img/fm/newfolder.gif" class="action" title="{$lang_fm_create_dir}" onclick="fnCreateDir('{$current_path}')"/>
			<img src="/common/adm/img/fm/newfile.gif" class="action" title="{$lang_fm_create_file}" onclick="fnCreateFile('{$current_path}')"/>
			<img src="/common/adm/img/fm/upload.gif" class="action" title="{$lang_fm_upload_file}" onclick="fnUpload('{$current_path}')"/>
{else}
			&nbsp;
{/if}
		</td>
	</tr>
</table>

<table border="0" cellspacing="0" cellpadding="0" class="table_osn">
	<tr class="th">
		<th valign="top" style="width: 0%;"><img src="/common/adm/img/tabs/table_osn/t-tl2.gif" width="6" height="7" border="0" alt=""><br></th>
        <th align="center" rowspan="2">&nbsp;</th>
		<th align="center" rowspan="2" width="100%"{if $sort == 'name'}{if $order == 'asc'} class="sort"{else} class="sort"{/if}{/if}>
			<div class="top"><a href="index.php?obj=FM&path={$current_path}&sort=name&order={if $sort == 'name' && $order == 'asc'}desc{else}asc{/if}">{$lang_pure_name}</a> {if $sort == 'name'}{if $order == 'asc'}<img src="/common/adm/img/tabs/table_osn/down.gif" border="0" alt="">{else}<img src="/common/adm/img/tabs/table_osn/up.gif" border="0" alt="">{/if}{/if}</div>
		</th>
{if $current_path != '/'}
		<th align="center" rowspan="2"{if $sort == 'size'}{if $order == 'asc'} class="sort"{else} class="sort"{/if}{/if}>
			<div class="top"><a href="index.php?obj=FM&path={$current_path}&sort=size&order={if $sort == 'size' && $order == 'asc'}desc{else}asc{/if}">{$lang_fm_size}</a> {if $sort == 'size'}{if $order == 'asc'}<img src="/common/adm/img/tabs/table_osn/down.gif" border="0" alt="">{else}<img src="/common/adm/img/tabs/table_osn/up.gif" border="0" alt="">{/if}{/if}</div>
		</th>
		<th align="center" rowspan="2"{if $sort == 'mtime'}{if $order == 'asc'} class="sort"{else} class="sort"{/if}{/if}>
			<div class="top"><a href="index.php?obj=FM&path={$current_path}&sort=mtime&order={if $sort == 'mtime' && $order == 'asc'}desc{else}asc{/if}">{$lang_date}</a> {if $sort == 'mtime'}{if $order == 'asc'}<img src="/common/adm/img/tabs/table_osn/down.gif" border="0" alt="">{else}<img src="/common/adm/img/tabs/table_osn/up.gif" border="0" alt="">{/if}{/if}</div>
		</th>
{/if}
        <th align="right" valign="top" style="text-align: right; width: 0px; padding: 0px 0px 0px 0px;"><img src="/common/adm/img/tabs/table_osn/t-tr2.gif" width="6" height="7" border="0" alt=""><br></th>
	</tr>
    <tr class="th2">
        <th valign="bottom"><img src="/common/adm/img/tabs/table_osn/t-bl.gif" width="6" height="5" border="0" alt=""><br></th>
        <th align="right" valign="bottom" style="text-align: right; width: 0px; padding: 0px 0px 0px 0px;"><img src="/common/adm/img/tabs/table_osn/t-br.gif" width="6" height="5" border="0" alt=""><br></th>
    </tr>
{foreach from=$file_list item=path name=path}
	<tr style="background: {if $smarty.foreach.path.iteration is odd}#ffffff{else}#edefee{/if};" onmouseover="this.style.backgroundColor = '#f7f0cc'" onmouseout="this.style.backgroundColor = '{if $smarty.foreach.path.iteration is odd}#ffffff{else}#edefee{/if}'">
		<td>&nbsp;</td>
        <td align="center">
			<div class="pad" style="height: 19px;"><img src="/common/adm/img/fm/mime/{$path.icon}.gif" class="icon" alt=""/></div>
		</td>
		<td>
			<div class="pad" style="height: 19px;">
{if $path.is_dir}
{if $path.readable}
				<a href="index.php?obj=FM&path={$path.path}&sort={$sort}&order={$order}">{$path.pure_name}</a>
{else}
				<span class="normal">{$path.pure_name}</span>
{/if}
{else}
{if $path.readable}
				<span class="normal">{$path.pure_name}</span>
{else}
				<span class="normal" style="color: #bbbbbb">{$path.pure_name}</span>
{/if}
{/if}
			</div>
		</td>
{if $current_path != '/'}
		<td align="right">
			<div class="pad" style="height: 19px;">{$path.size|default:'&nbsp;'}</div>
		</td>
		<td align="right">
			<div class="pad" style="height: 19px;">{$path.mtime|default:'&nbsp;'}</div>
		</td>
{/if}
		<td align="left" colspan="2" style="white-space: nowrap;">
			<div class="pad curhand" style="height: 19px;">
{if $path.mtime}
{if $path.is_dir}
				<img src="/common/adm/img/fm/rename.gif" class="action" title="{$lang_fm_rename_dir}" onclick="fnRename('{$current_path}','{$path.pure_name}')"/>
				<img src="/common/adm/img/fm/remove.gif" class="action" title="{$lang_fm_delete_dir}" onclick="fnDelete('{$current_path}','{$path.pure_name}'{if $path.no_empty},true{/if})"/>
{else}
				<img src="/common/adm/img/fm/rename.gif" class="action" title="{$lang_fm_rename_file}" onclick="fnRename('{$current_path}','{$path.pure_name}')"/>
				<img src="/common/adm/img/fm/remove.gif" class="action" title="{$lang_fm_delete_file}" onclick="fnDelete('{$current_path}','{$path.pure_name}')"/>
{if $path.readable}
				<img src="/common/adm/img/fm/download.gif" class="action" title="{$lang_fm_download_file}" onclick="fnDownload('{$current_path}','{$path.pure_name}')"/>
{if $path.editable}
				<img src="/common/adm/img/fm/edit.gif" class="action" title="{$lang_fm_edit_file}" onclick="fnEdit('{$current_path}','{$path.pure_name}')"/>
{/if}
{/if}
{/if}
{else}
			&nbsp;
{/if}
			</div>
		</td>
	</tr>
{/foreach}
</table>
{if $current_path != '/' && isset( $file_count ) && $file_count !== ''}
<div class="table_footer">
	{$lang_total_records}: <b>{$file_count}</b>
</div>
{/if}
