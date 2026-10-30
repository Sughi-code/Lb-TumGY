# Great books — an interactive guide to the Western canon

Catalogue and knowledge base of the great books that form the core of a classical
Western education. The folder is named `RSVP.6lab` (directory name only — this string
must never appear as a site title anywhere in the UI or metadata).

## What lives where
- `index.html` — the core page: header (brand **Great books**, era navigation), hero,
  catalogue with running numbers No. 1 … No. 120, one book per line.
  There is deliberately **no search bar** and **no big CTA button**.
- `books.js` — the single source of truth: 5 eras × 120 books, each entry is
  `[author, title, english description]`.
- `js/app.js` — renders the catalogue from `books.js`, computes numbering dynamically,
  builds relative links `books/<author>-<title>.html`.
- `great_books_120_english.md` — the annotation source for every book:
  **Annotation** + **Historical context**, one entry per canon number №1–№120.
- `build_books.py` — static generator: merges `books.js` with
  `great_books_120_english.md` and writes one page per book into `books/`.
- `books/*.html` — 120 generated book pages (title, meta description, canonical, OG,
  JSON-LD `Book` schema, Annotation + Historical Context sections, prev/next pager).
  Header/breadcrumbs show beginner topics only: Catalogue · Antiquity · Middle Ages ·
  Enlightenment · 19th Century · 20th Century; long era labels are not shown to users.
- `css/style.css` — Dark Mode Flat Design rules; **contains no hard-coded colors**,
  everything goes through tokens. Critical CSS is inlined in each HTML via `var(--token)`.
- `css/theme.css` — **centralized style management**: the single source of truth for all
  design tokens (`--bg`, `--surface`, `--text`, `--muted`, `--accent`, `--border`, `--radius`)
  plus preset skins (`[data-theme="light"]`). Loaded by every page before any other CSS.
- `js/theme.js` — centralized style-management EDITOR (admin-only). It is loaded
  exclusively by `/admin.html`, never by public pages; a runtime guard refuses to
  build the panel anywhere else.
- `admin.html` — **admin-only page** hosting the "Theme controls" panel. Not linked
  from the public site, absent from sitemap.xml, marked `noindex,nofollow`, listed
  under `Disallow:` in robots.txt, and gated by an access key (`greatbooks-admin`;
  for production replace with real server-side auth, e.g. HTTP Basic Auth/.htaccess).
- `404.html`, `favicon.svg`, `img/og-cover.svg` — error page and vector assets.

## Централизованное управление стилями (как этим пользоваться)
Весь сайт (главная, 120 страниц книг, 404) покрасится через CSS-переменные, объявленные
**один раз** в `css/theme.css`. В остальных файлах нет ни одного «жёсткого» цвета — только
`var(--bg)`, `var(--accent)` и т.д. Поэтому управлять стилями можно централизованно, тремя способами:

1. **Один файл — весь сайт.** Правим значение в `css/theme.css`
   (например, `--accent:#00BAC6;` → `--accent:#FF7A45;`) — и на всех 122 страницах
   сразу меняется акцентный цвет: ссылки, номера книг, кнопки, заголовки.
2. **Одна строка кода — мгновенная перекраска.** Из любого центрального места
   (панель настроек, A/B-тест, CMS):
   `document.documentElement.style.setProperty('--accent', '#FF7A45');`
3. **Готовые темы (пресеты).** В `theme.css` описан светлый скин `[data-theme="light"]`;
   достаточно `document.documentElement.setAttribute('data-theme','light')` —
   и вся палитра переключается разом.

**Как продемонстрировать на защите:** открыть **админ-страницу `/admin.html`** (она не
ссылается с публичной части сайта; ключ доступа для демо — `greatbooks-admin`) → секция
*«Theme controls»* → выбрать любой цвет или нажать Dark/Light: вся страница перекрашивается
мгновенно, а под панелью показывается ровно та центральная команда `setProperty(...)`,
которая в этот момент выполняется. Выбор сохраняется в localStorage и действует на всех
страницах сайта (открыть главную — видно результат). Публичная главная страница при этом
не содержит никаких элементов управления стилями — это требование безопасности/UX:
обычные посетители не могут менять оформление. Плюс доказательство из консоли:
`getComputedStyle(document.body).backgroundColor` берёт значение из `--bg`, а не из
правила `body{...}`.

## UI style (Dark Mode Flat Design)
- App background `#1E1F22`; card surface `#2B2D31`; primary text `#F2F3F5`;
  muted text `#949BA4`; accent neon cyan `#00BAC6`.
- Fonts: Inter / Roboto / system-ui; card radius 8–12px; subtle container outlines.
- Header: full-width dark bar, brand + horizontal nav links; no search field.
- Catalogue: one book per line, cyan number pill shows the book's number in the list.

## Responsive design (optimized for all displays, incl. mobile)
- `<meta name="viewport" content="width=device-width, initial-scale=1">` on all 122 pages.
- Fluid layout: centered container `max-width:980px`, flexible header/nav (`flex-wrap`),
  images/SVG scale via `img{max-width:100%;height:auto}` (crisp on Retina/High-DPI).
- Fluid typography with `clamp()` (h1, era titles, 404 code) — text scales with the screen.
- Breakpoints in `css/style.css`:
  * `min-width:641px–980px` (tablets): era cards switch to a 2-column CSS grid;
  * `max-width:640px` (phones): nav drops below the brand, tighter paddings, book rows wrap,
    touch targets get `min-height:44px` (Apple/Google accessibility guideline);
  * `max-width:900px + landscape`: reduced hero padding for landscape phones;
  * `prefers-reduced-motion`: transitions disabled.
- No horizontal scroll at 320px width; `-webkit-text-size-adjust:100%` prevents font inflation.

## Language
All user-facing text, metadata and code comments are in English.

## Regenerating the book pages
```bash
python3 build_books.py     # rewrites all 120 files in books/
```
Edit annotations in `great_books_120_english.md` (Annotation / Historical context per №),
edit catalogue data in `books.js`, then re-run the script.

## SEO / metadata
- `<title>` kept within 70–80 characters; `<meta description>` within 150–160 characters.
- H1 → H2 hierarchy aimed at snippets; `canonical`, OG/Twitter cards, JSON-LD
  (`WebSite` on the index, `Book` + `ItemList` position on every book page).
- Relative asset/link paths so the site works from any sub-path.
- **Yandex Webmaster**: put the verification file next to `index.html`, or add
  `<meta name="yandex-verification" content="…">` (placeholder marked in the `<head>`).

## Performance & security
- Non-blocking CSS via `rel=preload` + `<noscript>` fallback; scripts loaded with `defer`.
- All imagery is SVG (lightweight, scales without loss).
- Text is escaped before DOM insertion (XSS protection); counters come from data, not hardcode.

## Deployment (nginx)
```nginx
server {
    listen 443 ssl;
    server_name rsvp.6lab.ru;
    return 301 https://www.rsvp.6lab.ru$request_uri;
}
server {
    listen 443 ssl;
    server_name www.rsvp.6lab.ru;
    root /var/www/RSVP.6lab;
    error_page 404 /404.html;
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options SAMEORIGIN;
    add_header Referrer-Policy strict-origin-when-cross-origin;
    gzip on; gzip_types text/css application/javascript image/svg+xml;
    location ~* \.(css|js)$ { add_header Cache-Control "public, max-age=31536000, immutable"; }
}
```
