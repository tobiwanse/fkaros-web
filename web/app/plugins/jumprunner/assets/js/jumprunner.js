/* Jumprunner JS — minimal 3D map + compass */

var initMap;

(function ($) {
	'use strict';

	var NM_RADIUS_METERS = 1852;
	var DEFAULT_RANGE = 4500;
	var COMPASS_ALTITUDE = 100;
	var COMPASS_FIT_RANGE = 6000;
	var VIEW_STATE_KEY = 'jumprunner.map3d.viewState.v1';

	function readStoredViewState() {
		try {
			var raw = window.localStorage.getItem(VIEW_STATE_KEY);
			if (!raw) { return null; }
			var parsed = JSON.parse(raw);
			if (!parsed || typeof parsed !== 'object') { return null; }
			return parsed;
		} catch (e) {
			return null;
		}
	}

	function saveViewState(state) {
		if (!state) { return; }
		try {
			window.localStorage.setItem(VIEW_STATE_KEY, JSON.stringify(state));
		} catch (e) {}
	}

	function numberOrFallback(value, fallback) {
		return typeof value === 'number' && isFinite(value) ? value : fallback;
	}

	function destinationPoint(lat, lng, bearingDeg, distanceMeters) {
		var R = 6378137;
		var br = bearingDeg * Math.PI / 180;
		var lat1 = lat * Math.PI / 180;
		var lng1 = lng * Math.PI / 180;
		var dr = distanceMeters / R;

		var lat2 = Math.asin(
			Math.sin(lat1) * Math.cos(dr) +
			Math.cos(lat1) * Math.sin(dr) * Math.cos(br)
		);
		var lng2 = lng1 + Math.atan2(
			Math.sin(br) * Math.sin(dr) * Math.cos(lat1),
			Math.cos(dr) - Math.sin(lat1) * Math.sin(lat2)
		);

		return { lat: lat2 * 180 / Math.PI, lng: lng2 * 180 / Math.PI };
	}

	function buildCircleCoords(centerLat, centerLng, radius, altitude, segments) {
		var coords = [];
		for (var i = 0; i <= segments; i++) {
			var b = (i * 360) / segments;
			var p = destinationPoint(centerLat, centerLng, b, radius);
			coords.push({ lat: p.lat, lng: p.lng, altitude: altitude });
		}
		return coords;
	}

	function svgDims(text, fs, pad) {
		var tw = Math.round(text.length * fs * 0.6 + pad * 2);
		var th = fs + pad * 2;
		return { tw: tw, th: th };
	}

	function getCompassAltitudeMode(maps3d) {
		if (!maps3d || !maps3d.AltitudeMode) {
			//return 'RELATIVE_TO_GROUND';
			return 'ABSOLUTE';
		}
		return maps3d.AltitudeMode.ABSOLUTE;
	}

	var CARDINAL_NAMES = {
		0: 'N', 45: 'NO', 90: 'O', 135: 'SO',
		180: 'S', 225: 'SV', 270: 'V', 315: 'NV'
	};

	function Compass3D(map3d, maps3d) {
		this._map = map3d;
		this._maps3d = maps3d;
		this._center = null;
		this._visible = true;
	}

	Compass3D.prototype._attachElement = function (el) {
		if (!el) { return; }
		if (!el.isConnected) {
			this._map.appendChild(el);
		}
	};

	Compass3D.prototype._detachElement = function (el) {
		if (!el) { return; }
		try {
            el.map = null;
        } catch (e) {}
		if (el.parentNode) {
			el.parentNode.removeChild(el);
		}
	};

	Compass3D.prototype._clear = function () {
		var all = this._map.querySelectorAll('[data-jumprunner-compass]');
		for (var i = 0; i < all.length; i++) {
			this._detachElement(all[i]);
		}
	};

	Compass3D.prototype.setCenter = function (center) {
		this._center = center;
		//this._rebuild();
	};

	Compass3D.prototype.show = function () {
		if (this._visible) { return; }
		this._visible = true;
		//this._rebuild();
	};

	Compass3D.prototype.hide = function () {
		if (!this._visible) { return; }
		this._visible = false;
		this._clear();
	};

	Compass3D.prototype._addCircle = function () {
		var Polyline = this._maps3d.Polyline3DElement;
		if (!Polyline || !this._center) { return; }
        const altitudeMode = getCompassAltitudeMode(this._maps3d)
        //const altitudeMode = "CLAMP_TO_GROUND";
        //const altitudeMode = "ABSOLUTE";
        //const altitudeMode = "ABSOLUTE";
		var line = new Polyline({
			path: buildCircleCoords(this._center.lat, this._center.lng, NM_RADIUS_METERS, COMPASS_ALTITUDE, 96),
			strokeColor: '#0b6fb8',
			strokeWidth: 5,
			altitudeMode: altitudeMode,
		});

		line.setAttribute('data-jumprunner-compass', '1');
		this._attachElement(line);
	};

	Compass3D.prototype._addLines = function () {
		var Polyline = this._maps3d.Polyline3DElement;
		var c = this._center;
		var altMode = getCompassAltitudeMode(this._maps3d);

		if (!Polyline || !c) { return; }

		for (var b = 0; b < 180; b += 30) {
			var p1 = destinationPoint(c.lat, c.lng, b, NM_RADIUS_METERS);
			var p2 = destinationPoint(c.lat, c.lng, b + 180, NM_RADIUS_METERS);
			var line = new Polyline({
				path: [
					{ lat: p1.lat, lng: p1.lng, altitude: COMPASS_ALTITUDE },
					{ lat: c.lat, lng: c.lng, altitude: COMPASS_ALTITUDE },
					{ lat: p2.lat, lng: p2.lng, altitude: COMPASS_ALTITUDE }
				],
				strokeColor: 'rgba(11,111,184,0.85)',
				strokeWidth: 5,
				altitudeMode: altMode
			});

			line.setAttribute('data-jumprunner-compass', '1');
			this._attachElement(line);
		}
	};

	Compass3D.prototype._addLabel = function (lat, lng, text, variant) {
		var isDir = variant === 'direction';
		var scale = Math.max(0.3, Math.min(3.0, DEFAULT_RANGE / (this._map.range || DEFAULT_RANGE)));
		var fs = Math.round((isDir ? 28 : 24) * scale);
		var pad = Math.round(10 * scale);
		var d = svgDims(text, fs, pad);
		var tw = d.tw;
		var th = d.th;
		var altMode = getCompassAltitudeMode(this._maps3d);

		var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
		
		svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
		svg.setAttribute('width', String(tw));
		svg.setAttribute('height', String(th));
		svg.setAttribute('viewBox', '0 0 ' + tw + ' ' + th);

		var rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
		rect.setAttribute('x', '0');
		rect.setAttribute('y', '0');
		rect.setAttribute('width', String(tw));
		rect.setAttribute('height', String(th));
		rect.setAttribute('rx', '4');
		rect.setAttribute('fill', isDir ? 'rgba(11,111,184,0.92)' : 'rgba(255,255,255,0.85)');
		svg.appendChild(rect);

		var t = document.createElementNS('http://www.w3.org/2000/svg', 'text');
		t.setAttribute('x', String(tw / 2));
		t.setAttribute('y', '50%');
		t.setAttribute('font-family', 'Helvetica Neue,Helvetica,Arial,sans-serif');
		t.setAttribute('font-size', String(fs));
		t.setAttribute('font-weight', isDir ? '700' : '500');
		t.setAttribute('text-anchor', 'middle');
		t.setAttribute('dominant-baseline', 'central');
		t.setAttribute('fill', isDir ? '#ffffff' : '#0a2a44');
		t.textContent = text;
		svg.appendChild(t);

		var newEL = document.createElement('gmp-marker');
		newEL.setAttribute('data-jumprunner-compass', '1')
		newEL.setAttribute('position', '' + lat + ',' + lng + ',' + COMPASS_ALTITUDE);
		newEL.setAttribute('altitude-mode', altMode);
		newEL.setAttribute('title', 'Compass');
		newEL.setAttribute('anchor-top', '-50%');
		newEL.setAttribute('anchor-left', '-50%');
		newEL.appendChild(svg);
		this._attachElement(newEL);
	};

	Compass3D.prototype._addLabels = function () {
		var c = this._center;
		if (!c) { return; }

		for (var b = 0; b < 360; b += 15) {
			var p = destinationPoint(c.lat, c.lng, b, NM_RADIUS_METERS);
			var text = CARDINAL_NAMES[b] !== undefined ? CARDINAL_NAMES[b] : (b + '\u00B0');
			var variant = CARDINAL_NAMES[b] !== undefined ? 'direction' : 'degree';

			if (variant === 'degree') {
				p = destinationPoint(p.lat, p.lng, b + 180, 160);
			}
			if (variant === 'direction') {
				p = destinationPoint(p.lat, p.lng, b, 160);
			}
			this._addLabel(p.lat, p.lng, text, variant);
		}

		var axisDegrees = [360, 45, 90, 135, 180, 225, 270, 315];
		for (var i = 0; i < axisDegrees.length; i++) {
			var deg = axisDegrees[i];
			var bearing = deg === 360 ? 0 : deg;
			var pos = destinationPoint(c.lat, c.lng, bearing, NM_RADIUS_METERS);
			pos = destinationPoint(pos.lat, pos.lng, bearing + 180, 160);
			this._addLabel(pos.lat, pos.lng, deg + '\u00B0', 'degree');
		}
	};

	Compass3D.prototype._rebuild = function () {
		this._clear();
		if (!this._visible || !this._center) { return; }
		this._addCircle();
		this._addLines();
		this._addLabels();
	};

	function updateCompassToggleButton($button, visible) {
		if (!$button.length) { return; }
		$button.attr('aria-pressed', visible ? 'true' : 'false');
		$button.attr('title', visible ? 'Dölj kompass' : 'Visa kompass');
	}

	initMap = async function () {
		var $el = $('gmp-map-3d.jumprunner-map');
		if (!$el.length) { return; }

		var $root = $el.closest('.jumprunner');
		var $compassToggle = $root.find('.jumprunner-compass-toggle').first();
		var storedState = readStoredViewState();
		var lat = numberOrFallback(storedState && storedState.lat, parseFloat($el.data('lat')) || 0);
		var lng = numberOrFallback(storedState && storedState.lng, parseFloat($el.data('lng')) || 0);
		var tilt = numberOrFallback(storedState && storedState.tilt, 60);
		var heading = numberOrFallback(storedState && storedState.heading, 0);
		var range = Math.max(DEFAULT_RANGE, numberOrFallback(storedState && storedState.range, DEFAULT_RANGE));

		var maps3d;
		try {
			maps3d = await google.maps.importLibrary('maps3d');
		} catch (e) {
			console.error('Jumprunner: failed to load maps3d library', e);
			return;
		}

		var map3d = $el[0];
		if (map3d.__jumprunnerInitialized) { return; }
		map3d.__jumprunnerInitialized = true;

		map3d.center = { lat: lat, lng: lng, altitude: 0 };
		map3d.range = range;
		map3d.tilt = tilt;
		map3d.heading = heading;
		if (window.jumprunnerData && window.jumprunnerData.mapId) {
			map3d.setAttribute('map-id', window.jumprunnerData.mapId);
		}

		var compass = new Compass3D(map3d, maps3d);
		var compassVisible = true;
		var selectedCenter = { lat: lat, lng: lng, altitude: COMPASS_ALTITUDE };
		compass.setCenter(selectedCenter);
        compass._rebuild();

		function persistCurrentView(centerOverride) {
			if (centerOverride) {
				selectedCenter = { lat: centerOverride.lat, lng: centerOverride.lng, altitude: COMPASS_ALTITUDE };
			}
			var centerPos = selectedCenter;
			if (!centerPos) { return; }
			var state = {
				lat: numberOrFallback(centerPos.lat, lat),
				lng: numberOrFallback(centerPos.lng, lng),
				tilt: numberOrFallback(map3d.tilt, tilt),
				heading: numberOrFallback(map3d.heading, heading),
				range: Math.max(DEFAULT_RANGE, numberOrFallback(map3d.range, range))
			};
			saveViewState(state);
		}

		map3d.addEventListener('gmp-rangechange', function () {
			persistCurrentView();
			if (compassVisible) {
				//compass._rebuild();
			}
		});

		map3d.addEventListener('gmp-headingchange', function () {
			persistCurrentView();
			if (compassVisible) {
				//compass._rebuild();
			}
		});

		map3d.addEventListener('gmp-tiltchange', function () {
			persistCurrentView();
			if (compassVisible) {
				//compass._rebuild();
			}
		});

		map3d.addEventListener('gmp-click', function (evt) {
			var pos = evt && evt.position;
			if (!pos) { return; }
			var latVal = typeof pos.lat === 'function' ? pos.lat() : pos.lat;
			var lngVal = typeof pos.lng === 'function' ? pos.lng() : pos.lng;
			if (typeof latVal !== 'number' || typeof lngVal !== 'number') { return; }
			var center = { lat: latVal, lng: lngVal, altitude: COMPASS_ALTITUDE };
			map3d.dataset.lastLat = latVal;
			map3d.dataset.lastLng = lngVal;
			selectedCenter = center;
			compass.setCenter(center);
			persistCurrentView(center);
			compass._rebuild();
			map3d.flyCameraTo({
				endCamera: {
					center: center,
					altitudeMode: getCompassAltitudeMode(maps3d),
					tilt: numberOrFallback(map3d.tilt, tilt),
					heading: numberOrFallback(map3d.heading, heading),
					range: COMPASS_FIT_RANGE
				},
				durationMillis: 2000
			});
		});

		$compassToggle.on('click', function () {
			compassVisible = !compassVisible;
			if (compassVisible) {
				compass.show();
			} else {
				compass.hide();
			}
			updateCompassToggleButton($compassToggle, compassVisible);
		});
		
		updateCompassToggleButton($compassToggle, compassVisible);
		persistCurrentView({ lat: lat, lng: lng, altitude: 0 });
	};

})(jQuery);
