<?php

namespace App\Http\Livewire;

/* Clases */
use Livewire\Component;

/* Modelos */
use App\Models\Account;
use App\Models\Transaction;

/**
 * Componente para el tablero principal
 */
class Dashboard extends Component
{  
    public function render()
    {
        $accounts = Account::all();
        $transactions = Transaction::getMostRecent();
        return view('livewire.dashboard', compact('accounts', 'transactions'));
    }
}
