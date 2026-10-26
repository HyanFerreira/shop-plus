<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(): View
    {
        return view('favorites.index');
    }

    public function store(Request $request, string $product): JsonResponse|RedirectResponse
    {
        $product = Product::query()->visibleInStorefront()->findOrFail($product);
        $user = $request->user();
        // The unique constraint also protects concurrent/retried requests.
        $user->favoriteProducts()->newPivotStatement()->insertOrIgnore([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $request->expectsJson()
            ? response()->json(['favorited' => true])
            : back();
    }

    public function destroy(Request $request, string $product): JsonResponse|RedirectResponse
    {
        // Allow removal even after a saved product is unpublished or soft-deleted.
        $product = Product::withTrashed()->findOrFail($product);
        $request->user()->favoriteProducts()->detach($product->id);

        return $request->expectsJson()
            ? response()->json(['favorited' => false])
            : back();
    }
}
