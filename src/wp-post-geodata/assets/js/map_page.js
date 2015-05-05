/**
 Main Javascript file
 **/
var map = {};

(function(w, $, m) {

    var setUp = function setUp() {

	var apiKey = 'AIzaSyDZkhfsGmPWSal6jwa_YYGvjg2IZxcn4xA',
		regionsTableId = '17aT9Ud-YnGiXdXEJUyycH2ocUqreOeKGbzCkUw',
		mapCentre = [39.206719, -98.470459], // Kansas
		haveMaps = false;
	if ($('#map').length) {
	    // Object to attach all the map functionality to
	    m.map = {
		id: $('#map').attr('data-map-id'),
		element: $('#map')[0],
		apiKey: apiKey, // Google API Key
		regionsTableId: regionsTableId, // Fusion Table ID for US States
		baseFusionTableUrl: 'https://www.googleapis.com/fusiontables/v1/tables/',
		baseFusionTableQueryUrl: 'https://www.googleapis.com/fusiontables/v1/query?sql=',
		centre: mapCentre,
		locations: [],
		open_infobox: false, // Flag to show if an infoBox is open
		regions: {}, // State boundary polgon collection
		regionOptions: {
		    strokeColor: "#0088cc",
		    strokeOpacity: 1,
		    strokeWeight: 1,
		    fillColor: "#e6f4fa",
		    fillOpacity: 0.4,
		}
	    };

	    // Load Google Maps JS
	    if (window.google)
		if (window.google.maps)
		    haveMaps = true;
	    if (!haveMaps)
		Modernizr.load('https://maps.google.com/maps/api/js?sensor=false&key=' + m.map.apiKey + '&callback=window.drawMap');

	    // Set the listener for post link click events
	    $('body').on('click', '.map-post-link', function(e) {
		e.preventDefault()
		loadPostContent($(this).attr('data-id'), $(this).attr('data-type'));
	    });
	}
    }

    /**
     * Callback for Google Maps
     * Main controller to build the map page
     **/
    w.drawMap = function() {
	var mapOptions = {
	    zoom: 4,
	    center: new google.maps.LatLng(m.map.centre[0], m.map.centre[1]),
	    mapTypeId: google.maps.MapTypeId.TERRAIN
	};
	m.map.map = new google.maps.Map(m.map.element, mapOptions);
	importFusionTable(function() {
	    getPostData()
	});
    }

    /**
     * Import the Region mapping data
     **/
    var importFusionTable = function importFusionTable(callback) {
	$.getJSON(m.map.baseFusionTableUrl + m.map.regionsTableId + '/columns?key=' + m.map.apiKey, function(data) {
	    // Get the column names from the table data
	    var columns = [];
	    $.each(data.items, function(key, val) {
		columns.push(val.name);
	    })
	    // Create the query to select data from all the columns
	    columns = columns.join(',');
	    var query = 'SELECT ' + columns + ' FROM ' + m.map.regionsTableId + ' ORDER BY id ASC';
	    $.getJSON(m.map.baseFusionTableQueryUrl + escape(query) + '&key=' + m.map.apiKey, function(data) {
		addPolygons(data)
		callback()
	    })
	})
    }


    /**
     * Create the mapping polygons from the data acquired from the Fusion table
     * Add the list of polygons to the mapping object
     **/
    var addPolygons = function addPolygons(data) {
	var regions = []
	$('#regions .region').each(function() {
	    regions.push($(this).attr('data-region-id'));
	});
	for (var i in data.rows) {

	    var row = {};
	    // Map the row data to object using column names
	    $.each(data.rows[i], function(key, val) {
		row[data.columns[key]] = val;
	    })
	    if ($.inArray(row.id, regions) > -1) {
		var content = {
		    infoTitle: row.name,
		    infoCopy: '<p><a class="map-post-link" href="#" data-id="' + row.id + '" data-type="region">Laws of the Land for ' + row.name + '</a></p>'
		}
		// Add the mapRegion object to the list of map regions
		m.map.regions[row.id] = new mapRegion(row.id, row.name, row.geometry, {map: m.map.map}, content);
	    }
	}

    }

    /**
     * 
     * @param {String} id
     * @param {String} name
     * @param {type} geometry
     * @param {Object} options
     * @param {Object} content
     * @returns {_L6.mapRegion}
     */
    var mapRegion = function(id, name, geometry, options, content) {
	var geometries = geometry.geometries ? geometry.geometries : [geometry.geometry];
	// Extend / Replace the base Polygon Options
	var polygonOptions = m.map.regionOptions;
	for (var key in options)
	    polygonOptions[key] = options[key]

	this.id = id;
	this.name = name;
	this.geometry = geometry;
	// create a Google maps polygon
	this.polygon = addPolygon(geometries, polygonOptions, content);

    }

    /**
     * geometries object is a GeometryCollection made up of a geometries array 
     * of objects each of which describe a section of the outline.
     * Each geometries object is a Polygon which in turn contains a co-ordinates
     * array of arrays each of which contains LatLng data of actual points
     * @param geometry Google Maps v3 GeometryCollection
     **/
    var addPolygon = function addPolygon(geometries, options, content) {
//	var coords = new google.maps.MVCArray();
	var coords = [];
	var bounds = new google.maps.LatLngBounds();

	for (var i = 0, glen = geometries.length; i < glen; i++) {
	    var polyCoords = [];
	    var polyBounds = new google.maps.LatLngBounds();
	    for (var n = 0, clen = geometries[i].coordinates[0].length; n < clen; n++) {
		var point = new google.maps.LatLng(geometries[i].coordinates[0][n][1], geometries[i].coordinates[0][n][0]);
		polyCoords.push(point);
		polyBounds.extend(point);
	    }

	    coords.push(polyCoords);
	    bounds.union(polyBounds);
	}
	options.paths = coords;
	var polygon = new google.maps.Polygon(options);
	polygon.defaultColor = options.fillColor;
	polygon.defaultOpacity = options.fillOpacity;
	polygon.toggleHighlight = function(mode) {
	    this.setOptions({
		fillColor: (mode) ? "#40a6d9" : this.defaultColor,
		fillOpacity: (mode) ? 0.8 : this.defaultOpacity
	    });
	}
	polygon.bounds = bounds;
	// state.setVisible(false);
	google.maps.event.addListener(polygon, 'mouseover', function(e) {
	    var posx, posy;
	    if (!m.map.open_infobox) {
		this.toggleHighlight(true);
		if (this.tooltip)
		{
		    this.tooltip.show();
		    if (e.Im.pageX || e.Im.pageY) {
			posx = e.Im.pageX;
			posy = e.Im.pageY;
		    }
		    else if (e.Im.clientX || e.Im.clientY) {
			posx = e.Im.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
			posy = e.Im.clientY + document.body.scrollTop + document.documentElement.scrollTop;
		    }
		    this.tooltip.move(posx, posy);
		}
	    }
	});
	google.maps.event.addListener(polygon, 'mouseout', function(e) {
	    if (!m.map.open_infobox)
	    {
		this.toggleHighlight(false)

		if (this.tooltip)
		{
		    this.tooltip.hide();
		}
	    }
	});
	google.maps.event.addListener(polygon, 'click', function(e) {
	    m.map.map.fitBounds(this.bounds);
	    addInfoWindow(this.bounds.getCenter(), content.infoTitle, content.infoCopy);
	});
	return polygon;
    }

//    }

    /**
     * 
     * @param polygon Google Maps v3 Polygon
     **/
//    var ploygonOutline = function ploygonOutline(polygon) {
//	var newCoords = [];
//	var coords = polygon.coordinates[0];
//	var bounds = new google.maps.LatLngBounds();
//	for (var i = 0, len = coords.length; i < len; i++) {
//	    var point = new google.maps.LatLng(coords[i][1], coords[i][0]);
//	    newCoords.push(point);
//	    bounds.extend(point);
//	}
//	return [newCoords, bounds];
//    }

    /**
     * Iterates over the returned data, adding each location to the location list
     * calls the method to draw the markers once complete
     * @returns {undefined}
     */
    var addPostData = function addPostData(postData) {
	for (var i in postData) {
	    m.map.locations[i] = new location(postData[i])
	}
	drawMarkers();
    }

    /**
     * Request a list of posts with a location meta set from the server
     * calls addPostData oncomplete
     * @returns {undefined}
     */
    var getPostData = function getPostData() {
	$.post(appsolPostGeoData.ajaxurl,
		{
		    action: 'get_locations',
		    appsol_ajax_nonce: appsolPostGeoData.ajax_nonce,
		    map_id: m.map.id
		},
	function(data, status, jqXHR) {
	    if (data) {
		addPostData(data)
	    }
	},
		'json'
		);
    }

    /**
     * draw the markers on the map and fit the initial view of the map round the markers
     * @returns {undefined}
     */
    var drawMarkers = function drawMarkers() {
	var marker_bounds = new google.maps.LatLngBounds();
	for (var i in m.map.locations)
	{
	    var location = m.map.locations[i].addMarker();
	    m.map.locations[i].marker.setVisible(true);
	    marker_bounds.extend(location);
	}
	m.map.map.setCenter(marker_bounds.getCenter());
    }

    // represents a location with an article associated
    var location = function location(post) {
	var self = this;
	var latlng = post.lat_lng.split(',');
	var post = post;
	this.marker = null;
	this.type = 'location';
	var content = '<a class="map-post-link" data-id="' + post.id + '"  data-type="location" href="' + post.link + '">'
	content += '<img src="' + post.image + '" width="200px" />';
	content += '</a>';
	content += '<p><a class="map-post-link" data-id="' + post.id + '"  data-type="location" href="' + post.link + '">Read</a></p>';
	this.addMarker = function() {
//	    var latlng = this.lat_lng.split(',');
	    var location = new google.maps.LatLng($.trim(latlng[0]), $.trim(latlng[1]))
	    this.marker = new google.maps.Marker({
		map: m.map.map,
		position: location,
		icon: appsolPostGeoData.themeurl + '/img/' + post.category + '.png',
		shadow: appsolPostGeoData.themeurl + '/img/' + post.category + '-shadow.png',
		zIndex: 5
	    });
	    new google.maps.event.addListener(self.marker, 'click', function() {
		addInfoWindow(self.marker.getPosition(), post.title, content);
	    });
	    return location;

	}
    }

    // represents an outfitter with a pin and associated region(s)
    var postMarker = function postMarker(post) {
	this.id = post.id;
	this.title = post.title;
	this.lat_lng = post.lat_lng;
	this.marker = null;
	this.type = 'post';
	this.areas = [];
	this.iso3166 = jQuery(domObject).attr('data-iso3166');
	this.areas.push(this.iso3166);
	this.default_icon = './wp-content/themes/teamwild-tv/images/map_icons/outfitter_pin.png';
	this.hover_icon = './wp-content/themes/teamwild-tv/images/map_icons/outfitter_pin_hover.png';
	var other_areas = domObject.attr('data-other-areas');
	if (other_areas)
	{
	    other_areas = other_areas.replace(/\s+/g, '').split(',');
	    this.areas = this.areas.concat(other_areas);
	}
	var self = this;
	this.toggleRegionHighlight = function(mode) {
	    for (var i = 0, len = this.areas.length; i < len; i++)
	    {
		wotw_regions[this.areas[i]].toggleHighlight(mode);
	    }
	}
	this.togglePinHighlight = function(mode) {
	    self.marker.setIcon((mode) ? this.hover_icon : this.default_icon);
	}
	this.toggleEverything = function(mode)
	{
	    if (!wotw_open_infobox)
	    {
		self.toggleRegionHighlight(mode);
		self.togglePinHighlight(mode);
	    }
	}
	jQuery(this.domObject).mouseover(function() {
	    self.toggleEverything(true);
	});
	jQuery(this.domObject).mouseout(function() {
	    self.toggleEverything(false);
	});
	this.addMarker = function() {
	    var latlng = this.lat_lng.split(',');
	    var location = new google.maps.LatLng(latlng[0], latlng[1]);
	    self.marker = new google.maps.Marker({
		map: wotw_map,
		position: location,
		zIndex: 10,
		title: this.name
	    });
	    google.maps.event.addListener(this.marker, 'mouseover', function() {
		self.toggleEverything(true);
		domObject.addClass('hover');
	    });
	    google.maps.event.addListener(this.marker, 'mouseout', function() {
		self.toggleEverything(false);
		domObject.removeClass('hover');
	    });
	    return location;
	}
	this.makeInfoWindow = function() {
	    var name = self.domObject.text().toLowerCase();
	    var image = self.domObject.attr('data-thumbnail');
	    var description = self.domObject.attr('data-description');
	    var link = self.domObject.children('a').attr('href');
	    var info = '<div class="outfitters-infowindow"><h3>' + name + '</h3>' + '<img class="left_float" src=' + image + ' alt=' + name + '/>' + '<p>' + description + '</p><a href="' + link + '">Read More</a></div>';
	    var infobox = new google.maps.InfoWindow({content: info, maxWidth: 387});

	    google.maps.event.addListener(self.marker, 'click', function() {
		closeCurrentInfoWindow();
		infobox.open(wotw_map, self.marker);
		self.togglePinHighlight(true);
		self.toggleRegionHighlight(true);
		wotw_open_infobox = infobox;
		wotw_open_infobox.outfitter = self;
	    });
	    google.maps.event.addListener(infobox, 'closeclick', function() {
		self.togglePinHighlight(false);
		self.toggleRegionHighlight(false);
		closeCurrentInfoWindow();
	    });
	}
    }

    var addInfoWindow = function addInfoWindow(position, title, copy) {
	var content = '<h4>' + title + '</h4>';
	if (copy)
	    content += copy;
	var infoWindow = new google.maps.InfoWindow({
	    position: position,
	    content: content,
	    maxWidth: 400
	});
	if (m.map.open_infobox)
	    m.map.open_infobox.close()
	m.map.open_infobox = infoWindow
	m.map.open_infobox.open(m.map.map)
    }

    var loadPostContent = function loadPostContent(postid, type) {
	$.post(appsolPostGeoData.ajaxurl,
		{
		    action: 'get_geopost',
		    appsol_ajax_nonce: appsolPostGeoData.ajax_nonce,
		    post: postid,
		    type: type,
		    map_id: m.map.id
		},
	function(data, status, jqXHR) {
	    if (data) {
		updateMapCopy(data)
	    }
	},
		'html'
		);
    }

    var updateMapCopy = function updateMapCopy(copy) {
	$('#map_copy').fadeOut(400, function() {
	    $(this).empty().append(copy).fadeIn(400);
	});
    }

    // Initiation point
    $(document).ready(setUp())

}(window, jQuery, map))