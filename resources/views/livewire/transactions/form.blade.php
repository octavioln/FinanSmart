<div>
    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            {{ $editing_group ? 'Editar operación' : ($selected_id > 0 ? 'Editar transacción' : 'Registrar compra') }}
        </x-slot>

        <x-slot name="content">

            @if($editing_group)
                <div class="mb-4 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    Esta operación genera dos movimientos. Solo puedes cambiar el monto y la fecha.
                </div>

                <div class="mb-4">
                    <x-label value="Categoría" class="mb-2" />
                    <p class="text-sm text-gray-700">{{ $categoryNames[$category_id] ?? '—' }}</p>
                </div>

                <div class="mb-4">
                    <x-label value="Descripción" class="mb-2" />
                    <p class="text-sm text-gray-700">{{ $description }}</p>
                </div>

                <div class="mb-4">
                    <x-label value="Cuenta" class="mb-2" />
                    <p class="text-sm text-gray-700">{{ $accountNames[$account_id] ?? '—' }}</p>
                </div>
            @else
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
                    <x-label value="Descripción" class="mb-2" />
                    <x-input type="text" wire:model="description" class="w-full"/>
                    <x-input-error for="description" />
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
            @endif

            <div class="mb-4">
                <x-label value="Monto" class="mb-2" />
                <x-input type="number" step="0.01" min="0.01" inputmode="decimal" wire:model="amount" class="w-full"/>
                <x-input-error for="amount" />
            </div>

            <div class="mb-4">
                <x-label value="Fecha" class="mb-2" />
                <x-date wire:model="effective_date"/>
                <x-input-error for="effective_date" />
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
