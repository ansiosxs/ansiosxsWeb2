<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionGeneral;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConfiguracionController extends Controller
{
    public function edit(): View
    {
        return view('admin.configuracion.edit', [
            'configuracion' => ConfiguracionGeneral::actual(),
            'roles' => [
                User::ROLE_ADMIN => 'Administradores',
                User::ROLE_EDITOR => 'Editores',
                User::ROLE_VOLUNTARIO => 'Voluntarios',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $menu = $request->input('secciones_visibles', []);
        $menu = is_array($menu) ? $menu : [];
        foreach (ConfiguracionGeneral::SECCIONES_POR_ROL as $rol => $seccionesPermitidas) {
            if (! array_key_exists($rol, $menu)) {
                $menu[$rol] = [];
            } elseif (is_array($menu[$rol])) {
                $menu[$rol] = array_values(array_unique($menu[$rol]));
            }
        }
        $estadisticas = $request->input('estadisticas_dashboard', []);
        $request->merge([
            'secciones_visibles' => $menu,
            'estadisticas_dashboard' => is_array($estadisticas)
                ? array_values(array_unique($estadisticas))
                : $estadisticas,
        ]);

        $reglas = [
            'plazo_prestamo_dias' => ['required', 'integer', 'between:1,365'],
            'max_prestamos_activos' => ['required', 'integer', 'between:1,100'],
            'permitir_membresia_vencida' => ['required', 'boolean'],
            'dias_recordatorio' => ['required', 'integer', 'between:0,365'],
            'secciones_visibles' => ['required', 'array:admin,editor,voluntario'],
            'estadisticas_dashboard' => ['present', 'array'],
            'estadisticas_dashboard.*' => ['string', Rule::in(array_keys(ConfiguracionGeneral::ESTADISTICAS))],
        ];
        foreach (ConfiguracionGeneral::SECCIONES_POR_ROL as $rol => $seccionesPermitidas) {
            $reglas["secciones_visibles.{$rol}"] = ['present', 'array'];
            $reglas["secciones_visibles.{$rol}.*"] = ['string', Rule::in($seccionesPermitidas)];
        }

        $datos = $request->validate($reglas);
        ConfiguracionGeneral::actual()->update($datos);

        return redirect()->route('admin.configuracion.edit')->with('status', 'La configuración general quedó guardada.');
    }
}
