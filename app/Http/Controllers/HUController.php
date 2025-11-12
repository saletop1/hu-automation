<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // Pastikan ini di-import

class HUController extends Controller
{
    private $pythonBaseUrl;

    public function __construct()
    {
        $this->pythonBaseUrl = env('PYTHON_API_URL', 'http://localhost:5000');
    }

    public function index()
    {
        // PERBAIKAN: Panggil getStockDataFromDB
        // Hapus batasan "perPage" (kirim null) untuk mengambil semua data
        $stockData = $this->getStockDataFromDB(1, null, '', '3000', '3D10');

        // Static data for plants
        $plantsData = [
            '2000' => ['21HU', '21LK', '21NH'],
            '3000' => ['3D10', '3DH1', '3DH2']
        ];

        return view('hu.index', compact('stockData', 'plantsData'));
    }

    public function createSingle()
    {
        return view('hu.create-single');
    }

    public function createSingleMulti()
    {
        return view('hu.create-single-multi');
    }

    public function createMultiple()
    {
        return view('hu.create-multiple');
    }

    // ==================== STOCK DATA METHODS ====================

    /**
     * Get stock data directly from MySQL database
     * Ini adalah fungsi privat yang dipanggil oleh index() dan getStock()
     */
    private function getStockDataFromDB($page = 1, $perPage = null, $material = '', $plant = '', $storageLocation = '')
    {
        try {
            $query = DB::table('stock_data');

            // Terapkan filter (Data diasumsikan sudah bersih, tidak perlu TRIM)
            if ($material) {
                $query->where('material', 'like', "%{$material}%");
            }
            if ($plant) {
                $query->where('plant', $plant); // Query cepat (menggunakan index)
            }
            if ($storageLocation) {
                $query->where('storage_location', $storageLocation); // Query cepat
            }

            // Hitung total SEBELUM pagination
            $total = $query->count();

            // PERBAIKAN: Logika untuk menghapus pagination
            // Jika $perPage tidak null, gunakan pagination. Jika null, ambil semua.
            if ($perPage !== null) {
                $query->limit($perPage)
                      ->offset(($page - 1) * $perPage);
            }
            // Jika $perPage == null, jangan gunakan limit/offset (ambil semua)

            $data = $query->orderBy('material')
                         ->orderBy('plant')
                         ->orderBy('storage_location')
                         ->orderBy('batch')
                         ->get();

            $finalPerPage = $perPage ?? $total;
            if ($finalPerPage == 0) $finalPerPage = 1; // hindari divide by zero

            return [
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $finalPerPage,
                    'total' => $total,
                    'total_pages' => $perPage ? ceil($total / $finalPerPage) : 1
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Database stock data fetch error: ' . $e->getMessage());
            return [
                'success' => false,
                'data' => [],
                'error' => $e->getMessage(),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage ?? 20,
                    'total' => 0,
                    'total_pages' => 0
                ]
            ];
        }
    }

    // FUNGSI INI (syncStock) TETAP MENGGUNAKAN PYTHON (BENAR)
    public function syncStock(Request $request)
    {
        $request->validate([
            'plant' => 'required|string',
            'storage_location' => 'required|string'
        ]);

        try {
            $response = Http::timeout(120)->post($this->pythonBaseUrl . '/stock/sync', [
                'plant' => $request->plant,
                'storage_location' => $request->storage_location
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return response()->json([
                    'success' => true,
                    'message' => $result['message'] ?? 'Stock data synced successfully!'
                ]);
            } else {
                $error = $response->json()['error'] ?? 'Failed to sync stock data';
                return response()->json([
                    'success' => false,
                    'error' => $error
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Stock sync error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to sync stock data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PERBAIKAN: Fungsi ini (untuk AJAX) sekarang membaca dari DB lokal
     * Ini akan menjadi super cepat dan TIDAK AKAN TIMEOUT
     */
    public function getStock(Request $request)
    {
        $page = $request->get('page', 1);
        // PERBAIKAN: Hapus batasan "perPage" (kirim null)
        $perPage = $request->get('per_page', null);
        $material = $request->get('material', '');
        $plant = $request->get('plant', '');
        $storageLocation = $request->get('storage_location', '');

        Log::info('Get Stock Request (Local DB):', [
            'material' => $material,
            'plant' => $plant,
            'storage_location' => $storageLocation
        ]);

        // Panggil fungsi lokal yang sudah diperbarui
        $result = $this->getStockDataFromDB($page, $perPage, $material, $plant, $storageLocation);

        return response()->json($result);
    }

    // PERBAIKAN: Fungsi ini sekarang membaca dari DB lokal
    public function getPlants(Request $request)
    {
        try {
            // Data bersih, tidak perlu TRIM
            $plants = DB::table('stock_data')
                        ->select('plant')
                        ->distinct()
                        ->orderBy('plant')
                        ->pluck('plant');

            return response()->json([
                'success' => true,
                'data' => $plants
            ]);

        } catch (\Exception $e) {
            Log::error('Plants fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // PERBAIKAN: Fungsi ini sekarang membaca dari DB lokal
    public function getStorageLocations(Request $request)
    {
        $plant = $request->get('plant', '');

        try {
            $query = DB::table('stock_data')
                        ->select('storage_location')
                        ->distinct();

            if ($plant) {
                // Data bersih, tidak perlu TRIM
                $query->where('plant', $plant);
            }

            $locations = $query->orderBy('storage_location')->pluck('storage_location');

            return response()->json([
                'success' => true,
                'data' => $locations
            ]);

        } catch (\Exception $e) {
            Log::error('Storage locations fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================================
    // ===== SEMUA FUNGSI HU CREATION TETAP MENGGUNAKAN PYTHON =====
    // ==========================================================

    public function storeSingle(Request $request)
    {
        // Validasi ini HARUS sesuai dengan nama <input> di form
        $request->validate([
            'hu_exid' => 'required|string',
            'pack_mat' => 'required|string',
            'plant' => 'required|string',
            'stge_loc' => 'required|string',
            'material' => 'required|string',
            'pack_qty' => 'required|numeric|min:0.001',
            'batch' => 'nullable|string',
            'sp_stck_no' => 'nullable|string',
            'base_unit_qty' => 'nullable|string', // Dibuat nullable
        ]);

        try {
            $data = $request->all();
            $data['sap_user'] = env('SAP_USER');
            $data['sap_password'] = env('SAP_PASSWORD');

            Log::info('Sending HU creation request to Python API', [
                'endpoint' => $this->pythonBaseUrl . '/hu/create-single',
                'data' => array_merge($data, ['sap_password' => '***'])
            ]);

            // Gunakan 'timeout' (untuk Laravel < 7)
            $response = Http::timeout(120)->post($this->pythonBaseUrl . '/hu/create-single', $data);

            Log::info('Python API response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return back()->with('success', $result['message'] ?? 'HU Created Successfully');
            } else {
                $error = $response->json()['error'] ?? 'Unknown error occurred';
                Log::error('HU Creation failed', ['error' => $error]);
                return back()->with('error', $error);
            }
        } catch (\Exception $e) {
            Log::error('HU Creation Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to connect to SAP system: ' . $e->getMessage());
        }
    }

    public function storeSingleMulti(Request $request)
    {
        $request->validate([
            'hu_exid' => 'required|string',
            'pack_mat' => 'required|string',
            'plant' => 'required|string',
            'stge_loc' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.material' => 'required|string',
            'items.*.pack_qty' => 'required|numeric|min:0.001',
            'items.*.batch' => 'nullable|string',
            'items.*.sp_stck_no' => 'nullable|string',
            'base_unit_qty' => 'nullable|string', // Dibuat nullable
        ]);

        try {
            $data = $request->all();
            $data['sap_user'] = env('SAP_USER');
            $data['sap_password'] = env('SAP_PASSWORD');

            // Tambahkan base_unit_qty ke setiap item
            $baseUnit = $request->input('base_unit_qty', ''); // Ambil dari hidden input
            foreach ($data['items'] as &$item) {
                $item['base_unit_qty'] = $baseUnit;
            }

            Log::info('Sending HU creation (multi) request to Python API', [
                'endpoint' => $this->pythonBaseUrl . '/hu/create-single-multi',
                'data' => array_merge($data, ['sap_password' => '***'])
            ]);

            $response = Http::timeout(120)->post($this->pythonBaseUrl . '/hu/create-single-multi', $data);

            Log::info('Python API response (multi)', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return back()->with('success', $result['message'] ?? 'HU with multiple materials created successfully');
            } else {
                $error = $response->json()['error'] ?? 'Unknown error occurred';
                Log::error('HU Creation (multi) failed', ['error' => $error]);
                return back()->with('error', $error);
            }
        } catch (\Exception $e) {
            Log::error('HU Creation (multi) Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to connect to SAP system: ' . $e->getMessage());
        }
    }

    public function storeMultiple(Request $request)
    {
        $request->validate([
            'hus' => 'required|array|min:1',
            'hus.*.hu_exid' => 'required|string',
            'hus.*.pack_mat' => 'required|string',
            'hus.*.plant' => 'required|string',
            'hus.*.stge_loc' => 'required|string',
            'hus.*.material' => 'required|string',
            'hus.*.pack_qty' => 'required|numeric|min:0.001',
            'hus.*.batch' => 'nullable|string',
            'hus.*.sp_stck_no' => 'nullable|string',
            'base_unit_qty' => 'nullable|string', // Dibuat nullable
        ]);

        try {
            $data = $request->all();
            $data['sap_user'] = env('SAP_USER');
            $data['sap_password'] = env('SAP_PASSWORD');

            // Tambahkan base_unit_qty ke setiap HU
            $baseUnit = $request->input('base_unit_qty', ''); // Ambil dari hidden input
            foreach ($data['hus'] as &$hu) {
                $hu['base_unit_qty'] = $baseUnit;
            }

            Log::info('Sending multiple HU creation request to Python API', [
                'endpoint' => $this->pythonBaseUrl . '/hu/create-multiple',
                'data' => array_merge($data, ['sap_password' => '***'])
            ]);

            $response = Http::timeout(120)->post($this->pythonBaseUrl . '/hu/create-multiple', $data);

            Log::info('Python API response (multiple)', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return back()->with('success', $result['message'] ?? 'Multiple HUs created successfully');
            } else {
                $error = $response->json()['error'] ?? 'Unknown error occurred';
                Log::error('Multiple HU Creation failed', ['error' => $error]);
                return back()->with('error', $error);
            }
        } catch (\Exception $e) {
            Log::error('Multiple HU Creation Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to connect to SAP system: ' . $e->getMessage());
        }
    }
}
