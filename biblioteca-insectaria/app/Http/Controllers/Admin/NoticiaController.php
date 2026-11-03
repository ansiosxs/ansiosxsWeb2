<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Noticia;
use App\Support\RichTextSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NoticiaController extends Controller
{
    public function __construct(private readonly RichTextSanitizer $htmlSanitizer) {}

    public function index(): View
    {
        return view('admin.noticias.index', [
            'noticias' => Noticia::query()->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.noticias.form', ['noticia' => new Noticia]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $imagen = $request->file('imagen')?->store('noticias', 'public');

        if ($request->hasFile('imagen') && ! $imagen) {
            abort(500, 'No se pudo guardar la imagen de la noticia.');
        }

        Noticia::query()->create([
            ...$datos,
            'imagen' => $imagen,
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('admin.noticias.index')->with('status', 'Noticia creada.');
    }

    public function edit(Noticia $noticia): View
    {
        return view('admin.noticias.form', compact('noticia'));
    }

    public function update(Request $request, Noticia $noticia): RedirectResponse
    {
        $datos = $this->validar($request);
        $imagenAnterior = $noticia->imagen;

        if ($request->hasFile('imagen')) {
            $imagen = $request->file('imagen')->store('noticias', 'public');

            if (! $imagen) {
                abort(500, 'No se pudo guardar la imagen de la noticia.');
            }

            $datos['imagen'] = $imagen;
        } elseif ($request->boolean('eliminar_imagen')) {
            $datos['imagen'] = null;
        }

        $noticia->update($datos);

        if ($imagenAnterior && $noticia->imagen !== $imagenAnterior) {
            $this->eliminarImagen($imagenAnterior);
        }

        return redirect()->route('admin.noticias.index')->with('status', 'Noticia actualizada.');
    }

    public function destroy(Noticia $noticia): RedirectResponse
    {
        $imagen = $noticia->imagen;
        $noticia->delete();

        if ($imagen) {
            $this->eliminarImagen($imagen);
        }

        return redirect()->route('admin.noticias.index')->with('status', 'Noticia eliminada.');
    }

    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'resumen' => ['nullable', 'string', 'max:500'],
            'contenido' => ['required', 'string', 'max:50000'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'eliminar_imagen' => ['nullable', 'boolean'],
        ]);

        $datos['resumen'] = $this->htmlSanitizer->sanitize($datos['resumen'] ?? null);
        $datos['contenido'] = $this->htmlSanitizer->sanitize($datos['contenido']);

        if ($datos['resumen'] !== null && trim(strip_tags($datos['resumen'])) === '') {
            $datos['resumen'] = null;
        }

        if (trim(strip_tags($datos['contenido'])) === '') {
            throw ValidationException::withMessages([
                'contenido' => 'El contenido debe incluir texto.',
            ]);
        }

        unset($datos['eliminar_imagen']);

        return $datos;
    }

    private function eliminarImagen(string $imagen): void
    {
        if (! Storage::disk('public')->delete($imagen)) {
            abort(500, 'No se pudo eliminar la imagen de la noticia.');
        }
    }
}
