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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Browser extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    // Mantido para links existentes como /catalogo?category=eletronicos.
    #[Url]
    public string $category = '';

    #[Url]
    public array $selectedCategories = [];

    #[Url]
    public array $brands = [];

    #[Url]
    public string $minPrice = '';

    #[Url]
    public string $maxPrice = '';

    #[Url]
    public int $minimumRating = 0;

    #[Url]
    public bool $inStock = false;

    #[Url]
    public bool $freeShipping = false;

    #[Url]
    public bool $expressShipping = false;

    #[Url]
    public string $sort = 'relevance';

    #[Url]
    public int $perPage = 12;

    public function updating(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('selectedCategories', 'brands', 'minPrice', 'maxPrice', 'minimumRating', 'inStock', 'freeShipping', 'expressShipping', 'category');
        $this->resetPage();
    }

    public function render(): View
    {
        $validation = Validator::make($this->only([
            'search', 'category', 'selectedCategories', 'brands', 'minPrice', 'maxPrice',
            'minimumRating', 'sort', 'perPage',
        ]), [
            'search' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:120'],
            'selectedCategories' => ['array', 'max:50'],
            'selectedCategories.*' => ['required', 'string', 'max:120'],
            'brands' => ['array', 'max:50'],
            'brands.*' => ['required', 'string', 'max:120'],
            'minPrice' => ['nullable', 'numeric', 'between:0,9999999999'],
            'maxPrice' => ['nullable', 'numeric', 'between:0,9999999999'],
            'minimumRating' => ['integer', 'between:0,5'],
            'sort' => [Rule::in(['relevance', 'name', 'price_asc', 'price_desc', 'rating'])],
            'perPage' => ['integer', 'between:1,100'],
        ]);
        // Rendering must stop before malformed URL/hydrated data reaches SQL or Blade.
        abort_if($validation->fails(), 422, 'Filtros de catálogo inválidos.');

        $query = Product::query()
            ->active()
            ->whereHas('category', fn (Builder $query) => $query->where('status', CatalogStatus::Active))
            ->with(['category', 'images', 'inventoryItem']);

        if (trim($this->search) !== '') {
            $term = addcslashes(trim($this->search), '\\%_');
            $query->where(function (Builder $query) use ($term) {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%')
                    ->orWhere('brand', 'like', '%'.$term.'%');
            });
        }

        $categorySlugs = array_values(array_filter(array_unique([
            ...$this->selectedCategories,
            $this->category,
        ])));
        if ($categorySlugs !== []) {
            $query->whereHas('category', fn (Builder $builder) => $builder->whereIn('slug', $categorySlugs));
        }

        if ($this->brands !== []) {
            $query->whereIn('brand', $this->brands);
        }
        if (is_numeric($this->minPrice) && (int) $this->minPrice > 0) {
            $query->where('price_cents', '>=', (int) $this->minPrice * 100);
        }
        if (is_numeric($this->maxPrice) && (int) $this->maxPrice > 0) {
            $query->where('price_cents', '<=', (int) $this->maxPrice * 100);
        }
        if ($this->minimumRating > 0) {
            $query->where('rating_average', '>=', $this->minimumRating);
        }
        if ($this->inStock) {
            $query->whereHas('inventoryItem', fn (Builder $builder) => $builder->whereColumn('on_hand', '>', 'reserved'));
        }
        if ($this->freeShipping) {
            $query->where('free_shipping', true);
        }
        if ($this->expressShipping) {
            $query->where('express_shipping', true);
        }

        match ($this->sort) {
            'name' => $query->orderBy('name'),
            'price_asc' => $query->orderBy('price_cents')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price_cents')->orderBy('id'),
            'rating' => $query->orderByDesc('rating_average')->orderByDesc('rating_count'),
            default => $query->orderByDesc('rating_average')->orderByDesc('rating_count')->latest('id'),
        };

        $perPage = in_array($this->perPage, [12, 24, 48], true) ? $this->perPage : 12;
        $products = $query->paginate($perPage);
        $categories = Category::active()->withCount(['products' => fn (Builder $builder) => $builder->active()])->orderBy('name')->get();
        $availableBrands = Product::query()->active()->whereHas('category', fn (Builder $builder) => $builder->where('status', CatalogStatus::Active))->select('brand')->selectRaw('count(*) as products_count')->groupBy('brand')->orderBy('brand')->get();
        $priceCeiling = (int) (Product::query()->active()->max('price_cents') ?? 0) / 100;

        return view('livewire.catalog.browser', compact('products', 'categories', 'availableBrands', 'priceCeiling'));
    }
}
