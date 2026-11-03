(function () {
  'use strict';

  var apiBase = document.querySelector('meta[name="api-base-url"]').content.replace(/\/+$/, '');
  if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    apiBase = window.location.protocol + '//' + window.location.hostname + ':8001/api';
  }

  var list = document.getElementById('public-links');
  var feedback = document.getElementById('links-feedback');

  function loadLinks() {
    feedback.textContent = 'Cargando enlaces…';
    feedback.hidden = false;
    feedback.setAttribute('aria-busy', 'true');
    list.replaceChildren();

    fetch(apiBase + '/enlaces', { headers: { Accept: 'application/json' } })
      .then(function (response) {
        if (!response.ok) throw new Error('La solicitud de enlaces falló (' + response.status + ').');
        return response.json();
      })
      .then(function (result) {
        var links = result.data;
        if (!Array.isArray(links)) throw new Error('La respuesta de enlaces tiene un formato inesperado.');

        feedback.setAttribute('aria-busy', 'false');
        if (links.length === 0) {
          feedback.textContent = 'Pronto encontrarás aquí nuestros enlaces.';
          return;
        }

        feedback.textContent = '';
        feedback.hidden = true;
        var sections = Object.create(null);
        links.forEach(function (item) {
          var sectionName = item.seccion || '';
          var section = sections[sectionName];
          if (!section) {
            section = document.createElement('section');
            section.className = 'link-hub__section';
            if (sectionName) {
              var heading = document.createElement('h2');
              heading.className = 'link-hub__section-title';
              heading.textContent = sectionName;
              section.appendChild(heading);
            }
            var sectionList = document.createElement('div');
            sectionList.className = 'link-hub__list';
            section.appendChild(sectionList);
            sections[sectionName] = section;
            list.appendChild(section);
          }

          var link = item.url ? document.createElement('a') : document.createElement('div');
          link.className = 'link-hub__item' + (item.url ? '' : ' link-hub__item--pending');
          if (item.url) {
            link.href = item.url;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
          } else {
            link.setAttribute('aria-disabled', 'true');
          }

          var title = document.createElement('span');
          title.className = 'link-hub__title';
          title.textContent = item.titulo;
          link.appendChild(title);

          if (item.descripcion) {
            var description = document.createElement('span');
            description.className = 'link-hub__description';
            description.textContent = item.descripcion;
            link.appendChild(description);
          }

          var arrow = document.createElement('span');
          arrow.className = 'link-hub__arrow';
          arrow.setAttribute('aria-hidden', 'true');
          arrow.textContent = item.url ? '↗' : 'Próximamente';
          if (!item.url) arrow.className = 'link-hub__pending-label';
          link.appendChild(arrow);
          section.querySelector('.link-hub__list').appendChild(link);
        });
      })
      .catch(function (error) {
        feedback.hidden = false;
        feedback.setAttribute('aria-busy', 'false');
        feedback.textContent = 'No pudimos cargar los enlaces. Revisa tu conexión e inténtalo nuevamente.';
        if (window.console && console.error) console.error(error);
        var retry = document.createElement('button');
        retry.className = 'link-hub__retry';
        retry.type = 'button';
        retry.textContent = 'Reintentar';
        retry.addEventListener('click', loadLinks);
        feedback.appendChild(document.createElement('br'));
        feedback.appendChild(retry);
      });
  }

  loadLinks();
})();
