/**
 * Comportamiento compartido por todas las páginas:
 * navbar (estado al hacer scroll + menú móvil), animaciones al hacer scroll,
 * imágenes con carga diferida y fallback, toasts y copiar al portapapeles.
 */
(function () {
  'use strict';

  var EMAILJS = {
    serviceId: 'service_rjxlczm',
    templateId: 'template_7hwhs2p',
    publicKey: 'OausZN2zX4s5uO1v_'
  };

  /* ---------- Helpers ---------- */

  function icon(name, className) {
    return '<svg class="icon ' + (className || '') + '" aria-hidden="true"><use href="#i-' + name + '"></use></svg>';
  }

  function $(selector, scope) {
    return (scope || document).querySelector(selector);
  }

  function $$(selector, scope) {
    return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
  }

  /* ---------- Navbar ---------- */

  function initNavbar() {
    var nav = $('.nav');
    if (!nav) return;

    var isHome = document.body.dataset.page === 'home';
    var solid = !isHome;

    nav.classList.toggle('nav--home', isHome);
    nav.classList.toggle('nav--solid', solid);

    function update() {
      var scrolled = window.scrollY > 50;
      nav.classList.toggle('is-scrolled', scrolled || solid);
    }

    update();
    window.addEventListener('scroll', update, { passive: true });

    var toggle = $('.nav__toggle');
    var menu = $('.mobile-menu');

    if (toggle && menu) {
      toggle.addEventListener('click', function () {
        var open = menu.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', String(open));
        toggle.innerHTML = icon(open ? 'close' : 'menu', 'icon-lg');
      });

      $$('a', menu).forEach(function (link) {
        link.addEventListener('click', function () {
          menu.classList.remove('is-open');
          toggle.setAttribute('aria-expanded', 'false');
          toggle.innerHTML = icon('menu', 'icon-lg');
        });
      });
    }

    // El submenú de "Proyectos" se abre también con click en móvil/táctil
    var caret = $('.nav__item--dropdown > a');
    var dropdown = $('.dropdown');
    if (caret && dropdown) {
      caret.addEventListener('click', function (event) {
        if (window.matchMedia('(min-width: 768px)').matches) return;
        event.preventDefault();
        dropdown.classList.toggle('is-open');
      });
    }
  }

  /* ---------- Animaciones al hacer scroll ---------- */

  function initReveal() {
    var items = $$('.reveal');
    if (!items.length) return;

    if (!('IntersectionObserver' in window)) {
      items.forEach(function (el) { el.classList.add('is-visible'); });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target;
        var delay = parseFloat(el.dataset.delay || 0);
        setTimeout(function () { el.classList.add('is-visible'); }, delay * 1000);
        observer.unobserve(el);
      });
    }, { threshold: 0.15 });

    items.forEach(function (el) { observer.observe(el); });
  }

  /* ---------- Imágenes diferidas con skeleton ---------- */

  function initLazyImages() {
    $$('.media-lazy').forEach(function (wrapper) {
      var img = $('img', wrapper);
      if (!img) return;

      if (!('IntersectionObserver' in window)) {
        img.src = img.dataset.src;
        img.addEventListener('load', function () { img.classList.add('is-loaded'); });
        return;
      }

      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          img.src = img.dataset.src;
          observer.disconnect();
        });
      }, { rootMargin: '200px' });

      img.addEventListener('load', function () { img.classList.add('is-loaded'); });
      observer.observe(wrapper);
    });

    // Fallback: si una imagen falla, se oculta su contenedor
    $$('img[data-fallback]').forEach(function (img) {
      img.addEventListener('error', function () {
        var parent = img.parentElement;
        if (parent) parent.style.display = 'none';
      });
    });
  }

  /* ---------- Toast ---------- */

  function toast(title, description, variant) {
    var region = $('.toast-region');
    if (!region) {
      region = document.createElement('div');
      region.className = 'toast-region';
      region.setAttribute('role', 'status');
      region.setAttribute('aria-live', 'polite');
      document.body.appendChild(region);
    }

    var el = document.createElement('div');
    el.className = 'toast' + (variant === 'error' ? ' toast--error' : '');
    el.innerHTML = '<div><strong></strong><p></p></div>';
    $('strong', el).textContent = title;
    $('p', el).textContent = description || '';
    region.appendChild(el);

    setTimeout(function () {
      el.classList.add('is-hiding');
      setTimeout(function () { el.remove(); }, 300);
    }, 4000);
  }

  /* ---------- Copiar al portapapeles ---------- */

  function initCopyButtons() {
    $$('[data-copy]').forEach(function (button) {
      button.addEventListener('click', function () {
        var text = button.dataset.copy;
        var field = button.dataset.copyLabel || 'Dato';

        function fallback() {
          var area = document.createElement('textarea');
          area.value = text;
          area.setAttribute('readonly', '');
          area.style.position = 'fixed';
          area.style.opacity = '0';
          document.body.appendChild(area);
          area.select();
          try {
            document.execCommand('copy');
            toast('✅ ' + field + ' copiado', 'El texto se copió al portapapeles.');
          } catch (error) {
            toast('No se pudo copiar', 'Copia manualmente: ' + text, 'error');
          }
          area.remove();
        }

        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(text)
            .then(function () { toast('✅ ' + field + ' copiado', 'El texto se copió al portapapeles.'); })
            .catch(fallback);
        } else {
          fallback();
        }
      });
    });
  }

  /* ---------- Smooth scroll para enlaces con hash entre páginas ---------- */

  function initHashScroll() {
    var hash = window.location.hash;
    if (!hash) return;

    window.addEventListener('load', function () {
      var target = document.getElementById(hash.slice(1));
      if (target) setTimeout(function () { target.scrollIntoView({ behavior: 'smooth' }); }, 80);
    });
  }

  /* ---------- Año del footer ---------- */

  function initYear() {
    $$('[data-year]').forEach(function (el) {
      el.textContent = new Date().getFullYear();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initNavbar();
    initReveal();
    initLazyImages();
    initCopyButtons();
    initHashScroll();
    initYear();
  });

  window.Ansiosxs = { toast: toast, icon: icon, emailjsConfig: EMAILJS, $: $, $$: $$ };
})();