<?php

namespace App\Livewire;

use App\Models\CartItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class HeaderCart extends Component
{
    protected $listeners = ['cart-updated' => '$refresh'];

    public function render(): View
    {
        $quantity = Auth::check()
            ? (int) CartItem::query()->whereHas('cart', fn ($query) => $query->where('user_id', Auth::id()))->sum('quantity')
            : 0;

        return view('livewire.header-cart', compact('quantity'));
    }
}
