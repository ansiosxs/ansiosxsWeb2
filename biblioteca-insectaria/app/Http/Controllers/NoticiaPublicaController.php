<?php

namespace App\Http\Controllers;

use App\Models\Noticia;
use App\Support\RichTextSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NoticiaPublicaController extends Controller
{
    public function __construct(private readonly RichTextSanitizer $htmlSanitizer) {}

    public function index(): JsonResponse
    {
        $noticias = Noticia::query()
            ->latest()
            ->paginate(12)
            ->through(fn (Noticia $noticia): array => $this->representar($noticia));

        return response()->json($noticias);
    }

    public function show(Noticia $noticia): JsonResponse
    {
        return response()->json(['data' => $this->representar($noticia)]);
    }

    public function pagina(Noticia $noticia): View
    {
        $contenido = $this->htmlSanitizer->sanitize($noticia->contenido) ?? '';
        $resumen = $this->htmlSanitizer->sanitize($noticia->resumen) ?? '';
        $descripcion = $this->descripcionMeta($resumen ?: $contenido);
        $imagenUrl = $noticia->imagen
            ? url(Storage::disk('public')->url($noticia->imagen))
            : asset('logocircular.png');

        return view('noticias.publica', [
            'noticia' => $noticia,
            'contenido' => $contenido,
            'descripcion' => $descripcion,
            'imagenUrl' => $imagenUrl,
            'resumen' => $resumen,
            'shareUrl' => route('noticias.publicas.show', $noticia),
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $noticia->titulo,
                'description' => $descripcion,
                'image' => $imagenUrl,
                'datePublished' => $noticia->created_at?->toIso8601String(),
                'mainEntityOfPage' => route('noticias.publicas.show', $noticia),
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => 'Ansiosxs – Nuevas Lecturas',
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => asset('logo.png'),
                    ],
                ],
            ],
        ]);
    }

    private function representar(Noticia $noticia): array
    {
        return [
            'id' => $noticia->id,
            'titulo' => $noticia->titulo,
            'resumen' => $this->htmlSanitizer->sanitize($noticia->resumen),
            'contenido' => $this->htmlSanitizer->sanitize($noticia->contenido),
            'imagen_url' => $noticia->imagen
                ? url(Storage::disk('public')->url($noticia->imagen))
                : null,
            'publicada_en' => $noticia->created_at?->toIso8601String(),
            'url' => route('noticias.publicas.show', $noticia),
        ];
    }

    private function descripcionMeta(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return Str::limit(trim($text), 160, '…');
    }
}
