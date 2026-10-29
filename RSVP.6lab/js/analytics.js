/* Great books — req. 10 (Google Analytics 4 & Yandex Metrika counters with their
 * forms pinned on the home page) + req. 11 (live line showing user activity on the
 * server). Works on static hosting: if LIVE_ENDPOINT (your own server counter) is
 * set, data comes from there; otherwise a local estimate for this tab is used. */
(function () {
  'use strict';

  var LS_GA = 'gb_ga4_id';        // storage key for the GA4 Measurement ID
  var LS_YM = 'gb_ym_id';         // storage key for the Metrika counter ID
  var LS_SEEN = 'gb_seen_days';   // array of visited days (for "Total")
  var TICK_MS = 20000;            // activity line refresh period
  var STALE_MS = 90000;           // a session counts as "live" if refreshed < 90 s ago
  /* Optional REST endpoint of your own server-side counter, e.g.
     https://rsvp.6lab.ru/api/visitors , returning {"now":N,"today":N,"total":N}.
     Leave it empty for standalone mode without a backend. */
  var LIVE_ENDPOINT = '';

  function $(id) { return document.getElementById(id); }

  function dayKey(d) {
    var m = d.getMonth() + 1, dd = d.getDate();
    return d.getFullYear() + '-' + (m < 10 ? '0' + m : m) + '-' + (dd < 10 ? '0' + dd : dd);
  }

  /* ---------- localStorage: degrade quietly in private mode ---------- */
  function lsGet(k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } }
  function lsSet(k, v) { try { window.localStorage.setItem(k, String(v)); } catch (e) {} }

  /* ============ REQ. 10: CONNECTING THE COUNTERS ==================== */
  var gaOn = false, ymOn = false;

  function loadGtag(id) {
    if (gaOn || !id) return;
    gaOn = true;
    window.dataLayer = window.dataLayer || [];
    if (typeof window.gtag !== 'function') {
      window.gtag = function () { window.dataLayer.push(arguments); };
    }
    window.gtag('js', new Date());
    window.gtag('config', id);
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
    document.head.appendChild(s);
  }

  function loadMetrika(id) {
    if (ymOn || !id || !/^\d+$/.test(id)) return;
    ymOn = true;
    (function (m, e, t, r, i, k, a) {
      m[i] = m[i] || function () { (m[i].a = m[i].a || []).push(arguments); };
      m[i].l = 1 * new Date();
      k = e.createElement(t); a = e.getElementsByTagName(t)[0];
      k.async = 1; k.src = r; a.parentNode.insertBefore(k, a);
    })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js', 'ym');
    window.ym(id, 'init', { clickmap: true, trackLinks: true, accurateTrackBounce: true });
    var n = document.createElement('noscript');
    n.innerHTML = '<div><img src="https://mc.yandex.ru/watch/' + encodeURIComponent(id) +
                  '" style="position:absolute;left:-9999px;" alt=""></div>';
    document.body.appendChild(n);
  }

  /* =============== REQ. 11: LIVE ACTIVITY LINE ====================== */
  function hbName() { return 'gbhb_' + dayKey(new Date()); }

  /* one tab = one session id (sessionStorage survives reloads) */
  function tabId() {
    var k = 'gb_tab_id', v = null;
    try {
      v = window.sessionStorage.getItem(k);
      if (!v) {
        v = 't' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
        window.sessionStorage.setItem(k, v);
      }
    } catch (e) { v = 't' + Math.random().toString(36).slice(2, 10); }
    return v;
  }

  function heartbeat() {
    try {
      var name = hbName(), now = Date.now();
      var raw = lsGet(name);
      var map = raw ? JSON.parse(raw) : {};
      map[tabId()] = now;
      lsSet(name, JSON.stringify(map));
    } catch (e) {}
  }

  function countLocalNow() {
    try {
      var raw = lsGet(hbName());
      if (!raw) return 1;
      var map = JSON.parse(raw), now = Date.now(), c = 0;
      for (var k in map) {
        if (Object.prototype.hasOwnProperty.call(map, k) && now - map[k] < STALE_MS) c++;
      }
      return Math.max(c, 1);
    } catch (e) { return 1; }
  }

  function readSeen() {
    try {
      var raw = lsGet(LS_SEEN);
      var arr = raw ? JSON.parse(raw) : [];
      if (!Array.isArray(arr)) arr = [];
      return arr;
    } catch (e) { return []; }
  }

  function countsLocal() {
    var today = dayKey(new Date());
    var seen = readSeen();
    if (seen.indexOf(today) < 0) { seen.push(today); lsSet(LS_SEEN, JSON.stringify(seen)); }
    var uniqueToday = 0;
    try {
      var map = JSON.parse(lsGet(hbName()) || '{}');
      uniqueToday = Object.keys(map).length;
    } catch (e) {}
    return { now: countLocalNow(), today: Math.max(uniqueToday, 1), total: seen.length };
  }

  function render(c) {
    var elNow = $('liveNow'), elToday = $('liveToday'), elTotal = $('liveTotal');
    if (elNow) elNow.textContent = c.now;
    if (elToday) elToday.textContent = c.today;
    if (elTotal) elTotal.textContent = c.total;
  }

  function tick() {
    heartbeat();
    var local = countsLocal();
    if (!LIVE_ENDPOINT) { render(local); return; }
    try {
      var ctl = ('AbortController' in window) ? new AbortController() : null;
      var to = ctl ? setTimeout(function () { ctl.abort(); }, 5000) : null;
      fetch(LIVE_ENDPOINT, { cache: 'no-store', signal: ctl ? ctl.signal : undefined })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (to) clearTimeout(to);
          render({
            now: (typeof j.now === 'number') ? j.now : local.now,
            today: (typeof j.today === 'number') ? j.today : local.today,
            total: (typeof j.total === 'number') ? j.total : local.total
          });
        })
        .catch(function () { if (to) clearTimeout(to); render(local); });
    } catch (e) { render(local); }
  }

  /* =================== HOME PAGE: COUNTER FORMS ===================== */
  function setStatus(id, text) {
    var el = $(id);
    if (el) el.textContent = text;
  }

  function bindForm(inputId, btnId, statusId, validate, enable, onLabel) {
    var input = $(inputId), btn = $(btnId);
    if (!input || !btn) return;                       // the forms exist only on the home page
    var key = input.dataset.lskey || '';
    var saved = lsGet(key);
    if (saved) { input.value = saved; enable(saved); setStatus(statusId, onLabel + ': ' + saved); }

    function apply() {
      var v = (input.value || '').trim();
      if (!validate(v)) { setStatus(statusId, 'Invalid format — check the identifier.'); return; }
      lsSet(key, v);
      enable(v);
      setStatus(statusId, onLabel + ': ' + v);
    }
    btn.addEventListener('click', apply);
    input.addEventListener('keydown', function (ev) {
      if ((ev.key || '') === 'Enter') { ev.preventDefault(); apply(); }
    });
  }

  function initForms() {
    var gaIn = $('ga4Id'), ymIn = $('ymId');
    if (gaIn) gaIn.dataset.lskey = LS_GA;
    if (ymIn) ymIn.dataset.lskey = LS_YM;

    bindForm('ga4Id', 'ga4Apply', 'ga4Status',
      function (v) { return /^G-[A-Z0-9]{6,12}$/.test(v); },
      loadGtag, 'Google Analytics enabled');

    bindForm('ymId', 'ymApply', 'ymStatus',
      function (v) { return /^\d{4,12}$/.test(v); },
      loadMetrika, 'Yandex Metrika enabled');
  }

  /* ========================= START ================================= */
  function start() {
    initForms();
    if ($('liveCounter')) {
      tick();
      setInterval(tick, TICK_MS);
      document.addEventListener('visibilitychange', function () {
        if (!document.hidden) tick();
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
