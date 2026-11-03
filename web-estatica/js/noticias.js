(function () {
  'use strict';

  var apiBase = document.querySelector('meta[name="api-base-url"]').content.replace(/\/+$/, '');
  if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    apiBase = window.location.protocol + '//' + window.location.hostname + ':8001/api';
  }
  var listing = document.getElementById('news-listing');
  var detail = document.getElementById('news-article');
  var grid = document.getElementById('news-grid');
  var feedback = document.getElementById('news-feedback');
  var moreButton = document.getElementById('news-more');
  var shareButton = document.getElementById('news-share');
  var shareUrlInput = document.getElementById('news-share-url');
  var shareFeedback = document.getElementById('news-share-feedback');
  var page = 1;
  var loading = false;
  var articleId = new URLSearchParams(window.location.search).get('id');

  function getJson(url) {
    return fetch(url, { headers: { Accept: 'application/json' } }).then(function (response) {
      if (!response.ok) throw new Error('La solicitud de noticias falló (' + response.status + ').');
      return response.json();
    });
  }

  function formatDate(value) {
    if (!value) return '';
    return new Date(value).toLocaleDateString('es-CL', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  }

  function plainText(value) {
    var container = document.createElement('div');
    container.innerHTML = value || '';
    return (container.textContent || '').replace(/\s+/g, ' ').trim();
  }

  function setMeta(attribute, name, value) {
    var element = document.querySelector('meta[' + attribute + '="' + name + '"]');
    if (!element) {
      element = document.createElement('meta');
      element.setAttribute(attribute, name);
      document.head.appendChild(element);
    }
    element.setAttribute('content', value);
  }

  function setCanonical(url) {
    var canonical = document.querySelector('link[rel="canonical"]');
    if (!canonical) {
      canonical = document.createElement('link');
      canonical.rel = 'canonical';
      document.head.appendChild(canonical);
    }
    canonical.href = url;
  }

  function updateArticleMetadata(article) {
    var description = plainText(article.resumen || article.contenido).slice(0, 160);
    var title = article.titulo + ' | Ansiosxs – Nuevas Lecturas';
    var image = article.imagen_url || new URL('images/logocircular.png', window.location.href).href;
    var shareUrl = article.url || window.location.href;

    document.title = title;
    document.querySelector('meta[name="description"]').content = description;
    setMeta('property', 'og:type', 'article');
    setMeta('property', 'og:title', article.titulo);
    setMeta('property', 'og:description', description);
    setMeta('property', 'og:url', shareUrl);
    setMeta('property', 'og:image', image);
    setMeta('property', 'og:image:alt', article.titulo);
    setMeta('name', 'twitter:card', article.imagen_url ? 'summary_large_image' : 'summary');
    setMeta('name', 'twitter:title', article.titulo);
    setMeta('name', 'twitter:description', description);
    setMeta('name', 'twitter:image', image);
    if (article.publicada_en) {
      setMeta('property', 'article:published_time', article.publicada_en);
    }
    setCanonical(shareUrl);
  }

  function setError(target, error, retry) {
    target.textContent = 'No pudimos cargar las noticias. Revisa tu conexión e inténtalo nuevamente.';
    if (window.console && console.error) console.error(error);
    if (!retry) return;

    var button = document.createElement('button');
    button.className = 'btn btn-lg btn-yellow mt-4';
    button.type = 'button';
    button.textContent = 'Reintentar';
    button.addEventListener('click', retry);
    target.appendChild(document.createElement('br'));
    target.appendChild(button);
  }

  function showCopyFallback(url) {
    shareUrlInput.value = url;
    shareUrlInput.hidden = false;
    shareUrlInput.focus();
    shareUrlInput.select();
    shareFeedback.textContent = 'Copia el enlace seleccionado para compartir esta noticia.';
  }

  function copyShareUrl(url) {
    if (!navigator.clipboard || !window.isSecureContext) {
      showCopyFallback(url);
      return;
    }

    navigator.clipboard.writeText(url).then(function () {
      shareFeedback.textContent = 'Enlace copiado.';
    }).catch(function () {
      showCopyFallback(url);
    });
  }

  function shareArticle(article) {
    var url = article.url || window.location.href;
    var description = plainText(article.resumen || article.contenido).slice(0, 160);

    if (navigator.share) {
      navigator.share({
        title: article.titulo,
        text: description,
        url: url
      }).catch(function (error) {
        if (error.name !== 'AbortError') copyShareUrl(url);
      });
      return;
    }

    copyShareUrl(url);
  }

  function createCard(article) {
    var card = document.createElement('article');
    card.className = 'news-card' + (article.imagen_url ? ' news-card--has-image' : ' news-card--no-image');

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
    var eyebrow = document.createElement('p');
    eyebrow.className = 'news-card__eyebrow';
    eyebrow.textContent = 'Noticias de la comunidad';
    var date = document.createElement('p');
    date.className = 'news-card__date';
    date.textContent = formatDate(article.publicada_en);
    var title = document.createElement('h2');
    title.className = 'news-card__title';
    title.textContent = article.titulo;
    var summary = document.createElement('div');
    summary.className = 'news-card__summary';
    if (article.resumen) {
      summary.innerHTML = article.resumen;
    } else {
      var plainContent = document.createElement('div');
      plainContent.innerHTML = article.contenido;
      var text = plainContent.textContent || '';
      summary.textContent = text.slice(0, 220) + (text.length > 220 ? '…' : '');
    }
    var link = document.createElement('a');
    link.className = 'news-link';
    link.href = article.url || 'noticias.html?id=' + encodeURIComponent(article.id);
    link.textContent = 'Leer noticia →';

    body.appendChild(eyebrow);
    body.appendChild(date);
    body.appendChild(title);
    body.appendChild(summary);
    body.appendChild(link);
    card.appendChild(body);
    return card;
  }

  function loadPage() {
    if (loading) return;
    loading = true;
    moreButton.disabled = true;
    feedback.textContent = 'Cargando noticias…';

    getJson(apiBase + '/noticias?page=' + page).then(function (payload) {
      if (payload.data.length === 0 && page === 1) {
        feedback.textContent = 'Todavía no hay noticias. Vuelve pronto para conocer nuestras novedades.';
        moreButton.hidden = true;
        return;
      }

      payload.data.forEach(function (article) {
        grid.appendChild(createCard(article));
      });
      feedback.textContent = '';
      moreButton.hidden = !payload.links.next;
      page += 1;
    }).catch(function (error) {
      setError(feedback, error, loadPage);
    }).finally(function () {
      loading = false;
      moreButton.disabled = false;
    });
  }

  function loadArticle() {
    listing.hidden = true;
    detail.hidden = false;
    getJson(apiBase + '/noticias/' + encodeURIComponent(articleId)).then(function (payload) {
      var article = payload.data;
      updateArticleMetadata(article);
      var cover = document.getElementById('news-article-cover');
      document.getElementById('news-article-date').textContent = formatDate(article.publicada_en);
      document.getElementById('news-article-title').textContent = article.titulo;
      document.getElementById('news-article-content').innerHTML = article.contenido;
      var summary = document.getElementById('news-article-summary');
      if (article.resumen) {
        summary.innerHTML = article.resumen;
        summary.hidden = false;
      }
      shareButton.addEventListener('click', function () {
        shareArticle(article);
      });
      if (article.imagen_url) {
        var image = document.getElementById('news-article-image');
        image.src = article.imagen_url;
        image.alt = article.titulo;
        image.hidden = false;
        cover.classList.remove('news-detail__cover--no-image');
      } else {
        cover.classList.add('news-detail__cover--no-image');
      }
    }).catch(function (error) {
      setError(document.getElementById('news-article-content'), error);
    });
  }

  moreButton.addEventListener('click', loadPage);
  shareUrlInput.addEventListener('click', function () {
    shareUrlInput.select();
  });
  if (articleId) {
    loadArticle();
  } else {
    loadPage();
  }
})();
