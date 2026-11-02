<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class NotifyDueSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:subscriptions:due {--days=7 : Días de anticipación para avisar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lista las suscripciones vencidas o próximas a vencer sin generar cargos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $limit = Carbon::today()->addDays($days);

        $subscriptions = Subscription::query()
            ->active()
            ->whereNotNull('next_payment_date')
            ->whereDate('next_payment_date', '<=', $limit)
            ->orderBy('next_payment_date')
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info("No hay suscripciones por vencer en los próximos {$days} día(s).");

            return self::SUCCESS;
        }

        $rows = $subscriptions->map(function (Subscription $subscription) {
            return [
                $subscription->id,
                $subscription->name,
                $subscription->next_payment_date->format('d/m/Y'),
                $subscription->scheduleStatusLabel(),
                number_format((float) $subscription->amount, 2),
            ];
        })->all();

        $this->table(['ID', 'Suscripción', 'Próximo pago', 'Estado', 'Monto'], $rows);

        foreach ($subscriptions as $subscription) {
            $message = sprintf(
                'Suscripción "%s" con vencimiento %s (%s).',
                $subscription->name,
                $subscription->next_payment_date->format('d/m/Y'),
                $subscription->scheduleStatusLabel()
            );

            if ($subscription->scheduleStatus() === Subscription::STATUS_OVERDUE) {
                Log::warning($message);
            } else {
                Log::info($message);
            }
        }

        return self::SUCCESS;
    }
}
