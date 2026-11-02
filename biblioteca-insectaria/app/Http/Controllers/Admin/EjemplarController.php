<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ejemplar;
use App\Models\Libro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EjemplarController extends Controller
{
    public function index(Libro $libro): View
    {
        return view('admin.ejemplares.index', ['libro' => $libro, 'ejemplares' => $libro->ejemplares()->orderBy('codigo_barras')->paginate(50)]);
    }

    public function create(Libro $libro): View
    {
        return view('admin.ejemplares.form', compact('libro'));
    }

    public function store(Request $request, Libro $libro): RedirectResponse
    {
        $codigo = trim($request->input('codigo_barras', ''));
        $existente = Ejemplar::with('libro')->where('codigo_barras', $codigo)->first();

        if ($existente) {
            return back()->withInput()->withErrors([
                'codigo_barras' => "Este código ya está registrado en el libro «{$existente->libro->titulo}».",
            ]);
        }

        $datos = $request->validate([
            'codigo_barras' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9-]+$/', 'unique:ejemplares,codigo_barras'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ], [
            'codigo_barras.required' => 'Escanea o ingresa el código de barras.',
            'codigo_barras.regex' => 'El código solo puede contener letras, números y guiones.',
            'codigo_barras.unique' => 'Este código ya está registrado.',
        ]);

        $libro->ejemplares()->create($datos);

        return redirect()->route('admin.libros.ejemplares.index', $libro)->with('status', 'Ejemplar registrado. Puedes escanear el siguiente código.')->with('focus_codigo', true);
    }

    public function update(Request $request, Libro $libro, Ejemplar $ejemplar): RedirectResponse
    {
        abort_unless($ejemplar->libro_id === $libro->id, 404);

        if ($ejemplar->prestamos()->where('estado', 'activo')->exists()) {
            return back()->withErrors(['ejemplar' => 'No se puede cambiar el estado mientras el ejemplar está prestado.']);
        }

        $datos = $request->validate([
            'estado' => ['required', 'in:disponible,extraviado,baja'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);
        $ejemplar->update($datos);

        return back()->with('status', 'Estado del ejemplar actualizado.');
    }

    public function destroy(Libro $libro, Ejemplar $ejemplar): RedirectResponse
    {
        abort_unless($ejemplar->libro_id === $libro->id, 404);

        if ($ejemplar->prestamos()->where('estado', 'activo')->exists()) {
            return back()->withErrors(['ejemplar' => 'No se puede dar de baja mientras el ejemplar está prestado.']);
        }

        if ($ejemplar->prestamos()->exists()) {
            $ejemplar->update(['estado' => 'baja']);
        } else {
            $ejemplar->delete();
        }

        return back()->with('status', 'Ejemplar dado de baja.');
    }
}
