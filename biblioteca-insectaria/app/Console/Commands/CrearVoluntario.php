<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CrearVoluntario extends Command
{
    protected $signature = 'biblioteca:crear-voluntario {--sql : Genera un INSERT para phpMyAdmin sin crear la cuenta localmente}';

    protected $description = 'Crea una cuenta de acceso para una persona voluntaria.';

    public function handle(): int
    {
        $datos = [
            'name' => $this->ask('Nombre'),
            'password' => $this->secret('Contraseña provisional (mínimo 12 caracteres)'),
        ];

        $validator = Validator::make($datos, [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Password::min(12)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $usuario = $validator->validated();

        if ($this->option('sql')) {
            $quote = static fn (string $value): string => "'".str_replace("'", "''", $value)."'";
            $hash = Hash::make($usuario['password']);

            $this->line('INSERT INTO `users` (`name`, `email`, `password`, `must_change_password`)');
            $this->line('VALUES (');
            $this->line('    '.$quote($usuario['name']).',');
            $this->line('    NULL,');
            $this->line('    '.$quote($hash).',');
            $this->line('    1');
            $this->line(');');
            $this->info('No se creó una cuenta en esta base local. Ejecuta el INSERT en phpMyAdmin.');

            return self::SUCCESS;
        }

        User::query()->create([
            'name' => $usuario['name'],
            'password' => $usuario['password'],
            'role' => User::ROLE_VOLUNTARIO,
            'activo' => true,
            'must_change_password' => true,
        ]);
        $this->info('Cuenta voluntaria creada.');

        return self::SUCCESS;
    }
}
