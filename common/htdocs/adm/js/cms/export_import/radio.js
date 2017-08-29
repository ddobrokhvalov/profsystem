addListener(window, 'load', beh_load);

function beh_load () {
	var i=1;
	while (el = document.getElementById('tr_list_'+i)) {
		addListener(el, 'click', set_active );
		i++;
	}
}

var active_row = null;
function set_active (obj) {
	if (obj.srcElement)
		el = obj.srcElement;

	if (obj.currentTarget)	
		el = obj.currentTarget


	while (el && (el.id.substring(0, 8)!='tr_list_') && el.parentElement)
		el = el.parentElement;

	if (el && set_radio(el))	{
		if (active_row) {
			active_row.style.backgroundColor = active_row.old_backgroundColor
			active_row.onmouseout = active_row.old_onmouseout;
			active_row.onmouseover = active_row.old_onmouseover;
			active_row.onmouseout();
		}
		
		el.old_backgroundColor = el.style.backgroundColor;
		el.style.backgroundColor = '#f7f0cc';
		el.old_onmouseover = el.onmouseover;
		el.old_onmouseout = el.onmouseout;
		el.onmouseover = null;
		el.onmouseout = null;
		active_row = el;
	}
}

function set_radio(el) {
	var div_radio;
	var el = el.cells[el.cells.length-1];

	while (el.childNodes.length>0) {
		if (!el.childNodes[0].tagName) {
			el=el.childNodes[1]
		}
		else {
			el=el.childNodes[0]	
		}
		
		if (el.tagName=='DIV') {
			div_radio = el;
			break;
		}
		
	}
	
	if (div_radio) {
		for (i=0; i<div_radio.childNodes.length; i++)
			if (div_radio.childNodes[i].tagName && (div_radio.childNodes[i].tagName.toUpperCase()=='INPUT') && (div_radio.childNodes[i].type.toUpperCase()=='RADIO')) {
				div_radio.childNodes[i].click();
				return true;
			}
	}
}

function CheckFillRadios(radio_el)
{
	if ( radio_value(radio_el) )
		return true;
		
		
	alert( Dictionary.translate( 'lang_no_records' ) );
    return false;
}


function add_element_to_form (frm, el_name, value) {
	el = document.createElement('INPUT');
	el.type='hidden';
	el.name=el_name;
	el.value=value;
	frm.appendChild(el);
	return el;
}