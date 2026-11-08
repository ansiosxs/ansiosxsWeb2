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
        <div class="public-header-inner"><a class="public-brand" href="{{ rtrim(config('cors.allowed_origins.0', 'https://www.ansiosxs.cl'), '/') }}/" aria-label="Volver al sitio de Ansiosxs"><img src="{{ asset('logo.png') }}" alt="Ansiosxs Nuevas Lecturas"></a>
            <div class="public-header-actions">
                @auth
                    <span class="public-header-user">{{ auth()->user()->name }}</span>
                    <a class="public-header-action" href="{{ route('admin.dashboard') }}" aria-label="Ir al panel de administración">Panel</a>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="public-header-action" type="submit">Cerrar sesión</button>
                    </form>
                @else
                    <a class="public-header-action" href="{{ route('login') }}">Acceso</a>
                @endauth
            </div>
        </div>
    </header>
    <main class="catalog-main">
        <section class="catalog-intro">
            <img class="catalog-mascot catalog-mascot--left" src="{{ asset('mascotas/bicho.png') }}" alt="" aria-hidden="true">
            <div><p class="eyebrow">Concepción, Chile</p><h1>Explora nuestro catálogo</h1><p>Busca libros y revisa su disponibilidad antes de visitarnos.</p></div>
            <img class="catalog-mascot catalog-mascot--right" src="{{ asset('mascotas/ave.png') }}" alt="" aria-hidden="true">
        </section>
        <section class="stats-grid catalog-stats" aria-label="Resumen del catálogo">
            <article class="stat-card"><span class="font-semibold text-[#716c77]">Ejemplares disponibles</span><strong>{{ number_format($unidadesDisponibles, 0, ',', '.') }}</strong><span class="text-sm text-[#716c77]">Unidades disponibles para préstamo</span></article>
            <article class="stat-card"><span class="font-semibold text-[#716c77]">Títulos en catálogo</span><strong>{{ number_format($titulosCatalogo, 0, ',', '.') }}</strong><span class="text-sm text-[#716c77]">Libros registrados, sin contar ejemplares</span></article>
        </section>
        <div id="catalog-app"
            data-muebles="{{ $muebles->toJson(JSON_UNESCAPED_UNICODE) }}"
            data-edit-url="@auth{{ route('admin.libros.edit', ['libro' => '__LIBRO_ID__']) }}@endauth"
            aria-live="polite"></div>
    </main>
    <footer class="public-footer">Biblioteca Comunitaria Insectaria · Los datos de disponibilidad pueden cambiar durante el día.</footer>
    <script>if ('serviceWorker' in navigator) window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch((error) => console.error('No se pudo registrar el caché del catálogo:', error)));</script>
</body>
</html>
