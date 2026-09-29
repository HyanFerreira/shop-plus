<div>
    @if (session('order-admin-status')) <div class="mb-4 rounded-md bg-green-50 p-4 text-green-800">{{ session('order-admin-status') }}</div> @endif
    <div class="space-y-5">
        @forelse ($orders as $order)
            <article class="rounded-lg bg-white p-6 shadow" wire:key="admin-order-{{ $order->id }}">
                <div class="flex flex-wrap justify-between gap-3"><div><h3 class="font-semibold">{{ $order->public_number }}</h3><p class="text-sm text-gray-500">Cliente: {{ $order->user->name }} · {{ $order->user->email }}</p></div><div class="text-right"><p>{{ $order->status->value }}</p><p class="font-semibold">{{ \App\Domain\Catalog\Price::fromCents($order->total_cents)->brl() }}</p></div></div>
                @if ($order->shipment)
                    <div class="mt-4 border-t pt-4"><p><strong>Rastreio fictício:</strong> {{ $order->shipment->tracking_code }}</p><p class="text-sm text-gray-600">{{ $order->shipment->status->value }} · {{ $order->shipment->method }}</p>
                        @if ($this->options($order->shipment))
                            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                                <select wire:model="targetStatus" class="rounded-md border-gray-300"><option value="">Próximo estado</option>@foreach ($this->options($order->shipment) as $option)<option value="{{ $option->value }}">{{ $option->value }}</option>@endforeach</select>
                                <input wire:model="note" maxlength="500" placeholder="Observação fictícia" class="flex-1 rounded-md border-gray-300">
                                <button type="button" wire:click="transition({{ $order->shipment->id }})" class="rounded-md bg-indigo-600 px-4 py-2 text-white">Atualizar</button>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="mt-4 border-t pt-4 text-sm text-gray-500">Aguardando pagamento autorizado.</p>
                @endif
                @if (in_array($order->status, [\App\Enums\OrderStatus::Paid, \App\Enums\OrderStatus::Processing, \App\Enums\OrderStatus::Shipped, \App\Enums\OrderStatus::Delivered], true))
                    <div class="mt-4 flex gap-2 border-t pt-4"><input wire:model="refundReason" maxlength="500" placeholder="Justificativa do reembolso" class="flex-1 rounded-md border-gray-300"><button wire:click="refund({{ $order->id }})" wire:confirm="Confirmar reembolso e devolução ao estoque?" class="rounded-md bg-red-700 px-4 py-2 text-white">Reembolsar</button></div>
                @endif
            </article>
        @empty
            <p class="rounded-lg bg-white p-8 text-center shadow">Nenhum pedido.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $orders->links() }}</div>
</div>
