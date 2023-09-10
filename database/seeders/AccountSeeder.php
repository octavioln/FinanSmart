<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Account::create([
            "name" => 'Efectivo',
            "description" => "Dinero en mano",
        ]);

        Account::create([
            "name" => 'Nómina',
            "description" => "Tarjeta de nómina",
        ]);

        Account::create([
            "name" => 'Ahorro',
            "description" => "Cuenta de ahorros",
        ]);
    }
}
