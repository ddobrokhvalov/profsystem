// Менеджер для отправки команд на сервер            
var Manager = 
{
	// Массив отправленных команд            
	aSenders: new Array(),
	
	// Массив объектов XMLHttpRequest            
	aRequests: new Array(),
	
	// Счетчик отправленных команд            
	iCmdIndex: 0,
	
	// Метод отправки команд на сервер            
	sendCommand: function( sUrl, oCommand, oOwner, sCallback, oParam, bSync )
	{
		var sSendMethod = bSync ? 'syncCommand' : 'asyncCommand';
		
		return Manager[sSendMethod]( sUrl, oCommand, oOwner, sCallback, oParam );
	},
	
	// Синхронная отправка команды на сервер            
	syncCommand: function( sUrl, oCommand, oOwner, sCallback, oParam )
	{
		// Создаем экземпляр объекта XMLHttpRequest            
		var oRequest = Manager.getRequester();
		if ( !oRequest ) return null;
		
		// Синхронно отправляем команду на сервер             
		with ( oRequest )
		{
			open( 'post', sUrl, false );
			setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
			send( Manager.serializeUrl( oCommand ) );
		}
		
		// Получаем результат работы команды            
		var xmlResponse = oRequest.responseXML;
		
		if ( xmlResponse && xmlResponse.documentElement &&
				xmlResponse.documentElement.tagName != 'parsererror' )
		{
			// Считываем метку команды            
			var iMark = xmlResponse.documentElement.getAttribute( 'mark' );
			
			// Выводим сообщени об ошибке            
			if ( iMark == 'error' )
				Manager.displayError( xmlResponse, true );
			// Выполняем callback-метод            
			else if ( oOwner && oOwner[ sCallback ] )
				oOwner[ sCallback ]( xmlResponse, oParam );
		}
		else
			Manager.displayError( oRequest.responseText );
		
		return true;
	},

	// Асинхронная отправка команды на сервер            
	asyncCommand: function( sUrl, oCommand, oOwner, sCallback, oParam )
	{
		// Присваиваем команде уникальную метку            
		var iMark = Manager.iCmdIndex++; oCommand.mark = iMark;
		
		// Составляем объект команды            
		Manager.aSenders[ iMark ] = { owner: oOwner, callback: sCallback, param: oParam };
		
		// Создаем экземпляр объекта XMLHttpRequest            
		var oRequest = Manager.getRequester();
		if ( !oRequest ) return null;
		
		// Созраняем его в массиве            
		Manager.aRequests.push( oRequest );
		
		// Асинхронно отправляем команду на сервер             
		with ( oRequest )
		{
			open( 'post', sUrl, true );
			onreadystatechange = Manager.changeState;
			setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
			send( Manager.serializeUrl( oCommand ) );
		}
		
		return iMark;
	},

	// Метод, вызываемый после прихода ответа с севера            
	changeState: function()
	{
		// Проходим по массиву отправленных запросов            
		for ( var iIndex in Manager.aRequests )
		{
			var oRequest = Manager.aRequests[iIndex];
			
			// Обрабатываем только успешно завершенные запросы             
			if ( oRequest.readyState == 4 && oRequest.status == 200 )
			{
				// Получаем результат работы команды            
				var xmlResponse = oRequest.responseXML;
				
				delete Manager.aRequests[iIndex];
				
				if ( xmlResponse && xmlResponse.documentElement &&
						xmlResponse.documentElement.tagName != 'parsererror' )
				{
					// Считываем метку команды            
					var iMark = xmlResponse.documentElement.getAttribute( 'mark' );
					
					// Выводим сообщени об ошибке            
					if ( iMark == 'error' )
						Manager.displayError( xmlResponse, true );
					else if ( Manager.aSenders[ iMark ] != null )
					{
						// Выполняем callback-метод            
						with ( Manager.aSenders[ iMark ] )
							if ( owner && owner[ callback ] )
								owner[ callback ]( xmlResponse, param );
						
						// Удаляем выполненную команду из массива            
						delete Manager.aSenders[ iMark ];
					}
				}
				else
					Manager.displayError( oRequest.responseText );
			}
		}
	},
	
	// Метод для прерывания выполнения команды            
	abortCommand: function( iMark )
	{
		if ( Manager.aSenders[ iMark ] != null )
			delete Manager.aSenders[ iMark ];
	},

	// Метод для упаковки параметров команды в URL            
	serializeUrl: function( oCommand )
	{
		var aParams = new Array();
		for ( var sName in oCommand )
			aParams[ aParams.length ] = sName + '=' + oCommand[ sName ];
		return aParams.join('&');
	},

	// Кроссбраузерный метод получения экземпляра объекта XMLHttpRequest            
	getRequester: function()
	{
		if ( window.XMLHttpRequest )
			return new window.XMLHttpRequest(); 
		else if ( window.ActiveXObject )
			return new ActiveXObject( 'Microsoft.XMLHTTP' );
		else
			return false;
	},
	
	// Метод отображения сообщения о серверной ошибке            
	displayError: function( xmlResponse, bIsXML )
	{
		var oErrorDiv = document.getElementById( 'error' );
		
		if ( !oErrorDiv ) return false;
		
		// Формирование HTML-представления о серверной ошибке            
		if ( bIsXML )
		{
			var aTags = new Array( 'msg', 'file', 'line', 'trace', 'debug' );
			for ( var iTagIndex in aTags )
			{
				var oResponseTag = xmlResponse.documentElement.getElementsByTagName( aTags[iTagIndex] );
				var oErrorSpan = document.getElementById( 'error_' + aTags[iTagIndex] );
				if ( oResponseTag.length && oResponseTag[0].firstChild && oErrorSpan )
					oErrorSpan.innerHTML = oResponseTag[0].firstChild.nodeValue;
			}
		}
		else
		{
			var sError = ( xmlResponse.length < 512 ) ? xmlResponse : xmlResponse.substr( 0, 512 ) + '...';
			var oErrorSpan = document.getElementById( 'error_msg' );
			if ( oErrorSpan )
			{
				while ( oErrorSpan.lastChild )
					oErrorSpan.removeChild( oErrorSpan.lastChild );
				
				oErrorSpan.appendChild( document.createTextNode( sError ) );
			}
		}
		
		// Очистка всего остального документа            
		while ( oErrorDiv.parentNode.lastChild &&
				oErrorDiv.parentNode.lastChild != oErrorDiv )
			oErrorDiv.parentNode.removeChild( oErrorDiv.parentNode.lastChild );
		
		// Отображение сообщения об ошибке            
		oErrorDiv.style.display = 'block';
	}
};