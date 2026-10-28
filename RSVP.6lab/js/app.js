/* RSVP.6lab — рендер каталога и сквозная нумерация книг */
(function () {
  'use strict';

  var DATA = window.RSVP_BOOKS;
  if (!DATA || !DATA.length) return;

  /* URL-friendly slug для страницы книги: author-title */
  function slugify(s) {
    return s.toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')           // ударения/диакритика латиницы
      .replace(/[^a-z0-9\s-]/g, '')              // только a-z0-9 (безопасно для URL)
      .trim()
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-');
  }

  /* Экранирование текста перед вставкой в HTML (защита от XSS) */
  function esc(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  var catalog = document.getElementById('catalog');
  var num = 0;                 // сквозной номер книги в общем списке
  var html = '';

  DATA.forEach(function (group) {
    html += '<article class="era" id="' + esc(group.slugEra) + '">';
    html += '<h2 class="era-title">' + esc(group.era) + '</h2>';
    html += '<p class="era-sub">' + esc(group.en) + '</p>';
    html += '<ol class="book-list" start="' + (num + 1) + '">';
    group.books.forEach(function (b) {
      num += 1;
      var author = esc(b[0]);
      var title = esc(b[1]);
      var href = '/books/' + slugify(b[0]) + '-' + slugify(b[1]) + '.html';
      html += '<li class="book-item">';
      html += '<a class="book-link" href="' + href + '" title="Страница книги: ' + title + ' — ' + author + '">';
      html += '<span class="book-num" aria-label="Номер в общем списке: ' + num + '">№&nbsp;' + num + '</span>';
      html += '<span class="book-author">' + author + '</span>';
      html += '<span class="book-title">' + title + '</span>';
      html += '</a>';
      html += '</li>';
    });
    html += '</ol></article>';
  });

  catalog.innerHTML = html;

  /* Сводка в hero: общее число книг считается динамически */
  var note = document.getElementById('totalNote');
  if (note) {
    note.textContent = 'Всего в каноне: ' + num + ' книг · ' + DATA.length + ' эпох';
    note.hidden = false;
  }
})();
