<?php

namespace Tests\Feature;

use App\Http\Livewire\Subscriptions;
use App\Models\Account;
use App\Models\Category;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubscriptionsLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = Account::factory()->create([
            'name' => 'Tarjeta Débito',
            'allows_purchases' => true,
        ]);
        $this->category = Category::factory()->create([
            'id' => 1,
            'name' => 'Servicios',
        ]);
    }

    public function test_guest_is_redirected_when_visiting_subscriptions_route(): void
    {
        $response = $this->get('/subscriptions');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_subscriptions_route(): void
    {
        $response = $this->actingAs($this->user)->get('/subscriptions');

        $response->assertStatus(200);
    }

    public function test_subscriptions_component_renders_correctly(): void
    {
        Subscription::factory()->create([
            'name' => 'Servicio de Internet',
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 599.00,
        ]);

        $this->actingAs($this->user);

        Livewire::test(Subscriptions::class)
            ->assertStatus(200)
            ->assertSee('Servicio de Internet')
            ->assertSee('Tarjeta Débito');
    }

    public function test_validation_fails_when_creating_subscription_without_required_fields(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Subscriptions::class)
            ->call('save')
            ->assertHasErrors(['name', 'account_id', 'category_id', 'payment_day', 'amount']);
    }

    public function test_can_create_new_subscription(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Subscriptions::class)
            ->set('name', 'Amazon Prime')
            ->set('account_id', $this->account->id)
            ->set('category_id', $this->category->id)
            ->set('payment_day', 10)
            ->set('amount', 99.00)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('subscriptions', [
            'name' => 'Amazon Prime',
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'payment_day' => 10,
            'amount' => 99.00,
        ]);
    }

    public function test_can_load_subscription_into_edit_modal(): void
    {
        $subscription = Subscription::factory()->create([
            'name' => 'Disney Plus',
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'payment_day' => 5,
            'amount' => 159.00,
        ]);

        $this->actingAs($this->user);

        Livewire::test(Subscriptions::class)
            ->call('edit', $subscription->id)
            ->assertSet('name', 'Disney Plus')
            ->assertSet('account_id', $this->account->id)
            ->assertSet('payment_day', 5)
            ->assertSet('amount', '159.00')
            ->assertSet('selected_id', $subscription->id)
            ->assertSet('open', true);
    }

    public function test_can_update_existing_subscription(): void
    {
        $subscription = Subscription::factory()->create([
            'name' => 'Gimnasio',
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'payment_day' => 1,
            'amount' => 400.00,
        ]);

        $this->actingAs($this->user);

        Livewire::test(Subscriptions::class)
            ->call('edit', $subscription->id)
            ->set('name', 'Gimnasio Plus')
            ->set('amount', 500.00)
            ->call('update')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'name' => 'Gimnasio Plus',
            'amount' => 500.00,
        ]);
    }

    public function test_can_delete_subscription(): void
    {
        $subscription = Subscription::factory()->create([
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test(Subscriptions::class)
            ->set('selected_id', $subscription->id)
            ->call('destroy');

        $this->assertSoftDeleted('subscriptions', [
            'id' => $subscription->id,
        ]);
    }

    public function test_can_change_sort_order(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Subscriptions::class)
            ->assertSet('sort', 'id')
            ->assertSet('direction', 'DESC')
            ->call('order', 'name')
            ->assertSet('sort', 'name')
            ->assertSet('direction', 'ASC')
            ->call('order', 'name')
            ->assertSet('direction', 'DESC');
    }
}
