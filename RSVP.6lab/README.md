# RSVP.6lab / Great books — интерактивный путеводитель по канону

Каталог/база знаний великих книг классического западного образования.
Главная страница (`index.html`) содержит ядро: header (бренд «RSVP.6lab / Great books»,
навигация, поиск), сквозную нумерацию книг №1…№120, ссылки на будущие страницы каждой книги.

## UI-стиль (Dark Mode Flat Design)
- Фон приложения `#1E1F22`, карточки `#2B2D31`, основной текст `#F2F3F5`, приглушённый `#949BA4`.
- Акцент — неоновый циан `#00BAC6` (заголовки секций, ссылки, CTA).
- Шрифты: Inter / Roboto / system-ui; скругления карточек 8–12px; тонкие обводки контейнеров.
- Header: тёмная плашка на всю ширину; слева ссылки навигации, справа строка поиска («Search all books...») с живым фильтром каталога.
- Каталог: одна книга = одна строка; эпохи оформлены как карточки.

## Структура
```
RSVP.6lab/
├── index.html        # ядро сайта (критический CSS инлайн, остальное — отложенно)
├── 404.html          # страница ошибки со ссылкой на главную (в том же dark-стиле)
├── books.js          # единый источник данных каталога (120 книг, 5 эпох)
├── js/app.js         # рендер каталога + нумерация №1…№120 + живой фильтр поиска
├── css/style.css     # основные стили (загружаются через preload)
├── favicon.svg       # лёгкая SVG-иконка (dark + cyan)
└── img/og-cover.svg  # Open Graph обложка (масштабируется без потерь)
```

## SEO / метатеги
- `<title>` — ≤80 символов; `<meta description>` — в диапазоне 150–160 (лимит Яндекса).
- Иерархия заголовков H1→H2 с прицелом на сниппет; `canonical`, OG/Twitter, JSON-LD.
- **Яндекс Вебмастер**: вставьте код верификации в корень сайта —
  либо файл `yandex_xxxxxxx.html` рядом с `index.html`,
  либо `<meta name="yandex-verification" content="…">` (место помечено комментарием в `<head>`).

## Производительность и безопасность
- Отложенная загрузка CSS неиспользуемых стилей (`rel=preload` + `noscript`).
- Все изображения — векторные SVG (легковесные, качественное масштабирование).
- Скрипты с `defer`; тексты экранируются перед вставкой в DOM (защита от XSS).
- Число книг вычисляется динамически из данных, а не хардкодится.

## Развёртывание (nginx)
```nginx
# отклик отдельно для www и без www (без склейки доменов — редирект 301)
server {
    listen 443 ssl;
    server_name rsvp.6lab.ru;
    return 301 https://www.rsvp.6lab.ru$request_uri;
}
server {
    listen 443 ssl;
    server_name www.rsvp.6lab.ru;
    root /var/www/RSVP.6lab;
    error_page 404 /404.html;          # кастомная 404 со ссылкой на главную
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options SAMEORIGIN;
    add_header Referrer-Policy strict-origin-when-cross-origin;
    gzip on; gzip_types text/css application/javascript image/svg+xml;  # сокращение статики
    location ~* \.(css|js)$ { add_header Cache-Control "public, max-age=31536000, immutable"; }
}
```

## Дальнейшие шаги
Для каждой книги будет создана отдельная страница `books/<author>-<title>.html`
(slug уже генерируется в `js/app.js`), где появятся описание, план чтения и метатеги страницы.
