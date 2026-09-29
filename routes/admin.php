<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'admin',
])->group(function () {
    Route::get('/admin', DashboardController::class)->name('admin.dashboard');
    Route::view('/admin/catalogo', 'admin.catalog')->name('admin.catalog');
    Route::view('/admin/abastecimento', 'admin.supply')->name('admin.supply');
    Route::view('/admin/pedidos', 'admin.orders')->middleware('no-store')->name('admin.orders');
});
