var CheckForm =
{
	// Массив обработчиков полей по умолчанию            
	aCheckHandlers: {
		'_nonempty_': { 'method': 'validate_nonempty', 'message': 'lang_check_nonempty' },
		'_email_': { 'method': 'validate_email', 'message': 'lang_check_email' },
		'_date_': { 'method': 'validate_date', 'message': 'lang_check_date' },
		'_time_': { 'method': 'validate_time', 'message': 'lang_check_time' },
		'_datetime_': { 'method': 'validate_datetime', 'message': 'lang_check_datetime' },
		'_alphastring_': { 'method': 'validate_alphastring', 'message': 'lang_check_alphastring' },
		'_login_': { 'method': 'validate_login', 'message': 'lang_check_login' },
		'_dirname_': { 'method': 'validate_dirname', 'message': 'lang_check_login' },
		'_int_': { 'method': 'validate_int', 'message': 'lang_check_int' },
		'_float_': { 'method': 'validate_float', 'message': 'lang_check_float' },
		
		// Следующие префиксы используются только в клиенской части            
		'_radio_': { 'method': 'validate_radio', 'message': 'lang_check_no_variant' },
		'_radioalt_': { 'method': 'validate_radioalt', 'message': 'lang_check_no_variant' },
		'_checkboxgroup_': { 'method': 'validate_checkboxgroup', 'message': 'lang_check_no_variant' },
		'_checkboxgroupalt_': { 'method': 'validate_checkboxgroupalt', 'message': 'lang_check_no_variant' } },

	// Ссылка на объект текущей формы            
	oForm: null,

	// Метод проверки правильности заполнения полей            
	validate: function( oForm )
	{
		if ( !oForm ) return false;
		
		this.oForm = oForm;
		
		for ( var i = 0; i < this.oForm.elements.length; i++ )
		{
			var oItem = this.oForm.elements[i];
			var sErrors = oItem.getAttribute( 'lang' );
			
			if ( !sErrors ) continue;
			
			var aErrors = new Array();
			for ( var prefix in this.aCheckHandlers )
				if ( sErrors.indexOf( prefix ) >= 0 )
					aErrors[aErrors.length] = prefix;
			
			if ( !aErrors.length ) continue;
			
			for ( var index in aErrors )
			{
				if ( !this.aCheckHandlers[aErrors[index]] ) continue;
				
				var sMethod = this.aCheckHandlers[aErrors[index]]['method'];
				if ( this[ sMethod ] && !this[ sMethod ]( oItem ) )
				{
					alert( Dictionary.translate( this.aCheckHandlers[aErrors[index]]['message'] ) );
					try { oItem.focus() } catch (e) {};
					return false;
				}
			}
		}
		
		return this.validate_ext();
	},

	// Проверка на заполнение обязательного поля            
	validate_nonempty: function( oItem )
	{
		if ( oItem.type == 'checkbox' )
			return oItem.checked;
		else if ( this.oForm[oItem.name + '_file'] && this.oForm[oItem.name + '_file'].type == 'file' )
			return oItem.value.replace( /(^\s*)|(\s*$)/g, '' ) != '' ||
				this.oForm[oItem.name + '_file'].value.replace( /(^\s*)|(\s*$)/g, '' ) != '';
		else
			return oItem.value.replace( /(^\s*)|(\s*$)/g, '' ) != '';
	},

	// Проверка на целое число            
	validate_int: function( oItem )
	{
		return ( oItem.value == '' ) || /^\-?\+?\d+$/.test( oItem.value );
	},

	// Проверка на число с плавающей точкой            
	validate_float: function( oItem )
	{
		return ( oItem.value == '' ) || /^\-?\+?\d+[\.,]?\d*$/.test( oItem.value );
	},

	// Проверка на e-mail            
	validate_email: function( oItem )
	{
		return ( oItem.value == '' ) || /^[A-Za-z0-9_\.-]+@[A-Za-z0-9_\.-]+\.[A-Za-z]{2,}$/.test( oItem.value );
	},

	// Проверка на логин            
	validate_login: function( oItem )
	{
		return ( oItem.value == '' ) || /^[A-Za-z0-9_]+$/.test( oItem.value );
	},

	// Проверка на название директории            
	validate_dirname: function( oItem )
	{
		return ( oItem.value == '' ) || /^[A-Za-z0-9_\.\[\]-]+$/.test( oItem.value );
	},

	// Проверка на строку из латинских букв            
	validate_alphastring: function( oItem )
	{
		return ( oItem.value == '' ) || /^[A-Za-z]+$/.test( oItem.value );
	},

	// Проверка на дату            
	validate_date: function( oItem )
	{
		if ( oItem.value == '' ) return true;
		
		var aMatches = oItem.value.match( /^(\d{2})\.(\d{2})\.(\d{4})$/ );
		if ( !aMatches ) return false;
		
		return this.check_date( aMatches[3], aMatches[2] - 1, aMatches[1] );
	},

	// Проверка на время            
	validate_time: function( oItem )
	{
		if ( oItem.value == '' ) return true;
		
		var aMatches = oItem.value.match( /^(\d{2})\:(\d{2})$/ );
		if ( !aMatches ) return false;
		
		return this.check_time( aMatches[1], aMatches[2] );
	},

	// Проверка на дату/время            
	validate_datetime: function( oItem )
	{
		if ( oItem.value == '' ) return true;
		
		var aMatches = oItem.value.match( /^(\d{2})\.(\d{2})\.(\d{4}) (\d{2})\:(\d{2})$/ );
		if ( !aMatches ) return false;
		
		return this.check_date( aMatches[3], aMatches[2] - 1, aMatches[1] ) && this.check_time( aMatches[4], aMatches[5] );
	},

	// Вспомогательный метод проверки корректности даты            
	check_date: function( sYear, sMonth, sDate )
	{
		var dTempDate = new Date( sYear, sMonth, sDate );
		var bValid =
			( dTempDate.getFullYear() == sYear ) &&
			( dTempDate.getMonth() == sMonth ) &&
			( dTempDate.getDate() == sDate );
		return bValid;
	},

	// Вспомогательный метод проверки корректности времени            
	check_time: function( sHour, sMinutes )
	{
		var bValid =
			( sHour >= 0 && sHour <= 23 ) &&
			( sMinutes >= 0 && sMinutes <= 59 );
		return bValid;
	},

	// Проверка чека группы радио-баттонов            
	validate_radio: function( oItem )
	{
		var aItems = this.oForm[oItem.name].length ?
			this.oForm[oItem.name] : [ this.oForm[oItem.name] ];
		for ( var i = 0; i < aItems.length; i++ )
			if ( aItems[i].checked )
				return true;
		return false;
	},

	// Проверка чека группы радио-баттонов с альтернативой            
	validate_radioalt: function( oItem )
	{
		var aItems = this.oForm[oItem.name].length ?
			this.oForm[oItem.name] : [ this.oForm[oItem.name] ];
		for ( var i = 0; i < aItems.length; i++ )
			if ( aItems[i].checked ) {
				if ( aItems[i].value != '_alt_' )
					return true;
				else if ( this.oForm['alt_' + oItem.name].value.replace( /(^\s*)|(\s*$)/g, '' ) != '' )
					return true;
			}
		return false;
	},

	// Проверка чека группы чекбоксов            
	validate_checkboxgroup: function( oItem )
	{
		var aItems = this.oForm[oItem.name].length ?
			this.oForm[oItem.name] : [ this.oForm[oItem.name] ];
		for ( var i = 0; i < aItems.length; i++ )
			if ( aItems[i].checked )
				return true;
		return false;
	},

	// Проверка чека группы чекбоксов с альтернативой            
	validate_checkboxgroupalt: function( oItem )
	{
		var aItems = this.oForm[oItem.name].length ?
			this.oForm[oItem.name] : [ this.oForm[oItem.name] ];
		for ( var i = 0; i < aItems.length; i++ )
			if ( aItems[i].checked ) {
				if ( aItems[i].value != '_alt_' )
					return true;
				else if ( this.oForm['alt_' + oItem.name].value.replace( /(^\s*)|(\s*$)/g, '' ) != '' )
					return true;
			}
		return false;
	},

	// Дополнительный метод, переопределямый для расширения функционала            
	validate_ext: function()
	{
		return true;
	}
};

/*
	Пример добавления нового обработчика:

	Dictionary.aWords['lang_check_nonempty'] = 'Сообщение при ошибке';

	CheckForm.aCheckHandlers[ '_prefix_' ] =
	{
		'method': 'validate_method',
		'message': 'lang_check_nonempty'
	};

	CheckForm[ 'validate_method' ] = function( oItem )
	{
		[code]
	};
*/
