<div>
    <x-dialog-modal wire:model="subscriptions_modal">
        <x-slot name="title">
            Registrar pago de suscripción
        </x-slot>

        <x-slot name="content">

            <div class="mb-4">
                <x-label value="Suscripción" class="mb-2" />
                <select wire:model="subscription_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="" selected>Seleccione</option>
                    @foreach($subscriptions as $key => $subscription)
                    <option value="{{$key}}">{{$subscription}}</option>
                    @endforeach
                </select>
                <x-input-error for="subscription_id" />
                @if($subscription_next_payment)
                    <p class="mt-1 text-xs text-gray-500">Último vencimiento registrado: {{ $subscription_next_payment }}</p>
                @endif
            </div>

            <div class="mb-4">
                <x-label value="Periodo que se paga" class="mb-2" />
                <x-input type="month" wire:model="billing_period" class="w-full" />
                <x-input-error for="billing_period" />
                <p class="mt-1 text-xs text-gray-500">
                    Se propone el próximo periodo pendiente. No se puede pagar dos veces el mismo periodo.
                </p>
            </div>
        
            <div class="mb-4">
                <x-label value="Fecha" class="mb-2" />
                <x-date wire:model="effective_date"/>
                <x-input-error for="effective_date" />
            </div>            

        </x-slot>


        <x-slot name="footer">

            <x-secondary-button class="mr-2" wire:click.prevent="resetUI()" wire:click="$set('subscriptions_modal', false)">
                Cancelar
            </x-secondary-button>

            <x-button wire:click="saveSubscription">
                Registrar
            </x-button>

        </x-slot>
    </x-dialog-modal>
</div>