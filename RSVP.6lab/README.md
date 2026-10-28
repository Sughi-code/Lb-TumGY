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
- `build_books.py` — static generator: writes one page per book into `books/`
  (description taken from `books.js`, i.e. from the *great_books_120_english* source file).
- `books/*.html` — 120 generated book pages (title, meta description, canonical, OG,
  JSON-LD `Book` schema, breadcrumb, prev/next pager, era navigation).
- `css/style.css` — Dark Mode Flat Design tokens; critical CSS is inlined in each HTML.
- `404.html`, `favicon.svg`, `img/og-cover.svg` — error page and vector assets.

## UI style (Dark Mode Flat Design)
- App background `#1E1F22`; card surface `#2B2D31`; primary text `#F2F3F5`;
  muted text `#949BA4`; accent neon cyan `#00BAC6`.
- Fonts: Inter / Roboto / system-ui; card radius 8–12px; subtle container outlines.
- Header: full-width dark bar, brand + horizontal nav links; no search field.
- Catalogue: one book per line, cyan number pill shows the book's number in the list.

## Language
All user-facing text, metadata and code comments are in English.

## Regenerating the book pages
```bash
python3 build_books.py     # rewrites all 120 files in books/
```
Edit descriptions in `books.js` (third element of each array), then re-run the script.

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
