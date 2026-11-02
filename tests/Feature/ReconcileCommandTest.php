<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconcileCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconcile_reports_balanced_accounts(): void
    {
        $account = Account::create([
            'name' => 'Cuenta reconciliable',
            'description' => 'Prueba',
            'opening_balance' => 1000.00,
            'available_amount' => 1000.00,
            'is_active' => true,
        ]);

        Transaction::create([
            'description' => 'Compra',
            'account_id' => $account->id,
            'category_id' => $this->category()->id,
            'type' => Transaction::TYPE_CARGO,
            'effective_date' => '2026-11-02',
            'amount' => 250.00,
        ]);
        $account->applyTransactionEffect(Transaction::TYPE_CARGO, 250.00);

        $this->artisan('finance:reconcile')
            ->expectsOutputToContain('Todas las cuentas cuadran')
            ->assertExitCode(0);
    }

    public function test_reconcile_detects_and_fixes_a_difference(): void
    {
        $account = Account::create([
            'name' => 'Cuenta descuadrada',
            'description' => 'Prueba',
            'opening_balance' => 1000.00,
            'available_amount' => 1000.00,
            'is_active' => true,
        ]);

        Transaction::create([
            'description' => 'Compra',
            'account_id' => $account->id,
            'category_id' => $this->category()->id,
            'type' => Transaction::TYPE_CARGO,
            'effective_date' => '2026-11-02',
            'amount' => 250.00,
        ]);
        $account->applyTransactionEffect(Transaction::TYPE_CARGO, 250.00);

        $account->update(['available_amount' => 500.00]);

        $this->artisan('finance:reconcile')->assertExitCode(1);

        $this->assertEquals(500.00, (float) $account->fresh()->available_amount);

        $this->artisan('finance:reconcile', ['--fix' => true])->assertExitCode(0);

        $this->assertEquals(750.00, (float) $account->fresh()->available_amount);
    }

    private function category()
    {
        return Category::create([
            'name' => 'Categoría de prueba',
            'description' => 'Prueba',
        ]);
    }
}
