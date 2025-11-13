<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB; // TAMBAHKAN INI
use App\Http\Controllers\HUController;

// Main Routes
Route::get('/', [HUController::class, 'index'])->name('hu.index');
Route::get('/hu/create-single', [HUController::class, 'createSingle'])->name('hu.create-single');
Route::get('/hu/create-single-multi', [HUController::class, 'createSingleMulti'])->name('hu.create-single-multi');
Route::get('/hu/create-multiple', [HUController::class, 'createMultiple'])->name('hu.create-multiple');
Route::get('/history', [HUController::class, 'history'])->name('hu.history');

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

// Debug Routes (optional - bisa dihapus di production)
Route::get('/debug-db', function() {
    try {
        $data = DB::table('stock_data')->where('hu_created', false)->limit(5)->get();
        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => DB::table('stock_data')->where('hu_created', false)->count()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

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
