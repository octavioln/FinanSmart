<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:reconcile {--fix : Corrige el saldo disponible con el saldo calculado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compara el saldo de cada cuenta con su saldo inicial y sus movimientos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $accounts = Account::withTrashed()->orderBy('id')->get();
        $rows = [];
        $differences = 0;
        $fixed = 0;

        foreach ($accounts as $account) {
            $calculated = $account->calculatedBalance();
            $actual = round((float) $account->available_amount, 2);
            $difference = round($actual - $calculated, 2);
            $status = 'OK';

            if (abs($difference) >= 0.005) {
                $differences++;
                $status = 'Descuadre';

                if ($this->option('fix')) {
                    DB::table('accounts')->where('id', $account->id)->update([
                        'available_amount' => $calculated,
                        'updated_at' => now(),
                    ]);

                    $status = 'Corregida';
                    $fixed++;
                }
            }

            $rows[] = [
                $account->id,
                $account->name,
                number_format((float) $account->opening_balance, 2),
                number_format($actual, 2),
                number_format($calculated, 2),
                number_format($difference, 2),
                $status,
            ];
        }

        $this->table(
            ['ID', 'Cuenta', 'Saldo inicial', 'Saldo actual', 'Saldo calculado', 'Diferencia', 'Estado'],
            $rows
        );

        if ($differences === 0) {
            $this->info('Todas las cuentas cuadran con sus movimientos.');

            return self::SUCCESS;
        }

        if ($fixed === $differences) {
            $this->info("Se corrigieron {$fixed} cuenta(s) descuadrada(s).");

            return self::SUCCESS;
        }

        $this->error("Se encontraron {$differences} cuenta(s) descuadrada(s). Usa --fix para corregirlas.");

        return self::FAILURE;
    }
}
