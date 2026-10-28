#!/usr/bin/env python3
"""Great books — static generator.
Reads the canonical data from books.js and writes one page per book into ./books/.
Run:  python3 build_books.py
"""
import re, html, os, sys

ROOT = os.path.dirname(os.path.abspath(__file__))
SRC = os.path.join(ROOT, 'books.js')
OUT = os.path.join(ROOT, 'books')

# --- parse books.js (window.GREAT_BOOKS = [ ... ];) -------------------------
js = open(SRC, encoding='utf-8').read()
m = re.search(r'window\.GREAT_BOOKS\s*=\s*(\[.*?\n\]);', js, re.S)
if not m:
    sys.exit('books.js: window.GREAT_BOOKS array not found')
arr = m.group(1)

# Convert the JS object literal into JSON: quote bare keys era:/slugEra:/books:
j = arr.replace('era:', '"era":').replace('slugEra:', '"slugEra":').replace('books:', '"books":')
j = re.sub(r'\},\s*{', '},{', j)
import json
data = json.loads(j)

def slugify(s: str) -> str:
    s = s.lower()
    s = ''.join(c for c in __import__('unicodedata').normalize('NFD', s)
                if not ('\u0300' <= c <= '\u036f'))
    s = re.sub(r"[^a-z0-9\s-]", '', s).strip()
    s = re.sub(r'\s+', '-', s)
    return re.sub('-+', '-', s)

def esc(s: str) -> str:
    return html.escape(s, quote=True)

SITE = 'https://rsvp.6lab.ru'

# --- flatten with running numbers -------------------------------------------
flat = []          # (num, era_title, era_slug, author, title, desc, href)
n = 0
for g in data:
    era = re.sub(r'^\S+\s*', '', g['era'])   # strip leading emoji for meta text
    for b in g['books']:
        n += 1
        fname = f"{slugify(b[0])}-{slugify(b[1])}.html"
        flat.append((n, g['era'], era, g['slugEra'], b[0], b[1], b[2], fname))

TOTAL = n
os.makedirs(OUT, exist_ok=True)

TPL = '''<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{title_tag}</title>
<meta name="description" content="{desc_meta}">
<meta name="robots" content="index, follow">
<meta name="author" content="Great books">
<link rel="canonical" href="{site}/books/{fname}">
<meta property="og:type" content="article">
<meta property="og:site_name" content="Great books">
<meta property="og:title" content="{og_title}">
<meta property="og:description" content="{desc_meta}">
<meta property="og:url" content="{site}/books/{fname}">
<meta property="og:image" content="{site}/img/og-cover.svg">
<meta property="og:locale" content="en_US">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#1E1F22">
<link rel="icon" href="../favicon.svg" type="image/svg+xml">
<style>
body{{margin:0;background:#1E1F22;color:#F2F3F5;font-family:Inter,Roboto,"Segoe UI",system-ui,-apple-system,Arial,sans-serif}}
header,main,footer{{max-width:980px;margin:0 auto;padding:0 16px}}
</style>
<link rel="preload" href="../css/style.css?v=3" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="../css/style.css?v=3"></noscript>
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
    <nav class="top-nav" aria-label="Main navigation">
      <a href="../#catalog">Catalogue</a>
      <a href="../#{era_slug}">{era_short}</a>
    </nav>
  </div>
</header>

<main>
  <article class="book-page">
    <p class="crumbs"><a href="../">Home</a> · <a href="../#catalog">Catalogue</a> · <a href="../#{era_slug}">{era_short}</a></p>
    <h1>{h1}</h1>
    <p class="counter-note">№&nbsp;{num} in the general list of {total} great books</p>
    <section class="about">
      <h2>About this book</h2>
      <p>{desc}</p>
    </section>
    <p class="pager">{prev}{next}</p>
  </article>
</main>

<footer class="site-footer">
  <p>© Great books · An interactive guide to the canon of classical liberal education</p>
  <p><a href="../">Back to the full catalogue</a></p>
</footer>
</body>
</html>
'''

ERA_SHORT = {'antiquity': 'Antiquity', 'middle-ages': 'Middle Ages',
             'enlightenment': 'Enlightenment', 'nineteenth-century': '19th Century',
             'twentieth-century': '20th Century'}

made = 0
for i, (num, era_full, era, era_slug, author, title, desc, fname) in enumerate(flat):
    h1 = f'{author} — {title}'
    title_tag = f'{author}: {title} | Great Books №{num}'[:78]
    desc_meta = f'{author}, {title}. {desc}'
    if len(desc_meta) > 158:
        cut = desc_meta[:156].rsplit(' ', 1)[0]
        desc_meta = cut + '…'
    desc_meta = esc(desc_meta)
    prev_l = next_l = ''
    if i > 0:
        p = flat[i-1]
        prev_l = f'<a class="btn-home" href="{p[7]}">← №{p[0]} {esc(p[4])}, “{esc(p[5])}”</a>'
    if i < TOTAL - 1:
        nx = flat[i+1]
        next_l = f'<a class="btn-home" href="{nx[7]}">№{nx[0]} {esc(nx[4])}, “{esc(nx[5])}” →</a>'
    ld = json.dumps({
        "@context": "https://schema.org", "@type": "Book",
        "name": title, "author": {"@type": "Person", "name": author},
        "url": f"{SITE}/books/{fname}",
        "description": html.unescape(desc_meta),
        "isPartOf": {"@type": "ItemList", "numberOfItems": TOTAL, "name": "Great books canon"},
        "position": num
    }, ensure_ascii=False)
    out = TPL.format(
        title_tag=esc(title_tag), desc_meta=desc_meta, site=SITE, fname=fname,
        og_title=esc(f'{author}: {title}'), ld_json=ld,
        era_slug=era_slug, era_short=esc(ERA_SHORT.get(era_slug, era)),
        h1=esc(h1), num=num, total=TOTAL, desc=esc(desc),
        prev=prev_l, next=next_l)
    open(os.path.join(OUT, fname), 'w', encoding='utf-8').write(out)
    made += 1

print(f'Generated {made} book pages in {OUT} (expected {TOTAL})')
