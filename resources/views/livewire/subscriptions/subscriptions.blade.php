
<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Suscripciones
    </h2>
</x-slot>
<div>
    <x-content>
        <div class="py-1 flex items-center">
            <x-button data-tooltip-target="tooltip-open" class="mr-2" wire:model="open" wire:click="$set('open', true)">
                <i class="fa-solid fa-plus"></i>
            </x-button>
            <div id="tooltip-open" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
                Agregar suscripción
                <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
        </div>
        <div class="py-3 flex items-center">
            <x-input class="flex-1 mr-2" type="text" wire:model.live.debounce.300ms='search' placeholder="Buscar..." />
        </div>
        @if ($suscriptions->count())
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="cursor-pointer px-6 py-3 " wire:click="order('name')">
                            Nombre
                            @if ($sort == 'name')
                                @if ($direction == 'ASC')
                                    <i class="fa-solid fa-sort-up float-right "></i>
                                @else
                                    <i class="fa-solid fa-sort-down float-right "></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right "></i>
                            @endif
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
                        <th scope="col" class="cursor-pointer px-6 py-3" wire:click="order('account_id')">
                            Cuenta
                            @if ($sort == 'account_id')
                                @if ($direction == 'ASC')
                                    <i class="fa-solid fa-sort-up float-right "></i>
                                @else
                                    <i class="fa-solid fa-sort-down float-right "></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right "></i>
                            @endif
                        </th>
                        <th scope="col" class="cursor-pointer px-6 py-3 " wire:click="order('payment_day')">
                            Día de pago
                            @if ($sort == 'payment_day')
                                @if ($direction == 'ASC')
                                    <i class="fa-solid fa-sort-up float-right "></i>
                                @else
                                    <i class="fa-solid fa-sort-down float-right "></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right "></i>
                            @endif
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
                    @foreach($suscriptions as $item)
                    <tr>
                        <td class="px-6 py-4 justify-center">
                            {{$item->name}}
                        </td>
                        <td class="px-6 py-4 justify-center">
                            {{$item->category_name}}
                        </td>
                        <td class="px-6 py-4 justify-center ">
                            {{$item->account_name}}
                        </td>
                        <td class="px-6 py-4 justify-center ">
                            {{$item->payment_day}}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                           $ {{ number_format($item->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 justify-center ">
                            <x-secondary-button class="mr-3" wire:click="edit({{$item->id}})">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </x-secondary-button>
                            <x-danger-button class="mr-3" wire:click.prevent='deleteConfimation({{$item->id}})'>
                                <i class="fa-solid fa-trash"></i>
                            </x-danger-button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
            @if($suscriptions->hasPages())
                <div class="p-2">
                    {{$suscriptions->links()}}
                </div>
            @endif
        @else
            <div class="px-6 py-4">
                No existen registros que mostrar
            </div>
        @endif
    </x-content>
    @include('livewire.subscriptions.form')
</div>