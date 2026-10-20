<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $usuarios = User::query()
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        return view('admin.usuarios.form', ['usuario' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $datos['password'] = Hash::make($datos['password']);
        $datos['activo'] = $datos['activo'] ?? true;

        User::query()->create($datos);

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario): View
    {
        return view('admin.usuarios.form', compact('usuario'));
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $datos = $this->validar($request, $usuario);

        $this->ensureAdminRemains($usuario, $datos['role'], (bool) ($datos['activo'] ?? $usuario->activo));

        if ($request->filled('password')) {
            $datos['password'] = Hash::make($request->input('password'));
        } else {
            unset($datos['password']);
        }

        $usuario->update($datos);

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuario actualizado correctamente.');
    }

    public function toggleStatus(User $usuario): RedirectResponse
    {
        if ($usuario->id === auth()->id()) {
            return back()->withErrors(['usuario' => 'No puedes cambiar tu propio estado.']);
        }

        $this->ensureAdminRemains($usuario, $usuario->role, ! $usuario->activo);

        $usuario->update(['activo' => ! $usuario->activo]);

        return back()->with('status', $usuario->activo ? 'Usuario activado.' : 'Usuario dado de baja.');
    }

    public function destroy(User $usuario): RedirectResponse
    {
        if ($usuario->id === auth()->id()) {
            return back()->withErrors(['usuario' => 'No puedes eliminar tu propia cuenta.']);
        }

        $this->ensureAdminRemains($usuario, null, false);

        $tieneHistorial = DB::table('prestamos')
            ->where('user_id', $usuario->id)
            ->orWhere('devuelto_por_user_id', $usuario->id)
            ->exists()
            || DB::table('anuncios')->where('user_id', $usuario->id)->exists()
            || DB::table('socios')->where('user_id', $usuario->id)->exists()
            || DB::table('user_action_logs')
                ->where('user_id', $usuario->id)
                ->orWhere('actor_id', $usuario->id)
                ->exists();

        if ($tieneHistorial) {
            return back()->withErrors([
                'usuario' => 'No se puede eliminar porque tiene registros asociados. Usa «Dar de baja» para revocar su acceso y conservar el historial.',
            ]);
        }

        $usuario->delete();

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuario eliminado correctamente.');
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        $reglas = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_VOLUNTARIO])],
            'activo' => ['nullable', 'boolean'],
        ];

        if ($request->filled('password') || ! $usuario) {
            $reglas['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $request->validate($reglas, [
            'email.unique' => 'Este correo ya está registrado en otra cuenta.',
            'role.in' => 'El rol seleccionado no es válido.',
        ]);
    }

    private function ensureAdminRemains(User $usuario, ?string $newRole, bool $willBeActive): void
    {
        if (! $usuario->isAdmin() || ! $usuario->activo || ($newRole === User::ROLE_ADMIN && $willBeActive)) {
            return;
        }

        $activeAdmins = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('activo', true)
            ->count();

        if ($activeAdmins <= 1) {
            throw ValidationException::withMessages([
                'usuario' => 'No se puede quitar, desactivar o eliminar al último administrador activo.',
            ]);
        }
    }
}
