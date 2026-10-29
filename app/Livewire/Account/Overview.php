<?php

namespace App\Livewire\Account;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Overview extends Component
{
    public function render(): View
    {
        $query = Order::query()->where('user_id', Auth::id());
        $counts = (clone $query)->selectRaw('status, COUNT(*) AS aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $ordersCount = (int) $counts->sum();
        $pendingCount = (int) $counts->get(OrderStatus::PendingPayment->value, 0);
        $deliveredCount = (int) $counts->get(OrderStatus::Delivered->value, 0);
        $favoritesCount = Auth::user()->favoriteProducts()->visibleInStorefront()->count();
        $recentOrders = $query->with(['items.product.images', 'shipment', 'statusHistories'])->latest()->orderByDesc('id')->limit(3)->get();

        return view('livewire.account.overview', compact('ordersCount', 'pendingCount', 'deliveredCount', 'favoritesCount', 'recentOrders'));
    }
}
