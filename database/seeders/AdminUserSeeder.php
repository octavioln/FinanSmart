<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea el usuario inicial cuando el registro público está deshabilitado.
     *
     * Uso:
     *   $env:ADMIN_EMAIL="tu@correo.com"
     *   $env:ADMIN_PASSWORD="una-contraseña-segura"
     *   php artisan db:seed --class=AdminUserSeeder
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('Define ADMIN_EMAIL y ADMIN_PASSWORD antes de ejecutar este seeder.');

            return;
        }

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrador'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        $this->command?->info('Usuario administrador creado o ya existente: '.$email);
    }
}
