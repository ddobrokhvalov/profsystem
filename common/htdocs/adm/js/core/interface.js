// Устранение мигания фоновых картинок в IE6            
try { document.execCommand( 'BackgroundImageCache', false, true ) } catch ( e ) { }

// Задание специфического обработчика событий            
function addListener( oObj, sEvent, oFunc )
{
	try {
		if ( document.attachEvent )
			oObj.attachEvent( 'on' + sEvent, oFunc );
		else if ( document.addEventListener )
			oObj.addEventListener( sEvent, oFunc, true );
		else
			eval( oObj + '.on' + sEvent + '=' + oFunc );
	 } catch ( e ) { }
}

// Снятие специфического обработчика событий            
function removeListener( oObj, sEvent, oFunc )
{
	try {
		if ( document.detachEvent )
			oObj.detachEvent( 'on' + sEvent, oFunc );
		else if ( document.removeEventListener )
			oObj.removeEventListener( sEvent, oFunc, true );
		else
			eval( oObj + '.on' + sEvent + ' = function() { return false }' );
	 } catch ( e ) { }
}

// Кросбраузерный метод определения абсолютных координат объекта            
function getPos( oObj )
{
	var x = oObj.offsetLeft, y = oObj.offsetTop;
	while ( oObj = oObj.offsetParent )
		if ( oObj.tagName != 'HTML' )
			x += oObj.offsetLeft, y += oObj.offsetTop;
	
	return { 'x': x, 'y': y };
}

// Метод перенаправления на заданный URL            
function redirect( oEvent, oUrl )
{
	if ( oEvent.button == 2 )
		return false;
		
	if ( oUrl.confirm && !confirm( oUrl.confirm ) )
		return false;

	if ( oEvent.shiftKey || oEvent.ctrlKey )
		window.open( oUrl.url );
	else
		window.location.href = oUrl.url;
}

// Метод перенаправления на заданный URL (если в адресной строке задан объект)            
function redirect_obj( oEvent, oUrl )
{
	if ( /obj=(.+?)(&|$)/.test( location.search ) )
		redirect( oEvent, oUrl );
}

// Кроссбраузерный метод прерывания передачи события            
function stopEventCascade( oEvent )
{
	if ( oEvent.stopPropagation )
		oEvent.stopPropagation();
	else
		oEvent.cancelBubble = true;
}

// Метод для отправки куков            
function setCookie( sName, sValue, sExpires, sPath, sDomain, sSecure )
{
	document.cookie = sName + '=' + escape(sValue) +
		( sExpires ? '; expires=' + sExpires.toGMTString() : '' ) +
		( sPath ? '; path=' + sPath : '' ) +
		( sDomain ? '; domain=' + sDomain : '' ) +
		( sSecure ? '; secure' : '' );
}

// Метод для чтения информации из куков            
function getCookie( sName )
{
	var aCookie = document.cookie.split( '; ' );
	for ( var i = 0; i < aCookie.length; i++ )
	{
		var aCrumb = aCookie[i].split( '=' );
		if ( sName == aCrumb[0] ) 
			return unescape( aCrumb[1] );
	}
	return null;
}

// Метод для очистки куков            
function deleteCookie( sName, sPath, sDomain )
{
	if ( getCookie( sName ) )
		document.cookie = sName + '=' + 
			( sName ? '; path=' + path : '' ) +
			( sDomain ? '; domain=' + domain : '') +
			'; expires=Thu, 01-Jan-70 00:00:01 GMT';
}

// Пустой метод            
function empty()
{
	return false;
}

///////////////////////////////////////////////////////////////////////////////            

// Сплиттер            
function Splitter()
{
	var self = this;
	
	// Инициализация сплиттера            
	this.init = function( sName )
	{
		// Граничные значения ширины левого контейнера            
		this.iMinWidth = document.body.offsetWidth / 20;
		this.iMaxWidth = document.body.offsetWidth / 2;
		
		// Граничные значения автоматической ширины левого контейнера            
		this.iDefaultMinWidth = document.body.offsetWidth / 6;
		
		this.oResize = document.getElementById( sName );
		
		// Ширина DIVа устанавливается на основании значения из куков,            
		// в противном случае берется ширина по умолчанию            
		var splitter_width = getCookie( 'splitter_width' );
		if ( splitter_width )
			this.setWidth( splitter_width );
		else
			this.setWidth( this.iDefaultMinWidth );
		
		// Определение и установка состояния левого контейнера            
		var splitter_toggled = getCookie( 'splitter_toggled' );
		if ( splitter_toggled == 'true' )
			this.setToggle( 'none' );
		
		// Сохраняем состояние контейнера в переменной объекта            
		this.toggled = splitter_toggled == 'true';
	}
	
	// Начало перемещения сплиттера (onmousedown)            
	this.startResize = function( oEvent )
	{
		if( this.toggled )
			return false;
		
		if( this.oResize.style.display == 'none' )
			return false;
		
		// Запоминаем текущее положение сплиттера            
		this.iBaseX = this.oResize.offsetWidth;
		this.iSpaceX = oEvent.clientX;
		
		// Отключаем возможность выделения текста мышью            
		document.body.ondrag = empty;
		document.body.onselectstart = empty;
		document.body.style.MozUserSelect = 'none';
		
		// Устанавливаем обработчики перемешения мыши и отпускания кнопки            
		addListener( document, 'mousemove', self.moveResize );
		addListener( document, 'mouseup', self.stopResize );
		
		// Меняем вид курсора на горизонтальные стрелки            
		document.body.style.cursor = 'w-resize';
	}
	
	// Процесс перемещения сплиттера (onmousemove)            
	this.moveResize = function( oEvent )
	{
		// Меняем ширину DIVа в зависимости от положения мыши            
		self.setWidth( self.iBaseX + ( oEvent.clientX - self.iSpaceX ) );
	}
	
	// Окончание перемещения сплиттера (onmouseup)            
	this.stopResize = function( oEvent )
	{
		// Отключаем обработчики перемешения мыши и отпускания кнопки            
		removeListener( document, 'mousemove', self.moveResize );
		removeListener( document, 'mouseup', self.stopResize );
		
		// Возвращаем возможность выделения текста мышью            
		document.body.ondrag = null;
		document.body.onselectstart = null;
		document.body.style.MozUserSelect = '';
		
		// Возвращаем вид курсора по умолчанию            
		document.body.style.cursor = '';
		
		// Установленное значение ширины сохраняем в куках            
		setCookie( 'splitter_width', self.oResize.offsetWidth )
	}
	
	// Метод установки ширины левого контейнера            
	this.setWidth = function( iWidth )
	{
		// Корректируем значение ширины в соответствии с граничными значениями            
		iWidth = ( iWidth >= this.iMinWidth ) ? iWidth : this.iMinWidth;
		iWidth = ( iWidth <= this.iMaxWidth ) ? iWidth : this.iMaxWidth;
		
		// Меняем ширину левого контейнера и всех его "братьев"            
		for ( var iIndex in this.oResize.parentNode.childNodes )
		{
			var oObj = this.oResize.parentNode.childNodes[iIndex];
			if ( oObj.tagName && oObj.tagName == 'DIV' )
				this.oResize.parentNode.childNodes[iIndex].style.width = iWidth;
		}
	}
	
	// Метод переключения видимости левого контейнера            
	this.toggle = function()
	{
		// Запоминаем текущее значение высоты левого контейнера            
		self.oResize.parentNode.style.height = self.oResize.offsetHeight;
		
		// Скрываем или показываем DIV в зависимости от его текущего состояния            
		self.setToggle( self.oResize.style.display == 'none' ? 'block' : 'none' );
		
		// Запоминаем состояние контейнера в переменной объекта            
		self.toggled = self.oResize.style.display == 'none';
		
		// Запоминаем состояние контейнера в куках            
		setCookie( 'splitter_toggled', self.toggled );
	}
	
	// Метод установки видимости или невидимости левого контейнера            
	this.setToggle = function( sDisplay )
	{
		// Меняем значение свойства display у контейнера и всех его "братьев"            
		for ( var iIndex in this.oResize.parentNode.childNodes )
		{
			var oObj = this.oResize.parentNode.childNodes[iIndex];
			if ( oObj.tagName && oObj.tagName == 'DIV' )
				this.oResize.parentNode.childNodes[iIndex].style.display = sDisplay;
		}
	}
}

// Выпадающее меню            
function Menu()
{
	var self = this;
	
	this.bShowEnabled = true;
	
	// Инициализация меню            
	this.init = function( sName, aItems, sType )
	{
		// Имя объекта меню            
		this.name = sName;
		
		// Массив элементов меню            
		this.items = aItems;
		
		// Тип, определяющий внешний вид меню            
		this.type = sType;
		
		if ( !this.items.length )
			return;
		
		this.container = document.getElementById( this.name );
		if ( !this.container )
			return false;
		
		// Создание первого (видимого) элемента меню            
		this.mainDiv = document.createElement( 'div' );
		this.mainDiv.className = 'menuMainDiv';
		
		if ( /MSIE/.test( navigator.userAgent ) )
			this.mainDiv.style.position = 'fixed';
		
		var sHtmlMenuItem = Template.htmlMenuItem( {
				'type': this.type, 'name': this.name, 'index': 0, 'image': this.items[0].image, 'alt': this.items[0].alt,
				'title': this.items[0].title, 'status': ( this.items[0].param && this.items[0].param.url ) ? this.items[0].param.url : '',
				'active': this.items[0].object && this.items[0].object[this.items[0].method] } );
		
		this.mainDiv.innerHTML = Template.htmlMainDiv( { 'item': sHtmlMenuItem, 'type': this.type, 'name': this.name, 'length': this.items.length } );
		
		// Помещаем первый элемент меню в документ            
		this.container.appendChild( this.mainDiv );
	}
	
	// Метод, открывающий меню            
	this.show = function( oEvent )
	{
		if ( !this.bShowEnabled || this.items.length < 2 )
			return false;
		
		// Меняем внешний вид первого элемента меню            
		this.mainDiv.className = 'menuMainDiv menuMainDivClick';
		
		// При необходимости создаем выпадающий списка            
		if ( !this.floatDiv )
		{
			// Создаем и помещаем в документ контейнер для списка            
			this.floatDiv = document.createElement( 'div' );
			this.floatDiv.className = 'menuFloatDiv';
			this.container.appendChild( this.floatDiv );
			
			// Создаем элементы списка            
			var sHtmlFloatRows = '';
			for ( var i = 1; i < this.items.length; i++ )
				sHtmlFloatRows += Template.htmlMenuItem( {
						'type': this.type, 'name': this.name, 'index': i, 'image': this.items[i].image, 'alt' : this.items[i].alt,
						'title': this.items[i].title, 'status': ( this.items[i].param && this.items[i].param.url ) ? this.items[i].param.url : '',
						'active': this.items[i].object && this.items[i].object[this.items[i].method] } );
			
			// Добавлем элементы списка в контейнер            
			this.floatDiv.innerHTML = Template.htmlFloatDiv( { 'item': sHtmlFloatRows, 'type': this.type, 'name': this.name } );
			
			// Корректируем ширину выпадающего списка в зависимости от типа меню            
			if ( this.type == 'only_image' )
			{
				this.floatDiv.style.width = this.mainDiv.offsetWidth + 'px';
				this.floatDiv.firstChild.style.width = '100%';
			}
			else if ( this.floatDiv.offsetWidth < this.mainDiv.offsetWidth )
			{
				this.floatDiv.style.width = ( this.mainDiv.offsetWidth + 3 ) + 'px';
				this.floatDiv.firstChild.style.width = '100%';
			}
			
			// Корректируем внешний вид выпадающего списка в зависимости от типа меню            
			if ( this.type != 'only_image' )
			{
				var oSpacer = document.getElementById( this.name + '_spacer' );
				var oBorder = document.getElementById( this.name + '_border' );
				
				var iArrowOffset = ( this.type == 'big_image' ) ? 11 : 0;
				
				oSpacer.style.width = Math.max( 0, this.floatDiv.offsetWidth - this.mainDiv.offsetWidth - 3 + iArrowOffset ) + 'px';
				oBorder.style.width = Math.max( 0, this.mainDiv.offsetWidth - 3 - iArrowOffset ) + 'px';
			}
		}
		
		// Определяем расположение выпадающего списка в завимости от            
		// ширины и высоты списка, а также от расположения меню на экране            
		var oPoint = getPos( this.mainDiv );
		
		this.floatDiv.style.top = ( oPoint.y + this.mainDiv.offsetHeight ) + 'px';
		if ( this.type == 'big_image' )
			this.floatDiv.style.left = oPoint.x + 'px';
		else
			this.floatDiv.style.left = ( oPoint.x + this.mainDiv.offsetWidth - this.floatDiv.firstChild.offsetWidth ) + 'px';
		
		// Делаем выпадающий список видимым            
		this.floatDiv.style.visibility = 'visible';
		
		// Вешаем временный обработчик на document.mousedown            
		addListener( document, 'mousedown', self.hide );
	}
	
	// Метод, скрывающий меню            
	this.hide = function( oEvent )
	{
		if ( oEvent.button == 2 )
			return false;
		
		// Меняем внешний вид первого элемента меню            
		self.mainDiv.className = 'menuMainDiv';
		
		// Делаем выпадающий список невидимым            
		self.floatDiv.style.visibility = 'hidden';
		
		// Снимаем временный обработчик с document.mousedown            
		removeListener( document, 'mousedown', self.hide );
		
		self.bShowEnabled = false;
		setTimeout( self.name + '.bShowEnabled = true', 500 );
	}
	
	// Изменение внешнего вида элемента меню при наведении мыши            
	this.select = function()
	{
		if ( !this.floatDiv || ( this.floatDiv && this.floatDiv.style.visibility != 'visible' ) )
    		this.mainDiv.className = 'menuMainDiv menuMainDivSelect';
	}
	
	// Восстановление внешнего вида элемента меню при убирании мыши            
	this.unselect = function()
	{
		if ( !this.floatDiv || ( this.floatDiv && this.floatDiv.style.visibility != 'visible' ) )
			this.mainDiv.className = 'menuMainDiv';
	}
	
	// Реакция на нажатие кнопки мыши на элементе меню            
	this.action = function( event, index )
	{
		if ( event.button == 2 )
			return false;
		
		var oItem = this.items[ index ];
		if ( oItem && oItem.object && oItem.object[oItem.method] )
			oItem.object[oItem.method](event, oItem.param);
	}
}

// Дерево            
function Tree()
{
	var self = this;
	
	// Путь к картинкам, иконкам и флажкам            
	this.imagePath = '/common/adm/img/tree/';
	
	// Размер отступа дочернего уровня от родительского            
	this.paddingStep = 16;
	
	// Инициализация дерева            
	this.init = function( sName, aItems, oParam )
	{
		this.name = sName;
		
		// Счетчик созданных узлов дерева            
		this.current = 0;
		
		// Массив действий по клику на узле дерева            
		this.items = new Array();
		
		// Массив дополнительных параметров            
		this.param = oParam;
		
		// Создание корневого контейнера            
		this.mainDiv = document.getElementById( this.name );
		if ( !this.mainDiv )
			return false;
		
		this.mainDiv.className = 'treeContainer';
		
		// Заполнение корневого контейнера            
		this.mainDiv.innerHTML = this.buildTree( aItems, 0 );
	}
	
	// Рекурсивный метод построения дерева            
	this.buildTree = function( aItems, iDepth )
	{
		var sItem = '';
		
		// Бежим по текущему уровню дерева            
		for ( var i = 0; i < aItems.length; i++ )
		{
			// Вычисляем имя уникального идентификатора для текущего узла            
			var sId = this.name + '_' + this.current++;
			
			// Заполняем массив действий по клику на узле дерева            
			if ( aItems[i].object && aItems[i].object[aItems[i].method] )
				this.items[sId] = aItems[i];
			
			// Собираем узел дерева            
			sItem += '' +
				'<div style="padding: 1px 0px 0px ' + ( iDepth * this.paddingStep ) + 'px;" class="treeItem" onmouseover="this.className = \'treeItemSelect\'" onmouseout="this.className = \'treeItem\'"><div style="height: 18px' + ( aItems[i].image ? '; background: url(' + aItems[i].image + ') no-repeat 13px 0px' : '' ) + '">' +
				'<img style="margin: ' + ( aItems[i].image ? '1px 22px 0px 2px' : '1px 5px 0px 2px' ) +  '; cursor: ' + ( aItems[i].is_children ? 'pointer' : 'default' ) + '" id="i_' + sId + '" class="treeImage" src="' + this.imagePath + ( ( aItems[i].items && !aItems[i].collapsed ) ? 'minus.gif' : ( aItems[i].is_children ? 'plus.gif' : 'none.gif' ) ) + '"'
					+ ( ( aItems[i].items || aItems[i].is_children ) ? ' onmousedown="' + this.name + '.expand( \'' + sId + '\', ' + ( iDepth + 1 ) + ', \'' + aItems[i].parent_id + '\'' + ', \'' + aItems[i].current_id + '\' )"' : '' ) + '/>'
					+ ( this.items[sId] ?
					'<a href="' + aItems[i].param.url + '" class="treeText' + ( aItems[i].parent_id == aItems[i].current_id ? ' treeTextCurrent' : '' ) + '">' + aItems[i].title + '</a>' + ( aItems[i].param.edit_url ? ' <a href="' + aItems[i].param.edit_url + '"><img src="/common/adm/img/menu/edit2.gif" border="0" alt="' + aItems[i].param.edit_title + '" title="' + aItems[i].param.edit_title + '"></a>' : '' ) + '<br/>' :
					'<span class="treeText' + ( aItems[i].parent_id == aItems[i].current_id ? ' treeTextCurrent' : '' ) + '">' + aItems[i].title + '</span><br/>' ) +
				'</div></div>' +
				'<div style="display: ' + ( ( aItems[i].items && !aItems[i].collapsed ) ? 'block' : 'none' ) + '" id="c_' + sId + '">' + ( aItems[i].items ? this.buildTree( aItems[i].items, iDepth + 1 ) : '' )  + '</div>';
		}
		
		return sItem;
	}
	
	// Метод раскрытия/скрытия узла дерева            
	this.expand = function( sId, iDepth, sParentId, sCurrentId )
	{
		var oDiv = document.getElementById( 'c_' + sId );
		var oImage = document.getElementById( 'i_' + sId );
		
		if ( !oDiv || !oImage )
			return false;
		
		// По src иконки определяем состояние узла            
		if ( /plus.gif/.test( oImage.src ) )
		{
			// Меняем иконку на "-"            
			oImage.src = this.imagePath + 'minus.gif';
			
			// Раскрываем узел дерева            
			oDiv.style.display = 'block';
			
			var bDivEmpty = !oDiv.firstChild || oDiv.firstChild.tagName == 'SPAN';
			
			// При необходимости запрашиваем с сервера список дочерних узлов. В противном случае            
			// отправляем на сервер пустую команду для сохранения состояния дерева            
			Manager.sendCommand( this.param.url, { 'command': 'tree_open', 'parent_id': sParentId, 'current_id': sCurrentId, 'name': this.param.name, 'empty': ( this.param.standalone || !bDivEmpty ? 1 : 0 ) }, this, 'expand_callback', { 'id': sId, 'depth': iDepth } );
			
			// При необходимости выводим надпись о процессе загрузки            
			if ( bDivEmpty )
				oDiv.innerHTML = '' +
					'<span style="margin-left: ' + ( iDepth * this.paddingStep ) + 'px">' + Dictionary.translate( 'lang_loading' ) + '</span>';
		}
		else
		{
			// Меняем иконку на "+"            
			oImage.src = this.imagePath + 'plus.gif';
			
			// Скрываем узел дерева            
			oDiv.style.display = 'none';
			
			// Отправляем на сервер пустую команду для сохранения состояния дерева            
			Manager.sendCommand( this.param.url, { 'command': 'tree_close', 'parent_id': sParentId, 'current_id': sCurrentId, 'name': this.param.name }, null, null, null );
		}
	}
	
	// Callback-метод раскрытия узла дерева            
	this.expand_callback = function( xmlResponse, oParam )
	{
		var xmlText = xmlResponse.documentElement.getElementsByTagName('items');
		if ( xmlText.length )
		{
			try {
				// Данные с сервера приходят в виде JavaScript-массива            
				eval( 'var aItems = [ ' + xmlText[0].firstChild.nodeValue + ' ]' );
			} catch ( e ) {
				alert( Dictionary.translate( 'lang_error_from_server' ) ); return false;
			}
			
			// Определяем контейнер родительского узла            
			var oDiv = document.getElementById( 'c_' + oParam.id );
			if ( !oDiv )
				return false;
			
			// Заполнение контейнер списком дочерних узлов            
			oDiv.innerHTML = this.buildTree( aItems, oParam.depth );
		}
	}
}

// Фильтр            
var Filter =
{
	// Имя формы фильтра            
	sFormName: '',
	
	// Путь к картинкам-стрелочкам            
	sUpImagePath: '/common/adm/img/filter/up1.gif',
	sDownImagePath: '/common/adm/img/filter/dwn1.gif',
	
	// Метод инициализации фильтра            
	init: function( sFormName, iFieldsCount )
	{
		Filter.sFormName = sFormName;
		
		if ( iFieldsCount > 1 )
			Filter.toggleFilter();
	},
	
	// Метод добавляет новые условия в фильтр            
	addFilter: function( sName )
	{
		var oFilterItem = document.getElementById( sName );
		if ( oFilterItem )
			oFilterItem.style.display = '';
		
		Filter.toggleFilter();
	},
	
	// Метод добавляет все условия в фильтре            
	addAllFilters: function()
	{
		var oFilterForm = document.forms[Filter.sFormName];
		for ( var i = 0; i < oFilterForm.childNodes.length; i++ )
			if ( oFilterForm.childNodes[i].tagName == 'DIV' && oFilterForm.childNodes[i].id != 'search' )
				Filter.addFilter( oFilterForm.childNodes[i].id )
	},
	
	// Метод удаляет выбранное условие в фильтре            
	dropFilter: function( sName )
	{
		var oFilterItem = document.getElementById( sName );
		if ( oFilterItem )
			oFilterItem.style.display = 'none';
		
		Filter.toggleFilter();
	},
	
	// Метод удаляет все условия из фильтра            
	dropAllFilters: function()
	{
		var oFilterForm = document.forms[Filter.sFormName];
		for ( var i = 0; i < oFilterForm.childNodes.length; i++ )
			if ( oFilterForm.childNodes[i].tagName == 'DIV' && oFilterForm.childNodes[i].id != 'search' )
				Filter.dropFilter( oFilterForm.childNodes[i].id );
	},
	
	// Действие на onchange селекта "Добавить фильтр"            
	selectFilter: function( oSelect )
	{
		var sName = oSelect.options[oSelect.selectedIndex].value;
		if ( sName == 'add_all_filters' )
			Filter.addAllFilters();
		else if ( sName == 'drop_all_filters' )
			Filter.dropAllFilters();
		else
			Filter.addFilter( sName );
		
		oSelect.selectedIndex = 0;
	},
	
	// Метод управления внешним видом кнопочки массового скрытия/раскрытия фильтра            
	toggleFilter: function()
	{
		var oFilterForm = document.forms[Filter.sFormName], bFilterExpanded = false;
		for ( var i = 0; i < oFilterForm.childNodes.length; i++ )
			if ( oFilterForm.childNodes[i].tagName == 'DIV' && oFilterForm.childNodes[i].id != 'search' )
				bFilterExpanded |= oFilterForm.childNodes[i].style.display != 'none';
		
		// Меняем внешний вид и поведение картинки-стрелочки            
		var oIndicator = document.getElementById( 'findicator' );
		
		if ( oIndicator )
		{
			oIndicator.src = bFilterExpanded ? Filter.sUpImagePath : Filter.sDownImagePath;
			oIndicator.onclick = bFilterExpanded ? Filter.dropAllFilters : Filter.addAllFilters;
		}
	},
	
	// Метод корректировки данных перед отправки формы            
	submitFilter: function()
	{
		var oFilterForm = document.forms[Filter.sFormName], aDisplayFields = new Array();
		for ( var i = 0; i < oFilterForm.childNodes.length; i++ )
		{
			if ( oFilterForm.childNodes[i].tagName == 'DIV' )
			{
				// Очищаем скрытые поля и заполняем список открытых            
				if ( oFilterForm.childNodes[i].style.display == 'none' )
				{
					var aInputs = oFilterForm.childNodes[i].getElementsByTagName( 'input' );
					for ( var j = 0; j < aInputs.length; j++ ) aInputs[j].value = '';
					
					var aSelects = oFilterForm.childNodes[i].getElementsByTagName( 'select' );
					for ( var j = 0; j < aSelects.length; j++ ) aSelects[j].selectedIndex = 0;
				}
				else
					aDisplayFields[aDisplayFields.length] = oFilterForm.childNodes[i].id;
			}
		}
		
		// Передаем список открытых полей в спецальном hidden-параметре            
		oFilterForm.display_fields.value = aDisplayFields.join( ',' );
		
		return true;
	}
}

// Статусная строка            
function StatusLine()
{
	// Метод инициализации статусной строки            
	this.init = function( sStatusLine, sContextHelp )
	{
		this.oStatusLine = document.getElementById( sStatusLine );
		this.oContextHelp = document.getElementById( sContextHelp );
		
		// Вариант использования статусной строки берем из куков            
		var bStatusLineVisible = getCookie( 'status_line_visible' );
		
		// По умолчанию показывается статусная строка            
		if ( bStatusLineVisible == 'false' )
			this.oContextHelp.style.display = 'block';
		// В противном случае контекстная помощь            
		else
			this.oStatusLine.style.display = 'block';
	}
	
	// Метод переключения состояния статусной строки            
	this.next = function()
	{
		var bStatusLineVisible = this.oStatusLine.style.display == 'block';
		
		this.oStatusLine.style.display = bStatusLineVisible ? 'none' : 'block';
		this.oContextHelp.style.display = bStatusLineVisible ? 'block' : 'none';
		
		// Сохраняем состояние статусной строки в куках            
		setCookie( 'status_line_visible', !bStatusLineVisible );
	}
}

// Объект сохранения состояния формы            
var FormState =
{
	// Объект формы            
	oForm: null,
	
	// Метод инициализация объекта            
	init: function( sFormName )
	{
		this.oForm = document.forms[sFormName]
		
		// Хапоминаем состояние формы            
		this.saveFormState();
	},
	
	// Метод возвращает состояние формы            
	getFormState: function()
	{
		var aFormState = new Array();
		for ( var i = 0; i < this.oForm.elements.length; i++ )
			if ( !this.oForm.elements[i].name.match( /^_lang_(\d+)$/ ) )
				aFormState[aFormState.length] =
					( this.oForm.elements[i].type == 'checkbox' || this.oForm.elements[i].type == 'radio' ) ?
						this.oForm.elements[i].checked : this.oForm.elements[i].value;
		return aFormState.join( '|' );
	},
	
	// Метод сохраняет состояние формы            
	saveFormState: function()
	{
		this.oForm.setAttribute( 'state', this.getFormState() );
	},
	
	// Метод провеяет состояние формы            
	checkFormState: function()
	{
		return this.oForm.getAttribute( 'state' ) == this.getFormState();
	},
	
	// Метод запрашивает подтверждение потери изменений            
	confirmChange: function()
	{
		if ( !FormState.checkFormState() )
			return confirm( Dictionary.translate( 'lang_confirm_change' ) );
		return true;
	}
}

// Объект для работы с распределенными операциями            
var Distributed =
{
	imagePath: '/common/adm/img/distributed/',
	
	// Метод инициализации объекта            
	init: function( iTotal, sUrl )
	{
		this.total = iTotal;
		this.url = sUrl;
		
		this.count = 0;
		
		this.oProgressSpan = document.getElementById( 'progressSpan' );
		
		// Создание прогрессбара            
		if ( this.total > 0 )
		{
			this.oOuterDiv = document.getElementById( 'progressDiv' );
			this.oOuterDiv.className = 'progressOuterDiv';
			
			this.oInnerDiv = document.createElement( 'div' );
			this.oInnerDiv.className = 'progressInnerDiv';
			this.oOuterDiv.appendChild( this.oInnerDiv );
			
			this.oImage = document.createElement( 'img' );
			this.oImage.className = 'progressImage';
			this.oInnerDiv.appendChild( this.oImage );
			
			// Сброс позиции прогрессбара в ноль            
			this.set( 0 );
		}
	},
	
	// Установка позиции прогрессбара            
	set: function( iPosition )
	{
		var iWidth = Math.round( iPosition * this.oOuterDiv.offsetWidth / 100 );
		
		if ( iWidth <= this.oImage.offsetWidth )
		{
			this.oInnerDiv.style.width = this.oImage.offsetWidth + 'px';
			this.oImage.src = this.imagePath + 'splitter_left.gif';
		}
		else if ( iWidth >= this.oOuterDiv.offsetWidth - this.oImage.offsetWidth )
		{
			this.oInnerDiv.style.width = this.oOuterDiv.offsetWidth + 'px';
			this.oImage.src = this.imagePath + 'splitter_right.gif';
		}
		else
		{
			this.oInnerDiv.style.width = iWidth + 'px';
			this.oImage.src = this.imagePath + 'splitter.gif';
		}
	},
	
	// Отправка на сервер очередной команды            
	send: function()
	{
		Manager.sendCommand( this.url, { 'command': 'distributed' }, this, 'response' );
	},
	
	// Обработка ответа сервера            
	response: function( xmlResponse, oParam )
	{
		var xmlText = xmlResponse.documentElement.getElementsByTagName( 'items' );
		
		if ( xmlText.length )
		{
			this.count = parseInt( xmlText[0].getAttribute( 'count' ) );
			
			// Для операций с заранее известными числом итераций            
			if ( this.total > 0 )
			{
				var iPosition = Math.max( Math.min ( this.count * 100 / this.total, 100 ), 0 );
				this.oProgressSpan.innerHTML = Dictionary.translate( 'lang_complete' ) + ': ' + Math.round( iPosition ) + '%';
				
				// Сдвигаем прогрессбар на нужную позицию            
				this.set( iPosition );
			}
			// Для операций с заранее неизвестными числом итераций            
			else
				this.oProgressSpan.innerHTML = Dictionary.translate( 'lang_complete' ) + ': ' + Math.round( this.count );
			
			// Если работа закончилась, преходим на страницу с результатами работы            
			if ( this.total == this.count || xmlText[0].getAttribute( 'final' ) )
				document.location.href = this.url.replace( /action=service/i, 'action=distributed_report' );
			// В противном случае посылаем на сервер очередную команду            
			else
				this.send();
		}
	}
}

// Панель вкладок            
var TabScroll =
{
	// Таймер автоматической прокрутки            
	timer: null,
	
	// Массив ячеек таблицы вкладок            
	cells: null,
	
	// Номер текущей ячейки            
	current: 0,
	
	// Метод инициализации панели вкладок            
	init: function( sTabScrollDiv, sTabScrollTable, sTabScrollImageLeft, sTabScrollImageRight )
	{
		// Находим объекты основного контейнера и таблицы вкладок            
		this.oTabScrollDiv = document.getElementById( sTabScrollDiv );
		this.oTabScrollTable = document.getElementById( sTabScrollTable );
		
		// Находим объекты кнопок управления скроллом            
		this.oTabScrollImageLeft = document.getElementById( sTabScrollImageLeft );
		this.oTabScrollImageRight = document.getElementById( sTabScrollImageRight );
		
		// Сохраняем в переменной объекта массив ячеек таблицы вкладок            
		this.cells = this.oTabScrollTable.rows[0].cells;
		
		// Запускам метод позиционирования активной вкладки            
		addListener( window, 'load', this.active );
	},
	
	// Прокрутка панели. Начало автоматической прокрутки            
	start: function( iDirection )
	{
		// Непосредственно прокрутка панели            
		this.set( this.current + iDirection );
		
		// Запуск таймера автоматической прокрутки            
		this.timer = setTimeout( 'TabScroll.start( ' + iDirection + ')', 500 );
	},
	
	// Остановка автоматической прокрутки панели            
	stop: function()
	{
		clearTimeout( this.timer );
	},
	
	// Метод позиционирования активной вкладки            
	active: function()
	{
		// Поиск активной вкладки по наличию атрибута 'active'            
		var iActiveCell = 0;
		for ( var i = 0; i < TabScroll.cells.length; i++ )
			if ( TabScroll.cells[i].getAttribute( 'active' ) )
				iActiveCell = i;
		
		// Подгоняем ширину панели под ширину родительского контейнера            
		TabScroll.oTabScrollDiv.style.width = ( 0.99 * TabScroll.oTabScrollDiv.parentNode.offsetWidth ) + 'px';
		
		// Позиционирование активной вкладки            
		TabScroll.set( iActiveCell );
	},
	
	// Метод установки состояния панели вкладок            
	set: function( iCell )
	{
		// Корректируем переданное значение номера вкладки            
		iCell = Math.max( 0, Math.min( iCell, this.cells.length - 1 ) );
		
		// Определяем абсолютное значение сдвига панели вкладок            
		var scrollLeft = 0;
		for ( var i = 0; i < iCell; i++ )
			if ( scrollLeft < this.oTabScrollDiv.scrollWidth - this.oTabScrollDiv.offsetWidth )
				scrollLeft += this.cells[i].offsetWidth;
			else
				break;
		
		// Непосредственно сдигаем панель на нужное растояние            
		this.oTabScrollDiv.scrollLeft = scrollLeft;
		
		// Запонимаем номер текущей ячейки            
		this.current = i;
		
		// Перерисовываем кнопки управления            
		this.image();
	},
	
	// Метод изменения внешнего вида кнопок управления            
	image: function()
	{
		// Определяем факт нахождения панели в граничных значения            
		var bLeftLimit = this.oTabScrollDiv.scrollLeft <= 0;
		var bRightLimit = this.oTabScrollDiv.scrollLeft >= this.oTabScrollDiv.scrollWidth - this.oTabScrollDiv.offsetWidth;
		
		// Меняем рисунок кнопок управления            
		this.oTabScrollImageLeft.className = bLeftLimit ? 'tab_move_left_hidden' : 'tab_move_left';
		this.oTabScrollImageRight.className = bRightLimit ? 'tab_move_right_hidden' : 'tab_move_right';
	}
}

// Шаблонизатор            
var Template =
{
	// Шаблон для создания первого контейнера выпадающего меню            
	htmlMainDiv: function( oParam )
	{
		if ( oParam.type == 'only_image' )
		{
			return '' +
				'<table border="0" cellspacing="0" cellpadding="0">' +
				'<tr>' +
				'   <td class="but3-l1"><img src="/common/adm/img/emp.gif" width="3" height="1" border="0" alt=""></td>' +
				'   <td class="but3-c1">' + oParam.item + '</td>' +
				'	<td class="but3-r1" unselectable="on" onmouseup="' + oParam.name + '.show( event )" onmouseover="' + oParam.name + '.select()" onmouseout="' + oParam.name + '.unselect()">' +
				'       <img src="/common/adm/img/emp.gif" width="26" height="1" border="0" alt=""/>' +
				'   </td>' +
				'</tr>' +
				'</table>';
		}
		else if ( oParam.type == 'big_image' )
		{
			return '' +
				'<table border="0" cellspacing="0" cellpadding="0">' +
				'<tr>' +
				'   <td class="but1-l1"><img src="/common/adm/img/emp.gif" width="3" height="1" border="0" alt=""></td>' +
				'   <td class="but1-c1">' + oParam.item + '</td>' +
				'	<td class="but1-r1" onmouseup="' + oParam.name + '.show( event )" onmouseover="' + oParam.name + '.select()" onmouseout="' + oParam.name + '.unselect()">' +
				'       <img src="/common/adm/img/emp.gif" width="11" height="1" border="0" alt=""/>' +
				'   </td>' +
				'</tr>' +
				'</table>';
		}
		else
		{
			return '' +
				'<table border="0" cellspacing="0" cellpadding="0">' +
				'<tr>' +
				'   <td class="but2-l1"><img src="/common/adm/img/emp.gif" width="3" height="1" border="0" alt=""></td>' +
				'   <td class="but2-c1">' + oParam.item + '</td>' +
				'	<td class="' + ( oParam.length > 1 ? 'but2-r1' : 'but2-r1-one' ) + '" onmouseup="' + oParam.name + '.show( event )" onmouseover="' + oParam.name + '.select()" onmouseout="' + oParam.name + '.unselect()">' +
				'       <img src="/common/adm/img/emp.gif" width="26" height="1" border="0" alt=""/>' +
				'   </td>' +
				'</tr>' +
				'</table>';
		}
	},
	
	// Шаблон для создания выпадающего списка меню            
	htmlFloatDiv: function( oParam )
	{
		if ( oParam.type == 'only_image' )
		{
			return '' +
				'<table border="0" cellspacing="0" cellpadding="0">' +
				'<tr>' +
				'	 <td class="box-l" style="width: 3px; padding: 0;"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></td>' +
				'    <td class="box-all"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></td>' +
				'    <td class="box-r" style="width: 3px; padding: 0;"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></td>' +
				'</tr>' +
				'<tr>' +
				'	 <td class="box-l"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></td>' +
				'    <td class="box-all">' + oParam.item + '</td>' +
				'    <td class="box-r"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></td>' +
				'</tr>' +
				'<tr>' +
				'	 <td style="width: 3px; padding: 0;"><img src="/common/adm/img/select/but2-box-bl.gif" width="3" height="3" border="0" alt=""><br></td>' +
				'    <td class="box-b"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></td>' +
				'    <td style="width: 3px; padding: 0;"><img src="/common/adm/img/select/but2-box-br.gif" width="3" height="3" border="0" alt=""><br></td>' +
				'</tr>' +
				'</table>';
		}
		else if ( oParam.type == 'big_image' )
		{
			return '' +
				'<table border="0" cellspacing="0" cellpadding="0">' +
				'<tr>' +
				'    <td style="width: 3px;"><img src="/common/adm/img/select/but1-box-tl.gif" width="3" height="3" border="0" alt="" style="margin: 0;"></td>' +
				'    <td class="box-t"><div id="' + oParam.name + '_border"><img src="/common/adm/img/emp.gif" width="0" height="0" border="0" alt=""></div></td>' +
				'    <td class="box-t"><div id="' + oParam.name + '_spacer"><img src="/common/adm/img/select/but1-box-tl22.gif" height="3" border="0" alt=""></div></td>' +
				'    <td><img src="/common/adm/img/select/but2-box-tr.gif" width="3" height="3" border="0" alt="" style="margin: 0;"></td>' +
				'</tr>' +
				'<tr>' +
				'    <td class="box-l"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></td>' +
				'    <td class="box-all" colspan="2">' + oParam.item + '</td>' +
				'    <td class="box-r"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></td>' +
				'</tr>' +
				'<tr>' +
				'    <td><img src="/common/adm/img/select/but2-box-bl.gif" width="3" height="3" border="0" alt="" style="margin: 0;"><br></td>' +
				'    <td class="box-b" colspan="2"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></td>' +
				'    <td><img src="/common/adm/img/select/but2-box-br.gif" width="3" height="3" border="0" alt=""></td>' +
				'</tr>' +
				'</table>';
		}
		else
		{
			return '' +
				'<table border="0" cellspacing="0" cellpadding="0">' +
				'<tr>' +
				'    <td style="width: 3px; padding: 0;"><img src="/common/adm/img/select/but2-box-tl.gif" width="3" height="3" border="0" alt="" style="margin: 0;"></td>' +
				'    <td class="box-t"><div id="' + oParam.name + '_spacer"><img src="/common/adm/img/emp.gif" width="0" height="0" border="0" alt=""></div></td>' +
				'    <td class="box-all"><div id="' + oParam.name + '_border"><img src="/common/adm/img/emp.gif" width="0" height="0" border="0" alt=""></div></td>' +
				'    <td class="box-r"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></td>' +
				'</tr>' +
				'<tr>' +
				'    <td class="box-l"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></td>' +
				'    <td class="box-all" colspan="2">' + oParam.item + '</td>' +
				'    <td class="box-r"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></td>' +
				'</tr>' +
				'<tr>' +
				'    <td style="width: 3px; padding: 0;"><img src="/common/adm/img/select/but2-box-bl.gif" width="3" height="3" border="0" alt="" style="margin: 0;"><br></td>' +
				'    <td class="box-b" colspan="2"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></td>' +
				'    <td style="width: 3px; padding: 0;"><img src="/common/adm/img/select/but2-box-br.gif" width="3" height="3" border="0" alt=""></td>' +
				'</tr>' +
				'</table>';
		}
	},
	
	// Шаблон для создания элемента выпадающего меню            
	htmlMenuItem: function( oParam )
	{
		if ( oParam.type == 'only_image' )
		{
			return '' +
				'<div style="background: url(\'' + oParam.image + '\') no-repeat 0px 0px;" class="menuItemDivLang"' + ( oParam.active ? ' onmousedown="' + oParam.name + '.action( event, ' + oParam.index + ( oParam.index ? ', true' : '' ) + ' )' : '' ) + '" title="'+ ( oParam.alt ? oParam.alt : '' ) +'">' +
					( oParam.index && oParam.title ? oParam.title : '' ) +
				'</div>';
		}
		else if ( oParam.type == 'big_image' && !oParam.index )
		{
			return '' +
				'<div class="menuh" style="background: url(\'' + oParam.image + '\') no-repeat center 0px; cursor: pointer;" class="menuItemDivImage"' + ( oParam.active ? ' onmousedown="' + oParam.name + '.action( event, ' + oParam.index + ( oParam.index ? ', true' : '' ) + ' )' : '' ) + '" title="' + oParam.title + '">' +
				'	<a href="' + oParam.status + '" onclick="return false"><span>' + oParam.title + '</span></a>' +
				'</div>';
		}
		else
		{
			return '' +
				'<div style="background: url(\'' + oParam.image + '\') no-repeat 0px 3px;" class="menuItemDiv"' + ( oParam.active ? ' onmousedown="' + oParam.name + '.action( event, ' + oParam.index + ( oParam.index ? ', true' : '' ) + ' )' : '' ) + '"><table border="0" cellspacing="0" cellpadding="0"><tr><td>' +
					( oParam.active ? '' +
						'	<a href="' + oParam.status + '" onclick="return false">' + oParam.title + '</a>' : '' +
						oParam.title ) + 
				'</td></tr></table></div>';
		}
	}
}

// Извлекаем из статусной строки имя текущего объекта            
// @todo В случае ненахождения нужно возвращать не PAGE, а объект по умолчанию            
function get_current_obj()
{
	if ( (r = location.search.match( /obj=(.+?)(&|$)/ )) && r[1]) {
		return r[1];
	}
	return 'PAGE';
}

// Метод пингования сервера. Отправка команды с параметрами командной строки            
function pingServer(add_params)
{
	if ( obj = get_current_obj() ) {
		Manager.sendCommand( 'index.php', { 'obj': obj, 'action': 'service', 'command': 'ping', 'params': escape( location.search + add_params) }, Pinger, 'ping_answer', null );
	}
}

// Раз в 10 минут пингуем сервер, чтобы не терялась сессия и сообщить серверу где мы находимся            
var pingInterval = setInterval( 'pingServer()', 600000 );

// Callback-метод пингера. В случае ошибки выводит соответствующее сообщение            
var Pinger =
{
	ping_answered: false,
	
	ping_answer: function( xmlResponse, oParam )
	{
		if ( Pinger.ping_answered ) return;
				
		if ( alerts = xmlResponse.documentElement.getElementsByTagName( 'alertmsg' ) )
		{
			for ( var i = 0; i < alerts.length; i++ )
			{
				Pinger.ping_answered = true;
				alert( alerts[i].firstChild.nodeValue );
			}
		}
	}
}

// Отправка на сервер команды разблокировки записи            
function unblock_record()
{
	if (obj = get_current_obj())
		Manager.sendCommand( 'index.php', {'obj': obj, 'action': 'service', 'command': 'unblock_record', 'params': escape(location.search)}, null, '', null, true );
	return false;
}

// Метод отменяет разблокировку записи            
function remove_unblock_record()
{
	removeListener(self, 'beforeunload', window.unblock_record);
}

// Метод для блокировки элементов формы редактирования заблокированной записи            
function disable_form_controls (sFormName)
{
	oForm = document.forms[sFormName]
	if (!oForm) return
	for ( var i = 0; i < oForm.elements.length; i++ )
		if (oForm.elements[i].type!='hidden')
			oForm.elements[i].disabled='DISABLED'
}

// Метод смены размера шрифта интерфейса            
function set_font( sFontSize )
{
	var cssPath = '/common/adm/css/';
	var cssFont = document.getElementById( 'css-font' );
	
	switch ( sFontSize )
	{
		case 'small': cssFont.href = cssPath + 'rbccontents-small.css'; break;
        case 'big': cssFont.href = cssPath + 'rbccontents-big.css'; break;
        default: cssFont.href = cssPath + 'rbccontents-middle.css';
    }
	
	// Запоминаем выьранный размер шрифта в куках            
	setCookie( 'font_size', sFontSize );
	
	// Корректируем внешний вид картинки смены размера шрифта            
	set_font_image( sFontSize );
}

// Метод корректировки внешнего вида картинки смены размера шрифта            
function set_font_image( sFontSize )
{
	var fontPath = '/common/adm/img/font/';
	
	var aFonts = new Array( 'small', 'middle', 'big' );
	
	for ( var iFont in aFonts )
	{
		var sFont = aFonts[iFont];
		
		var cssImage = document.getElementById( 'css-img-' + sFont );
		
		cssImage.setAttribute( 'srcOver', fontPath + 'f-' + sFont + '-a.gif' );
		cssImage.setAttribute( 'srcOut', fontPath + 'f-' + sFont + ( sFontSize == sFont ? '-a' : '' ) + '.gif' );
		cssImage.setAttribute( 'src', cssImage.getAttribute( 'srcOut' ) );
	}
}
