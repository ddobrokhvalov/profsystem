<div class="box3-tline"><div class="box3-t"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div></div>
    <div class="box32 export">
    
{literal}
<script type="text/javascript" src="/common/adm/js/cms/export_import/radio.js"></script>

<script>
function import_click () {
	frm = document.forms['checkbox_form'];
	if (CheckFillRadios(frm.elements['_f_page_id'])) {
		exp_time = document.forms['import_form'].elements['_f_EXPORT_TIME'].value;
		new_el = add_element_to_form (frm, '_f_EXPORT_TIME', exp_time);
		new_el.lang = 'errors_nonempty_';
		frm.elements['_f_EXPORT_TIME'] = new_el;
		if (CheckForm.validate( frm )) frm.submit();
		
	}
}
</script>
{/literal}

        <table style="width: 100%;">
        <tr>
            <td style="padding: 8px 9px 10px 9px;">
            <form name="import_form">
            <div class="import">{$lang_import_from_archive}<font color="red">*</font>:</div>
            
            <div class="import"><select name="_f_EXPORT_TIME" lang="errors_nonempty_">{html_options values=$export_times output=$export_times}</select></div>
            </form>
            
            <span class="left-black" style="margin: 11px 0px 0px 5px;"><span class="right-black"><input class="button-black" type="button" value="{$lang_import}" onClick="import_click()" /></span></span>
            </td>
            <td align="right" id="rfilter" style="padding: 8px 9px 10px 9px;">{$filter}</td>
        </tr>
        </table>

    </div>
<div class="box3-bline"><div class="box3-b"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div></div>