<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Es idempotente: usa el nombre como clave natural y no sobrescribe saldos.
     */
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Efectivo',
                'description' => 'Dinero en mano',
                'is_bank_account' => false,
                'is_cash' => true,
                'is_savings' => false,
                'allows_purchases' => true,
                'allows_withdrawal' => false,
                'allows_deposits' => true,
                'allows_transfers' => false,
            ],
            [
                'name' => 'Nómina',
                'description' => 'Tarjeta de nómina',
                'is_bank_account' => true,
                'is_cash' => false,
                'is_savings' => false,
                'allows_purchases' => true,
                'allows_withdrawal' => true,
                'allows_deposits' => true,
                'allows_transfers' => true,
            ],
            [
                'name' => 'Ahorro',
                'description' => 'Cuenta de ahorros',
                'is_bank_account' => true,
                'is_cash' => false,
                'is_savings' => true,
                'allows_purchases' => false,
                'allows_withdrawal' => false,
                'allows_deposits' => true,
                'allows_transfers' => true,
            ],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(['name' => $account['name']], $account);
        }
    }
}
