
<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Transacciones') }}
    </h2>
</x-slot>
<div>
    <x-content>
        <div class="py-1 flex items-center">
            <x-button class="mr-2" wire:model="open" wire:click="$set('open', true)">
                <i class="fa-solid fa-cart-shopping"></i> &nbsp; {{ __('Compra en comercio') }}
            </x-button>
            <!--<x-button class="mr-2">
                <i class="fa-brands fa-amazon"></i> &nbsp; {{ __('Pago de suscripción') }}
            </x-button>-->
            <x-button class="mr-2" wire:model="withdrawal_modal" wire:click="$set('withdrawal_modal', true)">
                <i class="fa-solid fa-money-bills"></i> &nbsp; {{ __('Retiro en cajero') }}
            </x-button>
            <!--<x-button class="mr-2">
                <i class="fa-solid fa-circle-dollar-to-slot"></i> &nbsp; {{ __('Registrar depósito') }}
            </x-button>-->
        </div>
        <div class="py-3 flex items-center">
            <x-input class="flex-1 mr-2" type="text" wire:model='search' placeholder="Buscar..." />
        </div>
        @if ($transactions->count())
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="w-24 cursor-pointer px-6 py-3" wire:click="order('id')">
                            ID
                            @if ($sort == 'id')
                                @if ($direction == 'ASC')
                                    <i class="fa-solid fa-sort-up float-right "></i>
                                @else
                                    <i class="fa-solid fa-sort-down float-right "></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right "></i>
                            @endif
                        </th>
                        <th scope="col" class="w-24 cursor-pointer px-6 py-3 " wire:click="order('effective_date')">
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
                        <th scope="col" class="cursor-pointer px-6 py-3">
                            Descripción
                        </th>
                        <th scope="col" class="w-100 cursor-pointer px-6 py-3 " wire:click="order('category_id')">
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
                        <th scope="col" class="w-24 cursor-pointer px-6 py-3 " wire:click="order('type')">
                            Tipo
                            @if ($sort == 'type')
                                @if ($direction == 'ASC')
                                    <i class="fa-solid fa-sort-up float-right "></i>
                                @else
                                    <i class="fa-solid fa-sort-down float-right "></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right "></i>
                            @endif
                        </th>
                        <th scope="col" class="w-50 cursor-pointer px-6 py-3 " wire:click="order('amount')">
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
                        <!--<th scope="col" class="px-6 py-3 text-center">
                        Acción
                        </th>-->
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $item)
                    <tr>
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            {{$item->id}}
                        </th>
                        <td class="px-6 py-4   justify-center">
                            {{ date('d/m/Y', strtotime($item->effective_date)) }}
                        </td>
                        <td class="px-6 py-4 justify ">
                            {{$item->description}}
                        </td>
                        <td class="px-6 py-4 justify-center">
                            {{$item->category_name}}
                        </td>
                        <td class="px-6 py-4 justify-center ">
                            {{$item->type}}
                        </td>
                        <td class="px-6 py-4 justify-center">
                            {{$item->amount}}
                        </td>
                        <!--<td class="px-6 py-4 flex justify-center">
                            <x-danger-button class="mr-3">
                                <i class="fa-solid fa-trash"></i>
                            </x-danger-button>   
                        </td> -->
                    </tr>
                    @endforeach
                </tbody>
            </table>
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
</div>