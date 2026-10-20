<!doctype html>
<html lang="es" class="h-full bg-[#fcfaff]">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar contraseña | Biblioteca Insectaria</title>
    @vite(['resources/css/app.css'])
</head>
<body class="auth-page min-h-full">
    <main class="auth-card">
        <a href="{{ route('catalogo') }}" aria-label="Volver al catálogo"><img class="auth-logo" src="{{ asset('logo.png') }}" alt="Ansiosxs Nuevas Lecturas"></a>
        <p class="eyebrow">Acceso</p>
        <h1>Recuperar contraseña</h1>
        <p>Ingresa tu correo registrado y te enviaremos un enlace para cambiar tu contraseña.</p>
        @if (session('status'))
            <div class="notice notice-success" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice notice-error" role="alert">{{ $errors->first() }}</div>
        @endif
        <form method="post" action="{{ route('password.email') }}">
            @csrf
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
            <button class="button" type="submit">Enviar enlace</button>
        </form>
        <a class="auth-back" href="{{ route('login') }}">Volver al inicio de sesión</a>
    </main>
</body>
</html>
