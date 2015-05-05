/*
Module to display map and allow movement of marker on Post edit page
*/
(function($, w) {
    var map,
	marker,
	geocoder;
	var defaultZoomLevel = 2;
	var locationZoomLevel = 12;
	var defaultCentre = [54, -2];

	var init = function init ()
	{
		createMap();
		if ($('#lat_lng').length) {
		    $('#geocode_button').click(function() {
		    	updateMapFromFormAddress();
		    });
		}
	};

	var updateMapFromFormAddress = function updateMapFromFormAddress ()
	{
		var address = getAddress();
    	if (address !== '') {
    		geocodeAddress(address, function(geoCodeResult)
    			{
    				if (geoCodeResult) {
		    			updateMapFromGeoCoderResult(geoCodeResult);
		    			updateFormFromGeoCoderResult(geoCodeResult);
		    		}
    			});
    	}
	};

	var createMap = function createMap ()
	{
		var isDefault = $('#lat_lng').val() == '';
		var centre = getStartLatLng();
	    var mapOptions = {
			zoom: isDefault? defaultZoomLevel : locationZoomLevel,
			center: centre,
			mapTypeId: google.maps.MapTypeId.ROADMAP
	    };
	    map = new google.maps.Map(document.getElementById('wp_post_geodata_map'), mapOptions);
	    if (!isDefault) {
			addMarker(centre);
	    }
	    geocoder = new google.maps.Geocoder();

	};

	var getStartLatLng = function getStartLatLng ()
	{
	    var centre;

		if ($('#lat_lng').val() == '') {
			centre = new google.maps.LatLng(defaultCentre[0], defaultCentre[1]);
	    } else {
			var latlng = $('#lat_lng').val().split(",");
			centre = new google.maps.LatLng(latlng[0], latlng[1]);
	    }
	    return centre;
	};

	var getAddress = function getAddress ()
	{
		// Build the address from the form data
		var address_array = [];
	    $('.location input[type="text"]').each(function() {
			if (this.value) {
			    address_array.push(this.value);
			}
	    });
		var address = address_array.join(', ');
		return address;
	};

    var geocodeAddress = function geocodeAddress(address, callBack)
    {
		// Use the Geocoder API to look up the Lat Lng of the address
		geocoder.geocode({address: address}, function(GeoCoderResult, GeocoderStatus)
		{
		    if (GeocoderStatus === google.maps.GeocoderStatus.OK) {
		    	callBack(GeoCoderResult[0]);
		    }
		    callBack(false);
		});
    };

    var updateFormFromGeoCoderResult = function updateFormFromGeoCoderResult (GeoCoderResult)
    {
    	var latlng = String(GeoCoderResult.geometry.location);
		$('#lat_lng').val(latlng.substring(1, latlng.indexOf(')')));
		for (var i in GeoCoderResult.address_components) {
		    if (GeoCoderResult.address_components[i].types.length) {
				var type = GeoCoderResult.address_components[i].types[0];
				var long_name = GeoCoderResult.address_components[i].long_name;
				var short_name = GeoCoderResult.address_components[i].short_name;
				switch (type) {
				    case 'route':
					    $('#street_address_2').val(long_name);
					break;
				    case 'sublocality':
					    $('#city').val(long_name);
					break;
				    case 'administrative_area_level_1':
					    $('#province').val(long_name);
					    $('#region').val(short_name);
					break;
				    case 'country':
					    $('#country').val(long_name);
					    $('#iso3166').val(short_name);
					break;
				    case 'postal_code':
					    $('#postal_code').val(long_name);
					break;
				}
		    }
		}
    };

    var updateMapFromGeoCoderResult = function updateMapFromGeoCoderResult (GeoCoderResult)
    {
    	addMarker(GeoCoderResult.geometry.location);
		map.panTo(GeoCoderResult.geometry.location);
		map.fitBounds(GeoCoderResult.geometry.viewport);
    };

    var addMarker = function addMarker(LatLng) 
    {
		if (marker) {
		    marker.setPosition(LatLng);
		} else {
		    marker = new google.maps.Marker(
			    {
				position: LatLng,
				map: map,
				draggable: true
			    });
		    google.maps.event.addListener(marker, 'dragend', function()
		     {
				var position = marker.getPosition().toString();
				$('#lat_lng').val(position.substring(1, position.indexOf(')')));
		    });
		}
    };

    $(document).ready(function($) {
		init();
    });

})(jQuery, window);

