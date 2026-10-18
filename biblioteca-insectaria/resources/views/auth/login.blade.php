<!doctype html>
<html lang="es" class="h-full bg-[#fcfaff]">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar | Biblioteca Insectaria</title>
    @vite(['resources/css/app.css'])
</head>
<body class="auth-page min-h-full">
    <main class="auth-card">
        <p class="eyebrow">Panel de gestión</p>
        <h1>Biblioteca Insectaria</h1><p>Acceso para voluntarias y voluntarios</p>
        @if ($errors->any()) <div class="notice notice-error" role="alert">{{ $errors->first() }}</div> @endif
        <form method="post" action="{{ route('login.store') }}">
            @csrf
            <label for="email">Correo</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            <label for="password">Contraseña</label><input id="password" name="password" type="password" autocomplete="current-password" required>
            <label class="checkbox"><input type="checkbox" name="remember" value="1"> Mantener sesión iniciada</label>
            <button class="button" type="submit">Ingresar</button>
        </form>
    </main>
</body>
</html>
