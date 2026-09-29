<?php

namespace App\Actions\Checkout;

use App\Domain\Checkout\ShippingCalculator;
use App\Enums\CatalogStatus;
use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Address;
use App\Models\Cart;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    public function __construct(private readonly ShippingCalculator $shipping) {}

    public function execute(User $user, int $addressId, string $shippingMethod, string $checkoutKey): Order
    {
        return DB::transaction(function () use ($user, $addressId, $shippingMethod, $checkoutKey) {
            $existing = Order::query()->where('user_id', $user->id)->where('checkout_key', $checkoutKey)->first();
            if ($existing) {
                return $existing;
            }

            if (! Str::isUuid($checkoutKey)) {
                throw ValidationException::withMessages(['checkout' => 'Chave de checkout inválida.']);
            }

            $address = Address::query()->where('user_id', $user->id)->find($addressId);
            $cart = Cart::query()->where('user_id', $user->id)->lockForUpdate()->first();
            $items = $cart?->items()->with(['product.category'])->orderBy('product_id')->lockForUpdate()->get();
            if (! $address || ! $items || $items->isEmpty()) {
                throw ValidationException::withMessages(['checkout' => 'Carrinho ou endereço inválido.']);
            }

            $inventory = InventoryItem::query()->whereIn('product_id', $items->pluck('product_id'))->orderBy('product_id')->lockForUpdate()->get()->keyBy('product_id');
            $subtotal = 0;
            $weight = 0;
            foreach ($items as $item) {
                $product = $item->product;
                $stock = $inventory->get($item->product_id);
                if (! $product || $product->status !== CatalogStatus::Active || $product->category?->status !== CatalogStatus::Active || ! $stock || $item->quantity > $stock->available()) {
                    throw ValidationException::withMessages(['checkout' => 'Um item do carrinho ficou indisponível.']);
                }
                $subtotal += $product->price_cents * $item->quantity;
                $weight += $product->weight_grams * $item->quantity;
            }

            $options = $this->shipping->options($address->postal_code_encrypted, $weight);
            $selected = $options[$shippingMethod] ?? null;
            if (! $selected) {
                throw ValidationException::withMessages(['shippingMethod' => 'Modalidade de entrega inválida.']);
            }

            $order = Order::create([
                'user_id' => $user->id,
                'public_number' => 'ORD-'.Str::upper(Str::random(16)),
                'checkout_key' => $checkoutKey,
                'status' => OrderStatus::PendingPayment,
                'subtotal_cents' => $subtotal,
                'shipping_cents' => $selected['price_cents'],
                'total_cents' => $subtotal + $selected['price_cents'],
                'address_snapshot_encrypted' => $this->addressSnapshot($address),
                'shipping_method' => $shippingMethod,
                'shipping_days' => $selected['days'],
                'placed_at' => now(),
            ]);

            foreach ($items as $item) {
                $product = $item->product;
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'unit_price_cents' => $product->price_cents,
                    'quantity' => $item->quantity,
                    'total_cents' => $product->price_cents * $item->quantity,
                ]);

                $stock = $inventory->get($product->id);
                $stock->increment('reserved', $item->quantity);
                StockMovement::create([
                    'inventory_item_id' => $stock->id,
                    'type' => StockMovementType::SaleReserved,
                    'quantity_delta' => $item->quantity,
                    'idempotency_key' => $this->movementKey($checkoutKey, $product->id),
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'reason' => 'Reserva para pedido '.$order->public_number,
                    'actor_id' => $user->id,
                ]);
            }

            $order->statusHistories()->create(['to_status' => OrderStatus::PendingPayment, 'actor_id' => $user->id, 'note' => 'Pedido criado']);
            $cart->items()->delete();

            return $order->load('items', 'statusHistories');
        }, 3);
    }

    /** @return array<string, string|null> */
    private function addressSnapshot(Address $address): array
    {
        return [
            'recipient' => $address->recipient_encrypted,
            'postal_code' => $address->postal_code_encrypted,
            'street' => $address->street_encrypted,
            'number' => $address->number_encrypted,
            'complement' => $address->complement_encrypted,
            'district' => $address->district_encrypted,
            'city' => $address->city_encrypted,
            'state' => $address->state_encrypted,
        ];
    }

    private function movementKey(string $key, int $productId): string
    {
        $hash = md5($key.':reservation:'.$productId);

        return substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-'.substr($hash, 12, 4).'-'.substr($hash, 16, 4).'-'.substr($hash, 20);
    }
}
