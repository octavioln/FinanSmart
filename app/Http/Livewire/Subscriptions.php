<?php

namespace App\Http\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\Subscription;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Componente para el manejo de las suscripciones
 */
class Subscriptions extends Component
{
    use LivewireAlert;
    use WithPagination;

    /* Propiedades */
    public $search;

    public $sort = 'id';

    public $direction = 'DESC';

    public $open = false;

    public $selected_id;

    public $name;

    public $account_id;

    public $category_id;

    public $payment_day;

    public $amount;

    /* Inicializa el componente */
    public function mount(): void
    {
        $this->selected_id = 0;
    }

    /* Reglas de validación */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'category_id' => [
                'required', 'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at')->where('is_active', true)->whereNull('system_key'),
            ],
            'payment_day' => ['required', 'integer', 'between:1,31'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999.99'],
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
        if (! $this->open) {
            return;
        }
        $this->resetUI();
    }

    /* Reinicia el formulario */
    public function resetUI(): void
    {
        $attributes = [
            'name', 'account_id', 'category_id', 'payment_day', 'amount',
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
        $rules = $this->rules();

        if (! array_key_exists($propertyName, $rules)) {
            return;
        }

        $this->validateOnly($propertyName, [$propertyName => $rules[$propertyName]]);
    }

    /* Renderiza la vista */
    public function render()
    {
        $accounts = Account::query()->where('is_active', true)->where('allows_purchases', 1)->pluck('name', 'id');
        $categories = Category::query()->where('is_active', true)->whereNull('system_key')->pluck('name', 'id');
        $suscriptions = Subscription::getAll($this->search, $this->sort, $this->direction);

        return view('livewire.subscriptions.subscriptions', compact('suscriptions', 'accounts', 'categories'));
    }

    /* Crea un nuevo registro */
    public function save(): void
    {
        $validatedData = $this->validate($this->rules());

        $this->ensureAccountAllowsPurchases($validatedData['account_id']);

        Subscription::create($validatedData);

        $this->resetUI();
        $this->alert('success', 'Suscripción registrada satisfactoriamente');
    }

    /* Muestra el formulario para editar un registro */
    public function edit($id)
    {
        $suscription = Subscription::find($id);

        if (! $suscription) {
            $this->alert('error', 'La suscripción seleccionada ya no existe.');

            return;
        }

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
        $validatedData = $this->validate($this->rules());

        $suscription = Subscription::find($this->selected_id);

        if (! $suscription) {
            $this->alert('error', 'La suscripción seleccionada ya no existe.');
            $this->resetUI();

            return;
        }

        $this->ensureAccountAllowsPurchases($validatedData['account_id']);

        $suscription->update($validatedData);

        $this->resetUI();
        $this->alert('success', 'Suscripción modificada satisfactoriamente');
    }

    /* Muestra la ventana para confirmar la eliminación */
    public function deleteConfimation($id)
    {
        $this->selected_id = $id;
        $this->dispatch('show-alert');
    }

    /* Elimina el registro */
    public function destroy()
    {
        $suscription = Subscription::find($this->selected_id);

        if (! $suscription) {
            $this->alert('error', 'La suscripción seleccionada ya no existe.');
            $this->resetUI();

            return;
        }

        $suscription->delete();
        $this->resetUI();
        $this->dispatch('post-deleted', 'Suscripción eliminada satisfactoriamente');
    }

    /* Verifica que la cuenta permita pagos de suscripciones. */
    protected function ensureAccountAllowsPurchases($accountId): void
    {
        $account = Account::find($accountId);

        if (! $account || ! $account->allows_purchases) {
            throw ValidationException::withMessages([
                'account_id' => 'La cuenta seleccionada no permite pagos de suscripciones.',
            ]);
        }
    }
}
