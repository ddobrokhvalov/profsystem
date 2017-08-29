<form action="" method="post" name="form_block" enctype="multipart/form-data" onsubmit="return CheckForm.validate( this )">

    <div class="hr2">
    	<div>{$lang_change_block} <span class="err">*</span>:</div>
    	<select style="width: 230px;" id="_form_INF_BLOCK_ID" name="_form_INF_BLOCK_ID" lang="errors_nonempty__int_">
    		<option value=""></option>
{foreach from=$block_list item=block_item}
			<option value="{$block_item.INF_BLOCK_ID}"{if $block_item.SELECTED} selected="selected"{/if}>{$block_item._TITLE|escape}</option>
{/foreach}
		</select>
	</div>
    
    <div class="hr2">
	    <div>{$lang_from_module}:</div>
    	<select style="width: 230px;" id="_form_PRG_MODULE_ID" name="_form_PRG_MODULE_ID" onchange="setSelect( '_form_INF_BLOCK_ID', aBlocks, {literal}{{/literal} 'name': 'module', 'value': this.options[this.selectedIndex].value } )">
    		<option value=""></option>
{foreach from=$module_list item=module_item}
			<option value="{$module_item.PRG_MODULE_ID}"{if $module_item.SELECTED} selected="selected"{/if}>{$module_item._TITLE|escape}</option>
{/foreach}
		</select>
	</div>
    
    <span class="left-black" style="margin: 10px 0px 0px 8px;"><span class="right-black"><input class="button-black" type="submit" value="{$lang_apply}" /></span></span>

    <div class="cboth"> </div>

   

{$html_hidden}
</form>
<script>
	var aBlocks = {$block_array};
{literal}
	function setSelect( sSelectId, aOptions, oFilter )
	{
		var oSelect = document.getElementById( sSelectId );
		oSelect.selectedIndex = 0;
		while ( oSelect.lastChild )
			 oSelect.removeChild( oSelect.lastChild );
		for ( var iOption in aOptions )
		{
			if ( oFilter.value == 0 || iOption == 0 ||
				aOptions[iOption][oFilter.name] == oFilter.value )
			{
				var oOption = document.createElement( 'option' );
				oOption.setAttribute( 'value', iOption );
				oOption.innerHTML = aOptions[iOption]['title'];
				oSelect.appendChild( oOption );
			}
		}
	}
{/literal}
</script>
