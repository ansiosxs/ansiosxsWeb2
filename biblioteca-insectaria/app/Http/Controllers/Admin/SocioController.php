<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Socio;
use App\Rules\RunChileno;
use App\Services\TelefonoChileno;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocioController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim($request->string('q')->toString());
        $socios = Socio::query()
            ->with('registradoPor')
            ->when($q !== '', fn ($query) => $query->where(fn ($search) => $search
                ->where('nombres', 'like', "%{$q}%")
                ->orWhere('apellidos', 'like', "%{$q}%")
                ->orWhere('run', 'like', '%'.RunChileno::normalizar($q).'%')))
            ->orderBy('apellidos')->orderBy('nombres')->paginate(25)->withQueryString();

        return view('admin.socios.index', compact('socios', 'q'));
    }

    public function create(): View
    {
        return view('admin.socios.form', ['socio' => new Socio]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $datos['run'] = RunChileno::normalizar($datos['run']);
        $datos['telefono'] = TelefonoChileno::normalizar($datos['telefono']);
        $datos['fecha_inscripcion'] = today();
        $datos['vigente_hasta'] = today()->addYear();
        $datos['activo'] = true;
        $datos['user_id'] = $request->user()->id;
        $socio = Socio::create($datos);

        return redirect()->route('admin.socios.show', $socio)->with('status', 'Socio inscrito con membresía vigente por un año.');
    }

    public function show(Socio $socio): View
    {
        $socio->load(['prestamos' => fn ($query) => $query->with(['libro', 'registradoPor'])->latest('fecha_prestamo')]);

        return view('admin.socios.show', compact('socio'));
    }

    public function edit(Socio $socio): View
    {
        return view('admin.socios.form', compact('socio'));
    }

    public function update(Request $request, Socio $socio): RedirectResponse
    {
        $datos = $this->validar($request, $socio);
        $datos['run'] = RunChileno::normalizar($datos['run']);
        $datos['telefono'] = TelefonoChileno::normalizar($datos['telefono']);
        $socio->update($datos);

        return redirect()->route('admin.socios.show', $socio)->with('status', 'Datos del socio actualizados.');
    }

    public function destroy(Socio $socio): RedirectResponse
    {
        $socio->update(['activo' => false]);

        return redirect()->route('admin.socios.index')->with('status', 'Socio desactivado. Se conservó su historial.');
    }

    public function renovar(Socio $socio): RedirectResponse
    {
        $socio->update(['vigente_hasta' => today()->addYear(), 'activo' => true]);

        return back()->with('status', 'Membresía renovada por un año.');
    }

    public function caducar(Socio $socio): RedirectResponse
    {
        $socio->update(['vigente_hasta' => today()->subDay()]);

        return back()->with('status', 'La membresía quedó caducada.');
    }

    public function cambiarCaducidad(Request $request, Socio $socio): RedirectResponse
    {
        $datos = $request->validate([
            'vigente_hasta' => ['required', 'date'],
        ]);
        $socio->update(['vigente_hasta' => $datos['vigente_hasta']]);

        return back()->with('status', 'Se actualizó la fecha de caducidad de la membresía.');
    }

    private function validar(Request $request, ?Socio $socio = null): array
    {
        $request->merge(['run' => RunChileno::normalizar((string) $request->input('run'))]);

        return $request->validate([
            'run' => ['required', 'string', 'max:12', new RunChileno, 'unique:socios,run'.($socio ? ','.$socio->id : '')],
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:30', 'regex:/^[+\d\s().-]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'comuna' => ['required', 'string', 'max:255'],
            'ocupacion' => ['nullable', 'string', 'max:255'],
        ], [
            'run.unique' => 'Ya existe un socio inscrito con este RUN.',
            'telefono.regex' => 'Ingresa un celular chileno válido.',
        ]);
    }
}
