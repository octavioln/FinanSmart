<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use Database\Seeders\AccountSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_and_category_seeders_are_idempotent(): void
    {
        $this->seed(AccountSeeder::class);
        $this->seed(CategorySeeder::class);

        $this->assertDatabaseCount('accounts', 3);
        $this->assertDatabaseCount('categories', 19);

        $this->seed(AccountSeeder::class);
        $this->seed(CategorySeeder::class);

        $this->assertDatabaseCount('accounts', 3);
        $this->assertDatabaseCount('categories', 19);
    }

    public function test_database_seeder_leaves_a_clean_database_ready(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('accounts', 3);
        $this->assertDatabaseCount('categories', 19);
        $this->assertDatabaseCount('users', 0);

        $this->assertTrue(Account::where('is_cash', true)->exists());
        $this->assertTrue(Account::where('is_savings', true)->exists());
        $this->assertTrue(Category::where('system_key', Category::SYSTEM_TRANSFER)->exists());
    }
}
