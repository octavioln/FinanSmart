<div>
    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            {{ $selected_id > 0 ? 'Editar categoría' : 'Agregar categoría' }}
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

            <p class="text-xs text-gray-500">
                Las categorías internas (Ahorro, Traspaso entre cuentas y Depósito) las usa el sistema para sus operaciones y no se pueden archivar.
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
