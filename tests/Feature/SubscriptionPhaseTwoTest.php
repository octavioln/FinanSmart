<?php

namespace Tests\Feature;

use App\Http\Livewire\Dashboard;
use App\Http\Livewire\Subscriptions;
use App\Http\Livewire\Transactions;
use App\Models\Account;
use App\Models\Category;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class SubscriptionPhaseTwoTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-02 10:00:00');

        $this->user = User::factory()->create();
        $this->seed(AccountSeeder::class);
        $this->seed(CategorySeeder::class);

        $this->account = Account::query()->where('allows_purchases', true)->firstOrFail();
        $this->account->update([
            'opening_balance' => 1000.00,
            'available_amount' => 1000.00,
        ]);

        $this->category = Category::query()->whereNull('system_key')->firstOrFail();

        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_paying_a_subscription_links_the_transaction_and_updates_the_schedule(): void
    {
        $subscription = $this->subscription();

        Livewire::test(Transactions::class)
            ->set('subscription_id', $subscription->id)
            ->set('billing_period', '2026-11')
            ->set('effective_date', '2026-11-02')
            ->call('saveSubscription')
            ->assertHasNoErrors();

        $payment = Transaction::query()
            ->where('subscription_id', $subscription->id)
            ->firstOrFail();

        $this->assertEquals('2026-11', $payment->billing_period);
        $this->assertEquals(Transaction::OPERATION_SUBSCRIPTION, $payment->operation_type);
        $this->assertEquals(100.00, (float) $payment->amount);
        $this->assertEquals(900.00, (float) $this->account->fresh()->available_amount);

        $subscription->refresh();

        $this->assertEquals('2026-11-02', $subscription->last_paid_at->toDateString());
        $this->assertEquals('2026-12-15', $subscription->next_payment_date->toDateString());
        $this->assertSame(Subscription::STATUS_CURRENT, $subscription->scheduleStatus());
    }

    public function test_cannot_pay_the_same_subscription_period_twice(): void
    {
        $subscription = $this->subscription();

        $this->pay($subscription, '2026-11');

        Livewire::test(Transactions::class)
            ->set('subscription_id', $subscription->id)
            ->set('billing_period', '2026-11')
            ->set('effective_date', '2026-11-03')
            ->call('saveSubscription')
            ->assertHasErrors(['billing_period']);

        $this->assertSame(1, Transaction::query()->where('subscription_id', $subscription->id)->count());
        $this->assertEquals(900.00, (float) $this->account->fresh()->available_amount);
    }

    public function test_can_pay_the_next_period(): void
    {
        $subscription = $this->subscription();

        $this->pay($subscription, '2026-11');

        Livewire::test(Transactions::class)
            ->set('subscription_id', $subscription->id)
            ->set('billing_period', '2026-12')
            ->set('effective_date', '2026-12-02')
            ->call('saveSubscription')
            ->assertHasNoErrors();

        $subscription->refresh();

        $this->assertSame(2, $subscription->transactions()->count());
        $this->assertEquals('2026-12-02', $subscription->last_paid_at->toDateString());
        $this->assertEquals('2027-01-15', $subscription->next_payment_date->toDateString());
        $this->assertEquals(800.00, (float) $this->account->fresh()->available_amount);
    }

    public function test_deleting_a_payment_releases_the_period_and_recalculates_the_schedule(): void
    {
        $subscription = $this->subscription();

        $this->pay($subscription, '2026-11');

        $payment = Transaction::query()->where('subscription_id', $subscription->id)->firstOrFail();

        Livewire::test(Transactions::class)
            ->set('selected_id', $payment->id)
            ->call('destroy');

        $this->assertSoftDeleted('transactions', ['id' => $payment->id]);
        $this->assertNull($payment->fresh()->billing_period);

        $subscription->refresh();

        $this->assertNull($subscription->last_paid_at);
        $this->assertEquals('2026-11-15', $subscription->next_payment_date->toDateString());
        $this->assertEquals(1000.00, (float) $this->account->fresh()->available_amount);

        $this->pay($subscription, '2026-11');

        $this->assertSame(1, Transaction::query()->where('subscription_id', $subscription->id)->count());
    }

    public function test_paused_subscriptions_are_hidden_and_cannot_be_paid(): void
    {
        $subscription = $this->subscription();

        Livewire::test(Subscriptions::class)
            ->call('pause', $subscription->id)
            ->assertHasNoErrors();

        $this->assertFalse($subscription->fresh()->is_active);

        $component = Livewire::test(Transactions::class);

        $this->assertArrayNotHasKey($subscription->id, $component->get('subscriptions'));

        $component
            ->set('subscription_id', $subscription->id)
            ->set('billing_period', '2026-11')
            ->set('effective_date', '2026-11-02')
            ->call('saveSubscription')
            ->assertHasErrors(['subscription_id']);

        $this->assertSame(0, $subscription->transactions()->count());
    }

    public function test_subscription_list_can_be_filtered_by_state(): void
    {
        $this->subscription(['name' => 'Streaming mensual']);

        $paused = $this->subscription(['name' => 'Gimnasio mensual']);
        $paused->update(['is_active' => false]);

        Livewire::test(Subscriptions::class)
            ->set('filter', 'active')
            ->assertSee('Streaming mensual')
            ->assertDontSee('Gimnasio mensual');

        Livewire::test(Subscriptions::class)
            ->set('filter', 'inactive')
            ->assertSee('Gimnasio mensual')
            ->assertDontSee('Streaming mensual');
    }

    public function test_subscription_status_reflects_due_dates_and_pauses(): void
    {
        $subscription = $this->subscription();

        $this->assertSame(Subscription::STATUS_CURRENT, $subscription->scheduleStatus());

        Carbon::setTestNow('2026-11-10 10:00:00');

        $this->assertSame(Subscription::STATUS_DUE_SOON, $subscription->scheduleStatus());
        $this->assertSame('Por vencer', $subscription->scheduleStatusLabel());

        Carbon::setTestNow('2026-11-20 10:00:00');

        $this->assertSame(Subscription::STATUS_OVERDUE, $subscription->scheduleStatus());
        $this->assertSame('Vencida', $subscription->scheduleStatusLabel());

        $subscription->update(['is_active' => false]);

        $this->assertSame(Subscription::STATUS_PAUSED, $subscription->scheduleStatus());
        $this->assertSame('Pausada', $subscription->scheduleStatusLabel());
    }

    public function test_next_payment_is_clamped_to_the_last_day_of_the_month(): void
    {
        $subscription = $this->subscription(['payment_day' => 31]);

        $this->pay($subscription, '2026-01', '2026-01-05');

        $subscription->refresh();

        $this->assertEquals('2026-02-28', $subscription->next_payment_date->toDateString());
    }

    public function test_due_command_reports_upcoming_and_overdue_subscriptions(): void
    {
        $subscription = $this->subscription(['name' => 'Internet del hogar']);

        $subscription->update(['next_payment_date' => '2026-11-01']);

        $this->artisan('finance:subscriptions:due')
            ->expectsOutputToContain('Internet del hogar')
            ->assertExitCode(0);
    }

    public function test_due_command_reports_when_there_is_nothing_to_notify(): void
    {
        $this->subscription(['payment_day' => 25]);

        $this->artisan('finance:subscriptions:due')
            ->expectsOutputToContain('No hay suscripciones por vencer')
            ->assertExitCode(0);
    }

    public function test_dashboard_shows_due_subscriptions(): void
    {
        $subscription = $this->subscription(['name' => 'Servicio por vencer']);

        $subscription->update(['next_payment_date' => '2026-11-05']);

        Livewire::test(Dashboard::class)
            ->assertSee('Suscripciones vencidas o por vencer')
            ->assertSee('Servicio por vencer');
    }

    private function subscription(array $attributes = []): Subscription
    {
        return Subscription::create(array_merge([
            'name' => 'Suscripción de prueba',
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'payment_day' => 15,
            'amount' => 100.00,
        ], $attributes));
    }

    private function pay(Subscription $subscription, string $period, string $date = '2026-11-02'): void
    {
        Livewire::test(Transactions::class)
            ->set('subscription_id', $subscription->id)
            ->set('billing_period', $period)
            ->set('effective_date', $date)
            ->call('saveSubscription')
            ->assertHasNoErrors();
    }
}
