
<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Suscripciones
    </h2>
</x-slot>
<div>
    <x-content>
        <div class="py-1 flex items-center">
            <x-button data-tooltip-target="tooltip-open" class="mr-2" wire:click="$set('open', true)">
                <i class="fa-solid fa-plus"></i>
            </x-button>
            <div id="tooltip-open" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
                Agregar suscripción
                <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
        </div>
        <div class="py-3 flex flex-col gap-2 sm:flex-row sm:items-center">
            <x-input class="flex-1" type="text" wire:model.live.debounce.300ms='search' placeholder="Buscar..." />
            <select wire:model.live="filter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                <option value="all">Todas</option>
                <option value="active">Activas</option>
                <option value="inactive">Pausadas</option>
            </select>
        </div>
        @if ($suscriptions->count())
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
                        <th scope="col" class="cursor-pointer px-6 py-3" wire:click="order('category_id')">
                            Categoría
                            @if ($sort == 'category_id')
                                <i class="fa-solid {{ $direction == 'ASC' ? 'fa-sort-up' : 'fa-sort-down' }} float-right"></i>
                            @else
                                <i class="fa-solid fa-sort float-right"></i>
                            @endif
                        </th>
                        <th scope="col" class="cursor-pointer px-6 py-3" wire:click="order('account_id')">
                            Cuenta
                            @if ($sort == 'account_id')
                                <i class="fa-solid {{ $direction == 'ASC' ? 'fa-sort-up' : 'fa-sort-down' }} float-right"></i>
                            @else
                                <i class="fa-solid fa-sort float-right"></i>
                            @endif
                        </th>
                        <th scope="col" class="cursor-pointer px-6 py-3" wire:click="order('amount')">
                            Monto
                            @if ($sort == 'amount')
                                <i class="fa-solid {{ $direction == 'ASC' ? 'fa-sort-up' : 'fa-sort-down' }} float-right"></i>
                            @else
                                <i class="fa-solid fa-sort float-right"></i>
                            @endif
                        </th>
                        <th scope="col" class="cursor-pointer px-6 py-3" wire:click="order('next_payment_date')">
                            Próximo pago
                            @if ($sort == 'next_payment_date')
                                <i class="fa-solid {{ $direction == 'ASC' ? 'fa-sort-up' : 'fa-sort-down' }} float-right"></i>
                            @else
                                <i class="fa-solid fa-sort float-right"></i>
                            @endif
                        </th>
                        <th scope="col" class="cursor-pointer px-6 py-3" wire:click="order('last_paid_at')">
                            Último pago
                            @if ($sort == 'last_paid_at')
                                <i class="fa-solid {{ $direction == 'ASC' ? 'fa-sort-up' : 'fa-sort-down' }} float-right"></i>
                            @else
                                <i class="fa-solid fa-sort float-right"></i>
                            @endif
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
                    @foreach($suscriptions as $item)
                    @php
                        $statusClasses = match ($item->status) {
                            'paused' => 'bg-gray-100 text-gray-700',
                            'overdue' => 'bg-red-100 text-red-700',
                            'due_soon' => 'bg-amber-100 text-amber-700',
                            default => 'bg-green-100 text-green-700',
                        };
                    @endphp
                    <tr wire:key="subscription-{{ $item->id }}" class="{{ $item->is_active ? '' : 'bg-gray-50 text-gray-400' }}">
                        <td class="px-6 py-4">
                            {{ $item->name }}
                            <span class="block text-xs text-gray-400">Día {{ $item->payment_day }}</span>
                        </td>
                        <td class="px-6 py-4">
                            {{ $item->category_name }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $item->account_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            $ {{ number_format($item->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $item->next_payment_date ? \Illuminate\Support\Carbon::parse($item->next_payment_date)->format('d/m/Y') : '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $item->last_paid_at ? \Illuminate\Support\Carbon::parse($item->last_paid_at)->format('d/m/Y') : 'Sin pagos' }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $statusClasses }}">
                                {{ $item->status_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-secondary-button class="mr-2" wire:click="edit({{ $item->id }})">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </x-secondary-button>

                            @if($item->is_active)
                                <x-secondary-button class="mr-2" wire:click="pause({{ $item->id }})">
                                    <i class="fa-solid fa-pause"></i>
                                </x-secondary-button>
                            @else
                                <x-secondary-button class="mr-2" wire:click="resume({{ $item->id }})">
                                    <i class="fa-solid fa-play"></i>
                                </x-secondary-button>
                            @endif

                            <x-danger-button wire:click.prevent='deleteConfimation({{ $item->id }})'>
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
                    {{ $suscriptions->links() }}
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
