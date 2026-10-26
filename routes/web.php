<?php

use App\Http\Controllers\CatalogProductController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::view('/catalogo', 'catalog.index')->middleware(\App\Http\Middleware\ValidateCatalogQuery::class)->name('catalog.index');
Route::get('/catalogo/{slug}', CatalogProductController::class)->name('catalog.show');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/favoritos', [\App\Http\Controllers\FavoriteController::class, 'index'])->middleware('no-store')->name('favorites.index');
    Route::post('/favoritos/{product}', [\App\Http\Controllers\FavoriteController::class, 'store'])->whereNumber('product')->middleware('no-store')->name('favorites.store');
    Route::delete('/favoritos/{product}', [\App\Http\Controllers\FavoriteController::class, 'destroy'])->whereNumber('product')->middleware('no-store')->name('favorites.destroy');

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::view('/meus-dados', 'personal-data.show')
        ->middleware('no-store')
        ->name('personal-data.show');

    Route::view('/carrinho', 'cart.show')->name('cart.show');
    Route::view('/checkout', 'checkout.show')->middleware('no-store')->name('checkout.show');
    Route::get('/pedidos', [OrderController::class, 'index'])->middleware('no-store')->name('orders.index');
    Route::get('/pedidos/{publicNumber}', [OrderController::class, 'show'])->middleware('no-store')->name('orders.show');
});
