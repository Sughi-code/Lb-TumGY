#!/usr/bin/env python3
"""Great books — static generator v2.

Reads canonical data from books.js and annotations (Annotation + Historical context)
from great_books_120_english.md, then writes one page per book into ./books/.

Header/breadcrumbs on book pages show beginner-friendly topics only:
Catalogue, Antiquity, Middle Ages, Enlightenment, 19th Century, 20th Century.
The era name is NOT shown next to the book title.

Run:  python3 build_books.py
"""
import re, html, os, sys, json, unicodedata

ROOT = os.path.dirname(os.path.abspath(__file__))
SRC = os.path.join(ROOT, 'books.js')
DOC = os.path.join(ROOT, 'great_books_120_english.md')
OUT = os.path.join(ROOT, 'books')

# --- parse books.js ----------------------------------------------------------
js = open(SRC, encoding='utf-8').read()
m = re.search(r'window\.GREAT_BOOKS\s*=\s*(\[.*?\n\]);', js, re.S)
if not m:
    sys.exit('books.js: window.GREAT_BOOKS array not found')
arr = m.group(1)
j = arr.replace('era:', '"era":').replace('slugEra:', '"slugEra":').replace('books:', '"books":')
data = json.loads(j)

def slugify(s: str) -> str:
    s = s.lower()
    s = ''.join(c for c in unicodedata.normalize('NFD', s) if not ('\u0300' <= c <= '\u036f'))
    s = re.sub(r"[^a-z0-9\s-]", '', s).strip()
    s = re.sub(r'\s+', '-', s)
    return re.sub('-+', '-', s)

def esc(s: str) -> str:
    return html.escape(s, quote=True)

# --- short display titles -----------------------------------------------------
# Long parenthetical clarifications are dropped so that consecutive book titles
# (prev/next pager links and H1) fit on a single line. The full title is kept
# in the SEO <title>, meta description and JSON-LD.
PAREN_RE = re.compile(r'\s*\((?:[^()]*)\)\s*$')

def short_title(title: str) -> str:
    return PAREN_RE.sub('', title).strip()

# --- parse the annotations file ----------------------------------------------
doc = open(DOC, encoding='utf-8').read()
entries = {}   # num -> (annotation, context)
pat = re.compile(
    r'^## №(\d+)\.\s*(.+?)\s*—\s*(.+?)\s*$\n'
    r'\*\*Annotation:\*\*\s*(.+?)$\n'
    r'\*\*Historical context:\*\*\s*(.+?)$',
    re.M)
for mm in pat.finditer(doc):
    n, author, title, ann, ctx = int(mm.group(1)), mm.group(2), mm.group(3), mm.group(4).strip(), mm.group(5).strip()
    entries[n] = (author, title, ann, ctx)

# --- flatten with running numbers --------------------------------------------
flat = []
n = 0
missing = []
for g in data:
    for b in g['books']:
        n += 1
        fname = f"{slugify(b[0])}-{slugify(b[1])}.html"
        if n not in entries:
            missing.append(n)
        flat.append((n, g['era'], g['slugEra'], b[0], b[1], b[2], fname))
TOTAL = n
if missing:
    sys.exit(f'Missing annotation entries for numbers: {missing}')

SITE = 'https://rsvp.6lab.ru'

# Russian SEO meta tags (title suffix / keywords) — the page content stays English
SEO_SITE_SUFFIX = ' | Книжный клуб Великих Книг'
SEC_ANN = 'Annotation'
SEC_CTX = 'Historical Context'

TPL = '''<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{title_tag}</title>
<meta name="description" content="{desc_meta}">
<meta name="keywords" content="великие книги, книжный клуб онлайн, западный канон, классическое образование, список книг сент джонс, университетская программа, трекер чтения, классическая литература, история философии, интеллектуальный клуб">
<meta name="robots" content="index, follow">
<meta name="author" content="Great books">
<link rel="canonical" href="{site}/books/{fname}">
<link rel="sitemap" type="application/xml" href="{site}/sitemap.xml">
<meta property="og:type" content="article">
<meta property="og:site_name" content="Great books">
<meta property="og:title" content="{og_title}">
<meta property="og:description" content="{desc_meta}">
<meta property="og:url" content="{site}/books/{fname}">
<meta property="og:image" content="{site}/img/og-cover.svg">
<meta property="og:locale" content="en_US">
<meta name="twitter:card" content="summary_large_image">
<!-- theme-color mirrors --bg from css/theme.css; keep in sync if the token changes -->
<meta name="theme-color" content="#1E1F22">
<link rel="icon" href="../favicon.svg" type="image/svg+xml">
<!-- Centralized styles: colors come ONLY from css/theme.css (one token file for all 120 pages) -->
<link rel="stylesheet" href="../css/theme.css?v=8">
<style>
body{{margin:0;background:var(--bg);color:var(--text);font-family:Inter,Roboto,"Segoe UI",system-ui,-apple-system,Arial,sans-serif;-webkit-user-select:none;user-select:none;-webkit-touch-callout:none}}
header,main,footer{{max-width:980px;margin:0 auto;padding:0 16px}}
input,textarea,select,[contenteditable="true"]{{-webkit-user-select:text;user-select:text}}
</style>
<script>/* copy protection: inline seed, full logic in shield.js */
(function(){{document.addEventListener('copy',function(e){{e.preventDefault();}},true);
document.addEventListener('cut',function(e){{e.preventDefault();}},true);
document.addEventListener('contextmenu',function(e){{e.preventDefault();}},true);}})();</script>
<link rel="preload" href="../css/style.css?v=8" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="../css/style.css?v=8"></noscript>
<script type="application/ld+json">
{ld_json}
</script>
</head>
<body>
<header class="site-header">
  <div class="header-inner">
    <div class="brand">
      <span class="logo" aria-hidden="true">📖</span>
      <span class="brand-name"><b>Great books</b></span>
    </div>
    <!-- Header navigation: identical to the home page header -->
    <nav class="top-nav" aria-label="Main navigation">
      <a href="../index.html">Catalogue</a>
      <a href="../index.html#antiquity">Antiquity</a>
      <a href="../index.html#middle-ages">Middle Ages</a>
      <a href="../index.html#enlightenment">Enlightenment</a>
      <a href="../index.html#nineteenth-century">19th Century</a>
      <a href="../index.html#twentieth-century">20th Century</a>
    </nav>
  </div>
</header>

<main>
  <article class="book-page">
    <h1>{h1}</h1>
    <p class="counter-note">№&nbsp;{num} in the general list of {total} great books</p>
    <section class="about">
      <h2>{sec_ann}</h2>
      <p>{annotation}</p>
    </section>
    <section class="about">
      <h2>{sec_ctx}</h2>
      <p>{context}</p>
    </section>
    <p class="pager">{prev}{next}</p>
  </article>
</main>

<footer class="site-footer">
  <p>© Great books · An interactive guide to the canon of classical liberal education</p>
  <p><a href="../index.html">Back to the full catalogue</a></p>
</footer>
<script src="../js/shield.js?v=8" defer></script>
</body>
</html>
'''

ERA_SHORT = {'antiquity': 'Antiquity', 'middle-ages': 'Middle Ages',
             'enlightenment': 'Enlightenment', 'nineteenth-century': '19th Century',
             'twentieth-century': '20th Century'}

os.makedirs(OUT, exist_ok=True)
made = 0
for i, (num, era_full, era_slug, author, title, desc, fname) in enumerate(flat):
    _, _, annotation, context = entries[num]
    # H1 uses the shortened display title so it always fits on one line
    st = short_title(title)
    h1 = f'{author} — {st}'
    # SEO tags keep the full canonical title (truncated safely by length rules)
    suffix = f' №{num}{SEO_SITE_SUFFIX}'

    def build_title_tag(tt):
        return f'{author}: {tt}' + suffix

    title_tag = build_title_tag(title)
    if len(html.unescape(title_tag)) > 70:            # Яндекс/Google: до ~70 символов
        title_tag = build_title_tag(PAREN_RE.sub('', title).strip())
    guard = 0
    while len(html.unescape(title_tag)) > 70 and guard < 400:   # обрезка по словам
        guard += 1
        core = title_tag[:len(title_tag) - len(suffix) - 1]
        cut = core.rfind(' ')                          # режем по пробелу — entity не порвётся
        core = (core[:cut] if cut > 0 else core).rstrip(' ,—-…')
        if not core:
            title_tag = f'{author}…' + suffix
            break
        title_tag = core + '…' + suffix
    desc_meta = f'{author}, {title}. {annotation}'
    if len(desc_meta) > 158:
        desc_meta = desc_meta[:156].rsplit(' ', 1)[0] + '…'
    prev_l = next_l = ''
    if i > 0:
        p = flat[i-1]
        prev_l = f'<a class="btn-home" href="{p[6]}" title="№{p[0]} {esc(p[3])}, “{esc(p[4])}”">← №{p[0]} {esc(p[3])}, “{esc(short_title(p[4]))}”</a>'
    if i < TOTAL - 1:
        nx = flat[i+1]
        next_l = f'<a class="btn-home" href="{nx[6]}" title="№{nx[0]} {esc(nx[3])}, “{esc(nx[4])}”">№{nx[0]} {esc(nx[3])}, “{esc(short_title(nx[4]))}” →</a>'
    ld = json.dumps({
        "@context": "https://schema.org", "@type": "Book",
        "name": title, "author": {"@type": "Person", "name": author},
        "url": f"{SITE}/books/{fname}",
        "description": annotation,
        "isPartOf": {"@type": "ItemList", "numberOfItems": TOTAL, "name": "Great books canon"},
        "position": num
    }, ensure_ascii=False)
    out = TPL.format(
        title_tag=esc(title_tag), desc_meta=esc(desc_meta), site=SITE, fname=fname,
        og_title=esc(f'{author}: {title}'), ld_json=ld,
        era_slug=era_slug, era_short=esc(ERA_SHORT.get(era_slug, era_slug)),
        sec_ann=SEC_ANN, sec_ctx=SEC_CTX,
        h1=esc(h1), num=num, total=TOTAL,
        annotation=esc(annotation), context=esc(context),
        prev=prev_l, next=next_l)
    open(os.path.join(OUT, fname), 'w', encoding='utf-8').write(out)
    made += 1

print(f'Generated {made} book pages in {OUT} (expected {TOTAL})')
