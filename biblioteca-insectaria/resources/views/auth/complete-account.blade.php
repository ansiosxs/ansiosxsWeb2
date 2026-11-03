<!doctype html>
<html lang="es" class="h-full bg-[#fcfaff]">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Completar cuenta | Biblioteca Insectaria</title>
    @vite(['resources/css/app.css'])
</head>
<body class="auth-page min-h-full">
    <main class="auth-card">
        <a href="{{ route('catalogo') }}" aria-label="Volver al catálogo"><img class="auth-logo" src="{{ asset('logo.png') }}" alt="Ansiosxs Nuevas Lecturas"></a>
        <p class="eyebrow">Primer ingreso</p>
        <h1>Completa tu cuenta</h1>
        <p>Para continuar, indica tu correo y crea la contraseña que usarás de ahora en adelante.</p>
        @if ($errors->any()) <div class="notice notice-error" role="alert">{{ $errors->first() }}</div> @endif
        <form method="post" action="{{ route('account.setup.store') }}">
            @csrf
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" maxlength="254" value="{{ old('email') }}" autocomplete="email" required autofocus>
            <label for="password">Nueva contraseña</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
            <label for="password_confirmation">Confirma tu nueva contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
            <button class="button" type="submit">Guardar y continuar</button>
        </form>
        <form class="mt-4" method="post" action="{{ route('logout') }}">
            @csrf
            <button class="auth-back" type="submit">Cerrar sesión</button>
        </form>
    </main>
    <img class="auth-mascot" src="{{ asset('mascotas/serpi.png') }}" alt="" aria-hidden="true">
</body>
</html>
