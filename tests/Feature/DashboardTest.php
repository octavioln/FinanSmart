<?php

namespace Tests\Feature;

use App\Http\Livewire\Dashboard;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_visiting_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_dashboard_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_component_renders_accounts_and_transactions(): void
    {
        $user = User::factory()->create();

        $account = Account::factory()->create(['name' => 'Cuenta Principal']);
        $category = Category::factory()->create(['name' => 'Alimentos']);

        $transaction = Transaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'description' => 'Compra de despensa',
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertStatus(200)
            ->assertSee('Cuenta Principal')
            ->assertSee('Compra de despensa');
    }

    public function test_can_change_sort_order(): void
    {
        $user = User::factory()->create();

        $account = Account::factory()->create();
        $category = Category::factory()->create();

        Transaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSet('sort', 'id')
            ->assertSet('direction', 'DESC')
            ->call('order', 'amount')
            ->assertSet('sort', 'amount')
            ->assertSet('direction', 'ASC')
            ->call('order', 'amount')
            ->assertSet('direction', 'DESC')
            ->assertStatus(200);
    }
}
