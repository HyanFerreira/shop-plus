<?php

namespace App\Livewire\Catalog;

use App\Enums\CatalogStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Browser extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $sort = 'newest';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Product::query()
            ->active()
            ->whereHas('category', fn (Builder $query) => $query->where('status', CatalogStatus::Active))
            ->with(['category', 'images']);

        if (trim($this->search) !== '') {
            $term = addcslashes(trim($this->search), '\\%_');
            $query->where(function (Builder $query) use ($term) {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%');
            });
        }

        if ($this->category !== '') {
            $query->whereHas('category', fn (Builder $query) => $query
                ->where('slug', $this->category)
                ->where('status', CatalogStatus::Active));
        }

        match ($this->sort) {
            'name' => $query->orderBy('name'),
            'price_asc' => $query->orderBy('price_cents')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price_cents')->orderBy('id'),
            default => $query->latest('id'),
        };

        $products = $query->paginate(12);
        $categories = Category::active()->orderBy('name')->get();

        return view('livewire.catalog.browser', compact('products', 'categories'));
    }
}
