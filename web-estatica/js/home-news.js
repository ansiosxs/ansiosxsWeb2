(function () {
  'use strict';

  var apiBase = document.querySelector('meta[name="api-base-url"]').content.replace(/\/+$/, '');
  if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    apiBase = window.location.protocol + '//' + window.location.hostname + ':8001/api';
  }

  var grid = document.getElementById('home-news-grid');
  var feedback = document.getElementById('home-news-feedback');

  function plainText(value) {
    var container = document.createElement('div');
    container.innerHTML = value || '';
    return (container.textContent || '').replace(/\s+/g, ' ').trim();
  }

  function formatDate(value) {
    if (!value) return '';
    return new Date(value).toLocaleDateString('es-CL', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  }

  function createCard(article) {
    var card = document.createElement('article');
    card.className = 'news-card';

    if (article.imagen_url) {
      var image = document.createElement('img');
      image.className = 'news-card__image';
      image.src = article.imagen_url;
      image.alt = article.titulo;
      image.loading = 'lazy';
      image.decoding = 'async';
      card.appendChild(image);
    }

    var body = document.createElement('div');
    body.className = 'news-card__body';

    var date = document.createElement('p');
    date.className = 'news-card__date';
    date.textContent = formatDate(article.publicada_en);

    var title = document.createElement('h3');
    title.className = 'news-card__title';
    title.textContent = article.titulo;

    var summary = document.createElement('p');
    summary.className = 'news-card__summary';
    var summaryText = plainText(article.resumen || article.contenido);
    summary.textContent = summaryText.slice(0, 180) + (summaryText.length > 180 ? '…' : '');

    var link = document.createElement('a');
    link.className = 'news-link';
    link.href = article.url || 'noticias.html?id=' + encodeURIComponent(article.id);
    link.textContent = 'Leer noticia';

    body.appendChild(date);
    body.appendChild(title);
    body.appendChild(summary);
    body.appendChild(link);
    card.appendChild(body);

    return card;
  }

  function loadNews() {
    feedback.hidden = false;
    feedback.setAttribute('aria-busy', 'true');
    feedback.textContent = 'Cargando noticias…';
    grid.replaceChildren();

    fetch(apiBase + '/noticias?page=1', { headers: { Accept: 'application/json' } })
      .then(function (response) {
        if (!response.ok) throw new Error('La solicitud de noticias falló (' + response.status + ').');
        return response.json();
      })
      .then(function (payload) {
        if (!Array.isArray(payload.data)) throw new Error('La respuesta de noticias tiene un formato inesperado.');

        feedback.setAttribute('aria-busy', 'false');
        if (payload.data.length === 0) {
          feedback.textContent = 'Todavía no hay noticias. Vuelve pronto para conocer nuestras novedades.';
          return;
        }

        feedback.hidden = true;
        payload.data.slice(0, 3).forEach(function (article) {
          grid.appendChild(createCard(article));
        });
      })
      .catch(function (error) {
        feedback.setAttribute('aria-busy', 'false');
        feedback.textContent = 'No pudimos cargar las noticias. Revisa tu conexión e inténtalo nuevamente.';
        if (window.console && console.error) console.error(error);
        var retry = document.createElement('button');
        retry.className = 'btn btn-lg btn-yellow mt-4';
        retry.type = 'button';
        retry.textContent = 'Reintentar';
        retry.addEventListener('click', loadNews);
        feedback.appendChild(document.createElement('br'));
        feedback.appendChild(retry);
      });
  }

  loadNews();
})();
