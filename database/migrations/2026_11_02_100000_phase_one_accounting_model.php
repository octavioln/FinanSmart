<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Formaliza el modelo contable: saldo inicial, cuentas especiales,
     * categorías internas, precisión monetaria e índices de consulta.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('opening_balance', 12, 2)->default(0)->after('available_amount');
            $table->boolean('is_cash')->default(false)->after('is_bank_account');
            $table->boolean('is_savings')->default(false)->after('is_cash');
            $table->boolean('is_active')->default(true)->after('is_savings');
            $table->index('is_active');
            $table->index('deleted_at');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('system_key', 30)->nullable()->unique()->after('description');
            $table->boolean('is_active')->default(true)->after('system_key');
            $table->index('is_active');
            $table->index('deleted_at');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('amount', 12, 2)->change();
            $table->index('account_id');
            $table->index('category_id');
            $table->index('effective_date');
            $table->index('deleted_at');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index('account_id');
            $table->index('category_id');
            $table->index('deleted_at');
        });

        $this->backfillAccountingModel();
    }

    /**
     * Ajusta los datos existentes para que la reconciliación parta de una
     * base conocida y las operaciones dejen de depender de IDs fijos.
     */
    private function backfillAccountingModel(): void
    {
        DB::table('accounts')->orderBy('id')->chunkById(100, function ($accounts) {
            foreach ($accounts as $account) {
                $net = DB::table('transactions')
                    ->where('account_id', $account->id)
                    ->whereNull('deleted_at')
                    ->selectRaw("COALESCE(SUM(CASE WHEN type = 'Abono' THEN amount ELSE -amount END), 0) as net")
                    ->value('net');

                $opening = round((float) $account->available_amount - (float) $net, 2);

                DB::table('accounts')->where('id', $account->id)->update([
                    'opening_balance' => $opening,
                    'is_cash' => $account->name === 'Efectivo',
                    'is_savings' => $account->name === 'Ahorro',
                    'is_active' => true,
                ]);
            }
        });

        $systemCategories = [
            'Ahorro' => 'savings',
            'Traspaso entre cuentas' => 'transfer',
            'Depósito' => 'deposit',
        ];

        foreach ($systemCategories as $name => $key) {
            $id = DB::table('categories')
                ->where('name', $name)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->value('id');

            if ($id) {
                DB::table('categories')->where('id', $id)->update(['system_key' => $key]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['account_id']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('amount', 8, 2)->change();
            $table->dropIndex(['account_id']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['effective_date']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['system_key']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['deleted_at']);
            $table->dropColumn(['system_key', 'is_active']);
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['deleted_at']);
            $table->dropColumn(['opening_balance', 'is_cash', 'is_savings', 'is_active']);
        });
    }
};
