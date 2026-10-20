<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::transliterate(Str::lower($credentials['login']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'login' => 'Demasiados intentos. Intenta nuevamente en un minuto.',
            ]);
        }

        $users = User::query()
            ->where('email', $credentials['login'])
            ->orWhere('name', $credentials['login'])
            ->get();
        $user = $users->first(fn (User $user): bool => Hash::check($credentials['password'], $user->password));

        if (! $user) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'login' => 'Las credenciales ingresadas no son correctas.',
            ]);
        }

        if (! $user->activo) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'login' => 'Esta cuenta está desactivada. Contacta al administrador.',
            ]);
        }

        RateLimiter::clear($key);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function forgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'No encontramos una cuenta asociada a ese correo.',
        ]);

        $usuario = User::query()->where('email', $request->input('email'))->first();
        if (! $usuario || ! $usuario->activo) {
            return back()->withErrors(['email' => 'No podemos enviar un enlace a una cuenta desactivada.']);
        }

        $status = \Illuminate\Support\Facades\Password::sendResetLink(
            $request->only('email')
        );

        return $status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT
            ? back()->with('status', 'Te hemos enviado un correo para restablecer tu contraseña.')
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetForm(string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => request('email')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $usuario = User::query()->where('email', $request->input('email'))->first();
        if (! $usuario || ! $usuario->activo) {
            return back()->withErrors(['email' => 'No se puede restablecer la contraseña de una cuenta desactivada.']);
        }

        $status = \Illuminate\Support\Facades\Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                ])->save();
            }
        );

        return $status === \Illuminate\Support\Facades\Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Tu contraseña se ha restablecido correctamente.')
            : back()->withErrors(['email' => __($status)]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
