       ymaps.ready(init);
        var myMap, 
            myPlacemark;

        function init(){ 
            myMap = new ymaps.Map("map", {
                center: [56.219326, 34.345829],
                zoom: 13,
                controls: ["zoomControl"]
            }); 
            
 			var myPlacemark = new ymaps.Placemark([56.219326, 34.345829], {}, {
		        iconLayout: 'default#image',
		        iconImageHref: '/common/img/profsystem_style/ymaps-icon.png',
		        iconImageSize: [70, 90],
		        iconImageOffset: [-35, -90]
		    });
            
            myMap.geoObjects.add(myPlacemark);

            myMap2 = new ymaps.Map("map2", {
                center: [56.855659, 35.925245],
                zoom: 14,
                controls: ["zoomControl"]
            }); 
            
 			var myPlacemark2 = new ymaps.Placemark([56.855659, 35.925245], {}, {
		        iconLayout: 'default#image',
		        iconImageHref: '/common/img/profsystem_style/ymaps-icon.png',
		        iconImageSize: [70, 90],
		        iconImageOffset: [-35, -90]
		    });
            
            myMap2.geoObjects.add(myPlacemark2);

        }
