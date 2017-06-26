var forgeEventsMap = {
    geocoder : false,
    map: false,
    marker: false,
    mapOptions : {
        zoom: 17,
        mapTypeId: google.maps.MapTypeId.ROADMAP 
    },

    init : function() {
        geocoder = new google.maps.Geocoder();
        $("#map_canvas").each(function() {
            var address = $(this).data('address');
            forgeEventsMap.map = new google.maps.Map($(this).get(0), forgeEventsMap.mapOptions);
            forgeEventsMap.codeAddress(address);
        });
    },

    codeAddress : function(address) {
        geocoder.geocode( { 'address': address}, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                forgeEventsMap.map.setCenter(results[0].geometry.location);
                if(forgeEventsMap.marker) {
                    forgeEventsMap.marker.setMap(null);
                }

                forgeEventsMap.marker = new google.maps.Marker({
                    map: forgeEventsMap.map,
                    position: results[0].geometry.location,
                    draggable: true
                });

            } else {
                console.log('Geocode was not successful for the following reason: ' + status);
            }
        });
    }
};

$(document).ready(forgeEventsMap.init);
$(document).on("ajaxReload", forgeEventsMap.init);