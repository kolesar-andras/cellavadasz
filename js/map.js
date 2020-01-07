var map;
var isMapDefault = true; // map is at default view
var mapHashDefault = null; // hash of default view
var hashSelfUpdated = false; // hash was updated by script
var featureClicked = null; // osm id of feature clicked
var overlay;
var closer;

$(document).ready(function () {

	source = new ol.source.GeoJSON(({
		projection: 'EPSG:3857',
		preFeatureInsert: function(feature) {
		feature.geometry.transform('EPSG:4326', 'EPSG:3857');
		},
		url: '/opencellid/sites/overpass.geojson',
		attributions: [
			new ol.Attribution({
				html: '<a href="http://wiki.openstreetmap.org/wiki/WikiProject_Hungary/Mobiltelefon-tornyok"><img src="img/logo/cellavadasz.png" /></a> A mobiltelefon-tornyok adatait folyamatosan gyűjtjük az <a href="http://www.openstreetmap.org/">OpenStreetMap</a> adatbázisában. A szerkesztés során felhasználjuk az <a href="http://opencellid.org/">OpenCellID</a> nyers méréseit, ezek pontos helyhez kötését a <a href="http://wiki.openstreetmap.org/wiki/WikiProject_Hungary/Mobiltelefon-tornyok">wikiben dokumentált</a> módszertan alapján végezzük. Az adatok <a href="https://hu.wikipedia.org/wiki/Open_Database_License">Open Database Licence</a> alatt érhetők el.</a>',
			})
		]

	}));
	source.once('change', function () {
		refreshMap();
		$('#loader').hide();
		popupFeatureId(featureClicked);
	});

	sites = new ol.layer.Vector({
		source: source,
		style: function(feature, resolution) {
			if (feature.n.type == 'relation') {
				if (feature.n.tags.type != 'link') return;
				if (!feature.n.members) return;
				if (!display.connections) return;
				var colours = getOperatorColours(feature);
				if (!colours.length) return null;

				var dash = null;
				if (feature.n.note == 'not-surveyed-half') dash = [4,4];
				if (feature.n.members.length < 2) dash = [1,3];

				return [
					new ol.style.Style({
						stroke: new ol.style.Stroke({
							color: addOpacity(colours[0], 0.5),
							width: 2,
							lineDash: dash
						})
					})
				];
			}
			var operators = getOperatorArray(feature);
			if (!operators.length) return;

			var small = !hasCellId(feature);
			var filename = 'img/' + operators.join('-') + '.svg';
			var icon = new ol.style.Icon({ src: filename, scale: small ? 0.7 : 1 });
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
					url: 'http://a.map.turistautak.hu/tiles/osm/{z}/{x}/{y}.png',
					attributions: [
						new ol.Attribution({
							html: '<a href="http://osm.turistautak.hu/"><img src="img/logo/openstreetmap.png" /></a> Alaptérkép: © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> közreműködők. A megjelenítés szabályait <a href="/www.openstreetmap.org/user/Kolesár">Kolesár András</a> készítette.',
						})
					]
				}),
				type: 'base',
				title: 'turistatérkép (OSM)'
			}),

			new ol.layer.Tile({
				source: new ol.source.OSM({
				attributions: [
					new ol.Attribution({
						html: '<a href="http://www.openstreetmap.org/"><img src="img/logo/openstreetmap.png" /></a> Alaptérkép: © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> közreműködők.',
					})
				]
				}),
				type: 'base',
				title: 'OpenStreetMap (mapnik)'
			}),

			new ol.layer.Tile({
				source: new ol.source.XYZ({
					url: 'http://a.map.turistautak.hu/tiles/measurements/{z}/{x}/{y}.png',
					attributions: [
						new ol.Attribution({
							html: '<a href="http://opencellid.org/"><img src="img/logo/opencellid.png" /></a> A nyers méréseket az <a href="http://opencellid.org/">OpenCellID</a> adatbázisából ábrázoljuk. CC-BY-SA 3.0</a>',
						})
					]
				}),
				title: 'mérések',
				visible: false
			}),

			new ol.layer.Tile({
				source: new ol.source.TileWMS({
					url: 'http://mlmap.digi.hu:8080/geoserver/gwc/service/wms',
					params: {
						'layers': 'coveragemap:onair',
						'tiled': true,
						'srs': 'EPSG:3857'
					},
					serverType: 'geoserver',
				}),
				title: 'Digi lefedettség',
				visible: false
			}),

			sites

		],
		view: new ol.View()
	});

	getHash();
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
	closer.onclick = closePopup;

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
			if (f.n.type != 'node') return;
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
		popupFeature(feature);
	});

	map.on('moveend', function() {
		isMapDefault = false;
		setHash();
	});

	$(document).on('keyup', function (e) {
		if (e.keyCode == 27) closePopup();
	});

});

function row (key, value) {
	if (key == 'mapillary') value = '<a href="http://www.mapillary.com/map/im/'+value+'">'+value+'</a>';
	if (key.replace) key = key.replace(new RegExp(':', 'g'), ':<wbr />');
	if (value.replace) value = value.replace(new RegExp(';', 'g'), ';<wbr />');
	return '<tr><td>'+key+'</td><td>'+value+'</td></tr>\n';
}

function popupFeature (feature) {

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
	featureClicked = feature.n.id;
	overlay.setPosition(coord);
	setHash();
}

function popupFeatureId (id) {
	feature = source.forEachFeature(function (f) {
		if (f.n.id == id) return f;
	});
	if (feature) popupFeature(feature);
}

function closePopup () {
	overlay.setPosition(undefined);
	closer.blur();
	featureClicked = null;
	setHash();
	return false;
};

function hexToRgb(hex) {
	var res = hex.match(/[a-f0-9]{2}/gi);
	return res && res.length === 3
		? res.map(function(v) { return parseInt(v, 16) })
		: null;
}

function addOpacity(hex, opacity) {
	var c = hexToRgb(hex);
	return r = 'rgba('+c[0]+','+c[1]+','+c[2]+','+opacity+')';
}
