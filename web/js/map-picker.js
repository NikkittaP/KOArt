/*
 * Location picker for the work form.
 *
 * Leaflet + OpenStreetMap tiles (no API key). The chosen point is written into
 * the hidden #paintings-coordinates input as "lat@lng" — the same format the
 * old Yandex widget used, so the server side is unchanged.
 *
 * Features: click the map or drag the marker to set the point; search a city /
 * address through the free Nominatim geocoder; clear the selection. The photo
 * EXIF / device-location auto-fill (painting-form.js) calls the exposed
 * window.paintingMapSetCoords() to pre-place the marker, but never overrides a
 * point the author already chose.
 */
(function () {
  var el = document.getElementById('paintings-map');
  if (!el || typeof L === 'undefined') return;

  var input = document.getElementById('paintings-coordinates');
  var searchInput = document.querySelector('.mappick-search');
  var clearBtn = document.querySelector('.mappick-clear');
  var statusEl = document.querySelector('.mappick-status');

  function parseCoords(v) {
    if (!v) return null;
    var p = String(v).split('@');
    if (p.length !== 2) return null;
    var lat = parseFloat(p[0]), lng = parseFloat(p[1]);
    if (isNaN(lat) || isNaN(lng)) return null;
    return [lat, lng];
  }

  var start = parseCoords(input && input.value);
  var defaultCenter = [55.751244, 37.618423]; // Moscow

  var map = L.map(el).setView(start || defaultCenter, start ? 12 : 4);
  // Drop the Leaflet prefix (the flag + "Leaflet" link); keep the required
  // OpenStreetMap tile attribution.
  map.attributionControl.setPrefix(false);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);

  var marker = null;

  function writeInput(latlng) {
    if (input) input.value = latlng.lat.toFixed(6) + '@' + latlng.lng.toFixed(6);
  }

  function setMarker(latlng, opts) {
    opts = opts || {};
    var ll = L.latLng(latlng);
    if (!marker) {
      marker = L.marker(ll, { draggable: true }).addTo(map);
      marker.on('dragend', function () { writeInput(marker.getLatLng()); });
    } else {
      marker.setLatLng(ll);
    }
    writeInput(ll);
    if (opts.pan !== false) map.setView(ll, Math.max(map.getZoom(), opts.zoom || 13));
  }

  if (start) setMarker(start, { pan: false });

  map.on('click', function (e) { setMarker(e.latlng, { pan: false }); });

  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      if (marker) { map.removeLayer(marker); marker = null; }
      if (input) input.value = '';
      if (statusEl) statusEl.textContent = '';
    });
  }

  // City / address search via Nominatim (OpenStreetMap, no key, ~1 req/s).
  function doSearch(q) {
    if (!q) return;
    if (statusEl) statusEl.textContent = '…';
    var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1'
      + '&accept-language=ru&q=' + encodeURIComponent(q);
    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data && data.length) {
          if (statusEl) statusEl.textContent = '';
          setMarker([parseFloat(data[0].lat), parseFloat(data[0].lon)], { zoom: 12 });
        } else if (statusEl) {
          statusEl.textContent = 'Ничего не найдено';
        }
      })
      .catch(function () { if (statusEl) statusEl.textContent = 'Ошибка поиска'; });
  }

  if (searchInput) {
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); doSearch(searchInput.value.trim()); }
    });
  }

  // Auto-fill hook for painting-form.js (photo EXIF / device location).
  window.paintingMapSetCoords = function (lat, lng) {
    if (input && parseCoords(input.value)) return; // keep the author's pick
    if (typeof lat !== 'number' || typeof lng !== 'number' || isNaN(lat) || isNaN(lng)) return;
    setMarker([lat, lng], { zoom: 13 });
  };

  // The map div is often laid out (in a panel) after Leaflet measures it.
  setTimeout(function () { map.invalidateSize(); }, 200);
})();
