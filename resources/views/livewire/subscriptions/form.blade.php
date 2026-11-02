<div>
    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            {{ $selected_id > 0 ? "Editar suscripción" : "Agregar suscripción"}}
        </x-slot>

        <x-slot name="content">

            <div class="mb-4">
                <x-label value="Descripción" class="mb-2" />
                <x-input type="text" wire:model="name" class="w-full"/>
                <x-input-error for="name" />
            </div>
            
            <div class="mb-4">
                <x-label value="Categoría" class="mb-2" />
                <select wire:model="category_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="" selected>Seleccione</option>
                    @foreach($categories as $key => $category)
                    <option value="{{$key}}">{{$category}}</option>
                    @endforeach
                </select>
                <x-input-error for="category_id" />
            </div>

            <div class="mb-4">
                <x-label value="Monto" class="mb-2" />
                <x-input type="number" step="0.01" min="0.01" inputmode="decimal" wire:model="amount" class="w-full"/>
                <x-input-error for="amount" />
            </div>

            <div class="mb-4">
                <x-label value="Forma de pago" class="mb-2" />
                <select wire:model="account_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="" selected>Seleccione</option>
                    @foreach($accounts as $key => $account)
                    <option value="{{$key}}">{{$account}}</option>
                    @endforeach
                </select>
                <x-input-error for="account_id" />
            </div>
            
            <div class="mb-4">
                <x-label value="Día de pago" class="mb-2" />
                <x-input type="number" min="1" max="31" inputmode="numeric" wire:model="payment_day" class="w-full"/>
                <x-input-error for="payment_day" />
            </div>

            <div class="mb-4">
                <label class="inline-flex items-center text-sm text-gray-700">
                    <x-checkbox class="mr-2" wire:model="is_active" />
                    Suscripción activa
                </label>
                <p class="mt-1 text-xs text-gray-500">
                    Una suscripción pausada conserva su historial, pero no aparece para registrar pagos.
                </p>
                <x-input-error for="is_active" />
            </div>

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