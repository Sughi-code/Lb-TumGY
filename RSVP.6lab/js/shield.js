/* Great books — copy protection shield (no visible text can be simply selected and copied).
 * Layers: CSS user-select lock, selectioncopy/copy/cut blocking, contextmenu + drag block,
 * Ctrl/Cmd shortcuts (C/X/A/U/S/P) blocking outside inputs, live clipboard scrubbing,
 * auto-clearing of any selection made before the block fires. */
(function () {
  'use strict';

  var MSG = 'Copying is disabled — please read on the site.';

  /* Robust editable-field detection: tagName + type + isContentEditable
     (works both in browsers and in test mocks). */
  function isEditable(el) {
    if (!el || el.nodeType !== 1) return false;
    if (el.isContentEditable === true) return true;
    var t = (el.tagName || '').toUpperCase();
    if (t === 'TEXTAREA' || t === 'SELECT') return true;
    if (t === 'INPUT') {
      var ty = (el.type || 'text').toLowerCase();
      return ['text', 'search', 'url', 'tel', 'email', 'password', 'number',
              'date', 'datetime-local', 'month', 'week', 'time'].indexOf(ty) >= 0;
    }
    return false;
  }

  /* A copy event only counts as legit if it originates inside an editable field */
  function inField(ev) {
    var t = ev.target;
    while (t && t !== document.body) {
      if (isEditable(t)) return true;
      t = t.parentNode;
    }
    // the selection may live entirely inside a field even if the event target is the body
    try {
      var sel = window.getSelection();
      if (sel && sel.rangeCount > 0) {
        var node = sel.getRangeAt(0).commonAncestorContainer;
        while (node && node.nodeType !== 1) node = node.parentNode;
        while (node && node !== document.body) {
          if (isEditable(node)) return true;
          node = node.parentNode;
        }
      }
    } catch (e) {}
    return false;
  }

  function killSelection() {
    try { window.getSelection().removeAllRanges(); } catch (e) {}
  }

  function block(ev) {
    if (inField(ev)) return;
    ev.preventDefault();
    killSelection();
    try { if (ev.clipboardData) ev.clipboardData.clearData(); } catch (e) {}
    try { if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText('').catch(function () {});
    } } catch (e) {}
  }

  ['copy', 'cut'].forEach(function (evt) {
    document.addEventListener(evt, block, true);
  });
  document.addEventListener('selectionchange', function () {
    var sel = window.getSelection();
    if (sel && !sel.isCollapsed && String(sel).length > 0) killSelection();
  });
  document.addEventListener('contextmenu', function (ev) {
    if (!isEditable(ev.target)) ev.preventDefault();
  }, true);
  document.addEventListener('dragstart', function (ev) {
    if (!isEditable(ev.target)) ev.preventDefault();
  }, true);

  document.addEventListener('keydown', function (ev) {
    if (isEditable(ev.target)) return;              // typing in a field stays free
    var mod = ev.ctrlKey || ev.metaKey;
    if (!mod) return;
    var k = (ev.key || '').toLowerCase();
    if (k === 'c' || k === 'x' || k === 'a' || k === 'u' || k === 's' || k === 'p') {
      ev.preventDefault();
      killSelection();
      try { ev.clipboardData && ev.clipboardData.clearData && ev.clipboardData.clearData(); } catch (e) {}
      alert(MSG);
    }
  }, true);

  /* Belt-and-braces: keep the system clipboard empty while browsing the site */
  setInterval(function () {
    try {
      if (window.getSelection && String(window.getSelection()).length > 0) killSelection();
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText('').catch(function () {});
      }
    } catch (e) {}
  }, 500);
})();
