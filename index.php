<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
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
        /* border-radius: 10px;
        border: 1px solid #cccccc; */
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
    <script src="lib/ol/build/ol.js" type="text/javascript"></script>
    <script src="lib/ol/build/ol3-layerswitcher.js" type="text/javascript"></script>
    <title>cellavadász</title>
  </head>
  <body>
<div id="sarok">
<div id="operators">
<div><img src="img/01.svg" /><input type="checkbox" id="checkbox.telenor" onclick="clickOperator()"/> <label for="checkbox.telenor">Telenor</label></div>
<div><img src="img/30.svg" /><input type="checkbox" id="checkbox.telekom" onclick="clickOperator()"/> <label for="checkbox.telekom">Telekom</label></div>
<div><img src="img/70.svg" /><input type="checkbox" id="checkbox.vodafone" onclick="clickOperator()"/> <label for="checkbox.vodafone">Vodafone</label></div>
<div><img src="img/00.svg" /><input type="checkbox" id="checkbox.unknown" onclick="clickOperator()"/> <label for="checkbox.unknown">ismeretlen</label></div>
</div>
<div id="count">
    <div><span id="count.all"></span> bázisállomás</div>
    <div><span id="count.operator"></span> szolgáltatóval</div>
    <div><span id="count.cellid"></span> cella-azonosítókkal</div>
</div>
</div>
    <div id="map" class="map">
            <div id="popup" class="ol-popup">
                <a href="#" id="popup-closer" class="ol-popup-closer"></a>
                <div id="popup-content"></div>
            </div>
</div>
    <script type="text/javascript">

function hasCellId (feature) {
	return !(
		!feature.n.tags['gsm:cellid'] &&
		!feature.n.tags['umts:cellid'] &&
		!feature.n.tags['lte:cellid']
	);
}

function clickOperator () {
    display.telenor = document.getElementById('checkbox.telenor').checked;
    display.telekom = document.getElementById('checkbox.telekom').checked;
    display.vodafone = document.getElementById('checkbox.vodafone').checked;
    display.unknown = document.getElementById('checkbox.unknown').checked;
    var checked = display.telenor || display.telekom || display.vodafone || display.unknown;
    if (!checked) {
	display.telenor = true;
	display.telekom = true;
	display.vodafone = true;
	display.unknown = true;
    }
    sites.changed();
    countCells();
}

var source = new ol.source.GeoJSON(
({
  projection: 'EPSG:3857',
  preFeatureInsert: function(feature) {
    feature.geometry.transform('EPSG:4326', 'EPSG:3857');
  },
  url: 'http://kolesar.turistautak.hu/osm/opencellid/geojson/overpass.geojson'
}));

var count = {
    all: 0,
    operator: 0,
    cellid: 0
};

var display = {
    telenor: true,
    telekom: true,
    vodafone: true,
    unknown: true
};

function getOperators(feature) {
    var operator = feature.n.tags.operator;
    var is = {};
    if (operator) {
        if (operator.indexOf('Telenor') != -1) is.telenor = true; // operators.push('01');
        if (operator.indexOf('Telekom') != -1) is.telekom = true; // operators.push('30');
        if (operator.indexOf('Vodafone') != -1) is.vodafone = true; // operators.push('70');
    }
    if (!is.telenor && !is.telekom && !is.vodafone) is.unknown = true; // operators.push('00');
    return is;
}

function countCells () {
    count.all = 0;
    count.operator = 0;
    count.cellid=0;

    source.forEachFeature(function (feature) {
	is = getOperators(feature);
	if (is.telenor && !display.telenor) return;
	if (is.telekom && !display.telekom) return;
	if (is.vodafone && !display.vodafone) return;
	if (is.unknown && !display.unknown) return;
	count.all++;
	if (feature.n.tags.operator) count.operator++;
	if (hasCellId(feature)) count.cellid++;
    });
    document.getElementById('count.all').innerHTML = count.all;
    document.getElementById('count.operator').innerHTML = count.operator;
    document.getElementById('count.cellid').innerHTML = count.cellid;
    document.getElementById('sarok').style.visibility = 'visible';
}

source.once('change', countCells);

var sites = new ol.layer.Vector({ source: source,

style: function(feature, resolution) {

    is = getOperators(feature);

    var operators = [];
    if (display.telenor && is.telenor) operators.push('01');
    if (display.telekom && is.telekom) operators.push('30');
    if (display.vodafone && is.vodafone) operators.push('70');
    if (display.unknown && is.unknown) operators.push('00');

    if (!operators.length) return;

    var small = !hasCellId(feature) ? '.small' : '';
    var filename = 'img/' + operators.join('-') + small + '.svg';

    var icon = new ol.style.Icon({
	src: filename
    });

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
       title: 'turistatérkép'
}),
new ol.layer.Tile({source: new ol.source.OSM(), type: 'base', title: 'mapnik'}),
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
  var feature = map.forEachFeatureAtPixel(evt.pixel,
      function(feature, layer) {
        return feature;
      });
  if (feature) {
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

// console.log(feature);
// alert('popup');
} else {
//    alert('nincs');
}
});

/*
// change mouse cursor when over marker
map.on('pointermove', function(e) {
  if (e.dragging) {
    $(element).popover('destroy');
    return;
  }
  var pixel = map.getEventPixel(e.originalEvent);
  var hit = map.hasFeatureAtPixel(pixel);
  map.getTarget().style.cursor = hit ? 'pointer' : '';
});
*/
    </script>
  </body>
</html>