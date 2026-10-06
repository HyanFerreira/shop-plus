<?php

namespace App\Http\Controllers;

use App\Enums\CatalogStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $categories = Category::query()
            ->active()
            ->orderBy('name')
            ->limit(9)
            ->get();

        $products = Product::query()
            ->active()
            ->whereHas('category', fn (Builder $query) => $query->where('status', CatalogStatus::Active))
            ->with(['category', 'images'])
            ->latest()
            ->limit(5)
            ->get();

        $bestSellers = Product::query()
            ->active()
            ->whereHas('category', fn (Builder $query) => $query->where('status', CatalogStatus::Active))
            ->with(['category', 'images'])
            ->orderByDesc('rating_average')
            ->orderByDesc('rating_count')
            ->limit(5)
            ->get();

        return view('home', compact('categories', 'products', 'bestSellers'));
    }
}
