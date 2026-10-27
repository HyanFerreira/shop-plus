<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicMonitoringController extends Controller
{
    private const PAID_STATUSES = ['paid', 'processing', 'shipped', 'delivered'];

    // Every aggregate, relation and detail uses this same defensive boundary.
    // An order mixing manual and automatic products is excluded in its entirety.
    private function syntheticOrders(): Builder
    {
        return Order::query()->where('is_automated_demo', true)
            ->whereHas('user', fn (Builder $users) => $users->where('is_automated_demo', true))
            ->whereHas('items')
            ->whereDoesntHave('items', fn (Builder $items) => $items->whereDoesntHave('product', fn (Builder $products) => $products->withTrashed()->where('is_automated_demo', true)));
    }

    private function orderListing(): Builder
    {
        return $this->syntheticOrders()->select(['id', 'user_id', 'public_number', 'status', 'total_cents', 'created_at'])
            ->with(['user:id,name', 'items:id,order_id,product_id,quantity', 'items.product' => fn ($products) => $products->withTrashed()->select(['id', 'name'])])
            ->orderByDesc('created_at')->orderByDesc('id');
    }

    private function cards(): array
    {
        $now = now();

        return ['totalUsers' => User::query()->count(), 'automatedUsersCount' => User::query()->where('is_automated_demo', true)->count(), 'recentUsersCount' => User::query()->whereBetween('created_at', [$now->copy()->subMinutes(5), $now])->count()];
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'tab' => ['sometimes', Rule::in(['users', 'products', 'transactions'])],
            'client_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
        ]);
        $tab = $filters['tab'] ?? 'users';
        if ($tab === 'users') {
            $records = User::query()->select(['id', 'name', 'created_at'])->where('is_automated_demo', true)
                ->selectSub($this->syntheticOrders()->selectRaw('COUNT(*)')->whereColumn('orders.user_id', 'users.id'), 'simulated_orders_count')
                ->selectSub($this->syntheticOrders()->selectRaw('COALESCE(SUM(total_cents), 0)')->whereColumn('orders.user_id', 'users.id')->whereIn('status', self::PAID_STATUSES), 'paid_purchases_cents')
                ->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->withQueryString();
        } elseif ($tab === 'products') {
            $soldQuantity = OrderItem::query()->selectRaw('COALESCE(SUM(quantity), 0)')->whereColumn('order_items.product_id', 'products.id')
                ->whereIn('order_id', $this->syntheticOrders()->select('id')->whereIn('status', self::PAID_STATUSES));
            $records = Product::query()->withTrashed()->select(['id', 'name', 'sku', 'price_cents', 'created_at'])->where('is_automated_demo', true)
                ->with(['images' => fn ($images) => $images->select(['id', 'product_id', 'path', 'sort_order'])->limit(1), 'inventoryItem:id,product_id,on_hand,reserved'])
                ->selectSub($this->syntheticOrders()->selectRaw('COUNT(*)')->whereHas('items', fn (Builder $items) => $items->whereColumn('order_items.product_id', 'products.id')), 'simulated_orders_count')
                ->selectSub($soldQuantity, 'sold_quantity')->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->withQueryString();
        } else {
            $query = $this->orderListing();
            if (! empty($filters['client_id'])) {
                $query->where('user_id', $filters['client_id']);
            }
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (! empty($filters['from'])) {
                $query->where('created_at', '>=', CarbonImmutable::parse($filters['from'], 'America/Sao_Paulo')->startOfDay()->utc());
            }
            if (! empty($filters['to'])) {
                $query->where('created_at', '<', CarbonImmutable::parse($filters['to'], 'America/Sao_Paulo')->addDay()->startOfDay()->utc());
            }
            $records = $query->paginate(20)->withQueryString();
        }

        return view('monitoring.index', [...$this->cards(), 'tab' => $tab, 'records' => $records, 'filters' => $filters]);
    }

    public function user(int $user): View
    {
        $subject = User::query()->select(['id', 'name', 'created_at'])->where('is_automated_demo', true)->findOrFail($user);
        $orders = $this->orderListing()->where('user_id', $subject->id)->paginate(20);

        return view('monitoring.related', ['subject' => $subject, 'kind' => 'user', 'orders' => $orders]);
    }

    public function product(int $product): View
    {
        $subject = Product::query()->withTrashed()->select(['id', 'name', 'sku', 'price_cents'])->where('is_automated_demo', true)->findOrFail($product);
        $orders = $this->orderListing()->whereHas('items', fn (Builder $items) => $items->where('product_id', $subject->id))->paginate(20);

        return view('monitoring.related', ['subject' => $subject, 'kind' => 'product', 'orders' => $orders]);
    }

    public function order(int $order): View
    {
        $order = $this->orderListing()->with(['items' => fn ($items) => $items->select(['id', 'order_id', 'product_id', 'quantity', 'unit_price_cents', 'total_cents']), 'payments:id,order_id,status,amount_cents,processed_at'])->findOrFail($order);
        $paymentIds = $order->payments->modelKeys();
        $movements = StockMovement::query()->select(['id', 'inventory_item_id', 'type', 'quantity_delta', 'created_at'])
            ->whereHas('inventoryItem', fn (Builder $stock) => $stock->whereIn('product_id', $order->items->pluck('product_id')))
            ->where(function (Builder $movements) use ($order, $paymentIds) {
                $movements->where(fn (Builder $references) => $references->where('reference_type', Order::class)->where('reference_id', $order->id))
                    ->orWhere(fn (Builder $references) => $references->where('reference_type', Payment::class)->whereIn('reference_id', $paymentIds));
            })
            ->with(['inventoryItem:id,product_id', 'inventoryItem.product' => fn ($products) => $products->withTrashed()->select(['id', 'name'])])
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(20);

        return view('monitoring.order', compact('order', 'movements'));
    }
}
