<?php

namespace App\Support;

class AutorLibro
{
    public static function normalizar(?string $autor): ?string
    {
        $autor = trim((string) $autor);

        if ($autor === '' || $autor === '-') {
            return null;
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/D', $autor, $fecha) === 1
            && checkdate((int) $fecha[2], (int) $fecha[1], (int) $fecha[3])) {
            return null;
        }

        return $autor;
    }
}
