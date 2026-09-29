<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::query()->where('user_id', Auth::id())->with('shipment')->latest()->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(string $publicNumber): View
    {
        $order = Order::query()
            ->where('user_id', Auth::id())
            ->where('public_number', $publicNumber)
            ->with(['items', 'statusHistories', 'payments', 'shipment.events'])
            ->firstOrFail();

        return view('orders.show', compact('order'));
    }
}
