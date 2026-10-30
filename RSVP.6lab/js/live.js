/* Great books — live activity line on the home page (req.: show how many users
 * are working on the server in real time).
 *
 * HOW IT WORKS
 * 1) Preferred mode — a true SERVER-SIDE counter. Every open tab sends an
 *    XHR "heartbeat" to api/visitors.php (same origin, so nginx serves it to
 *    PHP-FPM). The backend keeps one record per visitor tab in APCu and
 *    returns {"now":N,"today":N,"total":N}:
 *      now   = clients whose heartbeat arrived < STALE_MS ago (real-time);
 *      today = unique visitors since midnight (server timezone);
 *      total = unique visitors since the counter was first installed.
 *    A shared-storage BroadcastChannel syncs that response across all tabs of
 *    this browser instantly, so every tab shows the same server truth.
 * 2) Fallback mode — static hosting without PHP. Tabs register heartbeats in
 *    localStorage (one session id per tab, refreshed every tick; entries older
 *    than STALE_MS are dropped), which counts live visitors of this browser.
 *    The line degrades quietly instead of showing dashes.
 * 3) Transport is XMLHttpRequest with synchronous fallback: fetch() can be
 *    blocked by browser extensions / strict CSP, while a same-origin XHR to
 *    our own endpoint works reliably everywhere PHP is enabled.
 */
(function () {
  'use strict';

  var LIVE_ENDPOINT = 'api/visitors.php'; // relative: works from any sub-path
  var TICK_MS  = 15000;                   // heartbeat + refresh period
  var STALE_MS = 45000;                   // fallback "live" window (~3 missed ticks)
  var LS_DAY   = 'gbhb_';                 // fallback key prefix + YYYY-MM-DD
  var LS_SEEN  = 'gb_seen_days';          // fallback "total days" list

  /* ---- tab-close detection: the server drops this id right away ----
     The "bye" request is sent with keepalive: fetch(..., {keepalive:true})
     and navigator.sendBeacon() both survive page unloads (the browser keeps
     the request alive after the document is gone), so closing a tab removes
     the visitor from the real-time pool almost instantly. A crash (no unload
     at all) is covered by the server-side stale window. */
  function byeUrl() {
    return LIVE_ENDPOINT + '?bye=1&id=' + encodeURIComponent(tabId());
  }
  var byeSent = false;
  function sendBye() {
    if (byeSent) return;
    byeSent = true;
    var done = false;
    try {
      if (window.fetch && typeof Request === 'function') {
        var rq = new Request(byeUrl(), {
          method: 'GET',
          keepalive: true,           // key flag: request outlives the document
          cache: 'no-store',
          credentials: 'same-origin'
        });
        window.fetch(rq);
        done = true;
      }
    } catch (e) {}
    if (!done) {
      try {
        if (navigator.sendBeacon) navigator.sendBeacon(byeUrl(), '');
      } catch (e) {}
    }
  }
  window.addEventListener('pagehide', sendBye);
  window.addEventListener('beforeunload', sendBye);

  function $(id) { return document.getElementById(id); }

  function dayKey(d) {
    var m = d.getMonth() + 1, dd = d.getDate();
    return d.getFullYear() + '-' + (m < 10 ? '0' + m : m) + '-' + (dd < 10 ? '0' + dd : dd);
  }

  /* ---------- localStorage: degrade quietly in private mode ---------- */
  function lsGet(k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } }
  function lsSet(k, v) { try { window.localStorage.setItem(k, String(v)); } catch (e) {} }

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

  /* ================= SHARED SYNC BETWEEN TABS (BroadcastChannel) ========== */
  var bc = null;
  if ('BroadcastChannel' in window) {
    try { bc = new BroadcastChannel('gb_live'); } catch (e) { bc = null; }
  }
  if (bc) {
    bc.onmessage = function (ev) {
      var d = ev && ev.data;
      if (d && typeof d.now === 'number') render(d);
    };
  }

  function render(c) {
    var elNow = $('liveNow'), elToday = $('liveToday'), elTotal = $('liveTotal');
    if (elNow)   elNow.textContent   = c.now;
    if (elToday) elToday.textContent = c.today;
    if (elTotal) elTotal.textContent = c.total;
  }

  /* ==================== MODE 1: SERVER-SIDE HEARTBEAT (XHR) ================ */
  var serverOk = false;      // at least one successful reply during this visit
  var lastServer = null;     // last server payload, reused between ticks
  var inflight = false;      // do not stack requests on slow networks

  function sendHeartbeatXHR(cb) {
    var url = LIVE_ENDPOINT + '?id=' + encodeURIComponent(tabId()) +
              '&t=' + Date.now() + '&_=' + Math.random(); // no-cache
    var x = new XMLHttpRequest();
    x.open('GET', url, true);
    x.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    x.timeout = 8000;
    x.onload = function () {
      if (x.status >= 200 && x.status < 300) {
        try {
          var j = JSON.parse(x.responseText);
          if (j && typeof j.now === 'number') { cb(j); return; }
        } catch (e) {}
      }
      cb(null);
    };
    x.onerror = x.ontimeout = x.onabort = function () { cb(null); };
    x.send();
  }

  /* Only used if asynchronous XHR is unavailable (rare): a blocking call is
     still more reliable here than nothing, and runs outside DOMContentLoaded. */
  function sendHeartbeatSynchronous() {
    try {
      var url = LIVE_ENDPOINT + '?id=' + encodeURIComponent(tabId()) +
                '&t=' + Date.now() + '&_=' + Math.random();
      var x = new XMLHttpRequest();
      x.open('GET', url, false);
      x.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      x.send();
      if (x.status >= 200 && x.status < 300) {
        var j = JSON.parse(x.responseText);
        if (j && typeof j.now === 'number') return j;
      }
    } catch (e) {}
    return null;
  }

  /* ============ MODE 2: LOCAL FALLBACK (static hosting, no PHP) =========== */
  function hbName() { return LS_DAY + dayKey(new Date()); }

  function fallbackHeartbeat() {
    try {
      var name = hbName(), now = Date.now();
      var map = JSON.parse(lsGet(name) || '{}');
      map[tabId()] = now;
      lsSet(name, JSON.stringify(map));
    } catch (e) {}
  }

  function fallbackCounts() {
    var today = dayKey(new Date());
    var seen = [];
    try {
      seen = JSON.parse(lsGet(LS_SEEN) || '[]');
      if (!Array.isArray(seen)) seen = [];
    } catch (e) { seen = []; }
    if (seen.indexOf(today) < 0) { seen.push(today); lsSet(LS_SEEN, JSON.stringify(seen)); }

    var map = {}, now = Date.now(), live = 0;
    try { map = JSON.parse(lsGet(hbName()) || '{}'); } catch (e) { map = {}; }
    for (var k in map) {
      if (Object.prototype.hasOwnProperty.call(map, k) && now - map[k] < STALE_MS) live++;
    }
    return { now: Math.max(live, 1), today: Math.max(Object.keys(map).length, 1), total: Math.max(seen.length, 1) };
  }

  /* ============================== TICK LOOP =============================== */
  function applyResult(j) {
    if (j && typeof j.now === 'number') {   // a real payload with the numbers
      serverOk = true;
      lastServer = j;
      render(j);
      if (bc) { try { bc.postMessage(j); } catch (e) {} }
    } else if (lastServer) {
      render(lastServer);            // keep the last known server truth
    } else if (serverOk) {
      /* endpoint is alive but this reply carried no numbers (e.g. "bye" ack):
         leave the line as it is                                      */
    } else {
      fallbackHeartbeat();           // PHP not reachable at all -> local estimate
      render(fallbackCounts());
    }
  }

  function tick() {
    if (inflight) return;
    inflight = true;
    if (typeof XMLHttpRequest === 'undefined') {
      applyResult(sendHeartbeatSynchronous());
      inflight = false;
      return;
    }
    try {
      sendHeartbeatXHR(function (j) {
        inflight = false;
        applyResult(j);
      });
    } catch (e) {
      inflight = false;
      applyResult(null);
    }
  }

  /* ========================= START ================================= */
  function start() {
    if (!$('liveCounter')) return;               // the line exists only on the home page

    /* First paint must not wait for the network: show a value immediately,
       then confirm it with the real server number as soon as XHR replies. */
    fallbackHeartbeat();
    render(fallbackCounts());
    tick();                                      // async heartbeat -> server truth

    setInterval(tick, TICK_MS);
    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) tick();              // instant refresh when the tab returns
    });
    window.addEventListener('focus', tick);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
