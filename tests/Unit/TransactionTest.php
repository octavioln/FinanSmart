<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_transaction_with_relationships(): void
    {
        $account = Account::factory()->create(['name' => 'Nómina']);
        $category = Category::factory()->create(['name' => 'Comida']);

        $transaction = Transaction::create([
            'description' => 'Supermercado semanal',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'Cargo',
            'effective_date' => '2026-11-01',
            'amount' => 450.75,
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'description' => 'Supermercado semanal',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'Cargo',
            'amount' => 450.75,
        ]);
    }

    public function test_get_all_returns_paginated_transactions_with_account_and_category_names(): void
    {
        $account = Account::factory()->create(['name' => 'Tarjeta Débito']);
        $category = Category::factory()->create(['name' => 'Entretenimiento']);

        Transaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'description' => 'Boleto de cine',
        ]);

        $results = Transaction::getAll('', 'id', 'DESC');

        $this->assertCount(1, $results);
        $firstItem = $results->first();
        $this->assertEquals('Boleto de cine', $firstItem->description);
        $this->assertEquals('Tarjeta Débito', $firstItem->account_name);
        $this->assertEquals('Entretenimiento', $firstItem->category_name);
    }

    public function test_get_all_filters_by_search_term(): void
    {
        $account = Account::factory()->create(['name' => 'Cuenta Ahorro']);
        $category = Category::factory()->create(['name' => 'Salud']);

        Transaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'description' => 'Consulta dental',
        ]);

        Transaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'description' => 'Gasolina',
        ]);

        $results = Transaction::getAll('dental', 'id', 'DESC');

        $this->assertCount(1, $results);
        $this->assertEquals('Consulta dental', $results->first()->description);
    }

    public function test_get_all_excludes_soft_deleted_transactions(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->create();

        $active = Transaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'description' => 'Activa',
        ]);

        $deleted = Transaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'description' => 'Eliminada',
        ]);
        $deleted->delete();

        $results = Transaction::getAll('', 'id', 'DESC');

        $this->assertCount(1, $results);
        $this->assertEquals('Activa', $results->first()->description);
    }

    public function test_get_most_recent_returns_latest_transactions_paginated(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->create();

        for ($i = 1; $i <= 7; $i++) {
            Transaction::factory()->create([
                'account_id' => $account->id,
                'category_id' => $category->id,
                'description' => 'Transacción ' . $i,
            ]);
        }

        $recent = Transaction::getMostRecent();

        // getMostRecent usa paginate(5)
        $this->assertCount(5, $recent);
        $this->assertEquals('Transacción 7', $recent->first()->description);
    }
}
