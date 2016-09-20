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
	unknown: false,
	nosite: false
};

var defaultOptions = {
	display: JSON.stringify(display)
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
	sites.changed();
	refreshMap();
}

function getCheckboxes () {
	display.telenor = document.getElementById('checkbox.telenor').checked;
	display.telekom = document.getElementById('checkbox.telekom').checked;
	display.vodafone = document.getElementById('checkbox.vodafone').checked;
	display.unknown = document.getElementById('checkbox.unknown').checked;
	display.nosite = document.getElementById('checkbox.nosite').checked;
}

function setCheckboxes () {
	document.getElementById('checkbox.telenor').checked = display.telenor;
	document.getElementById('checkbox.telekom').checked = display.telekom;
	document.getElementById('checkbox.vodafone').checked = display.vodafone;
	document.getElementById('checkbox.unknown').checked = display.unknown;
	document.getElementById('checkbox.nosite').checked = display.nosite;
}

function refreshMap () {
	getCheckboxes();
	countCells();
	setHash();
}

function getOperatorArray (feature) {

	is = getOperators(feature);
	var operators = [];
	if (display.telenor && is.telenor) operators.push('01');
	if (display.telekom && is.telekom) operators.push('30');
	if (display.vodafone && is.vodafone) operators.push('70');
	if (display.unknown && is.unknown) operators.push('00');
	if (display.nosite && !is.site && !operators.length) operators.push('nosite');
	return operators;

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

function getOperatorColours(feature) {
    var colours = [];
    var is = getOperators(feature);
    if (display.telenor && is.telenor) colours.push('#00a9e3');
    if (display.telekom && is.telekom) colours.push('#000000');
    if (display.vodafone && is.vodafone) colours.push('#d5030b');
    if (!is.telenor && !is.telekom && !is.vodafone) is.unknown = true;
    if (display.unknown && is.unknown) colours.push('#808080');
    return colours;
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

function roundCoord (coord, zoom) {
	d = 0;
	if (zoom >= 17) d = 5; else
	if (zoom >= 9) d = 4; else
	if (zoom >= 5) d = 3; else
	if (zoom >= 3) d = 2; else
	if (zoom >= 2) d = 1;
	return coord.toFixed(d);
}

function parseHash (hash) {
	if (typeof(hash) != 'string') return {};
	var argsParsed = {};
	if (hash.charAt(0) == '#') hash = hash.substring(1);
	var args = hash.split('&');
	for (i=0; i < args.length; i++) {
		var arg = decodeURIComponent(args[i]);
		var kvp = arg.split('=');
		var key = kvp[0].trim();
		if (kvp.length == 1) {
			if (key.charAt(0) == '!') {
				argsParsed[key.substring(1)] = false;
			} else {
				argsParsed[key] = true;
			}
		} else {
			argsParsed[key] = kvp.slice(1).join('=').trim();
		}
	}
	return argsParsed;
}

function getHash () {
	var hash = window.location.hash;
	var args = parseHash(hash);
	if ('map' in args) {
		var parts = args.map.split('/');
		if (parts.length === 3) {
			var zoom = parseInt(parts[0], 10);
			var center = ol.proj.transform([
					parseFloat(parts[2]),
					parseFloat(parts[1])
				], 'EPSG:4326', 'EPSG:3857');
			var view = map.getView();
			view.setCenter(center);
			view.setZoom(zoom);
		}
	} else {
		var extent = ol.extent.boundingExtent([[16.1, 45.7], [22.9, 48.6]]);
		extent = ol.proj.transformExtent(extent, 'EPSG:4326', 'EPSG:3857');
		map.getView().fitExtent(extent, map.getSize());
		if (isMapDefault) mapHashDefault = hashForMap();
	}

	var displayOriginal = JSON.stringify(display);
	if ('operator' in args) {
		var operators = args.operator.split(';');
		$.each(display, function (key, value) {
			display[key] = operators.indexOf(key) != -1;
		});
	} else {
		display = JSON.parse(defaultOptions.display);
	}
	if (JSON.stringify(display) != displayOriginal) {
		setCheckboxes();
		clickOperator();
	}

	if ('id' in args) {
		featureClicked = args.id;
		popupFeatureId(featureClicked);
	} else if (featureClicked) {
		closePopup();
	}
}

function setHash () {
	var hash = [];
	var h = hashForMap();
	if (h !== null) hash.push(h);
	h = hashForOperator();
	if (h !== null) hash.push(h);
	h = hashForFeature();
	if (h !== null) hash.push(h);
	hashNew = '#'+hash.join('&');
	hashOld = window.location.hash;
	if (hashOld === '') hashOld = '#';
	if (hashOld != hashNew) {
		hashSelfUpdated = true;
		window.location.hash = hashNew;
	}
}

function hashForMap () {
	var view = map.getView();
	var zoom = view.getZoom();
	var center = view.getCenter();
	center = ol.proj.transform(center, 'EPSG:3857', 'EPSG:4326');
	var hash = 'map='+ zoom + '/' + roundCoord(center[1], zoom) + '/' + roundCoord(center[0], zoom);
	if (hash == mapHashDefault) return null;
	return hash;
}

function hashForOperator () {
	if (JSON.stringify(display) == defaultOptions.display) return null;
	var operator = [];
	$.each(display, function (key, value) {
		if (value) operator.push(key);
	});
	return 'operator='+operator.join(';');
}

function hashForFeature () {
	if (!featureClicked) return null;
	return 'id=' + featureClicked;
}

$(window).on('hashchange', function () {
	if (hashSelfUpdated) {
		hashSelfUpdated = false;
		return;
	}
	getHash();
});
