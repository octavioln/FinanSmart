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
        Schema::table('transactions', function (Blueprint $table) {
            $table->uuid('transaction_group_id')->nullable()->after('id');
            $table->string('operation_type', 20)->nullable()->after('transaction_group_id');
            $table->index('transaction_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['transaction_group_id']);
            $table->dropColumn(['transaction_group_id', 'operation_type']);
        });
    }
};
