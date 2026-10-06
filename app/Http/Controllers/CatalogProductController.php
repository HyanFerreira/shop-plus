<?php

namespace App\Http\Controllers;

use App\Enums\CatalogStatus;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class CatalogProductController extends Controller
{
    public function __invoke(string $slug): View
    {
        $product = Product::query()
            ->active()
            ->where('slug', $slug)
            ->whereHas('category', fn (Builder $query) => $query->where('status', CatalogStatus::Active))
            ->with(['category', 'images', 'inventoryItem'])
            ->firstOrFail();

        $relatedProducts = Product::query()
            ->active()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->with(['images'])
            ->orderByDesc('rating_average')
            ->limit(4)
            ->get();

        return view('catalog.show', compact('product', 'relatedProducts'));
    }
}
