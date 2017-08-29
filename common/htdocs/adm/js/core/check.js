// Изменение состояния всех чекбоксов в колонке            
function CheckAllBoxes( column, checkbox )
{
	for ( var i = 0; i < document.forms['checkbox_form'].elements.length; i++ )
	{
		var e = document.forms['checkbox_form'].elements[i];
		if ( e.type == 'checkbox' && !e.disabled && ( e.getAttribute( 'column' ) == column || !column ) )
			e.checked = checkbox.checked;
	}
}

// Подтверждение массовой операции            
function CheckFill()
{
	for( var i = 0; i < document.forms['checkbox_form'].elements.length; i++ )
	   	if ( document.forms['checkbox_form'].elements[i].checked == '1' &&
	   			document.forms['checkbox_form'].elements[i].id != 'check_all' )
	   		return true;
    
    alert( Dictionary.translate( 'lang_no_records' ) );
    
    return false;
}

// Подтверждение массового действия над записями            
function CheckFillConfirm( sConfirm )
{
	if ( CheckFill() )
		return confirm( sConfirm );
    
    return false;
}

// Меняет состояние чекбокса при клике на содержащую его ячейку таблицы            
function CheckBoxCellClick( sId )
{
	var oCheckBox = document.forms['checkbox_form'][sId];
	
	if ( oCheckBox.length )
		for ( var i = 0; i < oCheckBox.length; i++ )
			if ( oCheckBox[i].type == 'checkbox' )
				oCheckBox = oCheckBox[i];
	
	oCheckBox.checked = !oCheckBox.checked;
}

function check_group( el )
{
	div = el.parentNode;
	set_all_children_checkboxes_checked( div, el.checked );
	uncheck_parents(el);
}

function set_all_children_checkboxes_checked( par_el, check_option )
{
	if ( par_el.tagName=='INPUT' && par_el.type=='checkbox' )
		par_el.checked=check_option;
	
	if ( par_el.childNodes && par_el.childNodes.length )
	{
		par_el.ch=0;
		while ( par_el.ch < par_el.childNodes.length )
		{
			set_all_children_checkboxes_checked( par_el.childNodes[par_el.ch], check_option );
			par_el.ch++;
		}
	}
}

function uncheck_parents (el) {
	if (el.checked) return;
  while (el=el.parentNode) {
    if (el.tagName=='DIV') {
      
      for (i=0, n=el.childNodes.length; i<n; i++) {
        if (el.childNodes[i].tagName=='INPUT' && el.childNodes[i].type=='checkbox') {
          el.childNodes[i].checked='';
        }
      }
    }
  }
}

function check_enable(obj, ch_el_name) {
	ch_el = document.getElementById(ch_el_name);
	if (!ch_el || !ch_el.type || (ch_el.type.toUpperCase()!='CHECKBOX'))
		return false;
	
	if (obj.checked)
		ch_el.disabled = ''
	else {
		ch_el.checked = ''
		ch_el.disabled = 'DISABLED'
	}
}

function radio_value (radio_el) {
	if (!radio_el || !radio_el.length)
		return null;
	
	for (i=0; i<radio_el.length; i++) {
		if (!radio_el[i].type || radio_el[i].type.toUpperCase()!='RADIO') continue;
		if (radio_el[i].checked) 
			return radio_el[i].value;
	}

	return null;
}
