/**
 * Sprite de iconos SVG.
 * Se inyecta en el DOM para que los <use href="#i-*"> funcionen
 * incluso abriendo los archivos con file:// (sin servidor).
 */
(function () {
  var paths = {
    menu: '<line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/>',
    close: '<line x1="5" y1="5" x2="19" y2="19"/><line x1="19" y1="5" x2="5" y2="19"/>',
    'chevron-down': '<polyline points="6 9 12 15 18 9"/>',
    'chevron-left': '<polyline points="15 5 8 12 15 19"/>',
    'chevron-right': '<polyline points="9 5 16 12 9 19"/>',
    'arrow-right': '<line x1="4" y1="12" x2="19" y2="12"/><polyline points="13 6 19 12 13 18"/>',
    heart: '<path d="M12 20.3 4.6 13a4.6 4.6 0 0 1 6.5-6.5l.9.9.9-.9A4.6 4.6 0 1 1 19.4 13Z"/>',
    star: '<polygon points="12 3 14.7 9.1 21 9.8 16.3 14.1 17.6 20.3 12 17.2 6.4 20.3 7.7 14.1 3 9.8 9.3 9.1"/>',
    sparkles: '<path d="M12 4v4M12 16v4M4 12h4M16 12h4"/><path d="M7 7l2 2M15 15l2 2M17 7l-2 2M9 15l-2 2"/>',
    palette: '<path d="M12 3a9 9 0 1 0 0 18c1.1 0 1.6-.8 1.6-1.6 0-.5-.2-.9-.5-1.2-.3-.3-.5-.7-.5-1.2 0-.9.7-1.6 1.6-1.6H16a5 5 0 0 0 5-5c0-4-4-7.4-9-7.4Z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10.5" cy="7.5" r="1"/><circle cx="15" cy="8" r="1"/>',
    users: '<circle cx="9" cy="8" r="3"/><path d="M3 20v-1a5 5 0 0 1 5-5h2a5 5 0 0 1 5 5v1"/><path d="M16 5.2a3 3 0 0 1 0 5.6"/><path d="M18 14.3a5 5 0 0 1 3 4.7v1"/>',
    share: '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.6" y1="10.5" x2="15.4" y2="6.5"/><line x1="8.6" y1="13.5" x2="15.4" y2="17.5"/>',
    library: '<path d="M4 4h4v16H4zM10 4h4v16h-4z"/><path d="m16.5 4.8 3.9 15.4-3.8 1-3.9-15.4z"/>',
    truck: '<path d="M3 6h10v9H3z"/><path d="M13 9h4l3 3v3h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
    'book-heart': '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H19v14H6.5A2.5 2.5 0 0 0 4 19.5Z"/><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H19v4H6.5"/><path d="M8.6 10.8 7.4 9.7a1.3 1.3 0 0 1 2-1.7 1.3 1.3 0 0 1 2 1.7Z"/>',
    rocket: '<path d="M9 15c-1.5 1.5-2 5-2 5s3.5-.5 5-2a2.1 2.1 0 0 0-3-3Z"/><path d="M14 12c3-4 6.5-7 7-7 0 0-3 4-7 7l-3-3Z"/><path d="M9 11 6 8l3-3 3 1 2-3 3 1-1 3-3 2Z"/><path d="M11 15a6 6 0 0 1-4-4"/>',
    gift: '<rect x="3.5" y="8" width="17" height="12" rx="1"/><line x1="3.5" y1="12.5" x2="20.5" y2="12.5"/><line x1="12" y1="8" x2="12" y2="20"/><path d="M12 8S10.5 3 8 3a2.5 2.5 0 0 0 0 5Z"/><path d="M12 8s1.5-5 4-5a2.5 2.5 0 0 1 0 5Z"/>',
    copy: '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/>',
    'check-circle': '<circle cx="12" cy="12" r="9"/><polyline points="8.5 12.5 11 15 16 9.5"/>',
    send: '<path d="M21 3 10.5 13.5"/><path d="M21 3 14.5 21l-4-7.5L3 9.5Z"/>',
    mail: '<rect x="2.5" y="5" width="19" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/>',
    phone: '<path d="M6.5 3h3l1.5 4-2 1.5a12 12 0 0 0 5.5 5.5L16 12l4 1.5v3a2 2 0 0 1-2.2 2A17 17 0 0 1 3.5 5.2 2 2 0 0 1 5.5 3Z"/>',
    'map-pin': '<path d="M12 21s7-6.3 7-11a7 7 0 1 0-14 0c0 4.7 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
    calendar: '<rect x="3.5" y="5" width="17" height="16" rx="2"/><line x1="3.5" y1="10" x2="20.5" y2="10"/><line x1="8" y1="3" x2="8" y2="6"/><line x1="16" y1="3" x2="16" y2="6"/>',
    handshake: '<path d="m8 12 3-3 2 2 3-3 4 4-4 4-2-2-2 2-3-3"/><path d="M2 9l3-3 4 1"/><path d="M22 9l-3-3-3 1"/>',
    instagram: '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1"/>',
    youtube: '<rect x="2.5" y="5.5" width="19" height="13" rx="4"/><polygon points="10.5 9.5 15.5 12 10.5 14.5"/>',
    linkedin: '<path d="M6 9.5V20"/><circle cx="6" cy="5.5" r="1.6"/><path d="M11 20v-6a4 4 0 0 1 8 0v6"/><path d="M11 9.5V20"/>',
    facebook: '<circle cx="12" cy="12" r="9"/><path d="M14.6 20.5v-7h2.3l.4-2.6h-2.7V9.2c0-.8.2-1.3 1.4-1.3h1.4V5.6c-.2 0-1-.1-1.9-.1-1.9 0-3.2 1.2-3.2 3.4v1.9H9.7v2.6h2.6v7Z"/>'
  };

  var symbols = Object.keys(paths)
    .map(function (name) {
      return '<symbol id="i-' + name + '" viewBox="0 0 24 24">' + paths[name] + '</symbol>';
    })
    .join('');

  var sprite = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  sprite.setAttribute('aria-hidden', 'true');
  sprite.setAttribute('focusable', 'false');
  sprite.style.display = 'none';
  sprite.innerHTML = symbols;

  var injected = false;

  function inject() {
    if (injected || !document.body) return;
    injected = true;
    document.body.insertBefore(sprite, document.body.firstChild);
  }

  document.addEventListener('DOMContentLoaded', inject);
  inject();
})();