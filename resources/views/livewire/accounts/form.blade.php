<div>
    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            {{ $selected_id > 0 ? 'Editar cuenta' : 'Agregar cuenta' }}
        </x-slot>

        <x-slot name="content">
            <div class="mb-4">
                <x-label value="Nombre" class="mb-2" />
                <x-input type="text" wire:model="name" class="w-full" />
                <x-input-error for="name" />
            </div>

            <div class="mb-4">
                <x-label value="Descripción" class="mb-2" />
                <x-input type="text" wire:model="description" class="w-full" />
                <x-input-error for="description" />
            </div>

            <div class="mb-4">
                <x-label value="Saldo inicial" class="mb-2" />
                <x-input type="number" step="0.01" min="0" inputmode="decimal" wire:model="opening_balance" class="w-full" />
                <p class="mt-1 text-xs text-gray-500">
                    Es el saldo con el que abre la cuenta. Al editarlo se ajusta el saldo disponible por la diferencia.
                </p>
                <x-input-error for="opening_balance" />
            </div>

            <div class="mb-4 grid gap-3 sm:grid-cols-2">
                <label class="inline-flex items-center text-sm text-gray-700">
                    <x-checkbox class="mr-2" wire:model="is_bank_account" />
                    Es cuenta bancaria
                </label>
                <label class="inline-flex items-center text-sm text-gray-700">
                    <x-checkbox class="mr-2" wire:model="is_cash" />
                    Es la cuenta de efectivo
                </label>
                <label class="inline-flex items-center text-sm text-gray-700">
                    <x-checkbox class="mr-2" wire:model="is_savings" />
                    Es la cuenta de ahorro
                </label>
            </div>

            <div class="mb-4">
                <x-label value="Operaciones permitidas" class="mb-2" />
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="inline-flex items-center text-sm text-gray-700">
                        <x-checkbox class="mr-2" wire:model="allows_purchases" />
                        Compras y pagos
                    </label>
                    <label class="inline-flex items-center text-sm text-gray-700">
                        <x-checkbox class="mr-2" wire:model="allows_withdrawal" />
                        Retiros de cajero
                    </label>
                    <label class="inline-flex items-center text-sm text-gray-700">
                        <x-checkbox class="mr-2" wire:model="allows_deposits" />
                        Depósitos
                    </label>
                    <label class="inline-flex items-center text-sm text-gray-700">
                        <x-checkbox class="mr-2" wire:model="allows_transfers" />
                        Transferencias
                    </label>
                </div>
                <x-input-error for="is_cash" />
                <x-input-error for="is_savings" />
            </div>

            <p class="text-xs text-gray-500">
                Solo puede existir una cuenta de efectivo y una de ahorro activas. Al marcar una nueva, se quita la marca a la anterior.
            </p>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button class="mr-2" wire:click.prevent="resetUI">
                Cancelar
            </x-secondary-button>

            @if($selected_id < 1)
                <x-button wire:click="save">
                    Crear Registro
                </x-button>
            @else
                <x-button wire:click="update">
                    Actualizar Registro
                </x-button>
            @endif
        </x-slot>
    </x-dialog-modal>
</div>
