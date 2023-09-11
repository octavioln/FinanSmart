<?php

namespace App\Http\Livewire;

/* Clases */
use Livewire\Component;
use Livewire\WithPagination;
use Jantinnerezo\LivewireAlert\LivewireAlert;

/* Modelos */
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;

/**
 * Componente para el manejo de las transacciones
 */
class Transactions extends Component
{
    use withPagination;
    use LivewireAlert;

    /* Propiedades */
    public $search;
    public $sort = 'id', $direction = 'DESC';
    public $open = false;
    public $selected_id;
    public $type, $description, $account_id, $category_id, $effective_date, $amount;

    public $savings_modal = false;
    public $withdrawal_modal = false;
    public $deposit_modal = false;
    public $transfer_modal = false;
    public $accounts_withdraw = [];
    public $accounts_deposit = [];
    public $from_account_id;
    public $to_account_id;

    /* Inicializa el componente */
    public function mount(): void
    {
        $this->selected_id = 0;
        $this->accounts_deposit = Account::where('allows_deposits',1)->pluck('name','id');
        $this->accounts_withdraw = Account::where('allows_withdrawal',1)->pluck('name','id');
    }
    
    /* Reglas de validación */
    public function rules(): array
    {
        return [
            'description' => 'required',
            'account_id' => 'required',
            'category_id' => 'required',
            'type' => 'required',
            'effective_date' => 'required',
            'amount' => 'required',
        ];
    }

    /* Renderizar el componente con el resultado de la búsqueda */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

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

    /* Controla el cierre del modal para reinicar los valores del formulario */
    public function updatingOpen(): void
    {
        if(!$this->open) {
            return;
        }
        $this->resetUI();
    }

    /* Controla el cierre del modal de retiro de cajero, para reinicar los formularios */
    public function updatingwithdrawal_modal(): void
    {
        if(!$this->withdrawal_modal) {
            return;
        }
        $this->resetUI();
    }

    /* Controla el cierre del modal de retiro de cajero, para reinicar los formularios */
    public function updatingdeposit_modal(): void
    {
        if(!$this->deposit_modal) {
            return;
        }
        $this->resetUI();
    }

    /* Reinicia el formulario */
    public function resetUI(): void
    {
        $attributes = [
            'description','account_id','category_id' ,'type', 'effective_date','amount', 'from_account_id', 'to_account_id'
        ];

        foreach ($attributes as $attribute) {
            $this->$attribute = '';
        }

        $this->open = false;
        $this->savings_modal = false;
        $this->withdrawal_modal = false;
        $this->deposit_modal = false;
        $this->transfer_modal = false;
        $this->selected_id = 0;
    }

    /* Realiza la validación de la propiedad */
    public function updated($propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    /* Renderiza la vists */
    public function render()
    {
        $accounts = Account::where('allows_purchases', 1)->pluck('name','id');
        $categories = Category::whereNotIn('id', [18,19])->pluck('name','id');
        $transactions = Transaction::getAll($this->search, $this->sort, $this->direction);
        return view('livewire.transactions.transactions', compact('accounts', 'categories', 'transactions'));
    }

    public function save(): void
    {
        $this->type = 'Cargo';

        $validatedData = $this->validate([
            'description' => 'required',
            'account_id' => 'required',
            'category_id' => 'required',
            'type' => 'required',
            'effective_date' => 'required',
            'amount' => 'required',
        ]);

        Transaction::create($validatedData);

        $account = Account::find($this->account_id);
        $account->updateAvailableAmount($this->type, $this->amount);
        
        $this->resetUI();
        $this->alert('success', 'Transacción registrada satisfactoriamente');
    }

    public function saveWithdraw(): void
    {
        $cash =  Account::find(1);
        $account = Account::find($this->account_id);

        $validatedData = $this->validate([
            'amount' => 'required|max:'.$account->available_amount,
            'account_id' => 'required'
        ]);

        Transaction::create([
            'description' => 'Retiro de efectivo',
            'account_id' => $this->account_id,
            'category_id' => 18,
            'type' => 'Cargo',
            'effective_date' => $this->effective_date,
            'amount' => $this->amount
        ]);

        Transaction::create([
            'description' => 'Efectivo obtenido por Retiro en cajero',
            'account_id' => $cash->id,
            'category_id' => 18,
            'type' => 'Abono',
            'effective_date' => $this->effective_date,
            'amount' => $this->amount
        ]);
        $account->updateAvailableAmount('Cargo', $this->amount);
        $cash->updateAvailableAmount('Abono', $this->amount);
        
        $this->resetUI();
        $this->alert('success', 'Retiro en efectivo registrado.');
    }

    public function saveDeposit(): void
    {
        $account = Account::find($this->account_id);

        $validatedData = $this->validate([
            'amount' => 'required',
            'account_id' => 'required'
        ]);

        Transaction::create([
            'description' => 'Ingreso de recursos a la cuenta de ' . $account->name,
            'account_id' => $this->account_id,
            'category_id' => 19,
            'type' => 'Abono',
            'effective_date' => $this->effective_date,
            'amount' => $this->amount
        ]);
        $account->updateAvailableAmount('Abono', $this->amount);
        
        $this->resetUI();
        $this->alert('success', 'Retiro en efectivo registrado.');
    }

    public function saveSavings(): void
    {
        $savings_account =  Account::find(3);
        $account = Account::find($this->account_id);

        $validatedData = $this->validate([
            'amount' => 'required|max:'.$account->available_amount,
            'account_id' => 'required'
        ]);

        Transaction::create([
            'description' => 'Cargo por ahorro',
            'account_id' => $this->account_id,
            'category_id' => 18,
            'type' => 'Cargo',
            'effective_date' => $this->effective_date,
            'amount' => $this->amount
        ]);

        Transaction::create([
            'description' => 'Ingreso de ahorro desde ' . $account->name,
            'account_id' => $cash->id,
            'category_id' => 18,
            'type' => 'Abono',
            'effective_date' => $this->effective_date,
            'amount' => $this->amount
        ]);
        $account->updateAvailableAmount('Cargo', $this->amount);
        $savings_account->updateAvailableAmount('Abono', $this->amount);
        
        $this->resetUI();
        $this->alert('success', 'Retiro en efectivo registrado.');
    }

    public function saveTransfer(): void
    {
        $validatedData = $this->validate([
            'amount' => 'required',
            'from_account_id' => 'required',
            'to_account_id' => 'required'
        ]);
        
        $from_account =  Account::find($this->from_account_id);
        $to_account = Account::find($this->to_account_id);

        Transaction::create([
            'description' => 'Cargo por ahorro',
            'account_id' => $this->account_id,
            'category_id' => 18,
            'type' => 'Cargo',
            'effective_date' => $this->effective_date,
            'amount' => $this->amount
        ]);

        Transaction::create([
            'description' => 'Ingreso de ahorro desde ' . $account->name,
            'account_id' => $cash->id,
            'category_id' => 18,
            'type' => 'Abono',
            'effective_date' => $this->effective_date,
            'amount' => $this->amount
        ]);
        $account->updateAvailableAmount('Cargo', $this->amount);
        $savings_account->updateAvailableAmount('Abono', $this->amount);
        
        $this->resetUI();
        $this->alert('success', 'Retiro en efectivo registrado.');
    }
}
