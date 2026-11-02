<?php

namespace App\Http\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Componente para el manejo de las transacciones
 */
class Transactions extends Component
{
    use LivewireAlert;
    use WithPagination;

    /* Propiedades */
    public $search;

    public $sort = 'id';

    public $direction = 'DESC';

    public $open = false;

    public $selected_id;

    public $editing_group = false;

    public $type;

    public $description;

    public $account_id;

    public $category_id;

    public $effective_date;

    public $amount;

    public $savings_modal = false;

    public $withdrawal_modal = false;

    public $deposit_modal = false;

    public $transfer_modal = false;

    public $subscriptions_modal = false;

    public $accounts_withdraw = [];

    public $accounts_deposit = [];

    public $accounts_transfer = [];

    public $subscriptions = [];

    public $from_account_id;

    public $to_account_id;

    public $subscription_id;

    public $billing_period;

    public $subscription_next_payment;

    protected $listeners = [
        'deleteConfirmed' => 'destroy',
    ];

    /* Inicializa el componente */
    public function mount(): void
    {
        $this->selected_id = 0;
        $this->accounts_deposit = Account::query()->where('is_active', true)->where('allows_deposits', 1)->pluck('name', 'id');
        $this->accounts_withdraw = Account::query()->where('is_active', true)->where('allows_withdrawal', 1)->pluck('name', 'id');
        $this->accounts_transfer = Account::query()->where('is_active', true)->where('allows_transfers', 1)->pluck('name', 'id');
        $this->subscriptions = Subscription::query()
            ->where('is_active', true)
            ->whereHas('account', fn ($query) => $query->where('is_active', true))
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->pluck('name', 'id');
    }

    /* Reglas de validación */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'account_id' => ['required', 'integer', $this->activeAccountRule()],
            'category_id' => ['required', 'integer', $this->activeCategoryRule()],
            'type' => ['required', Rule::in(['Cargo', 'Abono'])],
            'effective_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999.99'],
            'from_account_id' => ['nullable', 'integer', $this->activeAccountRule()],
            'to_account_id' => ['nullable', 'integer', $this->activeAccountRule()],
            'subscription_id' => ['nullable', 'integer', $this->activeSubscriptionRule()],
            'billing_period' => ['nullable', 'date_format:Y-m'],
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

    /* Controla el cierre del modal de retiro de cajero, para reinicar los formularios */
    public function updatingwithdrawal_modal(): void
    {
        if (! $this->withdrawal_modal) {
            return;
        }
        $this->resetUI();
    }

    /* Controla el cierre del modal de retiro de cajero, para reinicar los formularios */
    public function updatingdeposit_modal(): void
    {
        if (! $this->deposit_modal) {
            return;
        }
        $this->resetUI();
    }

    /* Reinicia el formulario */
    public function resetUI(): void
    {
        $attributes = [
            'description', 'account_id', 'category_id', 'type', 'effective_date', 'amount',
            'from_account_id', 'to_account_id', 'subscription_id', 'billing_period',
            'subscription_next_payment',
        ];

        foreach ($attributes as $attribute) {
            $this->$attribute = '';
        }

        $this->open = false;
        $this->savings_modal = false;
        $this->withdrawal_modal = false;
        $this->deposit_modal = false;
        $this->transfer_modal = false;
        $this->subscriptions_modal = false;
        $this->selected_id = 0;
        $this->editing_group = false;
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

    /* Sugiere el periodo y muestra el próximo vencimiento al elegir una suscripción. */
    public function updatedSubscriptionId($value): void
    {
        $this->subscription_next_payment = null;

        if (! $value) {
            $this->billing_period = '';

            return;
        }

        $subscription = Subscription::find($value);

        if (! $subscription) {
            $this->billing_period = '';

            return;
        }

        $this->billing_period = $subscription->billingPeriodForNextPayment();
        $this->subscription_next_payment = $subscription->next_payment_date?->format('d/m/Y');
    }

    /* Renderiza la vista */
    public function render()
    {
        $accountNames = Account::pluck('name', 'id');
        $accounts = Account::query()->where('is_active', true)->where('allows_purchases', 1)->pluck('name', 'id');
        $categoryNames = Category::pluck('name', 'id');
        $categories = Category::query()->where('is_active', true)->whereNull('system_key')->pluck('name', 'id');
        $transactions = Transaction::getAll($this->search, $this->sort, $this->direction);

        return view('livewire.transactions.transactions', compact(
            'accounts',
            'accountNames',
            'categories',
            'categoryNames',
            'transactions'
        ));
    }

    public function save(): void
    {
        $this->type = 'Cargo';

        $rules = $this->rules();
        $rules['category_id'] = ['required', 'integer', $this->usableCategoryRule()];
        unset($rules['type']);

        $validatedData = $this->validate($rules);

        DB::transaction(function () use ($validatedData) {
            $account = $this->lockedAccount($validatedData['account_id']);
            $this->ensureAccountAllows($account, 'allows_purchases', 'account_id', 'La cuenta seleccionada no permite compras.');
            $this->ensureSufficientFunds($account, $validatedData['amount']);

            Transaction::create($validatedData + [
                'type' => Transaction::TYPE_CARGO,
                'operation_type' => Transaction::OPERATION_PURCHASE,
            ]);

            $account->applyTransactionEffect(Transaction::TYPE_CARGO, $validatedData['amount']);
        });

        $this->resetUI();
        $this->alert('success', 'Transacción registrada satisfactoriamente');
    }

    /* Muestra el formulario para editar un registro */
    public function edit($id)
    {
        $tr = Transaction::find($id);

        if (! $tr) {
            $this->alert('error', 'La transacción seleccionada ya no existe.');

            return;
        }

        $this->editing_group = (bool) $tr->transaction_group_id;

        if ($this->editing_group) {
            $rows = Transaction::where('transaction_group_id', $tr->transaction_group_id)
                ->orderBy('id')
                ->get();

            $tr = $rows->firstWhere('type', 'Cargo') ?? $rows->first();
        }

        $this->description = $tr->description;
        $this->account_id = $tr->account_id;
        $this->category_id = $tr->category_id;
        $this->type = $tr->type;
        $this->effective_date = $this->dateForInput($tr->effective_date);
        $this->amount = $tr->amount;
        $this->selected_id = $tr->id;
        $this->open = true;
    }

    /* Realiza la actualización de un registro */
    public function update()
    {
        $tr = Transaction::find($this->selected_id);

        if (! $tr) {
            $this->alert('error', 'La transacción seleccionada ya no existe.');
            $this->resetUI();

            return;
        }

        /* Las operaciones de dos asientos se editan como un solo grupo. */
        if ($this->editing_group && $tr->transaction_group_id) {
            $validatedData = $this->validate([
                'effective_date' => ['required', 'date'],
                'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999.99'],
            ]);

            DB::transaction(function () use ($tr, $validatedData) {
                $rows = Transaction::where('transaction_group_id', $tr->transaction_group_id)
                    ->orderBy('id')
                    ->get();

                $accounts = [];

                /* Revertir el efecto anterior de cada asiento. */
                foreach ($rows as $row) {
                    $accounts[$row->id] = $this->lockedAccount($row->account_id, 'account_id', false);
                    $accounts[$row->id]->applyTransactionEffect($row->type, $row->amount, true);
                }

                /* Validar que el nuevo monto no deje saldo negativo en los cargos. */
                foreach ($rows as $row) {
                    if ($row->type === 'Cargo') {
                        $this->ensureSufficientFunds($accounts[$row->id], $validatedData['amount']);
                    }
                }

                /* Aplicar el nuevo monto y fecha a ambos asientos. */
                foreach ($rows as $row) {
                    $row->update([
                        'amount' => $validatedData['amount'],
                        'effective_date' => $validatedData['effective_date'],
                    ]);

                    $accounts[$row->id]->applyTransactionEffect($row->type, $validatedData['amount']);
                }
            });

            $this->resetUI();
            $this->alert('success', 'Operación modificada satisfactoriamente');

            return;
        }

        $validatedData = $this->validate([
            'description' => ['required', 'string', 'max:255'],
            'account_id' => ['required', 'integer', $this->activeAccountRule(false)],
            'category_id' => ['required', 'integer', $this->activeCategoryRule(false)],
            'effective_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999.99'],
        ]);

        /* Las categorías internas no se pueden asignar a una compra normal. */
        if ($tr->category && $tr->category->isSystem()) {
            $validatedData['category_id'] = $tr->category_id;
        } elseif ($this->categoryIsInternal($validatedData['category_id'])) {
            throw ValidationException::withMessages([
                'category_id' => 'La categoría seleccionada es interna del sistema y no puede usarse en una compra.',
            ]);
        }

        if ((int) $validatedData['category_id'] !== (int) $tr->category_id) {
            $newCategory = Category::find($validatedData['category_id']);

            if (! $newCategory || ! $newCategory->is_active) {
                throw ValidationException::withMessages([
                    'category_id' => 'La categoría seleccionada está archivada.',
                ]);
            }
        }

        DB::transaction(function () use ($tr, $validatedData) {
            /* Revertir el efecto anterior en la cuenta original. */
            $formerAccount = $this->lockedAccount($tr->account_id, 'account_id', false);
            $formerAccount->applyTransactionEffect($tr->type, $tr->amount, true);

            /* Aplicar el nuevo efecto en la cuenta destino. */
            $newAccount = $this->lockedAccount(
                $validatedData['account_id'],
                'account_id',
                (int) $validatedData['account_id'] !== (int) $tr->account_id
            );

            if ($tr->type === Transaction::TYPE_CARGO) {
                $this->ensureSufficientFunds($newAccount, $validatedData['amount']);
            }

            $newAccount->applyTransactionEffect($tr->type, $validatedData['amount']);

            $tr->update($validatedData);
        });

        $this->resetUI();
        $this->alert('success', 'Transacción modificada satisfactoriamente');
    }

    public function saveWithdraw(): void
    {
        $validatedData = $this->validate([
            'account_id' => ['required', 'integer', $this->activeAccountRule()],
            'effective_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999.99'],
        ]);

        DB::transaction(function () use ($validatedData) {
            $account = $this->lockedAccount($validatedData['account_id']);
            $this->ensureAccountAllows($account, 'allows_withdrawal', 'account_id', 'La cuenta seleccionada no permite retiros.');

            $cash = Account::cash(true);

            if (! $cash) {
                throw ValidationException::withMessages([
                    'account_id' => 'No existe la cuenta de efectivo configurada.',
                ]);
            }

            $transferCategory = $this->systemCategory(Category::SYSTEM_TRANSFER);

            $this->ensureSufficientFunds($account, $validatedData['amount']);

            $groupId = (string) Str::uuid();

            Transaction::create([
                'description' => 'Retiro de efectivo',
                'account_id' => $account->id,
                'category_id' => $transferCategory->id,
                'type' => Transaction::TYPE_CARGO,
                'effective_date' => $validatedData['effective_date'],
                'amount' => $validatedData['amount'],
                'transaction_group_id' => $groupId,
                'operation_type' => Transaction::OPERATION_WITHDRAWAL,
            ]);

            Transaction::create([
                'description' => 'Efectivo obtenido por Retiro en cajero',
                'account_id' => $cash->id,
                'category_id' => $transferCategory->id,
                'type' => Transaction::TYPE_ABONO,
                'effective_date' => $validatedData['effective_date'],
                'amount' => $validatedData['amount'],
                'transaction_group_id' => $groupId,
                'operation_type' => Transaction::OPERATION_WITHDRAWAL,
            ]);

            $account->applyTransactionEffect(Transaction::TYPE_CARGO, $validatedData['amount']);
            $cash->applyTransactionEffect(Transaction::TYPE_ABONO, $validatedData['amount']);
        });

        $this->resetUI();
        $this->alert('success', 'Retiro en efectivo registrado.');
    }

    public function saveDeposit(): void
    {
        $validatedData = $this->validate([
            'account_id' => ['required', 'integer', $this->activeAccountRule()],
            'effective_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999.99'],
        ]);

        DB::transaction(function () use ($validatedData) {
            $account = $this->lockedAccount($validatedData['account_id']);
            $this->ensureAccountAllows($account, 'allows_deposits', 'account_id', 'La cuenta seleccionada no permite depósitos.');

            $depositCategory = $this->systemCategory(Category::SYSTEM_DEPOSIT);

            Transaction::create([
                'description' => 'Ingreso de recursos a la cuenta de '.$account->name,
                'account_id' => $account->id,
                'category_id' => $depositCategory->id,
                'type' => Transaction::TYPE_ABONO,
                'effective_date' => $validatedData['effective_date'],
                'amount' => $validatedData['amount'],
                'operation_type' => Transaction::OPERATION_DEPOSIT,
            ]);

            $account->applyTransactionEffect(Transaction::TYPE_ABONO, $validatedData['amount']);
        });

        $this->resetUI();
        $this->alert('success', 'Depósito registrado.');
    }

    public function saveSavings(): void
    {
        $validatedData = $this->validate([
            'account_id' => ['required', 'integer', $this->activeAccountRule()],
            'effective_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999.99'],
        ]);

        DB::transaction(function () use ($validatedData) {
            $account = $this->lockedAccount($validatedData['account_id']);
            $this->ensureAccountAllows($account, 'allows_withdrawal', 'account_id', 'La cuenta seleccionada no permite traspasos a ahorro.');

            $savingsAccount = Account::savings(true);

            if (! $savingsAccount) {
                throw ValidationException::withMessages([
                    'account_id' => 'No existe la cuenta de ahorro configurada.',
                ]);
            }

            if ((int) $account->id === (int) $savingsAccount->id) {
                throw ValidationException::withMessages([
                    'account_id' => 'La cuenta de origen no puede ser la misma cuenta de ahorro.',
                ]);
            }

            $savingsCategory = $this->systemCategory(Category::SYSTEM_SAVINGS);
            $depositCategory = $this->systemCategory(Category::SYSTEM_DEPOSIT);

            $this->ensureSufficientFunds($account, $validatedData['amount']);

            $groupId = (string) Str::uuid();

            Transaction::create([
                'description' => 'Traspaso de fondos hacia '.$savingsAccount->name,
                'account_id' => $account->id,
                'category_id' => $savingsCategory->id,
                'type' => Transaction::TYPE_CARGO,
                'effective_date' => $validatedData['effective_date'],
                'amount' => $validatedData['amount'],
                'transaction_group_id' => $groupId,
                'operation_type' => Transaction::OPERATION_SAVINGS,
            ]);

            Transaction::create([
                'description' => 'Ingreso de ahorro desde '.$account->name,
                'account_id' => $savingsAccount->id,
                'category_id' => $depositCategory->id,
                'type' => Transaction::TYPE_ABONO,
                'effective_date' => $validatedData['effective_date'],
                'amount' => $validatedData['amount'],
                'transaction_group_id' => $groupId,
                'operation_type' => Transaction::OPERATION_SAVINGS,
            ]);

            $account->applyTransactionEffect(Transaction::TYPE_CARGO, $validatedData['amount']);
            $savingsAccount->applyTransactionEffect(Transaction::TYPE_ABONO, $validatedData['amount']);
        });

        $this->resetUI();
        $this->alert('success', 'Ahorro registrado.');
    }

    public function saveTransfer(): void
    {
        $validatedData = $this->validate([
            'from_account_id' => ['required', 'integer', $this->activeAccountRule(), 'different:to_account_id'],
            'to_account_id' => ['required', 'integer', $this->activeAccountRule()],
            'effective_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999.99'],
        ]);

        DB::transaction(function () use ($validatedData) {
            $fromAccount = $this->lockedAccount($validatedData['from_account_id'], 'from_account_id');
            $toAccount = $this->lockedAccount($validatedData['to_account_id'], 'to_account_id');

            $this->ensureAccountAllows($fromAccount, 'allows_transfers', 'from_account_id', 'La cuenta de origen no permite transferencias.');
            $this->ensureSufficientFunds($fromAccount, $validatedData['amount']);

            $transferCategory = $this->systemCategory(Category::SYSTEM_TRANSFER);

            $groupId = (string) Str::uuid();

            Transaction::create([
                'description' => 'Traspaso de fondos hacia '.$toAccount->name,
                'account_id' => $fromAccount->id,
                'category_id' => $transferCategory->id,
                'type' => Transaction::TYPE_CARGO,
                'effective_date' => $validatedData['effective_date'],
                'amount' => $validatedData['amount'],
                'transaction_group_id' => $groupId,
                'operation_type' => Transaction::OPERATION_TRANSFER,
            ]);

            Transaction::create([
                'description' => 'Ingreso de fondos desde '.$fromAccount->name,
                'account_id' => $toAccount->id,
                'category_id' => $transferCategory->id,
                'type' => Transaction::TYPE_ABONO,
                'effective_date' => $validatedData['effective_date'],
                'amount' => $validatedData['amount'],
                'transaction_group_id' => $groupId,
                'operation_type' => Transaction::OPERATION_TRANSFER,
            ]);

            $fromAccount->applyTransactionEffect(Transaction::TYPE_CARGO, $validatedData['amount']);
            $toAccount->applyTransactionEffect(Transaction::TYPE_ABONO, $validatedData['amount']);
        });

        $this->resetUI();
        $this->alert('success', 'Transferencia registrada.');
    }

    public function saveSubscription(): void
    {
        $validatedData = $this->validate([
            'subscription_id' => ['required', 'integer', $this->activeSubscriptionRule()],
            'effective_date' => ['required', 'date'],
            'billing_period' => ['required', 'date_format:Y-m'],
        ]);

        try {
            DB::transaction(function () use ($validatedData) {
                $subscription = Subscription::query()
                    ->whereKey($validatedData['subscription_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $subscription || ! $subscription->is_active) {
                    throw ValidationException::withMessages([
                        'subscription_id' => 'La suscripción seleccionada ya no existe o está pausada.',
                    ]);
                }

                $category = Category::query()
                    ->whereKey($subscription->category_id)
                    ->where('is_active', true)
                    ->first();

                if (! $category) {
                    throw ValidationException::withMessages([
                        'subscription_id' => 'La categoría de la suscripción está archivada. Actualiza la suscripción antes de pagarla.',
                    ]);
                }

                if ($subscription->hasPaidPeriod($validatedData['billing_period'])) {
                    throw ValidationException::withMessages([
                        'billing_period' => 'Ya registraste un pago de esta suscripción para ese periodo.',
                    ]);
                }

                $account = $this->lockedAccount($subscription->account_id);
                $this->ensureAccountAllows($account, 'allows_purchases', 'subscription_id', 'La cuenta de la suscripción no permite pagos.');
                $this->ensureSufficientFunds($account, $subscription->amount, 'subscription_id');

                Transaction::create([
                    'description' => $subscription->name,
                    'account_id' => $subscription->account_id,
                    'category_id' => $subscription->category_id,
                    'type' => Transaction::TYPE_CARGO,
                    'effective_date' => $validatedData['effective_date'],
                    'amount' => $subscription->amount,
                    'operation_type' => Transaction::OPERATION_SUBSCRIPTION,
                    'subscription_id' => $subscription->id,
                    'billing_period' => $validatedData['billing_period'],
                ]);

                $account->applyTransactionEffect(Transaction::TYPE_CARGO, $subscription->amount);
            });
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')
                || str_contains(strtolower($exception->getMessage()), 'duplicate')) {
                throw ValidationException::withMessages([
                    'billing_period' => 'Ya registraste un pago de esta suscripción para ese periodo.',
                ]);
            }

            throw $exception;
        }

        $this->resetUI();
        $this->alert('success', 'Pago de suscripción registrado.');
    }

    public function deleteConfimation($id)
    {
        $this->selected_id = $id;
        $this->dispatch('show-alert');
    }

    public function destroy()
    {
        /* Obtener el registro */
        $tr = Transaction::find($this->selected_id);

        if (! $tr) {
            $this->alert('error', 'El registro seleccionado ya no existe.');
            $this->resetUI();

            return;
        }

        DB::transaction(function () use ($tr) {
            /* Si la transacción pertenece a un grupo, se elimina la operación completa. */
            $rows = $tr->transaction_group_id
                ? Transaction::where('transaction_group_id', $tr->transaction_group_id)->orderBy('id')->get()
                : collect([$tr]);

            /* Revertir el efecto de cada asiento en su cuenta. */
            foreach ($rows as $row) {
                $account = Account::whereKey($row->account_id)->lockForUpdate()->first();

                if ($account) {
                    $account->applyTransactionEffect($row->type, $row->amount, true);
                }
            }

            /* Procedemos a eliminar todos los asientos del grupo. */
            foreach ($rows as $row) {
                $row->delete();
            }
        });

        $this->resetUI();
        $this->alert('success', 'Registro eliminado con éxito.');
    }

    /* Devuelve una cuenta bloqueada para actualizar su saldo. */
    protected function lockedAccount($id, string $field = 'account_id', bool $requireActive = true): Account
    {
        $query = Account::whereKey($id);

        if ($requireActive) {
            $query->where('is_active', true);
        }

        $account = $query->lockForUpdate()->first();

        if (! $account) {
            throw ValidationException::withMessages([
                $field => 'La cuenta seleccionada ya no existe o está archivada.',
            ]);
        }

        return $account;
    }

    /* Verifica que la cuenta permita la operación solicitada. */
    protected function ensureAccountAllows(Account $account, string $flag, string $field, string $message): void
    {
        if (! $account->{$flag}) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    /* Verifica que la cuenta tenga fondos suficientes para la operación. */
    protected function ensureSufficientFunds(Account $account, $amount, string $field = 'amount'): void
    {
        if (round((float) $account->available_amount, 2) < round((float) $amount, 2)) {
            throw ValidationException::withMessages([
                $field => 'El monto excede el saldo disponible de la cuenta '.$account->name.'.',
            ]);
        }
    }

    /* Convierte la fecha almacenada en un valor compatible con el input. */
    protected function dateForInput($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return substr((string) $value, 0, 10);
    }

    /* Regla para cuentas no eliminadas. */
    protected function activeAccountRule(bool $requireActive = true)
    {
        $rule = Rule::exists('accounts', 'id')->whereNull('deleted_at');

        if ($requireActive) {
            $rule->where('is_active', true);
        }

        return $rule;
    }

    /* Regla para categorías no eliminadas. */
    protected function activeCategoryRule(bool $requireActive = true)
    {
        $rule = Rule::exists('categories', 'id')->whereNull('deleted_at');

        if ($requireActive) {
            $rule->where('is_active', true);
        }

        return $rule;
    }

    /* Regla para categorías que se pueden usar en una compra. */
    protected function usableCategoryRule()
    {
        return Rule::exists('categories', 'id')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->whereNull('system_key');
    }

    /* Regla para suscripciones activas. */
    protected function activeSubscriptionRule()
    {
        return Rule::exists('subscriptions', 'id')
            ->whereNull('deleted_at')
            ->where('is_active', true);
    }

    /* Devuelve una categoría interna configurada por su clave. */
    protected function systemCategory(string $key, string $field = 'category_id'): Category
    {
        $category = Category::query()
            ->withSystemKey($key)
            ->where('is_active', true)
            ->first();

        if (! $category) {
            throw ValidationException::withMessages([
                $field => 'No existe la categoría interna requerida ('.$key.'). Revisa la configuración de categorías.',
            ]);
        }

        return $category;
    }

    /* Indica si una categoría es interna del sistema. */
    protected function categoryIsInternal($id): bool
    {
        return Category::query()->whereKey($id)->whereNotNull('system_key')->exists();
    }
}
