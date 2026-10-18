<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class TelefonoChileno
{
    public static function normalizar(string $telefono): string
    {
        $digitos = preg_replace('/\D/', '', $telefono);
        $digitos = preg_replace('/^(56)?/', '', $digitos);

        if (! preg_match('/^9\d{8}$/', $digitos)) {
            throw ValidationException::withMessages([
                'telefono' => 'Ingresa un celular chileno válido de 9 dígitos.',
            ]);
        }

        return '+56 9 '.substr($digitos, 1, 4).' '.substr($digitos, 5, 4);
    }
}
