<?php

use App\Http\Controllers\HUController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Routes untuk HU Controller
    Route::get('/', [HUController::class, 'index'])->name('hu.index');
    Route::prefix('hu')->group(function () {
    Route::get('/create-single', [HUController::class, 'createSingle'])->name('hu.create-single');
    Route::get('/create-single-multi', [HUController::class, 'createSingleMulti'])->name('hu.create-single-multi');
    Route::get('/create-multiple', [HUController::class, 'createMultiple'])->name('hu.create-multiple');
    Route::get('/history', [HUController::class, 'history'])->name('hu.history');

    // POST routes untuk create HU
    Route::post('/store-single', [HUController::class, 'storeSingle'])->name('hu.store-single');
    Route::post('/store-single-multi', [HUController::class, 'storeSingleMulti'])->name('hu.store-single-multi');
    Route::post('/store-multiple', [HUController::class, 'storeMultiple'])->name('hu.store-multiple');

    // Export route
    Route::post('/export', [HUController::class, 'export'])->name('hu.export');

    // Stock sync routes - PERBAIKI INI
    Route::post('/sync-stock', [HUController::class, 'syncStock'])->name('hu.sync-stock'); // Route sudah ada, nama benar
    Route::get('/get-stock', [HUController::class, 'getStock'])->name('hu.get-stock'); // Route sudah ada, nama benar

    // TAMBAHKAN ROUTE YANG DIPANGGIL DI BLADE
    Route::post('/stock-sync', [HUController::class, 'syncStock'])->name('hu.stock.sync');
    Route::get('/stock-data', [HUController::class, 'getStock'])->name('hu.stock.data');

    Route::get('/get-plants', [HUController::class, 'getPlants'])->name('hu.get-plants');
    Route::get('/get-storage-locations', [HUController::class, 'getStorageLocations'])->name('hu.get-storage-locations');
});
