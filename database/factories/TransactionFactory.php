<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'description' => $this->faker->sentence(3),
            'account_id' => Account::factory(),
            'category_id' => Category::factory(),
            'type' => $this->faker->randomElement(['Cargo', 'Abono']),
            'effective_date' => $this->faker->date(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}
