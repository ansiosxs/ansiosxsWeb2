<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Libro;
use App\Models\Mueble;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LibroController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim($request->string('q')->toString());
        $libros = Libro::query()
            ->with('mueble:id,nombre')
            ->withUnidadesPrestadas()
            ->when($q !== '', fn ($query) => $query->where(fn ($search) => $search
                ->where('titulo', 'like', "%{$q}%")
                ->orWhere('autor', 'like', "%{$q}%"))
            )->orderBy('titulo')->paginate(25)->withQueryString();

        return view('admin.libros.index', compact('libros', 'q'));
    }

    public function create(): View
    {
        return view('admin.libros.form', ['libro' => new Libro, 'muebles' => Mueble::orderBy('nombre')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $libro = Libro::create($this->validated($request));

        return redirect()->route('admin.libros.show', $libro)->with('status', 'Libro creado correctamente.');
    }

    public function show(Libro $libro): View
    {
        $libro = Libro::query()->with('mueble')->withUnidadesPrestadas()->findOrFail($libro->id);

        return view('admin.libros.show', compact('libro'));
    }

    public function edit(Libro $libro): View
    {
        return view('admin.libros.form', ['libro' => $libro, 'muebles' => Mueble::orderBy('nombre')->get()]);
    }

    public function update(Request $request, Libro $libro): RedirectResponse
    {
        $libro->update($this->validated($request));

        return redirect()->route('admin.libros.show', $libro)->with('status', 'Libro actualizado correctamente.');
    }

    public function destroy(Libro $libro): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        if ($libro->prestamos()->where('estado', 'activo')->exists()) {
            return back()->withErrors(['libro' => 'No se puede dar de baja: el libro tiene unidades prestadas. Registra primero la devolución.']);
        }

        $libro->delete();

        return redirect()->route('admin.libros.index')->with('status', 'Libro dado de baja correctamente.');
    }

    private function validated(Request $request): array
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'autor' => ['required', 'string', 'max:255'],
            'cantidad_total' => ['required', 'integer', 'min:0', 'max:1000000'],
            'isbn' => ['nullable', 'string', 'max:20'],
            'editorial' => ['nullable', 'string', 'max:255'],
            'anio' => ['nullable', 'integer', 'between:1000,9999'],
            'portada_url' => ['nullable', 'url', 'max:255'],
            'mueble_id' => ['nullable', 'exists:muebles,id'],
        ], [
            'titulo.required' => 'El título es obligatorio.',
            'autor.required' => 'El autor es obligatorio.',
            'portada_url.url' => 'La portada debe ser una dirección web válida.',
        ]);

        if ($request->route('libro') instanceof Libro) {
            $prestadas = $request->route('libro')->prestamos()
                ->where('estado', 'activo')
                ->selectRaw('COALESCE(SUM(cantidad - cantidad_devuelta), 0) as total')
                ->value('total');

            if ((int) $datos['cantidad_total'] < (int) $prestadas) {
                throw ValidationException::withMessages([
                    'cantidad_total' => "No puedes fijar el inventario bajo las {$prestadas} unidades actualmente prestadas.",
                ]);
            }
        }

        return $datos;
    }
}
