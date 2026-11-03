<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnlacePublico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnlacePublicoController extends Controller
{
    public function index(): View
    {
        return view('admin.enlaces.index', [
            'enlaces' => EnlacePublico::query()->orderBy('orden')->orderBy('id')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.enlaces.form', ['enlace' => new EnlacePublico(['activo' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        EnlacePublico::query()->create([
            ...$this->validar($request),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('admin.enlaces.index')->with('status', 'Enlace creado.');
    }

    public function edit(EnlacePublico $enlace): View
    {
        return view('admin.enlaces.form', compact('enlace'));
    }

    public function update(Request $request, EnlacePublico $enlace): RedirectResponse
    {
        $enlace->update($this->validar($request));

        return redirect()->route('admin.enlaces.index')->with('status', 'Enlace actualizado.');
    }

    public function destroy(EnlacePublico $enlace): RedirectResponse
    {
        $enlace->delete();

        return redirect()->route('admin.enlaces.index')->with('status', 'Enlace eliminado.');
    }

    private function validar(Request $request): array
    {
        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:300'],
            'seccion' => ['nullable', 'string', 'max:120'],
            'url' => ['nullable', 'url:http,https', 'max:2048'],
            'orden' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);
        $validated['seccion'] = ($validated['seccion'] ?? null) ?: null;
        $validated['url'] = ($validated['url'] ?? null) ?: null;
        $validated['activo'] = $request->boolean('activo');

        return $validated;
    }
}
