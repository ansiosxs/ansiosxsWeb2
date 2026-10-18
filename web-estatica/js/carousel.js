/** Carrusel del hero de la portada. */
(function () {
  'use strict';

  var slides = Array.prototype.slice.call(document.querySelectorAll('.hero__slide'));
  var dots = Array.prototype.slice.call(document.querySelectorAll('.hero__dot'));
  if (slides.length < 2) return;

  var index = 0;
  var timer = null;
  var DELAY = 7000;

  function render() {
    slides.forEach(function (slide, i) {
      slide.classList.toggle('is-active', i === index);
      slide.setAttribute('aria-hidden', String(i !== index));
    });
    dots.forEach(function (dot, i) {
      dot.classList.toggle('is-active', i === index);
      dot.setAttribute('aria-current', i === index ? 'true' : 'false');
    });
  }

  function go(next) {
    index = (next + slides.length) % slides.length;
    render();
    restart();
  }

  function restart() {
    clearInterval(timer);
    timer = setInterval(function () { go(index + 1); }, DELAY);
  }

  document.querySelectorAll('[data-carousel]').forEach(function (el) {
    el.addEventListener('click', function () {
      go(el.dataset.carousel === 'next' ? index + 1 : index - 1);
    });
  });

  dots.forEach(function (dot, i) {
    dot.addEventListener('click', function () { go(i); });
  });

  document.addEventListener('visibilitychange', function () {
    if (document.hidden) clearInterval(timer);
    else restart();
  });

  render();
  restart();
})();