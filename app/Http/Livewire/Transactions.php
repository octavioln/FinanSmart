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

    /* Inicializa el componente */
    public function mount(): void
    {
        $this->selected_id = 0;
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

    /* Reinicia el formulario */
    public function resetUI(): void
    {
        $attributes = [
            'description','account_id','category_id' ,'type', 'effective_date','amount'
        ];

        foreach ($attributes as $attribute) {
            $this->$attribute = '';
        }

        $this->open = false;
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
        $accounts = Account::pluck('name','id');
        $categories = Category::pluck('name','id');
        $transactions = Transaction::getAll($this->search, $this->sort, $this->direction);
        return view('livewire.transactions.transactions', compact('accounts', 'categories', 'transactions'));
    }

    public function save(): void
    {
        $this->validate();
        Transaction::create([
            'description' => $this->description,
            'account_id' => $this->account_id,
            'category_id' => $this->category_id,
            'type' => $this->type,
            'effective_date' => $this->effective_date,
            'amount' => $this->amount
        ]);

        $account = Account::find($this->account_id);
        $account->updateAvailableAmount($this->type, $this->amount);
        
        $this->resetUI();
        $this->alert('success', 'Transacción registrada satisfactoriamente');
    }
}
