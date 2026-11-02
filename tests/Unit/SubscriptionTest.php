<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Category;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_subscription(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->create();

        $subscription = Subscription::create([
            'name' => 'Spotify Familiar',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'payment_day' => 15,
            'amount' => 179.00,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'name' => 'Spotify Familiar',
            'payment_day' => 15,
            'amount' => 179.00,
        ]);
    }

    public function test_get_all_returns_paginated_subscriptions_with_relations(): void
    {
        $account = Account::factory()->create(['name' => 'Tarjeta Débito']);
        $category = Category::factory()->create(['name' => 'Streaming']);

        Subscription::factory()->create([
            'name' => 'Netflix',
            'account_id' => $account->id,
            'category_id' => $category->id,
        ]);

        $results = Subscription::getAll('', 'id', 'DESC');

        $this->assertCount(1, $results);
        $this->assertEquals('Netflix', $results->first()->name);
        $this->assertEquals('Tarjeta Débito', $results->first()->account_name);
        $this->assertEquals('Streaming', $results->first()->category_name);
    }

    public function test_get_all_filters_by_search_term(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->create();

        Subscription::factory()->create([
            'name' => 'Gimnasio SmartFit',
            'account_id' => $account->id,
            'category_id' => $category->id,
        ]);

        Subscription::factory()->create([
            'name' => 'iCloud Storage',
            'account_id' => $account->id,
            'category_id' => $category->id,
        ]);

        $results = Subscription::getAll('SmartFit', 'id', 'DESC');

        $this->assertCount(1, $results);
        $this->assertEquals('Gimnasio SmartFit', $results->first()->name);
    }

    public function test_subscription_supports_soft_deletes(): void
    {
        $subscription = Subscription::factory()->create();

        $subscription->delete();

        $this->assertSoftDeleted('subscriptions', ['id' => $subscription->id]);
    }
}
