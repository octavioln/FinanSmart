<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        $available = $this->faker->randomFloat(2, 500, 10000);

        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'available_amount' => $available,
            'opening_balance' => $available,
            'is_bank_account' => $this->faker->boolean(),
            'is_cash' => false,
            'is_savings' => false,
            'is_active' => true,
            'allows_purchases' => true,
            'allows_withdrawal' => true,
            'allows_deposits' => true,
            'allows_transfers' => true,
        ];
    }
}
