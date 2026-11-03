<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $noticia->titulo }} | Ansiosxs – Nuevas Lecturas</title>
    <meta name="description" content="{{ $descripcion }}">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="{{ $shareUrl }}">
    <link rel="icon" type="image/png" href="{{ asset('logocircular.png') }}">
    <meta property="og:type" content="article">
    <meta property="og:locale" content="es_CL">
    <meta property="og:site_name" content="Ansiosxs – Nuevas Lecturas">
    <meta property="og:title" content="{{ $noticia->titulo }}">
    <meta property="og:description" content="{{ $descripcion }}">
    <meta property="og:url" content="{{ $shareUrl }}">
    <meta property="og:image" content="{{ $imagenUrl }}">
    <meta property="og:image:alt" content="{{ $noticia->titulo }}">
    @if ($noticia->created_at)
        <meta property="article:published_time" content="{{ $noticia->created_at->toIso8601String() }}">
    @endif
    <meta name="twitter:card" content="{{ $noticia->imagen ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $noticia->titulo }}">
    <meta name="twitter:description" content="{{ $descripcion }}">
    <meta name="twitter:image" content="{{ $imagenUrl }}">
    <script type="application/ld+json">@json($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
    <style>
        :root {
            color-scheme: light;
            font-family: "Nunito", "Segoe UI", sans-serif;
            color: #30243c;
            background: #fcfaff;
            font-synthesis: none;
            text-rendering: optimizeLegibility;
            --purple: #7042ad;
            --yellow: #ffdb31;
            --text-soft: rgba(45, 45, 45, .8);
        }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; }
        a { color: inherit; }
        header { padding: 1rem; border-bottom: 1px solid rgba(45, 45, 45, .12); background: #f7f4fa; }
        header a { display: block; width: min(100%, 80rem); margin: auto; color: #563884; font-weight: 800; text-decoration: none; }
        main { width: min(100%, 84rem); margin: 0 auto; padding: 2rem 1rem 5rem; }
        .article-layout { display: grid; overflow: hidden; background: #fff; }
        .cover-panel { position: relative; display: flex; min-height: 32rem; overflow: hidden; align-items: flex-end; background: linear-gradient(135deg, #ac7fff, #7356a2 54%, #405f65); isolation: isolate; }
        .cover-panel::after { position: absolute; z-index: 0; background: linear-gradient(180deg, rgba(20, 33, 35, .03) 24%, rgba(20, 33, 35, .82)); content: ""; inset: 0; }
        .cover-image { position: absolute; z-index: -1; width: 100%; height: 100%; object-fit: cover; inset: 0; }
        .back { position: absolute; z-index: 1; top: 1.25rem; left: 1.25rem; padding: .55rem .85rem; border: 1px solid rgba(255, 255, 255, .72); background: rgba(32, 34, 36, .25); color: #fff; font-size: .9rem; font-weight: 700; text-decoration: none; backdrop-filter: blur(8px); }
        .cover-copy { position: relative; z-index: 1; width: 100%; padding: clamp(1.5rem, 5vw, 3rem); color: #fff; }
        time { display: block; margin-bottom: .75rem; font-size: .8rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        h1 { max-width: 16ch; margin: 0; font-family: "Playfair Display", Georgia, serif; font-size: clamp(2.2rem, 5vw, 4.5rem); line-height: 1.05; text-wrap: balance; }
        .article-body { position: relative; align-self: center; padding: 2rem clamp(1.25rem, 4vw, 3.5rem); }
        .article-body::before { display: block; width: 100%; height: 1px; margin: 0 auto 2rem; background: rgba(45, 45, 45, .18); content: ""; }
        .article-body::after { position: absolute; top: 1.55rem; left: 50%; width: .85rem; height: .85rem; border: 3px solid #fff; border-radius: 50%; background: #9baa83; content: ""; transform: translateX(-50%); }
        .summary { margin-bottom: 1.5rem; color: #563884; font-family: "Playfair Display", Georgia, serif; font-size: clamp(1.2rem, 2vw, 1.55rem); line-height: 1.6; }
        .content { color: var(--text-soft); line-height: 1.9; overflow-wrap: anywhere; }
        .content p, .content div { margin-block: .65rem; }
        .content ul, .content ol { padding-left: 1.5rem; }
        .content a { color: var(--purple); text-decoration: underline; text-underline-offset: .15em; }
        .actions { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; margin-top: 2rem; }
        button { min-height: 2.8rem; padding: .65rem 1rem; border: 2px solid #302d32; border-radius: 999px; background: var(--yellow); color: #30243c; font: inherit; font-weight: 800; cursor: pointer; transition: transform .2s ease; }
        button:hover { transform: translateY(-2px); }
        #share-url { width: 100%; padding: .7rem; border: 1px solid rgba(45, 45, 45, .3); font: inherit; }
        #share-status { width: 100%; margin: 0; color: var(--text-soft); }
        footer { padding: 1.5rem 1rem; background: #f7f4fa; color: var(--text-soft); text-align: center; }
        @media (min-width: 960px) {
            main { padding-block: 3rem 5rem; }
            .article-layout { grid-template-columns: minmax(0, 1.08fr) minmax(20rem, .92fr); min-height: 46rem; }
            .cover-panel { min-height: 46rem; }
            .article-body { padding-inline: clamp(2rem, 5vw, 5rem); }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; } }
    </style>
</head>
<body>
    <header>
        <a href="{{ rtrim(config('cors.allowed_origins.0', 'https://www.ansiosxs.cl'), '/') }}/">Ansiosxs – Nuevas Lecturas</a>
    </header>
    <main>
        <article class="article-layout">
            <div class="cover-panel">
                <a class="back" href="{{ rtrim(config('cors.allowed_origins.0', 'https://www.ansiosxs.cl'), '/') }}/noticias.html">← Volver a noticias</a>
                @if ($noticia->imagen)
                    <img class="cover-image" src="{{ $imagenUrl }}" alt="{{ $noticia->titulo }}">
                @endif
                <div class="cover-copy">
                    @if ($noticia->created_at)
                        <time datetime="{{ $noticia->created_at->toIso8601String() }}">{{ $noticia->created_at->locale('es_CL')->translatedFormat('j \d\e F \d\e Y') }}</time>
                    @endif
                    <h1>{{ $noticia->titulo }}</h1>
                </div>
            </div>
            <div class="article-body">
                @if ($resumen !== '')
                    <div class="summary">{!! $resumen !!}</div>
                @endif
                <div class="content">{!! $contenido !!}</div>
                <div class="actions">
                    <button id="share-article" type="button">Compartir noticia</button>
                    <input id="share-url" type="url" value="{{ $shareUrl }}" readonly hidden aria-label="Enlace para compartir">
                    <p id="share-status" role="status" aria-live="polite"></p>
                </div>
            </div>
        </article>
    </main>
    <footer>© {{ now()->year }} Ansiosxs – Nuevas Lecturas</footer>
    <script>
        (function () {
            var button = document.getElementById('share-article');
            var input = document.getElementById('share-url');
            var status = document.getElementById('share-status');
            var shareData = {
                title: @json($noticia->titulo),
                text: @json($descripcion),
                url: @json($shareUrl)
            };

            function showCopyFallback() {
                input.hidden = false;
                input.focus();
                input.select();
                status.textContent = 'Copia el enlace seleccionado para compartir esta noticia.';
            }

            button.addEventListener('click', function () {
                if (navigator.share) {
                    navigator.share(shareData).catch(function (error) {
                        if (error.name !== 'AbortError') showCopyFallback();
                    });
                    return;
                }

                if (!navigator.clipboard || !window.isSecureContext) {
                    showCopyFallback();
                    return;
                }

                navigator.clipboard.writeText(shareData.url).then(function () {
                    status.textContent = 'Enlace copiado.';
                }).catch(showCopyFallback);
            });
        }());
    </script>
</body>
</html>
