<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description');
            $table->decimal('available_amount', $precision = 12, $scale = 2)->default(0);
            $table->boolean('is_bank_account')->default(0);
            $table->boolean('allows_purchases')->default(0);
            $table->boolean('allows_withdrawal')->default(0);
            $table->boolean('allows_deposits')->default(0);
            $table->boolean('allows_transfers')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
