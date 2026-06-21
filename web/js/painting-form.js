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
  var input = document.getElementById('ph-input');
  if (!input || typeof DataTransfer === 'undefined') return;

  var dropEl = document.getElementById('ph-drop');
  var emptyEl = document.getElementById('ph-empty');
  var coverEl = document.getElementById('ph-cover');
  var coverImg = document.getElementById('ph-cover-img');
  var thumbsEl = document.getElementById('ph-thumbs');
  var coverIndexEl = document.getElementById('ph-cover-index');
  var deviceCoordsEl = document.getElementById('ph-device-coords');

  var selected = [];     // our master list of File objects
  var coverIndex = 0;    // index (into selected) of the chosen cover
  var urls = [];         // object URLs to revoke on each render
  var locRequested = false;

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
    Array.prototype.forEach.call(fileList, function (f) {
      if (f && f.type && f.type.indexOf('image/') === 0) {
        selected.push(f);
      }
    });
    syncInput();
    render();
    requestLocationOnce();
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
    dropEl.classList.remove('drag');
    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
      addFiles(e.dataTransfer.files);
    }
  });
})();
