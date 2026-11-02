<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'available_amount' => $this->faker->randomFloat(2, 500, 10000),
            'is_bank_account' => $this->faker->boolean(),
            'allows_purchases' => true,
            'allows_withdrawal' => true,
            'allows_deposits' => true,
            'allows_transfers' => true,
        ];
    }
}
