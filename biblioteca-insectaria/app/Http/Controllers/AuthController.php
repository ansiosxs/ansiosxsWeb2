<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            if ($request->user()->needsAccountSetup()) {
                return redirect()->route('account.setup');
            }

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

        if ($user->needsAccountSetup()) {
            return redirect()->route('account.setup');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function showAccountSetup(Request $request): View|RedirectResponse
    {
        if (! $request->user()->needsAccountSetup()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.complete-account');
    }

    public function completeAccountSetup(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->needsAccountSetup()) {
            return redirect()->route('admin.dashboard');
        }

        $datos = $request->validate([
            'email' => ['required', Rule::email()->strict(), 'max:254', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                function (string $attribute, mixed $value, \Closure $fail) use ($user): void {
                    if (Hash::check($value, $user->password)) {
                        $fail('La nueva contraseña debe ser distinta de la contraseña provisional.');
                    }
                },
            ],
        ], [
            'email.email' => 'Ingresa un correo electrónico con formato válido.',
            'email.unique' => 'Este correo ya está registrado en otra cuenta.',
        ]);

        $user->forceFill([
            'email' => $datos['email'],
            'password' => Hash::make($datos['password']),
            'must_change_password' => false,
        ])->save();
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('status', 'Tu cuenta quedó configurada correctamente.');
    }

    public function forgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', Rule::email()->strict(), 'max:254', 'exists:users,email'],
        ], [
            'email.email' => 'Ingresa un correo electrónico con formato válido.',
            'email.exists' => 'No encontramos una cuenta asociada a ese correo.',
        ]);

        $usuario = User::query()->where('email', $request->input('email'))->first();
        if (! $usuario || ! $usuario->activo) {
            return back()->withErrors(['email' => 'No podemos enviar un enlace a una cuenta desactivada.']);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
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
            'email' => ['required', Rule::email()->strict(), 'max:254', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.email' => 'Ingresa un correo electrónico con formato válido.',
        ]);

        $usuario = User::query()->where('email', $request->input('email'))->first();
        if (! $usuario || ! $usuario->activo) {
            return back()->withErrors(['email' => 'No se puede restablecer la contraseña de una cuenta desactivada.']);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
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
