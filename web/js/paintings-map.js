/*
 * Admin works map.
 *
 * Renders every work that has a geotag on an interactive Leaflet map with
 * Leaflet.markercluster so nearby works group into a count bubble. Clicking a
 * marker opens a popup with an enlarged preview and basic info; from there the
 * owner can open the work for editing or view it on the public site (each in a
 * new tab).
 *
 * Data comes from two JSON <script> tags rendered by views/paintings/map.php,
 * so no data is inlined into executable JS.
 */
(function () {
  var el = document.getElementById('works-map');
  if (!el || typeof L === 'undefined') return;

  function readJson(id) {
    var node = document.getElementById(id);
    if (!node) return null;
    try { return JSON.parse(node.textContent || node.innerText || 'null'); }
    catch (e) { return null; }
  }

  var points = readJson('works-map-data') || [];
  var t = readJson('works-map-i18n') || {};

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  var map = L.map(el, { scrollWheelZoom: true }).setView([30, 10], 2);
  map.attributionControl.setPrefix(false);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);

  var cluster = L.markerClusterGroup({
    showCoverageOnHover: false,
    maxClusterRadius: 50,
    spiderfyOnMaxZoom: true
  });

  function popupHtml(p) {
    var parts = [];
    parts.push('<div class="wm-popup">');

    if (p.thumb) {
      parts.push('<div class="wm-pop-img"><img src="' + esc(p.thumb) + '" alt="' + esc(p.name) + '" loading="lazy"></div>');
    } else {
      parts.push('<div class="wm-pop-img wm-pop-noimg">' + esc(t.noPhoto || 'No photo') + '</div>');
    }

    parts.push('<div class="wm-pop-body">');
    parts.push('<div class="wm-pop-title">' + esc(p.name || ('#' + p.id)) + '</div>');

    var meta = [];
    if (p.section) meta.push('<span><b>' + esc(t.section || 'Section') + ':</b> ' + esc(p.section) + '</span>');
    if (p.series) meta.push('<span><b>' + esc(t.series || 'Series') + ':</b> ' + esc(p.series) + '</span>');
    if (p.date) meta.push('<span><b>' + esc(t.date || 'Date') + ':</b> ' + esc(p.date) + '</span>');
    if (meta.length) parts.push('<div class="wm-pop-meta">' + meta.join('') + '</div>');

    if (!p.visible) {
      parts.push('<div class="wm-pop-arch">' + esc(t.archived || 'Archived') + '</div>');
    }

    parts.push('<div class="wm-pop-actions">');
    parts.push('<a class="wm-btn" href="' + esc(p.editUrl) + '" target="_blank" rel="noopener">' + esc(t.edit || 'Edit') + '</a>');
    parts.push('<a class="wm-btn wm-btn-ghost" href="' + esc(p.viewUrl) + '" target="_blank" rel="noopener">' + esc(t.view || 'View on site') + '</a>');
    parts.push('</div>');

    parts.push('</div></div>');
    return parts.join('');
  }

  var markers = [];
  points.forEach(function (p) {
    if (typeof p.lat !== 'number' || typeof p.lng !== 'number') return;
    var m = L.marker([p.lat, p.lng]);
    m.bindPopup(popupHtml(p), { maxWidth: 280, minWidth: 220, className: 'wm-popup-wrap' });
    cluster.addLayer(m);
    markers.push(m);
  });

  map.addLayer(cluster);

  // Frame all markers on load (a little padding so edge markers aren't clipped).
  if (markers.length === 1) {
    map.setView(markers[0].getLatLng(), 8);
  } else if (markers.length > 1) {
    map.fitBounds(cluster.getBounds(), { padding: [40, 40], maxZoom: 12 });
  }

  // Keep the map sized correctly if the sidebar/layout shifts after load.
  setTimeout(function () { map.invalidateSize(); }, 200);
})();
