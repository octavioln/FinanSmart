<?php

namespace App\Http\Livewire;

use App\Models\Account;
use App\Models\Subscription;
use App\Models\Transaction;
use Livewire\Component;

/**
 * Componente para el tablero principal
 */
class Dashboard extends Component
{
    public $sort = 'id';

    public $direction = 'DESC';

    /* Cambia el valor de orden de la tabla */
    public function order($sort): void
    {
        if ($this->sort == $sort) {
            if ($this->direction == 'DESC') {
                $this->direction = 'ASC';
            } else {
                $this->direction = 'DESC';
            }
        } else {
            $this->sort = $sort;
            $this->direction = 'ASC';
        }
    }

    public function render()
    {
        $accounts = Account::query()->where('is_active', true)->orderBy('id')->get();
        $transactions = Transaction::getMostRecent($this->sort, $this->direction);
        $dueSubscriptions = Subscription::query()
            ->active()
            ->whereNotNull('next_payment_date')
            ->whereDate('next_payment_date', '<=', today()->addDays(7))
            ->orderBy('next_payment_date')
            ->get();

        return view('livewire.dashboard', compact('accounts', 'transactions', 'dueSubscriptions'));
    }
}
