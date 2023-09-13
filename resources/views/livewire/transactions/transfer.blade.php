<div>
    <x-dialog-modal wire:model="transfer_modal">
        <x-slot name="title">
            Transferir entre cuentas 
        </x-slot>

        <x-slot name="content">

            <div class="mb-4">
                <x-label value="Cuenta origen" class="mb-2" />
                <select wire:model="from_account_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="" seledted>Seleccione</option>
                    @foreach($accounts_transfer as $key => $account)
                        @if($key != $to_account_id || $from_account_id == $key )
                            <option value="{{$key}}">{{$account}}</option>
                        @endif
                    @endforeach
                </select>
                <x-input-error for="from_account_id" />
            </div>

            <div class="mb-4">
                <x-label value="Cuenta destino" class="mb-2" />
                <select wire:model="to_account_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="" seledted>Seleccione</option>
                    @foreach($accounts_transfer as $key => $account)
                        @if($key != $from_account_id || $to_account_id == $key) 
                            <option value="{{$key}}">{{$account}}</option>
                        @endif
                    @endforeach
                </select>
                <x-input-error for="to_account_id" />
            </div>

            <div class="mb-4">
                <x-label value="Monto" class="mb-2" />
                <x-input type="text" wire:model="amount" class="w-full"/>
                <x-input-error for="amount" />
            </div>
        
            <div class="mb-4">
                <x-label value="Fecha" class="mb-2" />
                <x-date wire:model="effective_date"/>
                <x-input-error for="effective_date" />
            </div>            

        </x-slot>


        <x-slot name="footer">

            <x-secondary-button class="mr-2" wire:click.prevent="resetUI()" wire:click="$set('transfer_modal', false)">
                Cancelar
            </x-secondary-button>

            <x-button wire:click="saveTransfer">
                Registrar
            </x-button>

        </x-slot>
    </x-dialog-modal>
</div>