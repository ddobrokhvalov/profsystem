       ymaps.ready(init);
        var myMap, 
            myPlacemark;

        function init(){ 
            myMap = new ymaps.Map("map", {
                center: [56.219793, 34.346096],
                zoom: 13,
                controls: ["zoomControl"]
            }); 
            
 			var myPlacemark = new ymaps.Placemark([56.219793, 34.346096], {}, {
		        iconLayout: 'default#image',
		        iconImageHref: '/common/img/profsystem_style/ymaps-icon.png',
		        iconImageSize: [70, 90],
		        iconImageOffset: [-35, -90]
		    });
            
            myMap.geoObjects.add(myPlacemark);
        }
