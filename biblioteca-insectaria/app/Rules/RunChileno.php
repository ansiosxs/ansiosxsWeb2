<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RunChileno implements ValidationRule
{
    public static function normalizar(string $run): string
    {
        return strtoupper((string) preg_replace('/[.\-\s]/', '', $run));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $run = self::normalizar((string) $value);

        if (! preg_match('/^\d{7,8}[0-9K]$/', $run)) {
            $fail('Ingresa un RUN válido, por ejemplo 12345678-5.');

            return;
        }

        $body = strrev(substr($run, 0, -1));
        $sum = 0;

        for ($position = 0; $position < strlen($body); $position++) {
            $sum += (int) $body[$position] * (($position % 6) + 2);
        }

        $expected = match ($sum % 11) {
            0 => '0',
            1 => 'K',
            default => (string) (11 - ($sum % 11)),
        };

        if (substr($run, -1) !== $expected) {
            $fail('El dígito verificador del RUN no es correcto.');
        }
    }
}
