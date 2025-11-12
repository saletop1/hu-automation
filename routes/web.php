<?php

use App\Http\Controllers\HUController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Main Routes
Route::get('/', [HUController::class, 'index'])->name('hu.index');
Route::get('/hu/create-single', [HUController::class, 'createSingle'])->name('hu.create-single');
Route::get('/hu/create-single-multi', [HUController::class, 'createSingleMulti'])->name('hu.create-single-multi');
Route::get('/hu/create-multiple', [HUController::class, 'createMultiple'])->name('hu.create-multiple');

// Stock Routes
Route::post('/stock/sync', [HUController::class, 'syncStock'])->name('hu.stock.sync');
Route::get('/stock/data', [HUController::class, 'getStock'])->name('hu.stock.data');

// HU Creation Routes
Route::post('/hu/create-single', [HUController::class, 'storeSingle'])->name('hu.store-single');
Route::post('/hu/create-single-multi', [HUController::class, 'storeSingleMulti'])->name('hu.store-single-multi');
Route::post('/hu/create-multiple', [HUController::class, 'storeMultiple'])->name('hu.store-multiple');

// Additional Routes untuk plants dan storage locations
Route::get('/stock/plants', [HUController::class, 'getPlants'])->name('hu.stock.plants');
Route::get('/stock/storage-locations', [HUController::class, 'getStorageLocations'])->name('hu.stock.storage-locations');

// Debug Routes
Route::get('/debug-db', [HUController::class, 'debugDb']);
Route::get('/debug-simple', function() {
    return response()->json([
        'message' => 'Simple debug route works',
        'timestamp' => now()
    ]);
});
Route::get('/debug-env', function() {
    return response()->json([
        'app_env' => env('APP_ENV'),
        'app_debug' => env('APP_DEBUG'),
        'db_connection' => env('DB_CONNECTION'),
        'db_database' => env('DB_DATABASE'),
        'db_host' => env('DB_HOST')
    ]);
});
Route::get('/check-errors', function() {
    $logFile = storage_path('logs/laravel.log');

    if (!file_exists($logFile)) {
        return response()->json(['error' => 'Log file not found']);
    }

    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recentLogs = array_slice($lines, -50);

    return response('<pre>' . implode("\n", $recentLogs) . '</pre>');
});

// TAMBAHKAN ROUTE YANG MISSING INI:
Route::get('/debug-db-simple', [HUController::class, 'debugDbSimple']);
Route::get('/test-stock-simple', [HUController::class, 'testStockSimple']);
Route::get('/test-mysql-direct', [HUController::class, 'testMysqlDirect']);
Route::get('/test-db', [HUController::class, 'testDatabase']);
Route::get('/check-schema', [HUController::class, 'checkSchema']);
Route::get('/view-logs', [HUController::class, 'viewLogs']);

// Alternative stock data routes untuk testing
Route::get('/stock/data-laravel', [HUController::class, 'getStockLaravel']);
Route::get('/stock/data-simple', [HUController::class, 'getStockSimple']);
