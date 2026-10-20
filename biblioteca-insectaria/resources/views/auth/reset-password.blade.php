<!doctype html>
<html lang="es" class="h-full bg-[#fcfaff]">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablecer contraseña</title>
    @vite(['resources/css/app.css'])
</head>
<body class="auth-page min-h-full">
    <main class="auth-card">
        <a href="{{ route('catalogo') }}" aria-label="Volver al catálogo"><img class="auth-logo" src="{{ asset('logo.png') }}" alt="Ansiosxs Nuevas Lecturas"></a>
        <p class="eyebrow">Seguridad</p>
        <h1>Nueva contraseña</h1>
        <p>Elige una contraseña nueva para tu cuenta.</p>
        @if ($errors->any())
            <div class="notice notice-error" role="alert">{{ $errors->first() }}</div>
        @endif
        <form method="post" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required>
            <label for="password">Nueva contraseña</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required>
            <label for="password_confirmation">Confirmar contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            <button class="button" type="submit">Cambiar contraseña</button>
        </form>
        <a class="auth-back" href="{{ route('login') }}">Volver al inicio</a>
    </main>
</body>
</html>
