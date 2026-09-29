<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $metrics = [
            'paid_sales_cents' => Order::whereIn('status', [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered])->sum('total_cents'),
            'pending_orders' => Order::where('status', OrderStatus::PendingPayment)->count(),
            'open_purchases' => PurchaseOrder::whereIn('status', [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Placed, PurchaseOrderStatus::PartiallyReceived])->count(),
            'low_stock' => InventoryItem::whereColumn('on_hand', '<=', 'minimum_level')->count(),
        ];

        return view('admin.dashboard', compact('metrics'));
    }
}
