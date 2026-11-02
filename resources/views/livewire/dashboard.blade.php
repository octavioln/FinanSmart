<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Dashboard
    </h2>
</x-slot>
<div>
    <x-content>
        @if($dueSubscriptions->isNotEmpty())
            <div class="mx-4 mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                <h2 class="font-semibold text-amber-800">Suscripciones vencidas o por vencer</h2>
                <ul class="mt-2 space-y-1 text-sm text-amber-900">
                    @foreach($dueSubscriptions as $subscription)
                        <li>
                            <span class="font-semibold">{{ $subscription->name }}</span>
                            — {{ $subscription->next_payment_date->format('d/m/Y') }}
                            ({{ $subscription->scheduleStatusLabel() }})
                            — $ {{ number_format((float) $subscription->amount, 2) }}
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('subscriptions') }}" class="mt-2 inline-block text-sm font-semibold text-amber-800 underline">
                    Ver suscripciones
                </a>
            </div>
        @endif

        <h2 class="px-4">Dinero disponible</h2>
        <div class="grid gap-6 py-6 px-4 mb-8 md:grid-cols-3 xl:grid-cols-3">
            @foreach($accounts as $item)
                <x-card type="info">
                    <x-slot name="logo">
                        @if($item->is_cash)
                            <i class="fa-solid fa-money-bills"></i>
                        @elseif($item->is_savings)
                            <i class="fa-solid fa-piggy-bank"></i>
                        @elseif($item->is_bank_account)
                            <i class="fa-solid fa-credit-card"></i>
                        @else
                            <i class="fa-solid fa-wallet"></i>
                        @endif
                    </x-slot>
                    <x-slot name="title">
                        {{$item->name}}
                    </x-slot>

                    <x-slot name="total">
                        $ {{number_format($item->available_amount,2)}}
                    </x-slot>
                </x-card>
            @endforeach
        </div>

        <h2 class="px-4">Transacciones más recientes</h2>
        @if ($transactions->count())
        <div class="py-4 relative overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="cursor-pointer px-6 py-3 " wire:click="order('effective_date')">
                            Fecha
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Descripción
                        </th>
                        <th scope="col" class="cursor-pointer px-6 py-3" wire:click="order('category_id')">
                            Categoría
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Cuenta
                        </th>
                        <th scope="col" class="cursor-pointer px-6 py-3 " wire:click="order('amount')">
                            Monto
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
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="px-6 py-4">
            No hay transacciones registradas.
        </div>
        @endif
    </x-content>
</div>