/* Great books — catalogue rendering with running book numbers (No. 1 … No. N) */
(function () {
  'use strict';

  var DATA = window.GREAT_BOOKS;
  if (!DATA || !DATA.length) return;

  /* URL-friendly slug for the book page: author-title */
  function slugify(s) {
    return s.toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')           // diacritics of the Latin script
      .replace(/[^a-z0-9\s-]/g, '')              // only a-z0-9 (URL-safe)
      .trim()
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-');
  }

  /* Escape text before inserting into HTML (XSS protection) */
  function esc(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  var catalog = document.getElementById('catalog');
  var num = 0;                 // running number of the book in the general list
  var html = '';

  DATA.forEach(function (group) {
    html += '<article class="era" id="' + esc(group.slugEra) + '">';
    html += '<h2 class="era-title">' + esc(group.era) + '</h2>';
    html += '<ol class="book-list" start="' + (num + 1) + '">';
    group.books.forEach(function (b) {
      num += 1;
      var author = esc(b[0]);
      var title = esc(b[1]);
      var href = 'books/' + slugify(b[0]) + '-' + slugify(b[1]) + '.html';
      html += '<li class="book-item">';
      html += '<a class="book-link" href="' + href + '" title="Book page: ' + title + ' — ' + author + '">';
      html += '<span class="book-num" aria-label="Number in the general list: ' + num + '">№&nbsp;' + num + '</span>';
      html += '<span class="book-author">' + author + '</span>';
      html += '<span class="book-title">' + title + '</span>';
      html += '</a>';
      html += '</li>';
    });
    html += '</ol></article>';
  });

  catalog.innerHTML = html;

  /* Hero summary: the total number of books is computed from the data, never hardcoded */
  var note = document.getElementById('totalNote');
  if (note) {
    note.textContent = 'In the canon: ' + num + ' books · ' + DATA.length + ' eras';
    note.hidden = false;
  }
})();
