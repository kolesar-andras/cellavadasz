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
	width: 200px;
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

    </style>
    <script src="lib/jquery.min.js" type="text/javascript"></script>
    <script src="lib/ol/build/ol.js" type="text/javascript"></script>
    <script src="lib/ol/build/ol3-layerswitcher.js" type="text/javascript"></script>
    <title>cellavadász</title>
  </head>
  <body>
    <div id="map" class="map">
            <div id="popup" class="ol-popup">
                <a href="#" id="popup-closer" class="ol-popup-closer"></a>
                <div id="popup-content"></div>
            </div>
</div>
    <script type="text/javascript">
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

new ol.layer.Vector({ source: new ol.source.GeoJSON(
({
  projection: 'EPSG:3857',
  preFeatureInsert: function(feature) {
    feature.geometry.transform('EPSG:4326', 'EPSG:3857');
  },
  url: 'http://kolesar.turistautak.hu/osm/opencellid/geojson/overpass.geojson'
})),


style: function(feature, resolution) {

    var color = '#909090';
    var width = feature.get('gsm:cellid') ? 1.8 : 1.2;
    var radius = feature.get('gsm:cellid') ? 7.0 : 5.0;
    var operator = feature.get('operator');
    switch (operator) {
        case 'Telekom':  color = '#000000'; break;
	case 'Telenor':  color = '#00a9e3'; break;
	case 'Vodafone': color = '#d5030b'; break;
    }
    if (operator && operator.indexOf(';') != -1) color = '#606060';

    var image = new ol.style.Circle({
        radius: radius,
        fill: new ol.style.Fill({color: color}),
        stroke: new ol.style.Stroke({color: 'white', width: width})
    });
/*
    text: new ol.style.Text({
	font: '8px sans-serif',
        text: feature.get('operator'),
        fill: new ol.style.Fill({color: '#000' }),
	offsetY: 12
    }) */

    var style = {image: image};
/*
    if (feature.get('gsm:cellid')) {
        return [new ol.style.Style(style), new ol.style.Style({image:
	    new ol.style.Circle({
		radius: 2,
        	fill: new ol.style.Fill({color: 'white'})
	    })
        })]
    };
*/
    return [new ol.style.Style(style)];
},

title: 'bázisállomások'

})

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

    var html = 'node <a href="http://openstreetmap.org/node/'+feature.get('id')+'">'+feature.get('id')+'</a>'+"<br/>\n";
    var prop = feature.n; // feature.getAttributes();
    for (k in prop) {
    //   html += 'gsm:cellid=' + feature.get('gsm:cellid');
if (k=='geometry') continue;
if (k=='id') continue;
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