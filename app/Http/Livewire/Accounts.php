<?php

namespace App\Http\Livewire;

use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Componente para administrar las cuentas financieras.
 */
class Accounts extends Component
{
    use LivewireAlert;
    use WithPagination;

    public $search;

    public $sort = 'name';

    public $direction = 'ASC';

    public $show_inactive = false;

    public $open = false;

    public $selected_id;

    public $name;

    public $description;

    public $opening_balance = 0;

    public $is_bank_account = false;

    public $is_cash = false;

    public $is_savings = false;

    public $allows_purchases = true;

    public $allows_withdrawal = false;

    public $allows_deposits = true;

    public $allows_transfers = false;

    public function mount(): void
    {
        $this->selected_id = 0;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('accounts', 'name')->whereNull('deleted_at')->ignore($this->selected_id ?: null),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'opening_balance' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'is_bank_account' => ['boolean'],
            'is_cash' => ['boolean'],
            'is_savings' => ['boolean'],
            'allows_purchases' => ['boolean'],
            'allows_withdrawal' => ['boolean'],
            'allows_deposits' => ['boolean'],
            'allows_transfers' => ['boolean'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingShowInactive(): void
    {
        $this->resetPage();
    }

    public function order($sort): void
    {
        $allowed = ['id', 'name', 'available_amount', 'is_bank_account', 'is_active'];

        if (! in_array($sort, $allowed, true)) {
            return;
        }

        if ($this->sort == $sort) {
            $this->direction = $this->direction == 'DESC' ? 'ASC' : 'DESC';
        } else {
            $this->sort = $sort;
            $this->direction = 'ASC';
        }
    }

    public function updatingOpen(): void
    {
        if (! $this->open) {
            return;
        }

        $this->resetUI();
    }

    public function resetUI(): void
    {
        $this->reset([
            'name', 'description', 'opening_balance', 'is_bank_account', 'is_cash', 'is_savings',
            'allows_purchases', 'allows_withdrawal', 'allows_deposits', 'allows_transfers',
        ]);

        $this->opening_balance = 0;
        $this->allows_purchases = true;
        $this->allows_deposits = true;
        $this->open = false;
        $this->selected_id = 0;
    }

    public function updated($propertyName): void
    {
        $rules = $this->rules();

        if (! array_key_exists($propertyName, $rules)) {
            return;
        }

        $this->validateOnly($propertyName, [$propertyName => $rules[$propertyName]]);
    }

    public function render()
    {
        $search = (string) $this->search;
        $sort = in_array($this->sort, ['id', 'name', 'available_amount', 'is_bank_account', 'is_active'], true)
            ? $this->sort
            : 'name';
        $direction = $this->direction === 'ASC' ? 'ASC' : 'DESC';

        $accounts = Account::query()
            ->when(! $this->show_inactive, fn ($query) => $query->where('is_active', true))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(10);

        return view('livewire.accounts.accounts', compact('accounts'));
    }

    public function save(): void
    {
        $validatedData = $this->validate($this->rules());
        $this->ensureFlagsAreCompatible($validatedData);

        DB::transaction(function () use ($validatedData) {
            $this->releaseSpecialFlags($validatedData);

            $validatedData['description'] = $validatedData['description'] ?? '';
            $validatedData['opening_balance'] = round((float) $validatedData['opening_balance'], 2);
            $validatedData['available_amount'] = $validatedData['opening_balance'];

            Account::create($validatedData);
        });

        $this->resetUI();
        $this->alert('success', 'Cuenta registrada satisfactoriamente');
    }

    public function edit($id): void
    {
        $account = Account::find($id);

        if (! $account) {
            $this->alert('error', 'La cuenta seleccionada ya no existe.');

            return;
        }

        $this->name = $account->name;
        $this->description = $account->description;
        $this->opening_balance = $account->opening_balance;
        $this->is_bank_account = $account->is_bank_account;
        $this->is_cash = $account->is_cash;
        $this->is_savings = $account->is_savings;
        $this->allows_purchases = $account->allows_purchases;
        $this->allows_withdrawal = $account->allows_withdrawal;
        $this->allows_deposits = $account->allows_deposits;
        $this->allows_transfers = $account->allows_transfers;
        $this->selected_id = $account->id;
        $this->open = true;
    }

    public function update(): void
    {
        $validatedData = $this->validate($this->rules());
        $account = Account::find($this->selected_id);

        if (! $account) {
            $this->alert('error', 'La cuenta seleccionada ya no existe.');
            $this->resetUI();

            return;
        }

        $this->ensureFlagsAreCompatible($validatedData, $account);

        DB::transaction(function () use ($account, $validatedData) {
            $lockedAccount = Account::whereKey($account->id)->lockForUpdate()->first();

            if (! $lockedAccount) {
                throw ValidationException::withMessages([
                    'name' => 'La cuenta seleccionada ya no existe.',
                ]);
            }

            $this->releaseSpecialFlags($validatedData, $lockedAccount);

            $openingBalance = round((float) $validatedData['opening_balance'], 2);
            $delta = round($openingBalance - (float) $lockedAccount->opening_balance, 2);
            $newAvailable = round((float) $lockedAccount->available_amount + $delta, 2);

            if ($newAvailable < 0) {
                throw ValidationException::withMessages([
                    'opening_balance' => 'El saldo inicial no puede dejar un saldo disponible negativo.',
                ]);
            }

            $validatedData['description'] = $validatedData['description'] ?? '';
            $validatedData['opening_balance'] = $openingBalance;
            $validatedData['available_amount'] = $newAvailable;

            $lockedAccount->update($validatedData);
        });

        $this->resetUI();
        $this->alert('success', 'Cuenta modificada satisfactoriamente');
    }

    public function archive($id): void
    {
        $account = Account::find($id);

        if (! $account) {
            $this->alert('error', 'La cuenta seleccionada ya no existe.');

            return;
        }

        if ($account->is_cash || $account->is_savings) {
            $this->alert('error', 'No puedes archivar la cuenta de efectivo o ahorro configurada. Cambia la configuración primero.');

            return;
        }

        if ($account->subscriptions()->exists()) {
            $this->alert('error', 'La cuenta tiene suscripciones asociadas. Elimina o cambia esas suscripciones antes de archivarla.');

            return;
        }

        $account->update(['is_active' => false]);
        $this->alert('success', 'Cuenta archivada satisfactoriamente');
    }

    public function restore($id): void
    {
        $account = Account::find($id);

        if (! $account) {
            $this->alert('error', 'La cuenta seleccionada ya no existe.');

            return;
        }

        $account->update(['is_active' => true]);
        $this->alert('success', 'Cuenta restaurada satisfactoriamente');
    }

    /**
     * Una cuenta no puede ser efectivo y ahorro al mismo tiempo.
     */
    protected function ensureFlagsAreCompatible(array $data, ?Account $account = null): void
    {
        if ($data['is_cash'] && $data['is_savings']) {
            throw ValidationException::withMessages([
                'is_cash' => 'Una cuenta no puede ser de efectivo y de ahorro al mismo tiempo.',
            ]);
        }

        if ($account && $account->is_cash && ! $data['is_cash']) {
            $other = Account::where('is_cash', true)->where('is_active', true)
                ->whereKeyNot($account->id)->exists();

            if (! $other) {
                throw ValidationException::withMessages([
                    'is_cash' => 'Debe existir al menos una cuenta de efectivo configurada.',
                ]);
            }
        }

        if ($account && $account->is_savings && ! $data['is_savings']) {
            $other = Account::where('is_savings', true)->where('is_active', true)
                ->whereKeyNot($account->id)->exists();

            if (! $other) {
                throw ValidationException::withMessages([
                    'is_savings' => 'Debe existir al menos una cuenta de ahorro configurada.',
                ]);
            }
        }
    }

    /**
     * Al marcar una cuenta como efectivo o ahorro, quita el flag a las demás.
     */
    protected function releaseSpecialFlags(array $data, ?Account $account = null): void
    {
        if ($data['is_cash']) {
            Account::where('is_cash', true)
                ->when($account, fn ($query) => $query->whereKeyNot($account->id))
                ->update(['is_cash' => false]);
        }

        if ($data['is_savings']) {
            Account::where('is_savings', true)
                ->when($account, fn ($query) => $query->whereKeyNot($account->id))
                ->update(['is_savings' => false]);
        }
    }
}
