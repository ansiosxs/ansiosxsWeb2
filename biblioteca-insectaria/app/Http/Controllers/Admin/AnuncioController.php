<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anuncio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnuncioController extends Controller
{
    public function index(): View
    {
        return view('admin.anuncios.index', ['anuncios' => Anuncio::query()->latest('publicar_desde')->paginate(25)]);
    }

    public function create(): View
    {
        return view('admin.anuncios.form', ['anuncio' => new Anuncio]);
    }

    public function store(Request $request): RedirectResponse
    {
        Anuncio::create([...$this->validar($request), 'user_id' => $request->user()->id]);

        return redirect()->route('admin.anuncios.index')->with('status', 'Anuncio creado.');
    }

    public function edit(Anuncio $anuncio): View
    {
        return view('admin.anuncios.form', compact('anuncio'));
    }

    public function update(Request $request, Anuncio $anuncio): RedirectResponse
    {
        $anuncio->update($this->validar($request));

        return redirect()->route('admin.anuncios.index')->with('status', 'Anuncio actualizado.');
    }

    public function destroy(Anuncio $anuncio): RedirectResponse
    {
        $anuncio->delete();

        return back()->with('status', 'Anuncio despublicado y eliminado.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'cuerpo' => ['required', 'string', 'max:10000'],
            'publicar_desde' => ['required', 'date'],
            'publicar_hasta' => ['nullable', 'date', 'after_or_equal:publicar_desde'],
        ], [
            'publicar_hasta.after_or_equal' => 'La fecha de término debe ser igual o posterior al inicio.',
        ]);
    }
}
