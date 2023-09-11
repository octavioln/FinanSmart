<div>
    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Registrar compra
        </x-slot>

        <x-slot name="content">

        <div class="mb-4">
                <x-label value="Categoría" class="mb-2" />
                <select wire:model="category_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="" seledted>Seleccione</option>
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
                <x-label value="Monto" class="mb-2" />
                <x-input type="text" wire:model="amount" class="w-full"/>
                <x-input-error for="amount" />
            </div>

            <div class="mb-4">
                <x-label value="Forma de pago" class="mb-2" />
                <select wire:model="account_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="" seledted>Seleccione</option>
                    @foreach($accounts as $key => $account)
                    <option value="{{$key}}">{{$account}}</option>
                    @endforeach
                </select>
                <x-input-error for="account_id" />
            </div>
            
            <div class="mb-4">
                <x-label value="Fecha" class="mb-2" />
                <x-date wire:model="effective_date"/>
                <x-input-error for="effective_date" />
            </div>

        </x-slot>


        <x-slot name="footer">

            <x-secondary-button class="mr-2" wire:click.prevent="resetUI()" wire:click="$set('open', false)">
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