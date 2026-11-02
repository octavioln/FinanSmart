<?php

namespace Tests\Feature;

use App\Http\Livewire\Categories;
use App\Models\Account;
use App\Models\Category;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoriesLivewireTest extends TestCase
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

    public function test_guest_is_redirected_when_visiting_categories_route(): void
    {
        $this->get('/categories')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_categories_route(): void
    {
        $this->actingAs($this->user)->get('/categories')->assertStatus(200);
    }

    public function test_can_create_and_update_a_category(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Categories::class)
            ->set('name', 'Educación')
            ->set('description', 'Cursos y libros')
            ->call('save')
            ->assertHasNoErrors();

        $category = Category::where('name', 'Educación')->firstOrFail();

        Livewire::test(Categories::class)
            ->call('edit', $category->id)
            ->set('name', 'Educación y cursos')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertEquals('Educación y cursos', $category->fresh()->name);
        $this->assertNull($category->fresh()->system_key);
    }

    public function test_rejects_duplicate_category_name(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Categories::class)
            ->set('name', 'Comida')
            ->set('description', 'Duplicada')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_can_archive_and_restore_a_normal_category(): void
    {
        $category = Category::create([
            'name' => 'Categoría temporal',
            'description' => 'Prueba',
        ]);

        $this->actingAs($this->user);

        Livewire::test(Categories::class)->call('archive', $category->id);

        $this->assertFalse($category->fresh()->is_active);
        $this->assertFalse(Category::usable()->whereKey($category->id)->exists());

        Livewire::test(Categories::class)->call('restore', $category->id);

        $this->assertTrue($category->fresh()->is_active);
        $this->assertTrue(Category::usable()->whereKey($category->id)->exists());
    }

    public function test_cannot_archive_a_system_category(): void
    {
        $category = Category::where('system_key', Category::SYSTEM_TRANSFER)->firstOrFail();

        $this->actingAs($this->user);

        Livewire::test(Categories::class)->call('archive', $category->id);

        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_cannot_archive_category_with_subscriptions(): void
    {
        $category = Category::create([
            'name' => 'Categoría con suscripción',
            'description' => 'Prueba',
        ]);
        $account = Account::where('allows_purchases', true)->firstOrFail();

        Subscription::create([
            'name' => 'Suscripción',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'payment_day' => 1,
            'amount' => 50.00,
        ]);

        $this->actingAs($this->user);

        Livewire::test(Categories::class)->call('archive', $category->id);

        $this->assertTrue($category->fresh()->is_active);
    }
}
