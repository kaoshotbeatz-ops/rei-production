// Admin/portal: CSRF injection + datetime normalization.
(function () {
  var meta = document.querySelector('meta[name="csrf-token"]');
  if (meta) {
    document.querySelectorAll('form').forEach(function (f) {
      if (f.method && f.method.toLowerCase() === 'get') return;
      if (f.querySelector('input[name="_csrf"]')) return;
      var i = document.createElement('input');
      i.type = 'hidden'; i.name = '_csrf'; i.value = meta.content;
      f.appendChild(i);
    });
  }
  // datetime-local -> "YYYY-MM-DD HH:MM"
  document.querySelectorAll('form').forEach(function (f) {
    f.addEventListener('submit', function () {
      f.querySelectorAll('input[type="datetime-local"]').forEach(function (el) {
        if (el.value) el.value = el.value.replace('T', ' ');
      });
    });
  });

  // --- Project board: drag-and-drop with persistence ---
  var board = document.getElementById('board');
  if (board) {
    var token = meta ? meta.content : '';
    var dragged = null;
    board.querySelectorAll('.taskcard').forEach(function (c) {
      c.addEventListener('dragstart', function () { dragged = c; setTimeout(function () { c.classList.add('dragging'); }, 0); });
      c.addEventListener('dragend', function () { c.classList.remove('dragging'); });
    });
    board.querySelectorAll('.dropzone').forEach(function (zone) {
      zone.addEventListener('dragover', function (e) {
        e.preventDefault();
        zone.classList.add('over');
        var after = afterElement(zone, e.clientY);
        if (!dragged) return;
        if (after == null) zone.appendChild(dragged); else zone.insertBefore(dragged, after);
      });
      zone.addEventListener('dragleave', function () { zone.classList.remove('over'); });
      zone.addEventListener('drop', function () {
        zone.classList.remove('over');
        var status = zone.dataset.status;
        var ids = [].map.call(zone.querySelectorAll('.taskcard'), function (c) { return Number(c.dataset.id); });
        // update the column count badges
        board.querySelectorAll('.col').forEach(function (col) {
          var cc = col.querySelector('.cc'); if (cc) cc.textContent = col.querySelectorAll('.taskcard').length;
        });
        fetch('/admin/tasks/reorder', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
          body: JSON.stringify({ status: status, ids: ids }),
        });
      });
    });
    function afterElement(zone, y) {
      var els = [].slice.call(zone.querySelectorAll('.taskcard:not(.dragging)'));
      return els.reduce(function (closest, child) {
        var box = child.getBoundingClientRect();
        var offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) return { offset: offset, element: child };
        return closest;
      }, { offset: -Infinity }).element || null;
    }
  }

  // --- Resource Engine: tab switching ---
  var tabWrap = document.querySelector('[data-tabs]');
  if (tabWrap) {
    tabWrap.querySelectorAll('.tab').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var name = btn.dataset.tab;
        tabWrap.querySelectorAll('.tab').forEach(function (b) { b.classList.toggle('active', b === btn); });
        document.querySelectorAll('.tabpane').forEach(function (p) { p.classList.toggle('active', p.dataset.pane === name); });
      });
    });
  }

  // --- Resource Engine: live availability check ---
  var availForm = document.querySelector('form[data-availability]');
  if (availForm) {
    var box = document.getElementById('availability');
    var rid = availForm.dataset.availability;
    function fmt(v) { return v ? v.replace('T', ' ') : ''; }
    function check() {
      var s = availForm.querySelector('[name="start_at"]').value;
      var e = availForm.querySelector('[name="end_at"]').value;
      if (!s || !e) { box.hidden = true; return; }
      fetch('/admin/api/availability?resource_id=' + rid + '&start=' + encodeURIComponent(fmt(s)) + '&end=' + encodeURIComponent(fmt(e)))
        .then(function (r) { return r.json(); })
        .then(function (d) {
          box.hidden = false;
          if (d.ok) { box.className = 'avail ok'; box.textContent = '✅ Available — no conflicts in this window.'; }
          else { box.className = 'avail bad'; box.textContent = '⛔ Conflict: overlaps ' + (d.conflicts ? d.conflicts.length : 0) + ' existing allocation(s).'; }
        }).catch(function () { box.hidden = true; });
    }
    availForm.querySelector('[name="start_at"]').addEventListener('change', check);
    availForm.querySelector('[name="end_at"]').addEventListener('change', check);
  }
})();
