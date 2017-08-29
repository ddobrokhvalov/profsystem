<div class="box3-tline"><div class="box3-t"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div></div>
    <div class="box32 export">

        {literal}
        <script type="text/javascript" src="/common/adm/js/cms/export_import/radio.js"></script>
        <script>
        function radio_changed (parent) {
        	root_checker = document.getElementById('include_root');
        	
        	if (parent==0) {
        		root_checker.checked = ''
        		root_checker.disabled='DISABLED'
        	}
        	else 
        		root_checker.disabled=''
        }
        
        function export_click () {
        	if (frm=document.forms['checkbox_form']) {
        		if (CheckFillRadios(frm.elements['_f_page_id'])) {
        			var arr = new Array('create_blocks', 'export_content', 'create_templates', 'create_template_types', 'include_root');
        			for (i=0; i<arr.length; i++) {
        				if (document.getElementById(arr[i]).checked) {
        					add_element_to_form (frm, arr[i], 1);
        				}
        			}
        			
        			filesize = document.getElementById('file_size').value;
        			add_element_to_form (frm, 'file_size', filesize);
        			
        			frm.submit()	
        		}
        	}
        }
        
        </script>
        {/literal}
        
        <table style="width: 100%; margin: 0;">
        <tr>
            <td style="padding: 8px 9px 10px 9px;">
                <form name='flags_form'>
                
                <div class="list"><input class="checkbox" type="checkbox" name="create_blocks" id="create_blocks" value="1" onClick="check_enable(this, 'export_content')"><label for="create_blocks"> {$lang_es_create_new_blocks}</label></div>
                
                
                <div class="list hr2"><input class="checkbox" type="checkbox" id="export_content" name="export_content" value="1" DISABLED><label for="export_content"> {$lang_es_export_context_elements}</label></div>
                
                
                <div class="list"><input class="checkbox" type="checkbox" id="create_templates" name="create_templates" value="1" onClick="check_enable(this, 'create_template_types')"><label for="create_templates"> {$lang_es_create_new_templates}</label></div>
                
                
                <div class="list hr2"><input class="checkbox" type="checkbox" id="create_template_types" name="create_template_types" value="1" DISABLED><label for="create_template_types"> {$lang_es_create_new_template_types}</label></div>
                
                
                <div class="list hr2"><input class="checkbox" type="checkbox" id="include_root" name="include_root" value="1" CHECKED><label for="include_root"> {$lang_es_include_tree_root_into_export}</label></div>
                
                
                <div class="list">{$lang_es_export_file_size}:
                <select name="file_size" id="file_size">
                    <option value='0.5'>0.5 Mb</option>
                    <option value='1' SELECTED>1 Mb</option>
                    <option value='2'>2 Mb</option>
                    <option value='4'>4 Mb</option>
                </select></div>
                
                <span class="left-black" style="margin: 0px 0px 0px 10px;"><span class="right-black"><input class="button-black" type="button" value="{$lang_export}" onClick="export_click()" /></span></span>
                </form>
            
            </td>
            <td align="right" id="rfilter" style="padding: 8px 9px 10px 9px;">{$filter}</td>
        </tr>
        </table>

    </div>
<div class="box3-bline"><div class="box3-b"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div></div>