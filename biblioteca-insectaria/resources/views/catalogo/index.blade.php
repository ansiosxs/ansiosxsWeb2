<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Consulta el catálogo público de la Biblioteca Comunitaria Insectaria.">
    <title>Catálogo | Biblioteca Insectaria</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-page">
    <header class="public-header">
        <div class="public-header-inner"><a class="brand" href="{{ route('catalogo') }}">Biblioteca Comunitaria Insectaria</a>
            <a href="{{ auth()->check() ? route('admin.dashboard') : route('login') }}">Acceso voluntariado</a>
        </div>
    </header>
    <main class="catalog-main">
        <section class="catalog-intro"><p class="eyebrow">Concepción, Chile</p><h1>Explora nuestro catálogo</h1><p>Busca libros y revisa su disponibilidad antes de visitarnos.</p></section>
        @if ($anuncios->isNotEmpty())
            <section class="announcements" aria-label="Anuncios">
                @foreach ($anuncios as $anuncio)<article class="announcement"><h2>{{ $anuncio->titulo }}</h2><p>{{ $anuncio->cuerpo }}</p></article>@endforeach
            </section>
        @endif
        <div id="catalog-app" aria-live="polite"></div>
    </main>
    <footer class="public-footer">Biblioteca Comunitaria Insectaria · Los datos de disponibilidad pueden cambiar durante el día.</footer>
    <script>if ('serviceWorker' in navigator) window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch((error) => console.error('No se pudo registrar el caché del catálogo:', error)));</script>
</body>
</html>
