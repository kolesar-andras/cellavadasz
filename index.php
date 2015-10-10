<!doctype html>
<html lang="hu">
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="lib/ol/css/ol.css" type="text/css">
	<link rel="stylesheet" href="lib/ol/css/ol3-layerswitcher.css" type="text/css">
	<link rel="stylesheet" href="css/page.css" type="text/css">
	<script src="lib/jquery.min.js" type="text/javascript"></script>
	<script src="lib/jquery-cookie.js" type="text/javascript"></script>
	<script src="lib/jquery-lang.js" type="text/javascript"></script>
	<script src="js/lang.js" type="text/javascript"></script>
	<script src="lib/ol/build/ol.js" type="text/javascript"></script>
	<script src="lib/ol/build/ol3-layerswitcher.js" type="text/javascript"></script>
	<script src="js/stat.js" type="text/javascript"></script>
	<script src="js/map.js" type="text/javascript"></script>
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

	</script>
	</body>
</html>
