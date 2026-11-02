<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Completa el modelo de suscripciones: estado, periodo pagado,
     * próximo vencimiento y vínculo con la transacción que liquidó el pago.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('subscription_id')
                ->nullable()
                ->after('category_id')
                ->constrained('subscriptions')
                ->nullOnDelete();
            $table->string('billing_period', 7)->nullable()->after('subscription_id');
            $table->unique(['subscription_id', 'billing_period']);
            $table->index('billing_period');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('amount');
            $table->date('last_paid_at')->nullable()->after('is_active');
            $table->date('next_payment_date')->nullable()->after('last_paid_at');
            $table->index('is_active');
            $table->index('next_payment_date');
        });

        $this->backfillSubscriptions();
    }

    /**
     * Vincula los pagos históricos cuando la coincidencia es inequívoca y
     * calcula el próximo vencimiento de cada suscripción.
     */
    private function backfillSubscriptions(): void
    {
        $subscriptions = DB::table('subscriptions')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        foreach ($subscriptions as $subscription) {
            $payments = DB::table('transactions')
                ->whereNull('deleted_at')
                ->whereNull('subscription_id')
                ->where('operation_type', 'subscription')
                ->where('description', $subscription->name)
                ->where('account_id', $subscription->account_id)
                ->where('category_id', $subscription->category_id)
                ->orderBy('effective_date')
                ->get();

            $usedPeriods = [];

            foreach ($payments as $payment) {
                $period = substr((string) $payment->effective_date, 0, 7);

                if ($period === '' || isset($usedPeriods[$period])) {
                    continue;
                }

                $usedPeriods[$period] = true;

                DB::table('transactions')->where('id', $payment->id)->update([
                    'subscription_id' => $subscription->id,
                    'billing_period' => $period,
                ]);
            }

            $lastPeriod = empty($usedPeriods) ? null : max(array_keys($usedPeriods));

            $lastPaidAt = DB::table('transactions')
                ->where('subscription_id', $subscription->id)
                ->whereNotNull('billing_period')
                ->whereNull('deleted_at')
                ->max('effective_date');

            DB::table('subscriptions')->where('id', $subscription->id)->update([
                'is_active' => true,
                'last_paid_at' => $lastPaidAt,
                'next_payment_date' => $this->nextPaymentDate((int) $subscription->payment_day, $lastPeriod),
            ]);
        }
    }

    /**
     * Calcula la próxima fecha de pago a partir del último periodo liquidado
     * o, si no hay pagos, de la próxima ocurrencia del día de pago.
     */
    private function nextPaymentDate(int $paymentDay, ?string $lastPeriod): string
    {
        $today = Carbon::today();

        if ($lastPeriod) {
            $month = Carbon::createFromFormat('Y-m-d', $lastPeriod.'-01')->addMonth();
        } else {
            $month = $today->copy()->startOfMonth();

            if ($this->dueDate($month, $paymentDay)->lt($today)) {
                $month->addMonth();
            }
        }

        return $this->dueDate($month, $paymentDay)->toDateString();
    }

    /**
     * Ajusta el día de pago al último día real del mes.
     */
    private function dueDate(Carbon $month, int $paymentDay): Carbon
    {
        $month = $month->copy()->startOfMonth();

        return $month->day(min($paymentDay, $month->daysInMonth))->startOfDay();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['subscription_id', 'billing_period']);
            $table->dropIndex(['billing_period']);
            $table->dropForeign(['subscription_id']);
            $table->dropColumn(['subscription_id', 'billing_period']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['next_payment_date']);
            $table->dropColumn(['is_active', 'last_paid_at', 'next_payment_date']);
        });
    }
};
