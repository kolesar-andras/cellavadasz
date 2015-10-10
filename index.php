<!doctype html>
<html lang="hu">
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="lib/ol/css/ol.css" type="text/css">
	<link rel="stylesheet" href="lib/ol/css/ol3-layerswitcher.css" type="text/css">
	<style>
		html, body, .map {
			height: 100%;
			width: 100%;
			margin: 0;
			font-family: sans-serif;
			font-size: 10px;
		}

		a, a:link, a:visited, a:hover, a:active { color: #404040; }

		.ol-popup {
			position: absolute;
			background-color: white;
			-webkit-filter: drop-shadow(0 1px 4px rgba(0,0,0,0.2));
			filter: drop-shadow(0 1px 4px rgba(0,0,0,0.2));
			padding: 15px;
			bottom: 12px;
			left: -50px;
			width: 250px;
		}
		.ol-popup:after, .ol-popup:before {
			top: 100%;
			border: solid transparent;
			content: " ";
			height: 0;
			width: 0;
			position: absolute;
			pointer-events: none;
		}
		.ol-popup:after {
			border-top-color: white;
			border-width: 10px;
			left: 48px;
			margin-left: -10px;
		}
		.ol-popup:before {
			border-top-color: #cccccc;
			border-width: 11px;
			left: 48px;
			margin-left: -11px;
		}
		.ol-popup-closer {
			text-decoration: none;
			color: #000000;
			position: absolute;
			top: 2px;
			right: 8px;
		}
		.ol-popup-closer:after {
			content: "✖";
		}
		#sarok {
			position: absolute;
			bottom: 0;
			left: 0;
			z-index: 1000;
			padding: 5px;
			visibility: hidden;
			/* Fallback for web browsers that doesn't support RGBa */
			background: rgb(255, 255, 255);
			/* RGBa with 0.6 opacity */
			background: rgba(255, 255, 255, 0.6);
			/* For IE 5.5 - 7*/
			filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=#99FFFFFF, endColorstr=#99FFFFFF);
			/* For IE 8*/
			-ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr=#99FFFFFF, endColorstr=#99FFFFFF)";
		}

		#count span {
			font-weight: bold;
		}
		#operators {
			-webkit-touch-callout: none;
			-webkit-user-select: none;
			-khtml-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
			user-select: none;
			padding-bottom: 5px;
		}
		#operators img {
			margin: -18px -18px -18px -18px;
			pointer-events: none;
		}
	</style>
	<script src="lib/jquery.min.js" type="text/javascript"></script>
	<script src="lib/jquery-cookie.js" type="text/javascript"></script>
	<script src="lib/jquery-lang.js" type="text/javascript"></script>
	<script src="js/lang.js" type="text/javascript"></script>
	<script src="lib/ol/build/ol.js" type="text/javascript"></script>
	<script src="lib/ol/build/ol3-layerswitcher.js" type="text/javascript"></script>
	<script type="text/javascript">
		var userLang = navigator.language || navigator.userLanguage;
		var lang = new Lang('hu', userLang.substring(0, 2) == 'hu' ? 'hu' : 'en', true);
		
		// update map on language change
		$(lang).on('afterUpdate', function () {
			$.each(map.getLayers().getArray(), function (index, layer) {
				var update = {};
				var title = layer.getProperties().title;
				if (!layer.getProperties().defaulttitle) {
					update.defaulttitle = title;
				} else {
					title = layer.getProperties().defaulttitle;
				}
				update.title = lang.translate(title);
				layer.setProperties(update);
			});
		});
	</script>
	<title lang="hu">cellavadász</title>
</head>
<body>
	<div id="sarok">
		<div id="operators">
			<div><img src="img/01.svg" /><input type="checkbox" id="checkbox.telenor" onclick="clickOperator()"/> <label for="checkbox.telenor">Telenor</label></div>
			<div><img src="img/30.svg" /><input type="checkbox" id="checkbox.telekom" onclick="clickOperator()"/> <label for="checkbox.telekom">Telekom</label></div>
			<div><img src="img/70.svg" /><input type="checkbox" id="checkbox.vodafone" onclick="clickOperator()"/> <label for="checkbox.vodafone">Vodafone</label></div>
			<div lang="hu" title="bázisállomás egyelőre meghatározatlan szolgáltatóval"><img src="img/00.svg" /><input type="checkbox" id="checkbox.unknown" onclick="clickOperator()"/> <label lang="hu" for="checkbox.unknown">ismeretlen</label></div>
			<div lang="hu" title="torony, amelyől még nem tudjuk, hogy bázisállomás-e"><img src="img/nosite.svg" /><input type="checkbox" id="checkbox.nosite" onclick="clickOperator()"/> <label lang="hu" for="checkbox.nosite">torony</label></div>
		</div>
		<div id="count">
			<div lang="hu" title="tornyok összesen (víztornyokkal, kéményekkel együtt)"><span id="count.all"></span> torony</div>
			<div lang="hu" title="bázisállomásként címkézett tornyok"><span id="count.site"></span> bázisállomás</div>
			<div lang="hu" title="szolgáltatóval címkézett bázisállomások"><span id="count.operator"></span> ~ szolgáltatóval</div>
			<div lang="hu" title="bázisállomások összesen (közösek külön-külön számítva)"><span id="count.unique.site"></span> ~ szolgáltatókra bontva</div>
			<div lang="hu" title="cella-azonosítókkal is címkézett bázisállomások"><span id="count.cellid"></span> ~ cella-azonosítókkal</div>
			<div lang="hu" title="bázisállomásokhoz kapcsolt cellák (gsm, umts, lte)"><span id="count.unique.cellid"></span> cella</div>
		</div>
		<a href="#lang-en" onclick="window.lang.change('en'); console.log(sites); console.log(sites); return false;">english</a> | <a href="#lang-hu" onclick="window.lang.change('hu'); return false;">hungarian</a>
	</div>
	<div id="map" class="map">
		<div id="popup" class="ol-popup">
			<a href="#" id="popup-closer" class="ol-popup-closer"></a>
			<div id="popup-content"></div>
		</div>
	</div>
	<script type="text/javascript">

		var count = {
			all: 0,
			site: 0,
			operator: 0,
			cellid: 0
		};

		var display = {
			telenor: true,
			telekom: true,
			vodafone: true,
			unknown: true,
			nosite: true
		};

		function hasCellId (feature) {
			return !(
				!feature.n.tags['gsm:cellid'] &&
				!feature.n.tags['umts:cellid'] &&
				!feature.n.tags['lte:cellid']
			);
		}

		function getVisibleItems (feature, key) {
			if (!feature.n.tags[key]) return;
			var out = [];
			operators = feature.n.tags.operator.split('; ');
			items = feature.n.tags[key].split('; ');
			for (var i=0; i<operators.length; i++) {
				operator = operators[i];
				if (operator == 'Telenor' && !display.telenor) continue;
				if (operator == 'Telekom' && !display.telekom) continue;
				if (operator == 'Vodafone' && !display.vodafone) continue;
				out.push(items[i]);
			}
			return out.join('; ');
		}

		function getCount (value) {
			if (!value) return 0;
			items = value.split(';');
			var count = 0;
			for (i=0; i<items.length; i++) {
				item = items[i].trim();
				if (item == 'fixme') continue;
				if (item == 'none') continue;
				count++;
			}
			return count;
		}

		function clickOperator () {
			display.telenor = document.getElementById('checkbox.telenor').checked;
			display.telekom = document.getElementById('checkbox.telekom').checked;
			display.vodafone = document.getElementById('checkbox.vodafone').checked;
			display.unknown = document.getElementById('checkbox.unknown').checked;
			display.nosite = document.getElementById('checkbox.nosite').checked;
			var checked = display.telenor || display.telekom || display.vodafone || display.unknown || display.nosite;
			if (!checked) {
				display.telenor = true;
				display.telekom = true;
				display.vodafone = true;
				display.unknown = true;
				display.nosite = true;
			}
			sites.changed();
			countCells();
		}

		function getOperators(feature) {
			var operator = feature.n.tags.operator;
			var is = {};
			if (operator) {
				if (operator.indexOf('Telenor') != -1) is.telenor = true; // operators.push('01');
				if (operator.indexOf('Telekom') != -1) is.telekom = true; // operators.push('30');
				if (operator.indexOf('Vodafone') != -1) is.vodafone = true; // operators.push('70');
			}
			if (feature.n.tags['communication:mobile_phone']) is.site = true;
			if (is.site && !is.telenor && !is.telekom && !is.vodafone) is.unknown = true; // operators.push('00');
			return is;
		}

		function countCells () {
			count.all = 0;
			count.site = 0;
			count.operator = 0;
			count.cellid = 0;
			count.unique = {};
			count.unique.site = 0;
			count.unique.cellid = {};
			count.unique.cellid.gsm = 0;
			count.unique.cellid.umts = 0;
			count.unique.cellid.lte = 0;

			source.forEachFeature(function (feature) {
			is = getOperators(feature);
			if (!(
				(is.telenor && display.telenor) ||
				(is.telekom && display.telekom) ||
				(is.vodafone && display.vodafone) ||
				(is.unknown && display.unknown) ||
				(!is.site && display.nosite)
			)) return;
			count.all++;
			if (
			    feature.n.tags['communication:mobile_phone'] &&
			    feature.n.tags['communication:mobile_phone'] != 'no'
			) count.site++;
			if (feature.n.tags.operator) {
			    count.operator++;
			    count.unique.site += getCount(getVisibleItems(feature, 'operator'));
			}
			if (hasCellId(feature)) {
			    count.cellid++;
			    count.unique.cellid.gsm  += getCount(getVisibleItems(feature, 'gsm:cellid'));
			    count.unique.cellid.umts += getCount(getVisibleItems(feature, 'umts:cellid'));
			    count.unique.cellid.lte  += getCount(getVisibleItems(feature, 'lte:cellid'));
			}
			});
			document.getElementById('count.all').innerHTML = count.all;
			document.getElementById('count.site').innerHTML = count.site;
			document.getElementById('count.operator').innerHTML = count.operator;
			document.getElementById('count.cellid').innerHTML = count.cellid;
			document.getElementById('count.unique.site').innerHTML = count.unique.site;
			document.getElementById('count.unique.cellid').innerHTML =
				count.unique.cellid.gsm + '+' +
				count.unique.cellid.umts + '+' +
				count.unique.cellid.lte + '=' + (
				count.unique.cellid.gsm +
				count.unique.cellid.umts +
				count.unique.cellid.lte);

			document.getElementById('sarok').style.visibility = 'visible';
		}

		var source = new ol.source.GeoJSON(({
			projection: 'EPSG:3857',
			preFeatureInsert: function(feature) {
			feature.geometry.transform('EPSG:4326', 'EPSG:3857');
			},
			url: 'http://kolesar.turistautak.hu/osm/opencellid/geojson/overpass.geojson'
		}));
		source.once('change', countCells);

		var sites = new ol.layer.Vector({
			source: source,
			style: function(feature, resolution) {
				is = getOperators(feature);
				var operators = [];
				if (display.telenor && is.telenor) operators.push('01');
				if (display.telekom && is.telekom) operators.push('30');
				if (display.vodafone && is.vodafone) operators.push('70');
				if (display.unknown && is.unknown) operators.push('00');
				if (display.nosite && !is.site) operators.push('nosite');

				if (!operators.length) return;

				var small = !hasCellId(feature) ? '.small' : '';
				var filename = 'img/' + operators.join('-') + small + '.svg';
				var icon = new ol.style.Icon({ src: filename });
				var style = {image: icon};
				return [new ol.style.Style(style)];
			},

			title: 'bázisállomások'
		});

		var map = new ol.Map({
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

		var layerSwitcher = new ol.control.LayerSwitcher();
		map.addControl(layerSwitcher);

		var container = document.getElementById('popup');
		var content = document.getElementById('popup-content');
		var closer = document.getElementById('popup-closer');

		/**
		 * Add a click handler to hide the popup.
		 * @return {boolean} Don't follow the href.
		 */
		closer.onclick = function() {
			overlay.setPosition(undefined);
			closer.blur();
			return false;
		};

		var overlay = new ol.Overlay({
			element: container,
			positioning: 'bottom-center',
			stopEvent: false
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
			html += 'id=<a href="http://openstreetmap.org/node/'+feature.get('id')+'">'+feature.get('id')+'</a>'+"<br/>\n";
			html += 'user=<a href="http://openstreetmap.org/user/'+feature.get('user')+'">'+feature.get('user')+'</a>'+"<br/>\n";
			html += 'changeset=<a href="http://openstreetmap.org/changeset/'+feature.get('changeset')+'">'+feature.get('changeset')+'</a>'+"<br/>\n";
			var prop = feature.n;
			for (k in prop) {
				if (['timestamp', 'version'].indexOf(k) == -1) continue;
				if (typeof(prop[k]) === 'undefined') continue;
				html += k + '=' + prop[k] + "<br/>\n";
			}
			var prop = feature.n.tags; // feature.getAttributes();
			for (k in prop) {
				html += k + '=' + prop[k] + "<br/>\n";
			}
			content.innerHTML = html;
			overlay.setPosition(coordinate);
		});
	</script>
	</body>
</html>
