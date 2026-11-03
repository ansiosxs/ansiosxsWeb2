<?php

namespace App\Http\Controllers;

use App\Models\EnlacePublico;
use Illuminate\Http\JsonResponse;

class EnlacesPublicosController extends Controller
{
    public function index(): JsonResponse
    {
        $enlaces = EnlacePublico::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('id')
            ->get(['id', 'titulo', 'descripcion', 'seccion', 'url']);

        return response()->json(['data' => $enlaces]);
    }
}
