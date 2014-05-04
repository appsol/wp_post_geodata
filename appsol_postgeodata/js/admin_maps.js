(function($, w) {
    var map,
	marker,
	geocoder;
    $(document).ready(function($) {
	if ($('#lat_lng').length) {
	    var zoom, centre, marker;
	    if ($('#lat_lng').val() == '')
	    {
		zoom = 1;
		centre = new google.maps.LatLng(54, -2);
	    }
	    else
	    {
		zoom = 5;
		var latlng = $('#lat_lng').val().split(",");
		centre = new google.maps.LatLng(latlng[0], latlng[1]);
	    }
	    var mapOptions = {
		zoom: zoom,
		center: centre,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	    };
	    map = new google.maps.Map(document.getElementById('admin_map_container'), mapOptions);
	    if ($('#lat_lng').val() != '')
	    {
		add_marker(centre);
	    }
	    geocoder = new google.maps.Geocoder();
	    $('#geocode_button').click(function() {
		geocode();
	    });
	}
    });

    var geocode = function geocode() {
	var address_array = new Array();
	if ($('.address-field').length)
	{
	    $('.address-field').each(function() {
		if (this.value) {
		    address_array.push(this.value);
		}
	    });
	}
	else if ($('#event_venue').length)
	{
	    $('#event_venue input[name=venue\\[City\\]], #event_venue input[name=venue\\[Address\\]]').each(function() {
		if (this.value) {
		    address_array.push(this.value);
		}
	    });
	}
	var address = address_array.join(', ');
	geocoder.geocode({address: address}, function(results, status) {
	    console.log(results);
	    if (status == google.maps.GeocoderStatus.OK)
	    {
		var latlng = String(results[0].geometry.location);
		$('#lat_lng').val(latlng.substring(1, latlng.indexOf(')')));
		add_marker(results[0].geometry.location);
		map.panTo(results[0].geometry.location);
		map.fitBounds(results[0].geometry.viewport);
		for (var i in results[0].address_components) {
		    if (results[0].address_components[i].types.length) {
			var type = results[0].address_components[i].types[0];
			var long_name = results[0].address_components[i].long_name;
			var short_name = results[0].address_components[i].short_name;
			switch (type) {
			    case 'route':
				if ($('#street_address_2').val() != long_name)
				    $('#street_address_2').val(long_name)
				break;
			    case 'sublocality':
				if ($('#city').val() != long_name)
				    $('#city').val(long_name)
				break;
			    case 'administrative_area_level_1':
				if ($('#province').val() != long_name)
				    $('#province').val(long_name)
				if ($('#region').val() != short_name)
				    $('#region').val(short_name)
				break;
			    case 'country':
				if ($('#country').val() != long_name)
				    $('#country').val(long_name)
				if ($('#iso3166').val() != short_name)
				    $('#iso3166').val(short_name)
				break;
			    case 'postal_code':
				if ($('#postal_code').val() != long_name)
				    $('#postal_code').val(long_name)
				break;
			}
		    }
		}
	    }
	    else
	    {
		alert("Could not find that address.");
	    }
	});
    }
    var add_marker = function add_marker(point) {
	if (marker)
	{
	    marker.setPosition(point)
	}
	else {
	    marker = new google.maps.Marker(
		    {
			position: point,
			map: map,
			draggable: true
		    });
	    google.maps.event.addListener(marker, 'dragend', function() {
		var position = marker.getPosition().toString();
		$('#lat_lng').val(position.substring(1, position.indexOf(')')));
	    });
	}

    }

})(jQuery, window)

