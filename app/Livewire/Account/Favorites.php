<?php

namespace App\Livewire\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Favorites extends Component
{
    use WithPagination;

    #[On('favorite-changed')]
    public function refreshFavorites(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        abort_unless(Auth::check(), 401);
        $products = Auth::user()->favoriteProducts()->visibleInStorefront()->withFavoriteState()
            ->with(['images', 'inventoryItem'])->orderByPivot('id', 'desc')->paginate(12);

        return view('livewire.account.favorites', compact('products'));
    }
}
