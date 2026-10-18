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
            'email' => $this->ask('Correo electrónico'),
            'password' => $this->secret('Contraseña (mínimo 12 caracteres)'),
        ];

        $validator = Validator::make($datos, [
            'name' => ['required', 'string', 'max:255'],
            'email' => array_filter([
                'required',
                'email',
                'max:255',
                $this->option('sql') ? null : 'unique:users,email',
            ]),
            'password' => ['required', 'string', Password::min(12)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($this->option('sql')) {
            $quote = static fn (string $value): string => "'".str_replace("'", "''", $value)."'";
            $usuario = $validator->validated();
            $hash = Hash::make($usuario['password']);

            $this->line('INSERT INTO `users` (`name`, `email`, `password`) VALUES ('
                .$quote($usuario['name']).', '
                .$quote($usuario['email']).', '
                .$quote($hash).');');
            $this->info('No se creó una cuenta en esta base local. Ejecuta el INSERT en phpMyAdmin.');

            return self::SUCCESS;
        }

        User::query()->create($validator->validated());
        $this->info('Cuenta voluntaria creada.');

        return self::SUCCESS;
    }
}
