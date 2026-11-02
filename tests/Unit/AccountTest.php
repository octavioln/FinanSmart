<?php

namespace Tests\Unit;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_account_with_all_attributes(): void
    {
        $account = Account::create([
            'name' => 'Cuenta BBVA',
            'description' => 'Cuenta principal de nómina',
            'available_amount' => 1500.50,
            'is_bank_account' => true,
            'allows_purchases' => true,
            'allows_withdrawal' => true,
            'allows_deposits' => true,
            'allows_transfers' => true,
        ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Cuenta BBVA',
            'available_amount' => 1500.50,
            'allows_purchases' => 1,
            'allows_withdrawal' => 1,
            'allows_deposits' => 1,
            'allows_transfers' => 1,
        ]);
    }

    public function test_update_available_amount_decreases_balance_on_cargo(): void
    {
        $account = Account::factory()->create([
            'available_amount' => 1000.00,
        ]);

        $account->updateAvailableAmount('Cargo', 250.00);

        $this->assertEquals(750.00, $account->fresh()->available_amount);
    }

    public function test_update_available_amount_increases_balance_on_abono(): void
    {
        $account = Account::factory()->create([
            'available_amount' => 500.00,
        ]);

        $account->updateAvailableAmount('Abono', 300.00);

        $this->assertEquals(800.00, $account->fresh()->available_amount);
    }

    public function test_update_available_amount_does_not_change_balance_on_unknown_type(): void
    {
        $account = Account::factory()->create([
            'available_amount' => 500.00,
        ]);

        $account->updateAvailableAmount('Invalido', 100.00);

        $this->assertEquals(500.00, $account->fresh()->available_amount);
    }

    public function test_account_supports_soft_deletes(): void
    {
        $account = Account::factory()->create();

        $account->delete();

        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }
}
