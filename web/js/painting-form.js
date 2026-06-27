/*
 * Photo-first uploader for the "Add work" page.
 *
 * Lets the author pick / drag in one or more images and see the cover
 * immediately, before any data is filled in. Nothing is uploaded here — the
 * files ride along with the normal form submit (multipart). The server then
 * stores them and marks the chosen one (cover_index) as the cover.
 *
 * Also captures the device's current location as a fallback geotag
 * (device_coords) — the server prefers the photo's own EXIF GPS and only uses
 * this when the photo has none.
 */
(function () {
  // Stop the browser from navigating to (opening) a file that is dropped
  // anywhere on the page — by default a missed drop opens the image in the
  // tab, which looks like "nothing happened". This guard runs even if the
  // uploader below bails out.
  window.addEventListener('dragover', function (e) { e.preventDefault(); });

  var input = document.getElementById('ph-input');
  if (!input || typeof DataTransfer === 'undefined') {
    // No uploader on this page: still swallow the drop so nothing opens.
    window.addEventListener('drop', function (e) { e.preventDefault(); });
    return;
  }

  var dropEl = document.getElementById('ph-drop');
  var emptyEl = document.getElementById('ph-empty');
  var coverEl = document.getElementById('ph-cover');
  var coverImg = document.getElementById('ph-cover-img');
  var thumbsEl = document.getElementById('ph-thumbs');
  var coverIndexEl = document.getElementById('ph-cover-index');
  var deviceCoordsEl = document.getElementById('ph-device-coords');
  var errorEl = document.getElementById('ph-error');

  // Upload limits / messages come from data-* on the dropzone so the copy
  // stays translated (Yii::t) and the MB cap matches the server.
  var ds = (dropEl && dropEl.dataset) ? dropEl.dataset : {};
  var maxMb = parseFloat(ds.maxMb) || 15;
  var maxBytes = maxMb * 1024 * 1024;
  var msgLarge = ds.errLarge || 'Image "{name}" is too large — maximum is {max} MB.';
  var msgType = ds.errType || 'File "{name}" is not an image and was skipped.';

  var selected = [];     // our master list of File objects
  var coverIndex = 0;    // index (into selected) of the chosen cover
  var urls = [];         // object URLs to revoke on each render
  var locRequested = false;

  function showErrors(list) {
    if (!errorEl) return;
    if (!list.length) { errorEl.hidden = true; errorEl.innerHTML = ''; return; }
    errorEl.innerHTML = list.map(function (m) {
      return String(m).replace(/[&<>]/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c];
      });
    }).join('<br>');
    errorEl.hidden = false;
  }

  function revokeUrls() {
    urls.forEach(function (u) { URL.revokeObjectURL(u); });
    urls = [];
  }

  function objUrl(file) {
    var u = URL.createObjectURL(file);
    urls.push(u);
    return u;
  }

  // Push our master list back into the real <input> so it submits with the form.
  function syncInput() {
    var dt = new DataTransfer();
    selected.forEach(function (f) { dt.items.add(f); });
    input.files = dt.files;
  }

  function render() {
    if (coverIndex >= selected.length) coverIndex = 0;
    coverIndexEl.value = String(coverIndex);
    revokeUrls();

    if (!selected.length) {
      emptyEl.hidden = false;
      coverEl.hidden = true;
      thumbsEl.hidden = true;
      thumbsEl.innerHTML = '';
      return;
    }

    emptyEl.hidden = true;
    coverEl.hidden = false;
    coverImg.src = objUrl(selected[coverIndex]);

    thumbsEl.innerHTML = '';
    selected.forEach(function (file, i) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'ph-thumb' + (i === coverIndex ? ' is-cover' : '');
      btn.title = file.name;

      var img = document.createElement('img');
      img.src = objUrl(file);
      btn.appendChild(img);

      var x = document.createElement('span');
      x.className = 'ph-thumb-x';
      x.textContent = '×';
      x.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        removeAt(i);
      });
      btn.appendChild(x);

      btn.addEventListener('click', function () {
        coverIndex = i;
        render();
      });
      thumbsEl.appendChild(btn);
    });

    // Show the strip only when there's more than the cover.
    thumbsEl.hidden = selected.length <= 1;
  }

  function addFiles(fileList) {
    // Validate FIRST, change state only after. A bad drop (too big / not an
    // image) must never wipe the photo the author already picked — otherwise
    // the preview "collapses" and they have to reload to try again.
    var errors = [];
    var valid = [];
    Array.prototype.forEach.call(fileList, function (f) {
      if (!f) return;
      // Reject non-images with a clear message instead of silently ignoring.
      if (!f.type || f.type.indexOf('image/') !== 0) {
        errors.push(msgType.replace('{name}', f.name || '?'));
        return;
      }
      // Reject oversized files client-side (the server would reject them too,
      // but this gives instant, understandable feedback).
      if (f.size > maxBytes) {
        errors.push(msgLarge.replace('{name}', f.name || '?').replace('{max}', maxMb));
        return;
      }
      valid.push(f);
    });

    showErrors(errors);

    // Nothing usable in this batch: keep the current selection as-is.
    if (!valid.length) return;

    if (!input.multiple) {
      // One image per work: the newest valid pick replaces the previous one.
      selected = [valid[valid.length - 1]];
      coverIndex = 0;
    } else {
      valid.forEach(function (f) { selected.push(f); });
    }

    syncInput();
    render();
    requestLocationOnce();
    if (selected.length) readPhotoGps(selected[coverIndex] || selected[0]);
  }

  // Read the cover photo's GPS (EXIF) client-side and drop a map marker. The
  // map keeps any point the author already chose (see paintingMapSetCoords).
  function readPhotoGps(file) {
    if (!file || typeof EXIF === 'undefined' || typeof window.paintingMapSetCoords !== 'function') return;
    EXIF.getData(file, function () {
      var lat = toDecimal(EXIF.getTag(this, 'GPSLatitude'), EXIF.getTag(this, 'GPSLatitudeRef'));
      var lng = toDecimal(EXIF.getTag(this, 'GPSLongitude'), EXIF.getTag(this, 'GPSLongitudeRef'));
      if (lat !== null && lng !== null && (lat !== 0 || lng !== 0)) {
        window.paintingMapSetCoords(lat, lng);
      }
    });
  }

  // EXIF GPS is [deg, min, sec] (each a Number-like); ref is N/S/E/W.
  function toDecimal(dms, ref) {
    if (!dms || dms.length < 3) return null;
    var d = +dms[0], m = +dms[1], s = +dms[2];
    if (isNaN(d) || isNaN(m) || isNaN(s)) return null;
    var dec = d + m / 60 + s / 3600;
    if (ref === 'S' || ref === 'W') dec = -dec;
    return Math.round(dec * 1e6) / 1e6;
  }

  function removeAt(i) {
    selected.splice(i, 1);
    if (coverIndex >= selected.length) coverIndex = Math.max(0, selected.length - 1);
    syncInput();
    render();
  }

  function requestLocationOnce() {
    if (locRequested || !deviceCoordsEl || !navigator.geolocation || !selected.length) return;
    locRequested = true;
    navigator.geolocation.getCurrentPosition(
      function (pos) {
        deviceCoordsEl.value = pos.coords.latitude + '@' + pos.coords.longitude;
        if (typeof window.paintingMapSetCoords === 'function') {
          window.paintingMapSetCoords(pos.coords.latitude, pos.coords.longitude);
        }
      },
      function () { /* denied / unavailable — leave blank, EXIF or manual still work */ },
      { enableHighAccuracy: false, timeout: 8000, maximumAge: 600000 }
    );
  }

  // Native picker. The change event replaces input.files with just the new
  // pick, so we read those, append to our master list, then re-sync.
  input.addEventListener('change', function () {
    var picked = input.files;
    if (picked && picked.length) addFiles(picked);
  });

  // Drag & drop onto the dropzone.
  ['dragenter', 'dragover'].forEach(function (ev) {
    dropEl.addEventListener(ev, function (e) {
      e.preventDefault();
      dropEl.classList.add('drag');
    });
  });
  ['dragleave', 'dragend'].forEach(function (ev) {
    dropEl.addEventListener(ev, function () { dropEl.classList.remove('drag'); });
  });
  dropEl.addEventListener('drop', function (e) {
    e.preventDefault();
    e.stopPropagation();
    dropEl.classList.remove('drag');
    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
      addFiles(e.dataTransfer.files);
    }
  });

  // Catch drops that land just outside the (small) dropzone too: instead of
  // the browser opening the image, route them into the uploader.
  window.addEventListener('drop', function (e) {
    e.preventDefault();
    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
      addFiles(e.dataTransfer.files);
    }
  });
})();
