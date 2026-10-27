<?php

use App\Http\Controllers\AcademicMonitoringController;
use App\Http\Controllers\CatalogProductController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserDirectoryController;
use App\Http\Middleware\ValidateCatalogQuery;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::view('/catalogo', 'catalog.index')->middleware(ValidateCatalogQuery::class)->name('catalog.index');
Route::get('/catalogo/{slug}', CatalogProductController::class)->name('catalog.show');

Route::get('/usuarios', UserDirectoryController::class)
    ->middleware(['auth:sanctum', config('jetstream.auth_session'), 'no-store'])
    ->name('users.index');

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'no-store'])->prefix('monitoramento')->name('monitoring.')->group(function () {
    Route::get('/', [AcademicMonitoringController::class, 'index'])->name('index');
    Route::get('/usuarios/{user}', [AcademicMonitoringController::class, 'user'])->whereNumber('user')->name('users.show');
    Route::get('/produtos/{product}', [AcademicMonitoringController::class, 'product'])->whereNumber('product')->name('products.show');
    Route::get('/pedidos/{order}', [AcademicMonitoringController::class, 'order'])->whereNumber('order')->name('orders.show');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/favoritos', [FavoriteController::class, 'index'])->middleware('no-store')->name('favorites.index');
    Route::post('/favoritos/{product}', [FavoriteController::class, 'store'])->whereNumber('product')->middleware('no-store')->name('favorites.store');
    Route::delete('/favoritos/{product}', [FavoriteController::class, 'destroy'])->whereNumber('product')->middleware('no-store')->name('favorites.destroy');

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
