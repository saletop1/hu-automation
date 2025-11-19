<?php

use App\Http\Controllers\HUController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Routes untuk HU Controller
Route::get('/', [HUController::class, 'index'])->name('hu.index');

Route::prefix('hu')->group(function () {
    // GET routes
    Route::get('/create-single', [HUController::class, 'createSingle'])->name('hu.create-single');
    Route::get('/create-single-multi', [HUController::class, 'createSingleMulti'])->name('hu.create-single-multi');
    Route::get('/create-multiple', [HUController::class, 'createMultiple'])->name('hu.create-multiple');
    Route::get('/history', [HUController::class, 'history'])->name('hu.history');
    Route::get('/check-python-api', [HUController::class, 'checkPythonAPI'])->name('hu.check-python-api');
    Route::get('/python-status', [HUController::class, 'getPythonAPIStatus'])->name('hu.python-status');
    Route::get('/sync-stock-debug', [HUController::class, 'syncStockDebug'])->name('hu.sync-stock-debug');

    // Stock sync routes
    Route::get('/get-stock', [HUController::class, 'getStock'])->name('hu.get-stock');
    Route::get('/stock-data', [HUController::class, 'getStock'])->name('hu.stock.data');
    Route::post('/sync-stock', [HUController::class, 'syncStock'])->name('hu.sync-stock');

    // Data routes
    Route::get('/get-plants', [HUController::class, 'getPlants'])->name('hu.get-plants');
    Route::get('/get-storage-locations', [HUController::class, 'getStorageLocations'])->name('hu.get-storage-locations');
    Route::get('/materials', [HUController::class, 'getMaterialData'])->name('hu.materials');
    Route::get('/material-by-code', [HUController::class, 'getMaterialByCode'])->name('hu.material-by-code');

    // POST routes untuk create HU
    Route::post('/store-single', [HUController::class, 'storeSingle'])->name('hu.store-single');
    Route::post('/store-single-multi', [HUController::class, 'storeSingleMulti'])->name('hu.store-single-multi');
    Route::post('/store-multiple', [HUController::class, 'storeMultiple'])->name('hu.store-multiple');

    // Export route
    Route::post('/export', [HUController::class, 'export'])->name('hu.export');
});
