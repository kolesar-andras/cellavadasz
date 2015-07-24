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
    background: rgba(255, 255, 255, 0.4);
    /* For IE 5.5 - 7*/
    filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=#66FFFFFF, endColorstr=#66FFFFFF);
    /* For IE 8*/
    -ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr=#66FFFFFF, endColorstr=#66FFFFFF)";

	}

	#sarok span {
		font-weight: bold;
	}

    </style>
    <script src="lib/jquery.min.js" type="text/javascript"></script>
    <script src="lib/ol/build/ol.js" type="text/javascript"></script>
    <script src="lib/ol/build/ol3-layerswitcher.js" type="text/javascript"></script>
    <title>cellavadász</title>
  </head>
  <body>
<div id="sarok">
    <div><span id="count.all"></span> bázisállomás</div>
    <div><span id="count.operator"></span> szolgáltatóval</div>
    <div><span id="count.cellid"></span> cella-azonosítókkal</div>
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
}

source.once('change', function () {
    source.forEachFeature(function (feature) {
	count.all++;
	if (feature.n.tags.operator) count.operator++;
	if (hasCellId(feature)) count.cellid++;
    });
    document.getElementById('count.all').innerHTML = count.all;
    document.getElementById('count.operator').innerHTML = count.operator;
    document.getElementById('count.cellid').innerHTML = count.cellid;
    document.getElementById('sarok').style.visibility = 'visible';
});

var sites = new ol.layer.Vector({ source: source,

style: function(feature, resolution) {

    var operators = [];
    var operator = feature.n.tags.operator;
    if (operator && operator.indexOf('Telenor') != -1) operators.push('01');
    if (operator && operator.indexOf('Telekom') != -1) operators.push('30');
    if (operator && operator.indexOf('Vodafone') != -1) operators.push('70');
    if (operators.length == 0) operators.push('00');
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