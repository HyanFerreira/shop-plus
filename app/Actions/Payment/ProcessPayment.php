<?php

namespace App\Actions\Payment;

use App\Domain\Payment\FictitiousCardValidator;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\SecurityAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProcessPayment
{
    public function __construct(private readonly FictitiousCardValidator $cards) {}

    public function execute(User $user, Order $order, string $number, string $cvv, int $expiryMonth, int $expiryYear, string $idempotencyKey): Payment
    {
        $card = $this->cards->validate($number, $cvv, $expiryMonth, $expiryYear);

        return DB::transaction(function () use ($user, $order, $card, $idempotencyKey) {
            if (! Str::isUuid($idempotencyKey)) {
                throw ValidationException::withMessages(['payment' => 'Chave de pagamento inválida.']);
            }

            $existing = Payment::query()->with('order')->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                if ($existing->order_id === $order->id && $existing->order->user_id === $user->id) {
                    return $existing;
                }

                throw ValidationException::withMessages(['payment' => 'Chave de pagamento inválida.']);
            }

            $order = Order::query()->where('user_id', $user->id)->lockForUpdate()->find($order->id);
            if (! $order || $order->status !== OrderStatus::PendingPayment) {
                throw ValidationException::withMessages(['payment' => 'Este pedido não pode ser pago.']);
            }

            $items = $order->items()->orderBy('product_id')->get();
            $inventory = InventoryItem::query()->whereIn('product_id', $items->pluck('product_id'))->orderBy('product_id')->lockForUpdate()->get()->keyBy('product_id');

            foreach ($items as $item) {
                $stock = $inventory->get($item->product_id);
                if (! $stock || $stock->reserved < $item->quantity || ($card['authorized'] && $stock->on_hand < $item->quantity)) {
                    throw ValidationException::withMessages(['payment' => 'Não foi possível concluir o pagamento.']);
                }
            }

            $payment = Payment::create([
                'order_id' => $order->id,
                'amount_cents' => $order->total_cents,
                'status' => $card['authorized'] ? PaymentStatus::Authorized : PaymentStatus::Failed,
                'token' => (string) Str::uuid(),
                'brand' => $card['brand'],
                'last_four' => $card['last_four'],
                'authorization_code' => $card['authorized'] ? Str::upper(Str::random(12)) : null,
                'idempotency_key' => $idempotencyKey,
                'processed_at' => now(),
            ]);

            foreach ($items as $item) {
                $stock = $inventory->get($item->product_id);
                $stock->decrement('reserved', $item->quantity);
                if ($card['authorized']) {
                    $stock->decrement('on_hand', $item->quantity);
                }
                StockMovement::create([
                    'inventory_item_id' => $stock->id,
                    'type' => $card['authorized'] ? StockMovementType::SaleCompleted : StockMovementType::ReservationReleased,
                    'quantity_delta' => -$item->quantity,
                    'idempotency_key' => $this->movementKey($idempotencyKey, $item->product_id),
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->id,
                    'reason' => $card['authorized'] ? 'Venda concluída' : 'Reserva liberada após recusa simulada',
                    'actor_id' => $user->id,
                ]);
            }

            $newStatus = $card['authorized'] ? OrderStatus::Paid : OrderStatus::Cancelled;
            $order->update(['status' => $newStatus]);
            $order->statusHistories()->create(['from_status' => OrderStatus::PendingPayment, 'to_status' => $newStatus, 'actor_id' => $user->id, 'note' => $card['authorized'] ? 'Pagamento fictício autorizado' : 'Pagamento fictício recusado']);

            if ($card['authorized']) {
                $shipment = Shipment::create([
                    'order_id' => $order->id,
                    'method' => $order->shipping_method,
                    'price_cents' => $order->shipping_cents,
                    'estimated_days' => $order->shipping_days,
                    'status' => ShipmentStatus::AwaitingProcessing,
                    'tracking_code' => 'TRK-'.Str::upper(Str::random(16)),
                ]);
                $shipment->events()->create(['to_status' => ShipmentStatus::AwaitingProcessing, 'note' => 'Entrega simulada criada automaticamente']);
            }

            app(SecurityAudit::class)->record($user, $card['authorized'] ? 'payment.authorized' : 'payment.failed', $payment, ['order_id' => $order->id, 'amount_cents' => $payment->amount_cents]);

            return $payment;
        }, 3);
    }

    private function movementKey(string $key, int $productId): string
    {
        $hash = md5($key.':payment:'.$productId);

        return substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-'.substr($hash, 12, 4).'-'.substr($hash, 16, 4).'-'.substr($hash, 20);
    }
}
