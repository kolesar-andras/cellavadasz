$(document).ready(function () {

	source = new ol.source.GeoJSON(({
		projection: 'EPSG:3857',
		preFeatureInsert: function(feature) {
		feature.geometry.transform('EPSG:4326', 'EPSG:3857');
		},
		url: 'http://kolesar.turistautak.hu/osm/opencellid/geojson/overpass.geojson'
	}));
	source.once('change', function () {
		countCells();
		$('#loader').hide();
	});

	sites = new ol.layer.Vector({
		source: source,
		style: function(feature, resolution) {
			is = getOperators(feature);
			var operators = [];
			if (display.telenor && is.telenor) operators.push('01');
			if (display.telekom && is.telekom) operators.push('30');
			if (display.vodafone && is.vodafone) operators.push('70');
			if (display.unknown && is.unknown) operators.push('00');
			if (display.nosite && !is.site && !operators.length) operators.push('nosite');

			if (!operators.length) return;

			var small = !hasCellId(feature) ? '.small' : '';
			var filename = 'img/' + operators.join('-') + small + '.svg';
			var icon = new ol.style.Icon({ src: filename });
			var style = {image: icon};
			return [new ol.style.Style(style)];
		},

		title: 'bázisállomások'
	});

	map = new ol.Map({
		target: 'map',
		layers: [
			new ol.layer.Tile({
				source: new ol.source.XYZ({
					 url: 'http://a.map.turistautak.hu/tiles/osm/{z}/{x}/{y}.png'
				}),
				type: 'base',
				title: 'turistatérkép (OSM)'
			}),

			new ol.layer.Tile({
				source: new ol.source.OSM(),
				type: 'base',
				title: 'OpenStreetMap (mapnik)'
			}),

			new ol.layer.Tile({
				source: new ol.source.XYZ({
					url: 'http://a.map.turistautak.hu/tiles/measurements/{z}/{x}/{y}.png',
					attributions: [
						new ol.Attribution({
							html: '<a href="http://opencellid.org/">OpenCellID database CC-BY-SA 3.0</a>',
							collapsed: true,
						})
					]
				}),
				title: 'mérések',
				visible: false
			}),

			sites

		],
		view: new ol.View({
			center: ol.proj.transform([19.5, 47.2], 'EPSG:4326', 'EPSG:3857'),
			zoom: 8
		})

	});

	layerSwitcher = new ol.control.LayerSwitcher();
	map.addControl(layerSwitcher);

	container = document.getElementById('popup');
	content = document.getElementById('popup-content');
	closer = document.getElementById('popup-closer');

	/**
	 * Add a click handler to hide the popup.
	 * @return {boolean} Don't follow the href.
	 */
	closer.onclick = function() {
		overlay.setPosition(undefined);
		closer.blur();
		return false;
	};

	overlay = new ol.Overlay({
		element: container,
		positioning: 'bottom-center',
		autoPan: true
	});
	map.addOverlay(overlay);

	// display popup on click
	map.on('click', function(evt) {
		var feature = map.forEachFeatureAtPixel(
			[evt.pixel[0]-5, evt.pixel[1]],
			function(feature, layer) {
				return feature;
			});
		if (!feature) return;
		var geometry = feature.getGeometry();
		var coord = geometry.getCoordinates();
		var coordinate = evt.coordinate;

		var html = '';
		html += '<table>\n';
		html += row('id', '<a href="http://openstreetmap.org/node/'+feature.get('id')+'">'+feature.get('id')+'</a>');
		html += row('user', '<a href="http://openstreetmap.org/user/'+feature.get('user')+'">'+feature.get('user')+'</a>');
		html += row('changeset', '<a href="http://openstreetmap.org/changeset/'+feature.get('changeset')+'">'+feature.get('changeset')+'</a>');
		var prop = feature.n;
		for (k in prop) {
			if (['timestamp', 'version'].indexOf(k) == -1) continue;
			if (typeof(prop[k]) === 'undefined') continue;
			html += row(k, prop[k]);
		}
		var prop = feature.n.tags; // feature.getAttributes();
		for (k in prop) {
			html += row(k, prop[k]);
		}
		html += '</table>\n';
		if (prop['mapillary']) {
			html += '<a href="http://www.mapillary.com/map/im/'+prop['mapillary']+'">';
			html += '<img src="https://d1cuyjsrcm0gby.cloudfront.net/'+prop['mapillary']+'/thumb-320.jpg" />';
			html += '</a>\n';
		}
		content.innerHTML = html;
		overlay.setPosition(coordinate);
	});

	function row (key, value) {
		if (key == 'mapillary') value = '<a href="http://www.mapillary.com/map/im/'+value+'">'+value+'</a>';
		key = key.replace(':', ':<wbr />');
		return '<tr><td>'+key+'</td><td>'+value+'</td></tr>\n';
	}

});
