<?php

namespace Tests\Feature;

use App\Http\Livewire\Accounts;
use App\Models\Account;
use App\Models\Category;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccountsLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->seed(AccountSeeder::class);
        $this->seed(CategorySeeder::class);
    }

    public function test_guest_is_redirected_when_visiting_accounts_route(): void
    {
        $this->get('/accounts')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_accounts_route(): void
    {
        $this->actingAs($this->user)->get('/accounts')->assertStatus(200);
    }

    public function test_can_create_account_with_opening_balance(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Accounts::class)
            ->set('name', 'Cuenta BBVA')
            ->set('description', 'Cuenta principal')
            ->set('opening_balance', 1200.50)
            ->set('allows_purchases', true)
            ->set('allows_deposits', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('accounts', [
            'name' => 'Cuenta BBVA',
            'opening_balance' => 1200.50,
            'available_amount' => 1200.50,
            'is_active' => true,
        ]);
    }

    public function test_rejects_duplicate_account_name(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Accounts::class)
            ->set('name', 'Nómina')
            ->set('description', 'Duplicada')
            ->set('opening_balance', 100)
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_updating_opening_balance_adjusts_available_amount(): void
    {
        $account = Account::create([
            'name' => 'Cuenta de prueba',
            'description' => 'Prueba',
            'opening_balance' => 1000.00,
            'available_amount' => 1000.00,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        Livewire::test(Accounts::class)
            ->call('edit', $account->id)
            ->set('opening_balance', 800.00)
            ->call('update')
            ->assertHasNoErrors();

        $this->assertEquals(800.00, (float) $account->fresh()->opening_balance);
        $this->assertEquals(800.00, (float) $account->fresh()->available_amount);
    }

    public function test_rejects_opening_balance_correction_that_would_leave_negative_balance(): void
    {
        $account = Account::create([
            'name' => 'Cuenta con gastos',
            'description' => 'Prueba',
            'opening_balance' => 1000.00,
            'available_amount' => 400.00,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        Livewire::test(Accounts::class)
            ->call('edit', $account->id)
            ->set('opening_balance', 300.00)
            ->call('update')
            ->assertHasErrors(['opening_balance']);

        $this->assertEquals(1000.00, (float) $account->fresh()->opening_balance);
        $this->assertEquals(400.00, (float) $account->fresh()->available_amount);
    }

    public function test_can_archive_and_restore_an_account(): void
    {
        $account = Account::create([
            'name' => 'Cuenta temporal',
            'description' => 'Prueba',
            'opening_balance' => 100.00,
            'available_amount' => 100.00,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        Livewire::test(Accounts::class)->call('archive', $account->id);

        $this->assertFalse($account->fresh()->is_active);

        Livewire::test(Accounts::class)->call('restore', $account->id);

        $this->assertTrue($account->fresh()->is_active);
    }

    public function test_cannot_archive_cash_or_savings_account(): void
    {
        $cash = Account::where('is_cash', true)->firstOrFail();
        $savings = Account::where('is_savings', true)->firstOrFail();

        $this->actingAs($this->user);

        Livewire::test(Accounts::class)->call('archive', $cash->id);
        Livewire::test(Accounts::class)->call('archive', $savings->id);

        $this->assertTrue($cash->fresh()->is_active);
        $this->assertTrue($savings->fresh()->is_active);
    }

    public function test_cannot_archive_account_with_subscriptions(): void
    {
        $account = Account::create([
            'name' => 'Cuenta con suscripción',
            'description' => 'Prueba',
            'opening_balance' => 100.00,
            'available_amount' => 100.00,
            'is_active' => true,
        ]);
        $category = Category::whereNull('system_key')->firstOrFail();

        Subscription::create([
            'name' => 'Suscripción',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'payment_day' => 1,
            'amount' => 50.00,
        ]);

        $this->actingAs($this->user);

        Livewire::test(Accounts::class)->call('archive', $account->id);

        $this->assertTrue($account->fresh()->is_active);
    }

    public function test_creating_a_new_cash_account_releases_the_previous_flag(): void
    {
        $previousCash = Account::where('is_cash', true)->firstOrFail();

        $this->actingAs($this->user);

        Livewire::test(Accounts::class)
            ->set('name', 'Efectivo nuevo')
            ->set('description', 'Caja nueva')
            ->set('opening_balance', 100.00)
            ->set('is_cash', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($previousCash->fresh()->is_cash);
        $this->assertTrue(Account::where('name', 'Efectivo nuevo')->firstOrFail()->is_cash);
    }
}
