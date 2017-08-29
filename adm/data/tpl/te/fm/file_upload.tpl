<h2 style="margin: 10px 0px 11px 0px;">{$lang_fm_upload_file}</h2>

{literal}
<script>
	function fnAddRow()
	{
		var oList = document.getElementById( 'upload_list' );
		var cItems = oList.getElementsByTagName( 'div' );
		if ( cItems.length < 11 )
		{
			oList.appendChild( cItems[0].cloneNode( true ) );
			oList.lastChild.style.display = 'block';
		}
	}
	
	function fnDelRow( oItem )
	{
        for ( var oDiv = oItem; oDiv.tagName != 'DIV'; oDiv = oDiv.parentNode );
        
		var oList = document.getElementById( 'upload_list' );
		var cItems = oList.getElementsByTagName( 'div' );
		if ( cItems.length > 2 )
		{
			oDiv.parentNode.removeChild( oDiv );
		}
	}
</script>
{/literal}
<form action="index.php" method="post" enctype="multipart/form-data">

<div class="fm-zag">{$lang_fm_select_files}:</div>

<div id="upload_list">
    <div class="fm-fields" style="display: none">
	    <table border="0" cellspacing="0" cellpadding="0">
	        <tr>
	        	<td><input style="width: 318px;" size="46" type="file" name="file[]"/></td>
	            <td class="curhand"><img src="/common/adm/img/fm/plus.gif" width="19" height="18" border="0" alt="{$lang_fm_add_any_more}" title="{$lang_fm_add_any_more}" onclick="fnAddRow(); return false" style="cursor: pointer;"></td>
	            <td class="curhand"><img src="/common/adm/img/fm/minus.gif" width="19" height="18" border="0" alt="{$lang_fm_remove_field}" title="{$lang_fm_remove_field}" onclick="fnDelRow(this)" style="cursor: pointer;"></td>
	        </tr>
	    </table>
    </div>
    <div class="fm-fields">
	    <table border="0" cellspacing="0" cellpadding="0">
	        <tr>
	            <td><input style="width: 318px;" size="46" type="file" name="file[]"/></td>
	            <td class="curhand"><img src="/common/adm/img/fm/plus.gif" width="19" height="18" border="0" alt="{$lang_fm_add_any_more}" title="{$lang_fm_add_any_more}" onclick="fnAddRow(); return false" style="cursor: pointer;"></td>
	        </tr>
	    </table>
    </div>
</div>

<div style="font-size: 10px; margin: 0px 0px 0px 0px;"><input type='checkbox' id="rewrite" name="rewrite" value="1"/>&nbsp;<label for="rewrite">{$lang_fm_rewrite}</label></div>

<div style="padding: 0px 0px 20px 0px;">
	{$html_hidden}
	
    <table border="0" cellspacing="0" cellpadding="0">
<tr>
	<td style="padding: 0px 10px 0px 0px"><span class="left-black"><span class="right-black"><input class="button-black" type="submit" value="{$lang_fm_upload}" /></span></span></td><td><span class="left-black"><span class="right-black"><input class="button-black" type="button" value="{$lang_fm_cancel}" onclick="document.location.href = '{$back_url}'" /></span></span></td>
</tr>
</table>

</div>

</form>
