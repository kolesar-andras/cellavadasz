var keephash = false;
var isMapDefault = true;
var map;

$(document).ready(function () {

	source = new ol.source.GeoJSON(({
		projection: 'EPSG:3857',
		preFeatureInsert: function(feature) {
		feature.geometry.transform('EPSG:4326', 'EPSG:3857');
		},
		url: 'http://kolesar.turistautak.hu/osm/opencellid/geojson/overpass.geojson'
	}));
	source.once('change', function () {
		refreshMap();
		$('#loader').hide();
	});

	sites = new ol.layer.Vector({
		source: source,
		style: function(feature, resolution) {
			var operators = getOperatorArray(feature);
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
		view: new ol.View()
	});

	keephash = true;

	getHash('#map=8/47.2/19.5');
	setCheckboxes();

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
		var c0 = map.getPixelFromCoordinate(evt.coordinate);
		var distance = null;
		var feature = null;
		source.forEachFeature(function (f) {
			var operators = getOperatorArray(f);
			if (!operators.length) return; // exclude invisible

			var c1 = map.getPixelFromCoordinate(f.getGeometry().getCoordinates());
			var d = Math.sqrt(
				Math.pow(c1[0]-c0[0], 2) +
				Math.pow(c1[1]-c0[1], 2)
			);
			if (d > 12) return;
			if (distance === null || d<distance) {
				distance = d;
				feature = f;
			}
		});
		if (!feature) return;
		var geometry = feature.getGeometry();
		var coord = geometry.getCoordinates();

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
		overlay.setPosition(coord);
	});

	function row (key, value) {
		if (key == 'mapillary') value = '<a href="http://www.mapillary.com/map/im/'+value+'">'+value+'</a>';
		if (key.replace) key = key.replace(':', ':<wbr />');
		if (value.replace) value = value.replace(new RegExp(';', 'g'), ';<wbr />');
		return '<tr><td>'+key+'</td><td>'+value+'</td></tr>\n';
	}

	map.on('moveend', function() {
		if (keephash) {
			keephash = false;
			return;
		}
		setHash();
	});

});
