<!doctype html>
<html lang="es" class="h-full bg-[#fcfaff]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Panel') | Biblioteca Insectaria</title>
    @vite(['resources/css/app.css', 'resources/js/admin.js', 'resources/js/admin-resiliencia.js', 'resources/js/admin-interacciones.js'])
</head>
<body class="min-h-full bg-[#fcfaff] font-sans text-[#2d2d2d] antialiased">
    <div class="min-h-screen xl:flex" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
        <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-[#35234f]/40 xl:hidden" @click="sidebarOpen = false" aria-hidden="true"></div>

        <aside id="admin-sidebar" class="admin-sidebar fixed inset-y-0 left-0 z-50 flex w-[270px] flex-col border-r border-[#e7e0d8] bg-white transition-transform duration-200"
            :class="{ 'is-open': sidebarOpen }" aria-label="Navegación del panel">
            <div class="flex min-h-[88px] items-center justify-between border-b border-[#e7e0d8] px-5">
                <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3">
                    <img class="admin-brand-logo" src="{{ asset('logocircular.png') }}" alt="">
                    <span class="min-w-0">
                        <span class="block font-display text-lg font-bold leading-tight text-brand-900">Biblioteca</span>
                        <span class="block text-sm font-semibold text-[#716c77]">Insectaria</span>
                    </span>
                </a>
                <button type="button" class="admin-sidebar-close xl:hidden" @click="sidebarOpen = false" aria-label="Cerrar menú" aria-controls="admin-sidebar">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </button>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-6" aria-label="Principal">
                <div>
                    <p class="px-3 pb-2 text-xs font-extrabold uppercase tracking-[.14em] text-[#8b8491]">Biblioteca</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.dashboard') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.dashboard')]) @click="sidebarOpen = false">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3.75 10.5 12 3.75l8.25 6.75v9a.75.75 0 0 1-.75.75h-15a.75.75 0 0 1-.75-.75v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 20.25v-6h6v6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                            <span>Resumen</span>
                        </a>
                        <a href="{{ route('admin.libros.index') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.libros.*')]) @click="sidebarOpen = false">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4.5 4.75A2.25 2.25 0 0 1 6.75 2.5H20v17H6.75A2.25 2.25 0 0 0 4.5 21.75v-17Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M4.5 17.5a2.25 2.25 0 0 1 2.25-2.25H20M8 6.5h7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            <span>Libros e inventario</span>
                        </a>
                        <a href="{{ route('admin.socios.index') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.socios.*')]) @click="sidebarOpen = false">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 20.25v-1.5a3.75 3.75 0 0 0-3.75-3.75h-5.5A3.75 3.75 0 0 0 3 18.75v1.5m14-13.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm4 13.5v-1.5a3.75 3.75 0 0 0-3-3.675M15 3.2a3 3 0 0 1 0 5.85" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>Socios</span>
                        </a>
                    </div>
                </div>

                <div>
                    <p class="px-3 pb-2 text-xs font-extrabold uppercase tracking-[.14em] text-[#8b8491]">Circulación</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.prestamos.index') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.prestamos.index')]) @click="sidebarOpen = false">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 7.5h14m0 0-4-4m4 4-4 4M19 16.5H5m0 0 4 4m-4-4 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>Préstamos</span>
                        </a>
                        <a href="{{ route('admin.morosidad') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.morosidad')]) @click="sidebarOpen = false">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8.25" stroke="currentColor" stroke-width="1.7"/><path d="M12 7.5v5l3 1.75" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            <span>Morosidad</span>
                        </a>
                    </div>
                </div>

                <div>
                    <p class="px-3 pb-2 text-xs font-extrabold uppercase tracking-[.14em] text-[#8b8491]">Administración</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.turnos.index') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.turnos.*')]) @click="sidebarOpen = false">
                            <span aria-hidden="true">▦</span><span>Turnos y actividades</span>
                        </a>
                        <a href="{{ route('admin.reportes.index') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.reportes.*')]) @click="sidebarOpen = false">
                            <span aria-hidden="true">✉</span><span>Informes</span>
                        </a>
                        <a href="{{ route('admin.anuncios.index') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.anuncios.*')]) @click="sidebarOpen = false">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10.25v3.5h3l9 4v-11l-9 3.5H4Zm3 3.5 1.5 5h3l-1.5-4.333M16 9.5a3 3 0 0 1 0 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>Anuncios</span>
                        </a>
                        <a href="{{ route('admin.estanterias.index') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.estanterias.*', 'admin.muebles.*')]) @click="sidebarOpen = false">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10.5V6.75A2.25 2.25 0 0 1 6.25 4.5h11.5A2.25 2.25 0 0 1 20 6.75v3.75m-16 0h16v7H4v-7Zm2 7v2m12-2v2M7 10.5V8h10v2.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>Estanterías</span>
                        </a>
                        <a href="{{ route('admin.estadisticas') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.estadisticas')]) @click="sidebarOpen = false">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19.5V12m5 7.5V5m5 14.5v-10m5 10V8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                            <span>Estadísticas</span>
                        </a>
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.usuarios.index') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.usuarios.*')]) @click="sidebarOpen = false">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 20.25v-1.5a3.75 3.75 0 0 0-3.75-3.75h-5.5A3.75 3.75 0 0 0 3 18.75v1.5m14-13.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm4 13.5v-1.5a3.75 3.75 0 0 0-3-3.675M15 3.2a3 3 0 0 1 0 5.85" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span>Usuarios</span>
                            </a>
                            <a href="{{ route('admin.logs.index') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.logs.*')]) @click="sidebarOpen = false">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7.5 4.5h9a2.25 2.25 0 0 1 2.25 2.25v11.25A2.25 2.25 0 0 1 16.5 20.25h-9A2.25 2.25 0 0 1 5.25 18V6.75A2.25 2.25 0 0 1 7.5 4.5Zm0 5.25h9m-9 3.75h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                <span>Bitácora</span>
                            </a>
                        @endif
                    </div>
                </div>
            </nav>

            <div class="border-t border-[#e7e0d8] p-4">
                <a href="{{ route('catalogo') }}" target="_blank" rel="noopener" class="admin-catalog-link">
                    <span>Ver catálogo público</span><span aria-hidden="true">↗</span>
                </a>
            </div>
        </aside>

        <div class="min-w-0 flex-1 xl:ml-[270px]">
            <header class="admin-topbar sticky top-0 z-30 flex min-h-[76px] items-center justify-between gap-3 border-b border-[#e7e0d8] bg-white/95 px-4 backdrop-blur sm:px-6 xl:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="admin-menu-toggle xl:hidden" @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen" aria-controls="admin-sidebar" :aria-label="sidebarOpen ? 'Cerrar menú' : 'Abrir menú'">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </button>
                    <div class="min-w-0">
                        <p class="hidden text-xs font-extrabold uppercase tracking-[.12em] text-[#8b8491] sm:block">Biblioteca Insectaria</p>
                        <h1 class="truncate font-display text-lg font-bold text-[#35234f] sm:text-xl">@yield('title', 'Panel')</h1>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <span class="hidden text-right sm:block">
                        <span class="block text-sm font-bold text-[#35234f]">{{ auth()->user()->name }}</span>
                        <span class="block text-xs text-[#716c77]">{{ auth()->user()->isAdmin() ? 'Administrador' : 'Voluntario' }}</span>
                    </span>
                    <form method="post" action="{{ route('logout') }}">@csrf
                        <button class="button button-secondary admin-logout" type="submit">Cerrar sesión</button>
                    </form>
                </div>
            </header>

            <main class="admin-main min-h-[calc(100vh-76px)] max-w-none px-4 py-6 sm:px-6 xl:px-8">
                @if (session('status')) <div class="notice notice-success" role="status">{{ session('status') }}</div> @endif
                @if ($errors->any())
                    <div class="notice notice-error" role="alert"><strong>Revisa la información ingresada:</strong>
                        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
