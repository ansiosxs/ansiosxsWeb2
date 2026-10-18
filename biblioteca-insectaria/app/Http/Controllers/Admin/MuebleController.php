<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mueble;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MuebleController extends Controller
{
    public function index(): View
    {
        return view('admin.estanterias.index', ['muebles' => Mueble::query()->withCount('libros')->orderBy('nombre')->paginate(25)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:muebles,nombre'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ]);
        Mueble::create($datos);

        return back()->with('status', 'Estantería creada.');
    }

    public function update(Request $request, Mueble $mueble): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:muebles,nombre,'.$mueble->id],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ]);
        $mueble->update($datos);

        return back()->with('status', 'Estantería actualizada.');
    }

    public function destroy(Mueble $mueble): RedirectResponse
    {
        if ($mueble->libros()->exists()) {
            return back()->withErrors(['mueble' => 'No se puede eliminar una estantería que tiene libros asignados.']);
        }

        $mueble->delete();

        return back()->with('status', 'Estantería eliminada.');
    }
}
