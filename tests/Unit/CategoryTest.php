<?php

namespace Tests\Unit;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_category(): void
    {
        $category = Category::create([
            'name' => 'Transporte',
            'description' => 'Gastos en metro, autobús y gasolina',
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Transporte',
        ]);
    }

    public function test_category_supports_soft_deletes(): void
    {
        $category = Category::factory()->create();

        $category->delete();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }
}
