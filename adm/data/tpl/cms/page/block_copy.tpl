<div class="box3-tline"><div class="box3-t"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div></div>
    <div class="box32" style="padding: 8px 19px 0px 19px;">
    
        <table border="0" cellspacing="0" cellpadding="0" style="width: 93%">
        	<tr>
        		<td style="width: 50%" valign="top" class="hr">
                    <div class="copyblock">        
                        {if $INF_BLOCK_TITLE}
                            {$lang_page_block_select}: <b>{$INF_BLOCK_TITLE|escape}</b>
                        {/if}
                        
                    </div>
                </td>
                <td rowspan="5" style="width: 10px;">
					&nbsp;
				</td>
        		<td valign="top" class="hr">
            		<div class="copyblock">	
                        {$lang_page_block_colors}:
                        
                    </div>
        		</td>
        	</tr>
            <!--  -->
        	<tr>
        		<td valign="top" class="hr">
                    <div class="copyblock">        
                        {if $PRG_MODULE_TITLE}
                            {$lang_page_of_module}: <b>{$PRG_MODULE_TITLE|escape}</b>
                        {/if}
                        
                    </div>
                </td>
        		<td valign="top" class="hr">
            		<div class="copyblock">	
                        <div><img src="/common/adm/img/tabs/copy-ico-stop.gif" alt=""> - {$lang_page_block_other_module}</div>
                        
                    </div>
        		</td>
        	</tr>
            <!--  -->
        	<tr>
        		<td valign="top" class="hr">
                    <div class="copyblock">        
                        {if $PAGE_TITLE}
                            {$lang_page_on_page}: <b>{$PAGE_TITLE|escape}</b>
                        {/if}
                        
                    </div>
                </td>
        		<td valign="top" class="hr">
            		<div class="copyblock">	
                    {if $INF_BLOCK_TITLE}
                        <div><img src="/common/adm/img/tabs/copy-ico-warning.gif" alt=""> - {$lang_page_block_this_area}</div>
                        
                    {/if}
                    </div>
        		</td>
        	</tr>
            <!--  -->
        	<tr>
        		<td valign="top" class="hr">
                    <div class="copyblock">        
                        {if $TEMPLATE_AREA_TITLE}
                            {$lang_page_in_area}: <b>{$TEMPLATE_AREA_TITLE|escape}</b>
                        {/if}
                        
                    </div>
                </td>
        		<td valign="top" class="hr">
            		<div class="copyblock">	
                    {if $INF_BLOCK_TITLE}
                        <div><img src="/common/adm/img/tabs/copy-ico-free.gif" alt=""> - {$lang_page_block_this_module}</div>
                        
                    {/if}
                    </div>
        		</td>
        	</tr>
            <!--  -->
        	<tr>
        		<td valign="top" class="bot">
                    <div class="copyblock">        
                        <table border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="padding: 0px 7px 0px 0px;"><input type="checkbox" id="for_subtrees" onclick="document.forms['checkbox_form']['for_subtrees'].value = this.checked ? 1 : 0" style="margin: 0; padding: 0;  width: 13px; height: 13px;" /></td>
                            <td class="label" style="padding: 0px 7px 0px 0px;"><label for="for_subtrees">{$lang_page_apply_for_subtrees}</label></td>
                            <td style="padding: 0px 7px 0px 0px;"><input type="checkbox" id="all_version" checked="checked" onclick="document.forms['checkbox_form']['all_version'].value = this.checked ? 1 : 0" style="margin: 0; padding: 0;  width: 13px; height: 13px;" /></td>
                            <td class="label"><label for="all_version">{$lang_page_apply_for_all_versions}</label></td>
                        </tr>
                        </table>
                    </div>
                </td>
        		<td valign="top" class="bot">
            		<div class="copyblock">	
                        &nbsp;
                    </div>
        		</td>
        	</tr>
        </table>
        <div class="hr2">
			&nbsp;
		</div>
        
        <div class="spacer" style="width: 0px; height: 8px;"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div>
        <div class="errors-t"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div>
        <div class="errors-box">
        	
                {if $page_param_exists}
                {$lang_page_attention_page_param}<br/>
                {/if}
                {$lang_page_attention_generate1} <a href="index.php?obj=TEMPLATE">{$lang_page_attention_generate2}</a>.
            
        </div>
        <div class="errors-b"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div>
        <div class="spacer" style="width: 0px; height: 15px;"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div>

    </div>
<div class="box3-bline"><div class="box3-b"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div></div>



