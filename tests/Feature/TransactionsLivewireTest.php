<?php

namespace Tests\Feature;

use App\Http\Livewire\Transactions;
use App\Models\Account;
use App\Models\Category;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionsLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $cashAccount;       // id 1: Efectivo

    protected Account $checkingAccount;   // id 2: Nómina

    protected Account $savingsAccount;    // id 3: Ahorro

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Ejecutar seeders oficiales de la aplicación
        $this->seed(AccountSeeder::class);
        $this->seed(CategorySeeder::class);

        $this->cashAccount = Account::find(1);
        $this->cashAccount->update(['available_amount' => 500.00]);

        $this->checkingAccount = Account::find(2);
        $this->checkingAccount->update(['available_amount' => 5000.00]);

        $this->savingsAccount = Account::find(3);
        $this->savingsAccount->update(['available_amount' => 2000.00]);

        $this->category = Category::find(1);
    }

    public function test_guest_is_redirected_when_visiting_transactions_route(): void
    {
        $response = $this->get('/transactions');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_transactions_route(): void
    {
        $response = $this->actingAs($this->user)->get('/transactions');

        $response->assertStatus(200);
    }

    public function test_transactions_component_renders_correctly(): void
    {
        Transaction::factory()->create([
            'description' => 'Compra en tienda',
            'account_id' => $this->checkingAccount->id,
            'category_id' => $this->category->id,
            'amount' => 120.00,
        ]);

        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->assertStatus(200)
            ->assertSee('Compra en tienda')
            ->assertSee('Nómina');
    }

    public function test_validation_fails_when_creating_transaction_without_fields(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->call('save')
            ->assertHasErrors(['description', 'account_id', 'category_id', 'effective_date', 'amount']);
    }

    public function test_can_create_standard_transaction_and_decreases_account_balance(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('description', 'Pago de servicios')
            ->set('account_id', $this->checkingAccount->id)
            ->set('category_id', $this->category->id)
            ->set('effective_date', '2026-11-02')
            ->set('amount', 500.00)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('transactions', [
            'description' => 'Pago de servicios',
            'account_id' => $this->checkingAccount->id,
            'category_id' => $this->category->id,
            'type' => 'Cargo',
            'amount' => 500.00,
        ]);

        // 5000.00 - 500.00 = 4500.00
        $this->assertEquals(4500.00, $this->checkingAccount->fresh()->available_amount);
    }

    public function test_can_update_transaction_and_adjust_balance_on_same_account(): void
    {
        $transaction = Transaction::create([
            'description' => 'Cena familiar',
            'account_id' => $this->checkingAccount->id,
            'category_id' => $this->category->id,
            'type' => 'Cargo',
            'effective_date' => '2026-11-02',
            'amount' => 200.00,
        ]);
        $this->checkingAccount->updateAvailableAmount('Cargo', 200.00); // Saldo: 4800.00

        $this->actingAs($this->user);

        // Editamos el monto a 300.00 (100.00 adicionales de cargo)
        Livewire::test(Transactions::class)
            ->call('edit', $transaction->id)
            ->set('amount', 300.00)
            ->call('update')
            ->assertHasNoErrors();

        $this->assertEquals(300.00, $transaction->fresh()->amount);
        // Saldo anterior 4800.00 - 100.00 diferencia = 4700.00
        $this->assertEquals(4700.00, $this->checkingAccount->fresh()->available_amount);
    }

    public function test_can_update_transaction_moving_to_different_account(): void
    {
        $transaction = Transaction::create([
            'description' => 'Gasto de viaje',
            'account_id' => $this->checkingAccount->id,
            'category_id' => $this->category->id,
            'type' => 'Cargo',
            'effective_date' => '2026-11-02',
            'amount' => 200.00,
        ]);
        $this->checkingAccount->updateAvailableAmount('Cargo', 200.00); // Saldo Nómina: 4800.00

        $this->actingAs($this->user);

        // Cambiamos a la cuenta de efectivo y cambiamos monto a 100.00
        Livewire::test(Transactions::class)
            ->call('edit', $transaction->id)
            ->set('account_id', $this->cashAccount->id)
            ->set('amount', 100.00)
            ->call('update')
            ->assertHasNoErrors();

        // Cuenta anterior (Nómina) debe reembolsarse: 4800.00 + 200.00 = 5000.00
        $this->assertEquals(5000.00, $this->checkingAccount->fresh()->available_amount);

        // Nueva cuenta (Efectivo) debe debitarse: 500.00 - 100.00 = 400.00
        $this->assertEquals(400.00, $this->cashAccount->fresh()->available_amount);
    }

    public function test_can_save_atm_withdrawal(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('account_id', $this->checkingAccount->id)
            ->set('amount', 1000.00)
            ->set('effective_date', '2026-11-02')
            ->call('saveWithdraw')
            ->assertHasNoErrors();

        // Se deben registrar dos transacciones: retiro de Nómina y entrada en Efectivo
        $this->assertDatabaseHas('transactions', [
            'account_id' => $this->checkingAccount->id,
            'type' => 'Cargo',
            'amount' => 1000.00,
        ]);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $this->cashAccount->id,
            'type' => 'Abono',
            'amount' => 1000.00,
        ]);

        // Saldos actualizados
        $this->assertEquals(4000.00, $this->checkingAccount->fresh()->available_amount);
        $this->assertEquals(1500.00, $this->cashAccount->fresh()->available_amount);
    }

    public function test_can_save_deposit(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('account_id', $this->checkingAccount->id)
            ->set('amount', 1500.00)
            ->set('effective_date', '2026-11-02')
            ->call('saveDeposit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('transactions', [
            'account_id' => $this->checkingAccount->id,
            'type' => 'Abono',
            'amount' => 1500.00,
            'category_id' => 19,
        ]);

        // Saldo Nómina: 5000.00 + 1500.00 = 6500.00
        $this->assertEquals(6500.00, $this->checkingAccount->fresh()->available_amount);
    }

    public function test_can_save_savings_transfer(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('account_id', $this->checkingAccount->id)
            ->set('amount', 800.00)
            ->set('effective_date', '2026-11-02')
            ->call('saveSavings')
            ->assertHasNoErrors();

        // 5000.00 - 800.00 = 4200.00
        $this->assertEquals(4200.00, $this->checkingAccount->fresh()->available_amount);
        // 2000.00 + 800.00 = 2800.00
        $this->assertEquals(2800.00, $this->savingsAccount->fresh()->available_amount);
    }

    public function test_can_save_account_transfer(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('from_account_id', $this->checkingAccount->id)
            ->set('to_account_id', $this->cashAccount->id)
            ->set('amount', 300.00)
            ->set('effective_date', '2026-11-02')
            ->call('saveTransfer')
            ->assertHasNoErrors();

        // Origen: 5000.00 - 300.00 = 4700.00
        $this->assertEquals(4700.00, $this->checkingAccount->fresh()->available_amount);
        // Destino: 500.00 + 300.00 = 800.00
        $this->assertEquals(800.00, $this->cashAccount->fresh()->available_amount);
    }

    public function test_can_pay_subscription_transaction(): void
    {
        $subscription = Subscription::create([
            'name' => 'Netflix Premium',
            'account_id' => $this->checkingAccount->id,
            'category_id' => $this->category->id,
            'payment_day' => 15,
            'amount' => 299.00,
        ]);

        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('subscription_id', $subscription->id)
            ->set('effective_date', '2026-11-02')
            ->call('saveSubscription')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('transactions', [
            'description' => 'Netflix Premium',
            'account_id' => $this->checkingAccount->id,
            'type' => 'Cargo',
            'amount' => 299.00,
        ]);

        // 5000.00 - 299.00 = 4701.00
        $this->assertEquals(4701.00, $this->checkingAccount->fresh()->available_amount);
    }

    public function test_destroy_soft_deletes_transaction_and_restores_balance(): void
    {
        $transaction = Transaction::create([
            'description' => 'Compra cancelada',
            'account_id' => $this->checkingAccount->id,
            'category_id' => $this->category->id,
            'type' => 'Cargo',
            'effective_date' => '2026-11-02',
            'amount' => 400.00,
        ]);
        $this->checkingAccount->updateAvailableAmount('Cargo', 400.00); // Saldo: 4600.00

        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('selected_id', $transaction->id)
            ->call('destroy');

        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);

        // Se revierte el Cargo a Abono: 4600.00 + 400.00 = 5000.00
        $this->assertEquals(5000.00, $this->checkingAccount->fresh()->available_amount);
    }

    public function test_can_update_abono_and_adjust_balance_on_same_account(): void
    {
        $transaction = Transaction::create([
            'description' => 'Reembolso de compra',
            'account_id' => $this->checkingAccount->id,
            'category_id' => $this->category->id,
            'type' => 'Abono',
            'effective_date' => '2026-11-02',
            'amount' => 200.00,
        ]);
        $this->checkingAccount->updateAvailableAmount('Abono', 200.00); // Saldo: 5200.00

        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->call('edit', $transaction->id)
            ->set('amount', 300.00)
            ->call('update')
            ->assertHasNoErrors();

        $this->assertEquals(300.00, $transaction->fresh()->amount);
        // 5200.00 - 200.00 (revertir Abono) + 300.00 (nuevo Abono) = 5300.00
        $this->assertEquals(5300.00, $this->checkingAccount->fresh()->available_amount);
    }

    public function test_can_update_abono_moving_to_different_account(): void
    {
        $transaction = Transaction::create([
            'description' => 'Reembolso de viaje',
            'account_id' => $this->checkingAccount->id,
            'category_id' => $this->category->id,
            'type' => 'Abono',
            'effective_date' => '2026-11-02',
            'amount' => 200.00,
        ]);
        $this->checkingAccount->updateAvailableAmount('Abono', 200.00); // Saldo Nómina: 5200.00

        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->call('edit', $transaction->id)
            ->set('account_id', $this->cashAccount->id)
            ->set('amount', 100.00)
            ->call('update')
            ->assertHasNoErrors();

        // Cuenta anterior: 5200.00 - 200.00 = 5000.00
        $this->assertEquals(5000.00, $this->checkingAccount->fresh()->available_amount);

        // Nueva cuenta: 500.00 + 100.00 = 600.00
        $this->assertEquals(600.00, $this->cashAccount->fresh()->available_amount);
    }

    public function test_destroy_abono_restores_balance(): void
    {
        $transaction = Transaction::create([
            'description' => 'Reembolso cancelado',
            'account_id' => $this->checkingAccount->id,
            'category_id' => $this->category->id,
            'type' => 'Abono',
            'effective_date' => '2026-11-02',
            'amount' => 400.00,
        ]);
        $this->checkingAccount->updateAvailableAmount('Abono', 400.00); // Saldo: 5400.00

        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('selected_id', $transaction->id)
            ->call('destroy');

        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);

        // Se revierte el Abono: 5400.00 - 400.00 = 5000.00
        $this->assertEquals(5000.00, $this->checkingAccount->fresh()->available_amount);
    }

    public function test_atm_withdrawal_creates_a_transaction_group(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('account_id', $this->checkingAccount->id)
            ->set('amount', 1000.00)
            ->set('effective_date', '2026-11-02')
            ->call('saveWithdraw')
            ->assertHasNoErrors();

        $rows = Transaction::where('operation_type', 'withdrawal')->get();

        $this->assertCount(2, $rows);
        $this->assertNotNull($rows->first()->transaction_group_id);
        $this->assertEquals(
            $rows->first()->transaction_group_id,
            $rows->last()->transaction_group_id
        );
    }

    public function test_updating_a_grouped_operation_updates_both_legs_and_balances(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('account_id', $this->checkingAccount->id)
            ->set('amount', 1000.00)
            ->set('effective_date', '2026-11-02')
            ->call('saveWithdraw')
            ->assertHasNoErrors();

        $rows = Transaction::where('operation_type', 'withdrawal')->orderBy('id')->get();
        $cargo = $rows->firstWhere('type', 'Cargo');

        Livewire::test(Transactions::class)
            ->call('edit', $cargo->id)
            ->assertSet('editing_group', true)
            ->set('amount', 400.00)
            ->call('update')
            ->assertHasNoErrors();

        $rows = Transaction::where('operation_type', 'withdrawal')->orderBy('id')->get();

        $this->assertCount(2, $rows);
        $this->assertEquals(400.00, (float) $rows->first()->amount);
        $this->assertEquals(400.00, (float) $rows->last()->amount);

        // Nómina: 5000.00 - 400.00 = 4600.00
        $this->assertEquals(4600.00, $this->checkingAccount->fresh()->available_amount);

        // Efectivo: 500.00 + 400.00 = 900.00
        $this->assertEquals(900.00, $this->cashAccount->fresh()->available_amount);
    }

    public function test_updating_a_grouped_transfer_updates_both_legs_and_balances(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('from_account_id', $this->checkingAccount->id)
            ->set('to_account_id', $this->cashAccount->id)
            ->set('amount', 300.00)
            ->set('effective_date', '2026-11-02')
            ->call('saveTransfer')
            ->assertHasNoErrors();

        $cargo = Transaction::where('operation_type', 'transfer')
            ->where('type', 'Cargo')
            ->first();

        Livewire::test(Transactions::class)
            ->call('edit', $cargo->id)
            ->assertSet('editing_group', true)
            ->set('amount', 100.00)
            ->call('update')
            ->assertHasNoErrors();

        // Nómina: 5000.00 - 100.00 = 4900.00
        $this->assertEquals(4900.00, $this->checkingAccount->fresh()->available_amount);

        // Efectivo: 500.00 + 100.00 = 600.00
        $this->assertEquals(600.00, $this->cashAccount->fresh()->available_amount);
    }

    public function test_deleting_one_leg_of_a_group_deletes_the_whole_operation(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('account_id', $this->checkingAccount->id)
            ->set('amount', 1000.00)
            ->set('effective_date', '2026-11-02')
            ->call('saveWithdraw')
            ->assertHasNoErrors();

        $rows = Transaction::where('operation_type', 'withdrawal')->orderBy('id')->get();
        $cargo = $rows->firstWhere('type', 'Cargo');

        Livewire::test(Transactions::class)
            ->set('selected_id', $cargo->id)
            ->call('destroy');

        $this->assertSoftDeleted('transactions', ['id' => $rows->first()->id]);
        $this->assertSoftDeleted('transactions', ['id' => $rows->last()->id]);

        // Ambos saldos vuelven a su valor inicial.
        $this->assertEquals(5000.00, $this->checkingAccount->fresh()->available_amount);
        $this->assertEquals(500.00, $this->cashAccount->fresh()->available_amount);
    }

    public function test_rejects_zero_and_negative_amounts(): void
    {
        $this->actingAs($this->user);

        foreach ([0, -10] as $amount) {
            Livewire::test(Transactions::class)
                ->set('description', 'Compra inválida')
                ->set('account_id', $this->checkingAccount->id)
                ->set('category_id', $this->category->id)
                ->set('effective_date', '2026-11-02')
                ->set('amount', $amount)
                ->call('save')
                ->assertHasErrors(['amount']);
        }

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_rejects_non_numeric_amount(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('description', 'Compra inválida')
            ->set('account_id', $this->checkingAccount->id)
            ->set('category_id', $this->category->id)
            ->set('effective_date', '2026-11-02')
            ->set('amount', 'abc')
            ->call('save')
            ->assertHasErrors(['amount']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_rejects_nonexistent_account_and_category(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('description', 'Compra inválida')
            ->set('account_id', 99999)
            ->set('category_id', 99999)
            ->set('effective_date', '2026-11-02')
            ->set('amount', 100.00)
            ->call('save')
            ->assertHasErrors(['account_id', 'category_id']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_rejects_internal_categories_for_purchases(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('description', 'Compra inválida')
            ->set('account_id', $this->checkingAccount->id)
            ->set('category_id', 18)
            ->set('effective_date', '2026-11-02')
            ->set('amount', 100.00)
            ->call('save')
            ->assertHasErrors(['category_id']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_rejects_transfer_to_the_same_account(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('from_account_id', $this->checkingAccount->id)
            ->set('to_account_id', $this->checkingAccount->id)
            ->set('amount', 100.00)
            ->set('effective_date', '2026-11-02')
            ->call('saveTransfer')
            ->assertHasErrors(['from_account_id']);
    }

    public function test_rejects_transfer_with_insufficient_funds(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('from_account_id', $this->checkingAccount->id)
            ->set('to_account_id', $this->cashAccount->id)
            ->set('amount', 6000.00)
            ->set('effective_date', '2026-11-02')
            ->call('saveTransfer')
            ->assertHasErrors(['amount']);

        $this->assertEquals(5000.00, $this->checkingAccount->fresh()->available_amount);
    }

    public function test_rejects_withdrawal_with_insufficient_funds(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('account_id', $this->checkingAccount->id)
            ->set('amount', 6000.00)
            ->set('effective_date', '2026-11-02')
            ->call('saveWithdraw')
            ->assertHasErrors(['amount']);

        $this->assertEquals(5000.00, $this->checkingAccount->fresh()->available_amount);
        $this->assertEquals(500.00, $this->cashAccount->fresh()->available_amount);
    }

    public function test_rejects_withdrawal_from_account_that_does_not_allow_it(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('account_id', $this->cashAccount->id)
            ->set('amount', 100.00)
            ->set('effective_date', '2026-11-02')
            ->call('saveWithdraw')
            ->assertHasErrors(['account_id']);
    }

    public function test_rejects_purchase_that_would_leave_negative_balance(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('description', 'Compra sin fondos')
            ->set('account_id', $this->checkingAccount->id)
            ->set('category_id', $this->category->id)
            ->set('effective_date', '2026-11-02')
            ->set('amount', 6000.00)
            ->call('save')
            ->assertHasErrors(['amount']);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertEquals(5000.00, $this->checkingAccount->fresh()->available_amount);
    }

    public function test_allows_purchase_for_the_full_available_balance(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('description', 'Compra por todo el saldo')
            ->set('account_id', $this->checkingAccount->id)
            ->set('category_id', $this->category->id)
            ->set('effective_date', '2026-11-02')
            ->set('amount', 5000.00)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(0.00, $this->checkingAccount->fresh()->available_amount);
    }

    public function test_rejects_purchase_update_that_would_leave_negative_balance(): void
    {
        $transaction = Transaction::create([
            'description' => 'Compra original',
            'account_id' => $this->checkingAccount->id,
            'category_id' => $this->category->id,
            'type' => 'Cargo',
            'effective_date' => '2026-11-02',
            'amount' => 200.00,
        ]);
        $this->checkingAccount->updateAvailableAmount('Cargo', 200.00); // Saldo: 4800.00

        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->call('edit', $transaction->id)
            ->set('amount', 6000.00)
            ->call('update')
            ->assertHasErrors(['amount']);

        $this->assertEquals(200.00, $transaction->fresh()->amount);
        $this->assertEquals(4800.00, $this->checkingAccount->fresh()->available_amount);
    }

    public function test_rejects_purchase_update_when_destination_account_has_insufficient_funds(): void
    {
        $transaction = Transaction::create([
            'description' => 'Compra original',
            'account_id' => $this->checkingAccount->id,
            'category_id' => $this->category->id,
            'type' => 'Cargo',
            'effective_date' => '2026-11-02',
            'amount' => 200.00,
        ]);
        $this->checkingAccount->updateAvailableAmount('Cargo', 200.00); // Saldo: 4800.00

        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->call('edit', $transaction->id)
            ->set('account_id', $this->cashAccount->id)
            ->set('amount', 600.00)
            ->call('update')
            ->assertHasErrors(['amount']);

        $this->assertEquals($this->checkingAccount->id, $transaction->fresh()->account_id);
        $this->assertEquals(4800.00, $this->checkingAccount->fresh()->available_amount);
        $this->assertEquals(500.00, $this->cashAccount->fresh()->available_amount);
    }

    public function test_rejects_subscription_payment_that_would_leave_negative_balance(): void
    {
        $subscription = Subscription::create([
            'name' => 'Suscripción cara',
            'account_id' => $this->checkingAccount->id,
            'category_id' => $this->category->id,
            'payment_day' => 15,
            'amount' => 6000.00,
        ]);

        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('subscription_id', $subscription->id)
            ->set('effective_date', '2026-11-02')
            ->call('saveSubscription')
            ->assertHasErrors(['subscription_id']);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertEquals(5000.00, $this->checkingAccount->fresh()->available_amount);
    }

    public function test_rejects_grouped_update_that_would_leave_negative_balance(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('account_id', $this->checkingAccount->id)
            ->set('amount', 1000.00)
            ->set('effective_date', '2026-11-02')
            ->call('saveWithdraw')
            ->assertHasNoErrors();

        $cargo = Transaction::where('operation_type', 'withdrawal')
            ->where('type', 'Cargo')
            ->first();

        Livewire::test(Transactions::class)
            ->call('edit', $cargo->id)
            ->set('amount', 6000.00)
            ->call('update')
            ->assertHasErrors(['amount']);

        $rows = Transaction::where('operation_type', 'withdrawal')->orderBy('id')->get();

        $this->assertCount(2, $rows);
        $this->assertEquals(1000.00, (float) $rows->first()->amount);
        $this->assertEquals(1000.00, (float) $rows->last()->amount);
        $this->assertEquals(4000.00, $this->checkingAccount->fresh()->available_amount);
        $this->assertEquals(1500.00, $this->cashAccount->fresh()->available_amount);
    }

    public function test_withdrawal_uses_the_configured_cash_account_instead_of_id_one(): void
    {
        $this->cashAccount->update(['is_cash' => false]);

        $newCash = Account::create([
            'name' => 'Caja nueva',
            'description' => 'Efectivo nuevo',
            'opening_balance' => 100.00,
            'available_amount' => 100.00,
            'is_cash' => true,
            'is_active' => true,
            'allows_purchases' => true,
            'allows_deposits' => true,
        ]);

        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('account_id', $this->checkingAccount->id)
            ->set('amount', 200.00)
            ->set('effective_date', '2026-11-02')
            ->call('saveWithdraw')
            ->assertHasNoErrors();

        $this->assertEquals(300.00, (float) $newCash->fresh()->available_amount);
        $this->assertEquals(500.00, (float) $this->cashAccount->fresh()->available_amount);
    }

    public function test_savings_uses_the_configured_savings_account_instead_of_id_three(): void
    {
        $this->savingsAccount->update(['is_savings' => false]);

        $newSavings = Account::create([
            'name' => 'Ahorro nuevo',
            'description' => 'Ahorro nuevo',
            'opening_balance' => 0.00,
            'available_amount' => 0.00,
            'is_savings' => true,
            'is_active' => true,
            'allows_deposits' => true,
            'allows_transfers' => true,
        ]);

        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('account_id', $this->checkingAccount->id)
            ->set('amount', 800.00)
            ->set('effective_date', '2026-11-02')
            ->call('saveSavings')
            ->assertHasNoErrors();

        $this->assertEquals(800.00, (float) $newSavings->fresh()->available_amount);
        $this->assertEquals(2000.00, (float) $this->savingsAccount->fresh()->available_amount);
    }

    public function test_can_update_a_deposit_without_changing_its_internal_category(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('account_id', $this->checkingAccount->id)
            ->set('amount', 1000.00)
            ->set('effective_date', '2026-11-02')
            ->call('saveDeposit')
            ->assertHasNoErrors();

        $deposit = Transaction::where('operation_type', Transaction::OPERATION_DEPOSIT)->firstOrFail();

        Livewire::test(Transactions::class)
            ->call('edit', $deposit->id)
            ->set('amount', 1200.00)
            ->call('update')
            ->assertHasNoErrors();

        $this->assertEquals(1200.00, (float) $deposit->fresh()->amount);
        $this->assertEquals(
            Category::where('system_key', Category::SYSTEM_DEPOSIT)->value('id'),
            $deposit->fresh()->category_id
        );
        $this->assertEquals(6200.00, (float) $this->checkingAccount->fresh()->available_amount);
    }

    public function test_can_change_sort_order(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->assertSet('sort', 'id')
            ->assertSet('direction', 'DESC')
            ->call('order', 'description')
            ->assertSet('sort', 'description')
            ->assertSet('direction', 'ASC')
            ->call('order', 'description')
            ->assertSet('direction', 'DESC');
    }
}
