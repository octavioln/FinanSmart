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
use App\Models\Subscription;

/**
 * Componente para el manejo de las suscripciones
 */
class Subscriptions extends Component
{
    use withPagination;
    use LivewireAlert;

    /* Propiedades */
    public $search;
    public $sort = 'id', $direction = 'DESC';
    public $open = false;
    public $selected_id;
    public $name, $account_id, $category_id, $payment_day, $amount;

    /* Inicializa el componente */
    public function mount(): void
    {
        $this->selected_id = 0;
    }

    /* Reglas de validación */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'account_id' => 'required',
            'category_id' => 'required',
            'payment_day' => 'required',
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
            'name','account_id','category_id' ,'payment_day','amount'
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
    
    /* Renderiza la vista */
    public function render()
    {
        $accounts = Account::where('allows_purchases', 1)->pluck('name','id');
        $categories = Category::whereNotIn('id', [18,19])->pluck('name','id');
        $suscriptions = Subscription::getAll($this->search, $this->sort, $this->direction);
        return view('livewire.subscriptions.subscriptions', compact('suscriptions', 'accounts', 'categories'));
    }

    /* Crea un nuevo registro */
    public function save(): void
    {
        $validatedData = $this->validate([
            'name' => 'required',
            'account_id' => 'required',
            'category_id' => 'required',
            'payment_day' => 'required',
            'amount' => 'required',
        ]);

        Subscription::create($validatedData);
        
        $this->resetUI();
        $this->alert('success', 'Suscripción registrada satisfactoriamente');
    }

    /* Muestra el formulario para editar un registro */
    public function edit($id)
    {
        $suscription = Subscription::find($id);
        $this->name = $suscription->name;
        $this->account_id = $suscription->account_id;
        $this->category_id = $suscription->category_id;
        $this->payment_day = $suscription->payment_day;
        $this->amount = $suscription->amount;
        $this->selected_id = $suscription->id;
        $this->open = true;
    }

    /* Realiza la actualización de un registro */
    public function update()
    {
        $validatedData = $this->validate([
            'name' => 'required',
            'account_id' => 'required',
            'category_id' => 'required',
            'payment_day' => 'required',
            'amount' => 'required',
        ]);

        $suscription = Subscription::find($this->selected_id);
        $suscription->update($validatedData);

        $this->resetUI();
        $this->alert('success', 'Suscripción modificada satisfactoriamente');
    }

    /* Muestra la ventana para confirmar la eliminación */
    public function deleteConfimation($id)
    {
        $this->selected_id = $id;
        $this->dispatchBrowserEvent('show-alert');
    }

    /* Elimina el registro */
    public function destroy()
    {
        $suscription = Subscription::find($this->selected_id);
        $suscription->delete();
        $this->resetUI();
        $this->dispatchBrowserEvent('post-deleted', 'Suscripción eliminada satisfactoriamente');
    }
}
