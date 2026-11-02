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
