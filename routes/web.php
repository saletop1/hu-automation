<?php

use App\Http\Controllers\HUController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash; // TAMBAHKAN INI

// Public routes
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Auth routes untuk guest (user yang belum login)
Route::middleware('guest')->group(function () {
    // GET Routes
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');

    // POST Routes
    Route::post('/login', function (\Illuminate\Http\Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/hu');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email'));
    });

    Route::post('/register', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']), // SEKARANG SUDAH TERDEFINISI
        ]);

        Auth::login($user);

        return redirect('/hu');
    });
});

// Logout route - harus di luar guest middleware
Route::post('/logout', function (\Illuminate\Http\Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->name('logout');

// Protected HU Automation routes - hanya untuk user yang sudah login
Route::middleware(['auth'])->prefix('hu')->name('hu.')->group(function () {
    // Main page
    Route::get('/', [HUController::class, 'index'])->name('index');

    // GET routes - Create HU
    Route::get('/create-single', [HUController::class, 'createSingle'])->name('create-single');
    Route::get('/create-single-multi', [HUController::class, 'createSingleMulti'])->name('create-single-multi');
    Route::get('/create-multiple', [HUController::class, 'createMultiple'])->name('create-multiple');

    // GET routes - History & Status
    Route::get('/history', [HUController::class, 'history'])->name('history');
    Route::get('/check-python-api', [HUController::class, 'checkPythonAPI'])->name('check-python-api');
    Route::get('/python-status', [HUController::class, 'getPythonAPIStatus'])->name('python-status');
    Route::get('/sync-stock-debug', [HUController::class, 'syncStockDebug'])->name('sync-stock-debug');

    // Stock sync routes
    Route::get('/get-stock', [HUController::class, 'getStock'])->name('get-stock');
    Route::get('/stock-data', [HUController::class, 'getStock'])->name('stock.data');
    Route::post('/sync-stock', [HUController::class, 'syncStock'])->name('sync-stock');

    // Data routes
    Route::get('/get-plants', [HUController::class, 'getPlants'])->name('get-plants');
    Route::get('/get-storage-locations', [HUController::class, 'getStorageLocations'])->name('get-storage-locations');
    Route::get('/materials', [HUController::class, 'getMaterialData'])->name('materials');
    Route::get('/material-by-code', [HUController::class, 'getMaterialByCode'])->name('material-by-code');

    // POST routes untuk create HU
    Route::post('/store-single', [HUController::class, 'storeSingle'])->name('store-single');
    Route::post('/store-single-multi', [HUController::class, 'storeSingleMulti'])->name('store-single-multi');
    Route::post('/store-multiple', [HUController::class, 'storeMultiple'])->name('store-multiple');

    // Export route
    Route::post('/export', [HUController::class, 'export'])->name('export');
});

// Fallback route
Route::fallback(function () {
    return redirect('/login');
});
