/* Great books — copy protection shield (no visible text can be simply selected and copied).
 * Layers: CSS user-select lock, selectioncopy/copy/cut blocking, contextmenu + drag block,
 * Ctrl/Cmd shortcuts (C/X/A/U/S/P) blocking outside inputs, live clipboard scrubbing,
 * auto-clearing of any selection made before the block fires. */
(function () {
  'use strict';

  var MSG = 'Copying is disabled — please read on the site.';

  function allowNode(node) {
    if (!node || node.nodeType !== 1) return false;
    var t = node.tagName;
    return t === 'INPUT' || t === 'TEXTAREA' || t === 'SELECT' ||
           node.isContentEditable === true;
  }

  /* A copy event only counts as legit if it originates inside an editable field */
  function inField(ev) {
    var t = ev.target;
    while (t && t !== document.body) {
      if (allowNode(t)) return true;
      t = t.parentNode;
    }
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
    if (!allowNode(ev.target)) ev.preventDefault();
  }, true);
  document.addEventListener('dragstart', function (ev) {
    if (!allowNode(ev.target)) ev.preventDefault();
  }, true);

  document.addEventListener('keydown', function (ev) {
    if (allowNode(ev.target)) return;              // typing in a field stays free
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
