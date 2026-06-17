(function(){
  // Mobile sidebar toggle
  var burger = document.getElementById('aburger');
  var aside = document.getElementById('asidebar');
  if (burger && aside) {
    burger.addEventListener('click', function () {
      var open = aside.classList.toggle('open');
      document.body.style.overflow = open ? 'hidden' : '';
    });
  }

  // "Select all" checkbox in list grids (bulk actions)
  var master = document.querySelector('[data-check-all]');
  if (master) {
    master.addEventListener('change', function () {
      document.querySelectorAll('input[name="ids[]"]').forEach(function (cb) {
        cb.checked = master.checked;
      });
    });
  }

  // Photo picker: reflect selection with a highlighted border.
  var picks = document.querySelectorAll('.photo-grid input');
  if (picks.length) {
    var sync = function () {
      picks.forEach(function (i) {
        var card = i.closest('.photo-pick');
        if (card) { card.classList.toggle('sel', i.checked); }
      });
    };
    picks.forEach(function (i) { i.addEventListener('change', sync); });
    sync();
  }

  // "Load more": fetch the next cumulative page and swap the table body in
  // place. Degrades to a normal link (full reload) if JS/fetch is unavailable.
  var more = document.getElementById('loadmore');
  if (more && window.fetch) {
    more.addEventListener('click', function (e) {
      e.preventDefault();
      var url = more.getAttribute('href');
      var label = more.textContent;
      more.textContent = '…';
      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.text(); })
        .then(function (html) {
          var doc = new DOMParser().parseFromString(html, 'text/html');
          var sel = more.getAttribute('data-tbody') || '#works-tbody';
          var fresh = doc.querySelector(sel);
          var cur = document.querySelector(sel);
          if (fresh && cur) { cur.innerHTML = fresh.innerHTML; }
          var nextMore = doc.getElementById('loadmore');
          if (nextMore) {
            more.setAttribute('href', nextMore.getAttribute('href'));
            more.textContent = label;
          } else if (more.parentNode) {
            more.parentNode.removeChild(more);
          }
          if (master) { master.checked = false; }
        })
        .catch(function () { window.location = url; });
    });
  }
})();
