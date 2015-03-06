<!doctype html>
<html lang="en">
  <head>
    <link rel="stylesheet" href="lib/ol/css/ol.css" type="text/css">
    <link rel="stylesheet" href="lib/ol/css/ol3-layerswitcher.css" type="text/css">
    <style>
      html, body, .map {
        height: 100%;
        width: 100%;
	margin: 0;
      }
    </style>
    <script src="lib/ol/build/ol.js" type="text/javascript"></script>
    <script src="lib/ol/build/ol3-layerswitcher.js" type="text/javascript"></script>
    <title>cellavadász</title>
    <meta charset="UTF-8">
  </head>
  <body>
    <div id="map" class="map"></div>
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


style: (function() {
  var defaultStyle = [new ol.style.Style({
    image: new ol.style.Circle({
        radius: 6,
        fill: new ol.style.Fill({color: [255, 255, 255, 0.5]}),
        stroke: new ol.style.Stroke({color: 'black', width: 1.5})
    })
  })];
  var ruleStyle = [new ol.style.Style({
    image: new ol.style.Circle({
        radius: 6,
        fill: new ol.style.Fill({color: [0, 128, 0, 0.5]}),
        stroke: new ol.style.Stroke({color: 'black', width: 1.5})
    })
  })];
  return function(feature, resolution) {
    if (feature.get('gsm:cellid')) {
      return ruleStyle;
    } else {
      return defaultStyle;
    }
  };
})(),

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
    </script>
  </body>
</html>