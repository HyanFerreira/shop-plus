<?php

use App\Http\Controllers\CatalogProductController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/catalogo', 'catalog.index')->name('catalog.index');
Route::get('/catalogo/{slug}', CatalogProductController::class)->name('catalog.show');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::view('/meus-dados', 'personal-data.show')
        ->middleware('no-store')
        ->name('personal-data.show');

    Route::view('/carrinho', 'cart.show')->name('cart.show');
    Route::view('/checkout', 'checkout.show')->middleware('no-store')->name('checkout.show');
    Route::get('/pedidos/{publicNumber}', [OrderController::class, 'show'])->middleware('no-store')->name('orders.show');
});
