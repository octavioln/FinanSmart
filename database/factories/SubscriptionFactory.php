<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'account_id' => Account::factory(),
            'category_id' => Category::factory(),
            'payment_day' => $this->faker->numberBetween(1, 28),
            'amount' => $this->faker->randomFloat(2, 5, 200),
        ];
    }
}
