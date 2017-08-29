<table style="width: 100%; margin: 0; padding: 0; border-collapse: collapse; border: none;">
	<tr>
		<td style="width: 100%; padding: 0px 0px 0px 0px; vertical-align: top; border-right: 1px solid #E2E4E3;">
			<div class="block_map">
{$html_map}
			</div>
		</td>
		<td style="vertical-align: top;">
            <div class="block_param">
                
                <h2>{$lang_area_editing}</h2>

                {if $cur_area.PRG_MODULE_SYSTEM_NAME}
                    
                    <div class="pad">
                    	<div class="block_area_descr">		
                            <div class="hr2">{$lang_area}: <b>{$cur_area.TITLE|escape}</b> ({$cur_area.SYSTEM_NAME|escape})</div>
                            <div class="hr2">{$lang_module}: {$cur_area.PRG_MODULE_TITLE|escape}</div>
                            <div>{$lang_block}: {$cur_area.INF_BLOCK_TITLE|escape}</div>
                        </div>
                    </div>
                    
                    <!-- hr -->
                    <div class="hr"> </div>
                    <!-- /hr -->
                    
                {/if}
                
                <div class="pad">
                    <h3>{$lang_block_assigning}</h3>
        			<div class="block_param_form">
                    {$block_change}
                    </div>
                </div>
                
                <!-- hr -->
                <div class="hr"> </div>
                <!-- /hr -->
                
                <!-- links -->
                    <div class="block_param_links">
                        <div class="box-tline"><div class="box-t"><div> </div></div></div><div class="box"> 
                         
                            {if $add_block_link}
                            <div><a href="{$add_block_link}" class="add">{$lang_add_block}</a></div>
                            {/if}
                            {if $unlink_block_link}
                            <div><a href="{$unlink_block_link}" class="out" onClick="remove_unblock_record()">{$lang_unlink_block}</a></div>
                            {/if}
                            {if $copy_block_link}
                            <div><a href="{$copy_block_link}" class="change">{$lang_copy_block}</a></div>
                            {/if}
                        
                        </div><div class="box-bline"><div class="box-b"><div> </div></div></div>
                    </div>
                <!-- /links -->
                {if $block_params}
                
                <!-- hr -->
                <div class="hr"> </div>
                <!-- /hr -->
                
                <div class="pad">
                    <h3>{$lang_block_parameters}</h3>
        			<div class="block_param_form">{$block_params}</div>
                </div>
                {/if}

            </div>
		</td>
	</tr>
</table>
<div class="block_legend"><b>{$lang_page_block_location}</b> (  <img src="/common/adm/img/blocks/ico-1.gif" width="8" height="8" border="0" alt=""> - {$lang_page_block_active},   <img src="/common/adm/img/blocks/ico-2.gif" width="8" height="8" border="0" alt=""> - {$lang_page_block_content},   <img src="/common/adm/img/blocks/ico-3.gif" width="8" height="8" border="0" alt=""> - {$lang_page_block_empty},   <img src="/common/adm/img/blocks/ico-4.gif" width="8" height="8" border="0" alt=""> - {$lang_page_block_error} )</div>
