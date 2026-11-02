<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Cuentas
    </h2>
</x-slot>
<div>
    <x-content>
        <div class="py-1 flex items-center">
            <x-button data-tooltip-target="tooltip-open" class="mr-2" wire:click="$set('open', true)">
                <i class="fa-solid fa-plus"></i>
            </x-button>
            <div id="tooltip-open" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
                Agregar cuenta
                <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
        </div>

        <div class="py-3 flex flex-col gap-2 sm:flex-row sm:items-center">
            <x-input class="flex-1" type="text" wire:model.live.debounce.300ms='search' placeholder="Buscar..." />
            <label class="inline-flex items-center text-sm text-gray-600">
                <x-checkbox class="mr-2" wire:model.live="show_inactive" />
                Mostrar archivadas
            </label>
        </div>

        @if ($accounts->count())
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="cursor-pointer px-6 py-3" wire:click="order('name')">
                            Nombre
                            @if ($sort == 'name')
                                <i class="fa-solid {{ $direction == 'ASC' ? 'fa-sort-up' : 'fa-sort-down' }} float-right"></i>
                            @else
                                <i class="fa-solid fa-sort float-right"></i>
                            @endif
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Descripción
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Tipo
                        </th>
                        <th scope="col" class="cursor-pointer px-6 py-3 text-right" wire:click="order('available_amount')">
                            Saldo disponible
                            @if ($sort == 'available_amount')
                                <i class="fa-solid {{ $direction == 'ASC' ? 'fa-sort-up' : 'fa-sort-down' }} float-right"></i>
                            @else
                                <i class="fa-solid fa-sort float-right"></i>
                            @endif
                        </th>
                        <th scope="col" class="px-6 py-3 text-right">
                            Saldo inicial
                        </th>
                        <th scope="col" class="cursor-pointer px-6 py-3" wire:click="order('is_active')">
                            Estado
                            @if ($sort == 'is_active')
                                <i class="fa-solid {{ $direction == 'ASC' ? 'fa-sort-up' : 'fa-sort-down' }} float-right"></i>
                            @else
                                <i class="fa-solid fa-sort float-right"></i>
                            @endif
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Acción
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts as $item)
                    <tr wire:key="account-{{ $item->id }}" class="{{ $item->is_active ? '' : 'bg-gray-50 text-gray-400' }}">
                        <td class="px-6 py-4">
                            {{ $item->name }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $item->description }}
                        </td>
                        <td class="px-6 py-4">
                            @if($item->is_cash)
                                <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">Efectivo</span>
                            @elseif($item->is_savings)
                                <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">Ahorro</span>
                            @elseif($item->is_bank_account)
                                <span class="rounded-full bg-indigo-100 px-2 py-1 text-xs font-semibold text-indigo-700">Banco</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">Otra</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            $ {{ number_format($item->available_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            $ {{ number_format($item->opening_balance, 2) }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $item->is_active ? 'Activa' : 'Archivada' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-secondary-button class="mr-2" wire:click="edit({{ $item->id }})">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </x-secondary-button>

                            @if($item->is_active)
                                @if(! $item->is_cash && ! $item->is_savings)
                                    <x-danger-button wire:click="archive({{ $item->id }})">
                                        <i class="fa-solid fa-box-archive"></i>
                                    </x-danger-button>
                                @endif
                            @else
                                <x-button wire:click="restore({{ $item->id }})">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </x-button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($accounts->hasPages())
            <div class="p-2">
                {{ $accounts->links() }}
            </div>
        @endif
        @else
            <div class="px-6 py-4">
                No existen cuentas que mostrar
            </div>
        @endif
    </x-content>
    @include('livewire.accounts.form')
</div>
