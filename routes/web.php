<?php

use App\Http\Controllers\CatalogProductController;
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
});
