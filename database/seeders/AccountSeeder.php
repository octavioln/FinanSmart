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
            "is_bank_account" => 0,
            "allows_purchases" => 1,
            "allows_withdrawal" => 0,
            "allows_deposits" => 1,
            "allows_transfers" => 0,
        ]);

        Account::create([
            "name" => 'Nómina',
            "description" => "Tarjeta de nómina",
            "is_bank_account" => 1,
            "allows_purchases" => 1,
            "allows_withdrawal" => 1,
            "allows_deposits" => 1,
            "allows_transfers" => 1,
        ]);

        Account::create([
            "name" => 'Ahorro',
            "description" => "Cuenta de ahorros",
            "is_bank_account" => 1,
            "allows_purchases" => 0,
            "allows_withdrawal" => 0,
            "allows_deposits" => 1,
            "allows_transfers" => 1,
        ]);
    }
}
