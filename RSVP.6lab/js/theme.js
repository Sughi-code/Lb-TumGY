/* Great books — CENTRALIZED STYLE MANAGEMENT editor (ADMIN-ONLY).
 *
 * This script is loaded exclusively by /admin.html. It is intentionally NOT
 * referenced from index.html or any public page anymore: regular visitors
 * see the site painted by the tokens, but cannot edit them from the homepage.
 *
 * The whole site is painted through CSS custom properties defined ONCE in
 * css/theme.css (:root{--bg, --surface, --text, --muted, --accent, ...}).
 * This editor demonstrates the consequence: by changing those variables on
 * the root element (<html>) from a single place, EVERY style that references
 * var(--token) — header, hero, 120 catalogue rows, cards, footer — updates
 * instantly, without editing any CSS file and without re-rendering HTML.
 *
 * Mechanisms used here are exactly the ones available to any centralized
 * controller (a CMS skin editor, an A/B test, a settings panel):
 *   document.documentElement.style.setProperty('--accent', '#FF7A45');
 *   document.documentElement.setAttribute('data-theme', 'light'); // preset skin
 * Preferences persist in localStorage, so the chosen look applies to every
 * page load of this browser.
 */
(function () {
  'use strict';

  /* Hard guard: build the panel only inside the admin page. Even if this
     file were accidentally included on a public page, nothing would render. */
  if (!/\/admin\.html$/.test(location.pathname) &&
      !document.getElementById('themeControls')) return;

  var LS_KEY = 'gb_theme_prefs';           // {vars:{...}, preset:'default'|'light'}

  /* The tokens exposed in the UI = the contract of css/theme.css */
  var TOKENS = [
    { v: '--bg',      label: 'Background' },
    { v: '--surface', label: 'Surface' },
    { v: '--text',    label: 'Text' },
    { v: '--muted',   label: 'Muted text' },
    { v: '--accent',  label: 'Accent' },
    { v: '--border',  label: 'Border' }
  ];

  function $(id) { return document.getElementById(id); }
  function root() { return document.documentElement; }

  function currentColor(varName) {
    var inline = root().style.getPropertyValue(varName).trim();
    if (inline) return inline;
    var computed = getComputedStyle(root()).getPropertyValue(varName).trim();
    return toHex(computed) || '#2B2D31';
  }

  /* normalize rgb()/rgba() strings coming from getComputedStyle to #rrggbb */
  function toHex(c) {
    if (/^#[0-9a-f]{6}$/i.test(c)) return c;
    var m = /rgba?\((\d+)[,\s]+(\d+)[,\s]+(\d+)/.exec(c);
    if (!m) return null;
    return '#' + [1, 2, 3].map(function (i) {
      return parseInt(m[i], 10).toString(16).padStart(2, '0');
    }).join('');
  }

  function applyVar(name, value) {
    root().style.setProperty(name, value);
    savePrefs();
  }

  /* ---------------- persistence ---------------- */
  function loadPrefs() {
    try { return JSON.parse(localStorage.getItem(LS_KEY) || '{}'); }
    catch (e) { return {}; }
  }
  function savePrefs() {
    try {
      var vars = {};
      TOKENS.forEach(function (t) {
        var v = root().style.getPropertyValue(t.v).trim();
        if (v) vars[t.v] = v;
      });
      localStorage.setItem(LS_KEY, JSON.stringify({
        vars: vars,
        preset: root().getAttribute('data-theme') || 'default'
      }));
    } catch (e) {}
  }

  function restorePrefs() {
    var p = loadPrefs();
    if (p.preset && p.preset !== 'default') root().setAttribute('data-theme', p.preset);
    if (p.vars) for (var k in p.vars) root().style.setProperty(k, p.vars[k]);
  }

  /* ---------------- build the control panel ---------------- */
  function build() {
    var host = $('themeControls');
    if (!host) return;                       // section exists only on the home page
    restorePrefs();

    var grid = document.createElement('div');
    grid.className = 'theme-grid';
    TOKENS.forEach(function (t) {
      var wrap = document.createElement('label');
      wrap.className = 'theme-swatch';
      wrap.htmlFor = 'theme_' + t.v.replace(/[^a-z]/g, '');

      var inp = document.createElement('input');
      inp.type = 'color';
      inp.id = 'theme_' + t.v.replace(/[^a-z]/g, '');
      inp.value = currentColor(t.v);
      inp.addEventListener('input', function () { applyVar(t.v, inp.value); });

      var span = document.createElement('span');
      span.textContent = t.label;

      wrap.appendChild(span);
      wrap.appendChild(inp);
      grid.appendChild(wrap);
    });
    host.appendChild(grid);

    /* preset skins: one attribute on <html> switches the whole palette,
       because theme.css defines [data-theme="light"] centrally */
    var row = document.createElement('p');
    row.className = 'theme-presets';

    function mkBtn(label, preset) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'theme-btn';
      b.textContent = label;
      b.addEventListener('click', function () {
        if (preset === 'default') root().removeAttribute('data-theme');
        else root().setAttribute('data-theme', preset);
        /* preset switch replaces the token values: clear manual overrides so
           the preset wins, then sync the color pickers with the new palette */
        TOKENS.forEach(function (t) { root().style.removeProperty(t.v); });
        grid.querySelectorAll('input[type=color]').forEach(function (el, i) {
          el.value = currentColor(TOKENS[i].v);
        });
        savePrefs();
      });
      return b;
    }
    row.appendChild(mkBtn('Dark (default)', 'default'));
    row.appendChild(mkBtn('Light', 'light'));

    var reset = document.createElement('button');
    reset.type = 'button';
    reset.className = 'theme-btn theme-btn-reset';
    reset.textContent = 'Reset all';
    reset.addEventListener('click', function () {
      root().removeAttribute('data-theme');
      TOKENS.forEach(function (t) { root().style.removeProperty(t.v); });
      grid.querySelectorAll('input[type=color]').forEach(function (el, i) {
        el.value = currentColor(TOKENS[i].v);
      });
      savePrefs();
    });
    row.appendChild(reset);
    host.appendChild(row);

    /* show the exact central call being performed — for the demo/defense */
    var out = $('themeCode');
    function updateCode() {
      if (!out) return;
      var lines = [];
      TOKENS.forEach(function (t) {
        var v = root().style.getPropertyValue(t.v).trim();
        if (v) lines.push("document.documentElement.style.setProperty('" + t.v + "', '" + v + "');");
      });
      var preset = root().getAttribute('data-theme');
      if (preset) lines.unshift("document.documentElement.setAttribute('data-theme', '" + preset + "'); // skin from css/theme.css");
      out.textContent = lines.length ? lines.join('\n') :
        '/* No overrides: every page uses the defaults declared once in css/theme.css */';
    }
    host.addEventListener('input', updateCode);
    host.addEventListener('click', updateCode);
    updateCode();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', build);
  } else {
    build();
  }
})();
