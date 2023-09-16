
<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Transacciones
    </h2>
</x-slot>
<div>
    <x-content>
        <div class="py-1 flex items-center">
            <x-button data-tooltip-target="tooltip-open" class="mr-2" wire:model="open" wire:click="$set('open', true)">
                <i class="fa-solid fa-cash-register fa-2x"></i>
            </x-button>
            <div id="tooltip-open" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
                Registrar compra
                <div class="tooltip-arrow" data-popper-arrow></div>
            </div>

            <x-button data-tooltip-target="tooltip-subscriptions" class="mr-2" wire:model="subscriptions_modal" wire:click="$set('subscriptions_modal', true)">
                <i class="fa-solid fa-clock-rotate-left fa-2x"></i>
            </x-button>
            <div id="tooltip-subscriptions" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
                Pago suscripción
                <div class="tooltip-arrow" data-popper-arrow></div>
            </div>

            <x-button data-tooltip-target="tooltip-withdrawal"  class="mr-2" wire:model="withdrawal_modal" wire:click="$set('withdrawal_modal', true)">
                <i class="fa-solid fa-money-bills fa-2x"></i>
            </x-button>
            <div id="tooltip-withdrawal" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
                Retiro de cajero
                <div class="tooltip-arrow" data-popper-arrow></div>
            </div>

            <x-button data-tooltip-target="tooltip-deposit" class="mr-2" wire:model="deposit_modal" wire:click="$set('deposit_modal', true)">
                <i class="fa-solid fa-circle-dollar-to-slot fa-2x"></i> 
            </x-button>
            <div id="tooltip-deposit" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
                Deposito a cuenta
                <div class="tooltip-arrow" data-popper-arrow></div>
            </div>

            <x-button data-tooltip-target="tooltip-savings" class="mr-2" wire:model="savings_modal" wire:click="$set('savings_modal', true)">
                <i class="fa-solid fa-piggy-bank fa-2x"></i>  
            </x-button>
            <div id="tooltip-savings" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
                Registrar ahorro
                <div class="tooltip-arrow" data-popper-arrow></div>
            </div>

            <x-button data-tooltip-target="tooltip-transfer" class="mr-2" wire:model="transfer_modal" wire:click="$set('transfer_modal', true)">
                <i class="fa-solid fa-money-bill-transfer fa-2x"></i> 
            </x-button>
            <div id="tooltip-transfer" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
                Transferir entre cuentas
                <div class="tooltip-arrow" data-popper-arrow></div>
            </div>

        </div>
        <div class="py-3 flex items-center">
            <x-input class="flex-1 mr-2" type="text" wire:model='search' placeholder="Buscar..." />
        </div>
        @if ($transactions->count())
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="cursor-pointer px-6 py-3 " wire:click="order('effective_date')">
                            Fecha
                            @if ($sort == 'effective_date')
                                @if ($direction == 'ASC')
                                    <i class="fa-solid fa-sort-up float-right "></i>
                                @else
                                    <i class="fa-solid fa-sort-down float-right "></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right "></i>
                            @endif
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Descripción
                        </th>
                        <th scope="col" class="cursor-pointer px-6 py-3" wire:click="order('category_id')">
                            Categoría
                            @if ($sort == 'category_id')
                                @if ($direction == 'ASC')
                                    <i class="fa-solid fa-sort-up float-right "></i>
                                @else
                                    <i class="fa-solid fa-sort-down float-right "></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right "></i>
                            @endif
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Cuenta
                        </th>
                        <th scope="col" class="cursor-pointer px-6 py-3 " wire:click="order('amount')">
                            Monto
                            @if ($sort == 'amount')
                                @if ($direction == 'ASC')
                                    <i class="fa-solid fa-sort-up float-right "></i>
                                @else
                                    <i class="fa-solid fa-sort-down float-right "></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right "></i>
                            @endif
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Acción
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $item)
                    <tr>
                        <td class="px-6 py-4   justify-center">
                            {{ date('d/m/Y', strtotime($item->effective_date)) }}
                        </td>
                        <td class="px-6 py-4 justify-center">
                            {{$item->description}}
                        </td>
                        <td class="px-6 py-4 justify-center">
                            {{$item->category_name}}
                        </td>
                        <td class="px-6 py-4 justify-center ">
                            {{$item->account_name}}
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap {{$item->type == 'Abono' ? 'text-green-500' : 'text-red-600'}} ">
                            {{$item->type == 'Cargo' ? '-' : ''}}  ${{ number_format($item->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 justify-center ">
                            <x-danger-button class="mr-3" wire:click.prevent='deleteConfimation({{$item->id}})'>
                                <i class="fa-solid fa-trash"></i>
                            </x-danger-button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
            @if($transactions->hasPages())
                <div class="p-2">
                    {{$transactions->links()}}
                </div>
            @endif
        @else
            <div class="px-6 py-4">
                No existen registros que mostrar
            </div>
        @endif
    </x-content>
    @include('livewire.transactions.form')
    @include('livewire.transactions.withdraw')
    @include('livewire.transactions.deposit')
    @include('livewire.transactions.savings')
    @include('livewire.transactions.transfer')
    @include('livewire.transactions.subscriptions')
</div>