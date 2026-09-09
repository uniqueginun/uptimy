<?php

use App\Http\Controllers\SiteController;
use App\Http\Controllers\SiteEndpointController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::prefix('{domain}')->group(function () {
        Route::post('/endpoints', [SiteEndpointController::class, 'store'])->name('endpoints.store');
        Route::delete('/endpoints/{endpoint}', [SiteEndpointController::class, 'destroy'])->name('endpoints.destroy');
    })->as('sites.');

    Route::get('sites', [SiteController::class, 'index'])->name('sites.index');
    Route::get('sites/{domain}', [SiteController::class, 'show'])->name('sites.show');
    Route::post('sites', [SiteController::class, 'store'])->name('sites.store');
});

require __DIR__.'/settings.php';
