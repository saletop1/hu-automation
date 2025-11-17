<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\HuHistory;
use App\Models\Stock; // Pastikan model Stock di-import
use App\Exports\HuHistoryExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class HUController extends Controller
{
    private $pythonBaseUrl;

    public function __construct()
    {
        $this->pythonBaseUrl = env('PYTHON_API_URL', 'http://localhost:5000');
    }

    // Method index harus ada
    public function index()
    {
        try {
            // Ambil data stock yang BELUM dibuat HU dengan filter yang benar
            $stockData = $this->getStockDataFromDB(1, null, '', '3000', '3D10', '');

            // Data plants untuk dropdown - hanya yang belum dibuat HU
            $plantsData = Stock::select('plant', 'storage_location')
                ->where('hu_created', false)
                ->distinct()
                ->get()
                ->groupBy('plant')
                ->map(function ($item) {
                    return $item->pluck('storage_location')->unique()->values();
                });

            // Jika plantsData kosong, gunakan default
            if ($plantsData->isEmpty()) {
                $plantsData = [
                    '2000' => ['21HU', '21LK', '21NH'],
                    '3000' => ['3D10', '3DH1', '3DH2']
                ];
            }

            return view('hu.index', compact('stockData', 'plantsData'));

        } catch (\Exception $e) {
            Log::error('Index page error: ' . $e->getMessage());
            return view('hu.index', [
                'stockData' => ['success' => false, 'data' => [], 'pagination' => []],
                'plantsData' => []
            ]);
        }
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

    public function history()
    {
        try {
            $historyData = HuHistory::orderBy('created_at', 'desc')->get();

            // Untuk setiap history yang material_description-nya tidak ada, coba ambil dari stock_data
            foreach ($historyData as $item) {
                if (empty($item->material_description) || str_contains($item->material_description, 'not found')) {
                    Log::info('Trying to fix missing material_description for history ID: ' . $item->id);

                    // Format material untuk query (tambah leading zero)
                    $formattedMaterial = $this->formatMaterialForQuery($item->material);

                    $stockItem = DB::table('stock_data')
                        ->where('material', $formattedMaterial)
                        ->select('material_description')
                        ->first();

                    if ($stockItem && !empty($stockItem->material_description)) {
                        // Update history record
                        HuHistory::where('id', $item->id)
                            ->update(['material_description' => $stockItem->material_description]);

                        // Update object untuk tampilan saat ini
                        $item->material_description = $stockItem->material_description;

                        Log::info('Fixed material_description for history ID: ' . $item->id . ' - ' . $stockItem->material_description);
                    }
                }
            }

            Log::info('History data loaded: ' . $historyData->count() . ' records');

            return view('hu.history', compact('historyData'));
        } catch (\Exception $e) {
            Log::error('History fetch error: ' . $e->getMessage());
            $historyData = collect();
            return view('hu.history', compact('historyData'));
        }
    }

    // ==================== STOCK DATA METHODS ====================

    private function getStockDataFromDB($page = 1, $perPage = null, $material = '', $plant = '', $storageLocation = '', $search = '')
    {
        try {
            // PASTIKAN HANYA AMBIL DATA YANG BELUM DIBUAT HU
            $query = DB::table('stock_data')->where('hu_created', false);

            // Search general untuk material, deskripsi, atau sales document
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('material', 'like', "%{$search}%")
                      ->orWhere('material_description', 'like', "%{$search}%")
                      ->orWhere('sales_document', 'like', "%{$search}%")
                      ->orWhere('vendor_name', 'like', "%{$search}%")
                      ->orWhere(DB::raw("CONCAT(sales_document, item_number)"), 'like', "%{$search}%");
                });
            } else {
                // Backward compatibility - search material saja
                if ($material) {
                    $query->where('material', 'like', "%{$material}%");
                }
            }

            if ($plant) {
                $query->where('plant', $plant);
            }
            if ($storageLocation) {
                $query->where('storage_location', $storageLocation);
            }

            $total = $query->count();

            if ($perPage !== null) {
                $query->limit($perPage)
                      ->offset(($page - 1) * $perPage);
            }

            $data = $query->orderBy('material')
                         ->orderBy('plant')
                         ->orderBy('storage_location')
                         ->orderBy('batch')
                         ->get();

            $finalPerPage = $perPage ?? $total;
            if ($finalPerPage == 0) $finalPerPage = 1;

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

                // Setelah sync berhasil, pastikan untuk memuat ulang data dengan filter hu_created = false
                Log::info('Stock sync successful, reloading stock data with hu_created filter');

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

    public function getStock(Request $request)
{
    try {
        // Gunakan DB query builder untuk konsistensi
        $query = DB::table('stock_data')->where('hu_created', false);

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('material', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('material_description', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('sales_document', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('batch', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        if ($request->has('plant') && !empty($request->plant)) {
            $query->where('plant', $request->plant);
        }

        if ($request->has('storage_location') && !empty($request->storage_location)) {
            $query->where('storage_location', $request->storage_location);
        }

        $stockData = $query->orderBy('material', 'asc')->get();

        Log::info('Stock data fetched: ' . $stockData->count() . ' items (hu_created = false)');

        return response()->json([
            'success' => true,
            'data' => $stockData,
            'pagination' => [
                'total' => $stockData->count()
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Get stock error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => 'Failed to fetch stock data: ' . $e->getMessage()
        ], 500);
    }
}

    public function getPlants(Request $request)
    {
        try {
            // Hanya ambil plants dari data yang belum dibuat HU
            $plants = DB::table('stock_data')
                        ->where('hu_created', false)
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

    public function getStorageLocations(Request $request)
    {
        $plant = $request->get('plant', '');

        try {
            // Hanya ambil storage locations dari data yang belum dibuat HU
            $query = DB::table('stock_data')
                        ->where('hu_created', false)
                        ->select('storage_location')
                        ->distinct();

            if ($plant) {
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

    // ==================== HU CREATION METHODS ====================

    public function storeSingle(Request $request)
    {
        $request->validate([
            'hu_exid' => 'required|string|size:10|regex:/^\d+$/',
            'pack_mat' => 'required|string',
            'plant' => 'required|string',
            'stge_loc' => 'required|string',
            'material' => 'required|string',
            'pack_qty' => 'required|numeric|min:0.001',
            'batch' => 'nullable|string',
            'sp_stck_no' => 'nullable|string',
            'base_unit_qty' => 'nullable|string',
            'sap_user' => 'required|string',
            'sap_password' => 'required|string',
        ], [
            'hu_exid.size' => 'HU External ID harus tepat 10 digit angka.',
            'hu_exid.regex' => 'HU External ID hanya boleh berisi angka.',
        ]);

        try {
            $data = $request->all();

            Log::info('Sending HU creation request to Python API', [
                'endpoint' => $this->pythonBaseUrl . '/hu/create-single',
                'data' => array_merge($data, ['sap_password' => '***'])
            ]);

            $response = Http::timeout(120)->post($this->pythonBaseUrl . '/hu/create-single', $data);

            Log::info('Python API response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $result = $response->json();

                // Update stock status and create history
                $historyCreated = $this->updateStockAndHistory($request, $result, 'single');

                if ($historyCreated) {
                    Log::info('History created successfully for HU: ' . $request->hu_exid);
                } else {
                    Log::error('Failed to create history for HU: ' . $request->hu_exid);
                }

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
            'hu_exid' => 'required|string|size:10|regex:/^\d+$/',
            'pack_mat' => 'required|string',
            'plant' => 'required|string',
            'stge_loc' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.material' => 'required|string',
            'items.*.pack_qty' => 'required|numeric|min:0.001',
            'items.*.batch' => 'nullable|string',
            'items.*.sp_stck_no' => 'nullable|string',
            'base_unit_qty' => 'nullable|string',
            'sap_user' => 'required|string',
            'sap_password' => 'required|string',
        ], [
            'hu_exid.size' => 'HU External ID harus tepat 10 digit angka.',
            'hu_exid.regex' => 'HU External ID hanya boleh berisi angka.',
        ]);

        try {
            $data = $request->all();

            $baseUnit = $request->input('base_unit_qty', '');
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

                // Update stock status and create history for multiple items
                $historyCreated = $this->updateStockAndHistoryMulti($request, $result, 'single-multi');

                if ($historyCreated) {
                    Log::info('History created successfully for multi HU: ' . $request->hu_exid);
                } else {
                    Log::error('Failed to create history for multi HU: ' . $request->hu_exid);
                }

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
            'hus.*.hu_exid' => 'required|string|size:10|regex:/^\d+$/',
            'hus.*.pack_mat' => 'required|string',
            'hus.*.plant' => 'required|string',
            'hus.*.stge_loc' => 'required|string',
            'hus.*.material' => 'required|string',
            'hus.*.pack_qty' => 'required|numeric|min:0.001',
            'hus.*.batch' => 'nullable|string',
            'hus.*.sp_stck_no' => 'nullable|string',
            'base_unit_qty' => 'nullable|string',
            'sap_user' => 'required|string',
            'sap_password' => 'required|string',
        ], [
            'hus.*.hu_exid.size' => 'HU External ID harus tepat 10 digit angka.',
            'hus.*.hu_exid.regex' => 'HU External ID hanya boleh berisi angka.',
        ]);

        try {
            $data = $request->all();

            $baseUnit = $request->input('base_unit_qty', '');
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

                // Update stock status and create history for multiple HUs
                $historyCreated = $this->updateStockAndHistoryMultiple($request, $result, 'multiple');

                if ($historyCreated) {
                    Log::info('History created successfully for multiple HUs');
                } else {
                    Log::error('Failed to create history for multiple HUs');
                }

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

    // ==================== EXPORT METHOD ====================

    public function export(Request $request)
    {
        try {
            $selectedData = json_decode($request->selected_data, true);

            if (empty($selectedData)) {
                return redirect()->back()->with('error', 'Tidak ada data yang dipilih untuk di-export.');
            }

            return Excel::download(new HuHistoryExport($selectedData), 'hu_history_' . date('Ymd_His') . '.xlsx');

        } catch (\Exception $e) {
            Log::error('Export Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat export: ' . $e->getMessage());
        }
    }

    // ==================== HELPER METHODS ====================

    /**
     * Format material number untuk query database
     * Jika material hanya angka, tambahkan leading zero sampai 18 digit
     */
    private function formatMaterialForQuery($material)
    {
        // Jika material hanya berisi angka
        if (preg_match('/^\d+$/', $material)) {
            // Format ke 18 digit dengan leading zero (standar SAP)
            return str_pad($material, 18, '0', STR_PAD_LEFT);
        }

        // Jika bukan angka, return as-is
        return $material;
    }

    /**
     * Format material number untuk display (hapus leading zero)
     */
    private function formatMaterialForDisplay($material)
    {
        // Jika material hanya berisi angka dan ada leading zero
        if (preg_match('/^\d+$/', $material)) {
            // Hapus leading zero
            return ltrim($material, '0') ?: '0';
        }

        // Jika bukan angka, return as-is
        return $material;
    }

    private function updateStockAndHistory($request, $result, $scenarioType)
    {
        try {
            // Format material untuk query database (tambah leading zero)
            $formattedMaterial = $this->formatMaterialForQuery($request->material);

            // DEBUG: Log semua parameter yang diterima
            Log::info('DEBUG - Request data for history:', [
                'original_material' => $request->material,
                'formatted_material' => $formattedMaterial,
                'batch' => $request->batch,
                'plant' => $request->plant,
                'stge_loc' => $request->stge_loc,
                'hu_exid' => $request->hu_exid,
                'pack_qty' => $request->pack_qty
            ]);

            // ✅ PERBAIKAN: Tambahkan 'id' di select
            $stockCheck = DB::table('stock_data')
                ->where('material', $formattedMaterial)
                ->where('batch', $request->batch)
                ->where('plant', $request->plant)
                ->where('storage_location', $request->stge_loc)
                ->select('id', 'material_description', 'stock_quantity', 'hu_created') // ✅ TAMBAH 'id'
                ->first();

            Log::info('DEBUG - Stock data check result:', [
                'search_material' => $formattedMaterial,
                'batch' => $request->batch,
                'exists' => $stockCheck ? 'YES' : 'NO',
                'stock_id' => $stockCheck->id ?? 'NOT FOUND', // ✅ TAMBAH INI
                'material_description' => $stockCheck->material_description ?? 'NOT FOUND',
                'stock_quantity' => $stockCheck->stock_quantity ?? 0,
                'hu_created' => $stockCheck->hu_created ?? 'unknown'
            ]);

            // Jika data tidak ditemukan, cari dengan kriteria yang lebih longgar
            if (!$stockCheck) {
                Log::warning('Stock data not found with exact match, trying broader search...');

                // Cari tanpa batch - ✅ TAMBAH 'id' di select
                $stockCheck = DB::table('stock_data')
                    ->where('material', $formattedMaterial)
                    ->where('plant', $request->plant)
                    ->where('storage_location', $request->stge_loc)
                    ->select('id', 'material_description', 'stock_quantity', 'hu_created') // ✅ TAMBAH 'id'
                    ->first();

                if ($stockCheck) {
                    Log::info('DEBUG - Stock data found without batch:', [
                        'stock_id' => $stockCheck->id ?? 'NOT FOUND', // ✅ TAMBAH INI
                        'material_description' => $stockCheck->material_description ?? 'NOT FOUND'
                    ]);
                } else {
                    // Cari hanya berdasarkan material - ✅ TAMBAH 'id' di select
                    $stockCheck = DB::table('stock_data')
                        ->where('material', $formattedMaterial)
                        ->select('id', 'material_description', 'stock_quantity', 'hu_created') // ✅ TAMBAH 'id'
                        ->first();

                    if ($stockCheck) {
                        Log::info('DEBUG - Stock data found with material only:', [
                            'stock_id' => $stockCheck->id ?? 'NOT FOUND', // ✅ TAMBAH INI
                            'material_description' => $stockCheck->material_description ?? 'NOT FOUND'
                        ]);
                    } else {
                        // Coba cari tanpa formatting (original material) sebagai fallback - ✅ TAMBAH 'id' di select
                        $stockCheck = DB::table('stock_data')
                            ->where('material', $request->material)
                            ->select('id', 'material_description', 'stock_quantity', 'hu_created') // ✅ TAMBAH 'id'
                            ->first();

                        if ($stockCheck) {
                            Log::info('DEBUG - Stock data found with original material (no formatting):', [
                                'stock_id' => $stockCheck->id ?? 'NOT FOUND', // ✅ TAMBAH INI
                                'material_description' => $stockCheck->material_description ?? 'NOT FOUND'
                            ]);
                        }
                    }
                }
            }

            $materialDescription = $stockCheck->material_description ?? 'Material description not found in database';
            $stockId = $stockCheck->id ?? null; // ✅ AMBIL ID DARI STOCK_CHECK

            // Update stock status - PASTIKAN hu_created diupdate menjadi true
            $stockUpdated = false;
            if ($stockCheck) {
                // ✅ PERBAIKAN: Gunakan $stockId untuk update yang lebih akurat
                $stockUpdated = DB::table('stock_data')
                    ->where('id', $stockId) // Gunakan ID langsung
                    ->update([
                        'hu_created' => true,
                        'hu_created_at' => now(),
                        'hu_number' => $request->hu_exid
                    ]);

                // Jika update gagal dengan ID, coba dengan kriteria
                if (!$stockUpdated) {
                    $stockUpdated = DB::table('stock_data')
                        ->where('material', $formattedMaterial)
                        ->where('batch', $request->batch)
                        ->where('plant', $request->plant)
                        ->where('storage_location', $request->stge_loc)
                        ->update([
                            'hu_created' => true,
                            'hu_created_at' => now(),
                            'hu_number' => $request->hu_exid
                        ]);
                }

                Log::info('Stock update result for material ' . $formattedMaterial . ': ' . ($stockUpdated ? 'Success - hu_created set to true' : 'Failed'));
            }

            // Convert pack_qty to integer
            $quantity = (int) round($request->pack_qty);

            // Format material untuk display (hapus leading zero)
            $displayMaterial = $this->formatMaterialForDisplay($request->material);

            // ✅ PERBAIKAN: Gunakan $stockId yang sudah diambil
            $history = HuHistory::create([
                'stock_id' => $stockId, // ✅ GUNAKAN VARIABLE $stockId
                'hu_number' => $request->hu_exid,
                'material' => $displayMaterial,
                'material_description' => $materialDescription,
                'batch' => $request->batch,
                'quantity' => $quantity,
                'unit' => 'PC',
                'plant' => $request->plant,
                'storage_location' => $request->stge_loc,
                'sales_document' => $request->sp_stck_no,
                'scenario_type' => $scenarioType,
                'created_by' => Auth::check() ? Auth::user()->name : 'System',
                'created_at' => Carbon::now('Asia/Jakarta')
            ]);

            Log::info('History created with ID: ' . ($history->id ?? 'Unknown') .
                    ', Stock ID: ' . $stockId . // ✅ TAMBAH LOG INI
                    ', Material: ' . $displayMaterial .
                    ', Quantity: ' . $quantity .
                    ', Material Description: ' . $materialDescription);

            return true;

        } catch (\Exception $e) {
            Log::error('Error updating stock and history: ' . $e->getMessage());
            Log::error('Error details: ', [
                'material' => $request->material ?? 'null',
                'formatted_material' => $formattedMaterial ?? 'null',
                'batch' => $request->batch ?? 'null',
                'plant' => $request->plant ?? 'null',
                'stge_loc' => $request->stge_loc ?? 'null'
            ]);
            return false;
        }
    }

    private function updateStockAndHistoryMulti($request, $result, $scenarioType)
    {
        try {
            $successCount = 0;

            foreach ($request->items as $item) {
                // Format material untuk query database
                $formattedMaterial = $this->formatMaterialForQuery($item['material']);

                // DEBUG: Log pencarian stock
                Log::info('Searching stock for Multi HU:', [
                    'hu_exid' => $request->hu_exid,
                    'original_material' => $item['material'],
                    'formatted_material' => $formattedMaterial,
                    'plant' => $request->plant,
                    'storage_location' => $request->stge_loc,
                    'batch' => $item['batch'] ?? 'null'
                ]);

                // ✅ PERBAIKAN: Tambahkan 'id' di select
                $stockItem = DB::table('stock_data')
                    ->where('material', $formattedMaterial)
                    ->where(function($query) use ($item) {
                        if (!empty($item['batch'])) {
                            $query->where('batch', $item['batch']);
                        } else {
                            $query->whereNull('batch')->orWhere('batch', '');
                        }
                    })
                    ->where('plant', $request->plant)
                    ->where('storage_location', $request->stge_loc)
                    ->select('id', 'material_description', 'hu_created') // ✅ TAMBAH 'id'
                    ->first();

                // DEBUG: Log hasil pencarian pertama
                if ($stockItem) {
                    Log::info('Stock found in first query for Multi:', [
                        'stock_id' => $stockItem->id,
                        'material' => $formattedMaterial
                    ]);
                }

                // Jika tidak ketemu, cari tanpa filter batch
                if (!$stockItem) {
                    $stockItem = DB::table('stock_data')
                        ->where('material', $formattedMaterial)
                        ->where('plant', $request->plant)
                        ->where('storage_location', $request->stge_loc)
                        ->select('id', 'material_description', 'hu_created') // ✅ TAMBAH 'id'
                        ->first();

                    if ($stockItem) {
                        Log::info('Stock found in second query for Multi (no batch filter):', [
                            'stock_id' => $stockItem->id
                        ]);
                    }
                }

                // Jika masih tidak ketemu, cari hanya berdasarkan material
                if (!$stockItem) {
                    $stockItem = DB::table('stock_data')
                        ->where('material', $formattedMaterial)
                        ->select('id', 'material_description', 'hu_created') // ✅ TAMBAH 'id'
                        ->first();

                    if ($stockItem) {
                        Log::info('Stock found in third query for Multi (material only):', [
                            'stock_id' => $stockItem->id
                        ]);
                    }
                }

                // Fallback: cari dengan material original
                if (!$stockItem) {
                    $stockItem = DB::table('stock_data')
                        ->where('material', $item['material'])
                        ->select('id', 'material_description', 'hu_created') // ✅ TAMBAH 'id'
                        ->first();

                    if ($stockItem) {
                        Log::info('Stock found in fourth query for Multi (original material):', [
                            'stock_id' => $stockItem->id
                        ]);
                    }
                }

                $materialDescription = $stockItem->material_description ?? 'Material description not found in database';
                $stockId = $stockItem->id ?? null; // ✅ AMBIL ID DARI STOCK_ITEM

                // DEBUG: Log final stock_id
                Log::info('Final stock_id for Multi HU:', [
                    'hu_exid' => $request->hu_exid,
                    'material' => $formattedMaterial,
                    'stock_id' => $stockId,
                    'material_description' => $materialDescription
                ]);

                // Update stock status for each item - PASTIKAN hu_created diupdate menjadi true
                $stockUpdated = false;
                if ($stockItem && $stockId) {
                    // ✅ PERBAIKAN: Gunakan $stockId untuk update yang lebih akurat
                    $stockUpdated = DB::table('stock_data')
                        ->where('id', $stockId) // Gunakan ID langsung
                        ->update([
                            'hu_created' => true,
                            'hu_created_at' => now(),
                            'hu_number' => $request->hu_exid
                        ]);

                    // Jika update gagal dengan ID, coba dengan kriteria
                    if (!$stockUpdated) {
                        $stockUpdated = DB::table('stock_data')
                            ->where('material', $formattedMaterial)
                            ->where(function($query) use ($item) {
                                if (!empty($item['batch'])) {
                                    $query->where('batch', $item['batch']);
                                } else {
                                    $query->whereNull('batch')->orWhere('batch', '');
                                }
                            })
                            ->where('plant', $request->plant)
                            ->where('storage_location', $request->stge_loc)
                            ->update([
                                'hu_created' => true,
                                'hu_created_at' => now(),
                                'hu_number' => $request->hu_exid
                            ]);
                    }

                    Log::info('Multi stock update result:', [
                        'stock_id' => $stockId,
                        'material' => $formattedMaterial,
                        'updated' => $stockUpdated
                    ]);
                } else {
                    Log::warning('Cannot update stock for Multi - stock item not found or stock_id null', [
                        'material' => $formattedMaterial,
                        'stock_id' => $stockId
                    ]);
                }

                // Convert pack_qty to integer
                $quantity = (int) round($item['pack_qty']);

                // Format material untuk display
                $displayMaterial = $this->formatMaterialForDisplay($item['material']);

                // ✅ PERBAIKAN: Tambahkan stock_id ke history
                $history = HuHistory::create([
                    'stock_id' => $stockId, // ✅ TAMBAHKAN stock_id
                    'hu_number' => $request->hu_exid,
                    'material' => $displayMaterial,
                    'material_description' => $materialDescription,
                    'batch' => $item['batch'] ?? null,
                    'quantity' => $quantity,
                    'unit' => 'PC',
                    'plant' => $request->plant,
                    'storage_location' => $request->stge_loc,
                    'sales_document' => $item['sp_stck_no'] ?? null,
                    'scenario_type' => $scenarioType,
                    'created_by' => Auth::check() ? Auth::user()->name : 'System',
                    'created_at' => Carbon::now('Asia/Jakarta')
                ]);

                if ($history) {
                    $successCount++;
                    Log::info('Multi HU History created successfully:', [
                        'hu_number' => $request->hu_exid,
                        'stock_id' => $stockId,
                        'material' => $displayMaterial,
                        'history_id' => $history->id,
                        'quantity' => $quantity
                    ]);
                } else {
                    Log::error('Failed to create Multi HU History:', [
                        'hu_number' => $request->hu_exid,
                        'stock_id' => $stockId,
                        'material' => $displayMaterial
                    ]);
                }
            }

            Log::info('Multi HU history creation completed. Success: ' . $successCount . ' of ' . count($request->items));
            return $successCount > 0;

        } catch (\Exception $e) {
            Log::error('Error updating stock and history for multi: ' . $e->getMessage());
            Log::error('Stack trace for multi: ' . $e->getTraceAsString());
            return false;
        }
    }

    private function updateStockAndHistoryMultiple($request, $result, $scenarioType)
    {
        try {
            $successCount = 0;

            foreach ($request->hus as $hu) {
                // Format material untuk query database
                $formattedMaterial = $this->formatMaterialForQuery($hu['material']);

                // Ambil stock_data dengan formatted material - SEKALIAN AMBIL ID
                $stockItem = DB::table('stock_data')
                    ->where('material', $formattedMaterial)
                    ->where(function($query) use ($hu) {
                        if (!empty($hu['batch'])) {
                            $query->where('batch', $hu['batch']);
                        } else {
                            $query->whereNull('batch')->orWhere('batch', '');
                        }
                    })
                    ->where('plant', $hu['plant'])
                    ->where('storage_location', $hu['stge_loc'])
                    ->select('id', 'material_description', 'hu_created') // TAMBAHKAN 'id' DI SINI
                    ->first();

                // Jika tidak ketemu, cari tanpa filter batch
                if (!$stockItem) {
                    $stockItem = DB::table('stock_data')
                        ->where('material', $formattedMaterial)
                        ->where('plant', $hu['plant'])
                        ->where('storage_location', $hu['stge_loc'])
                        ->select('id', 'material_description', 'hu_created') // TAMBAHKAN 'id' DI SINI
                        ->first();
                }

                // Jika masih tidak ketemu, cari hanya berdasarkan material
                if (!$stockItem) {
                    $stockItem = DB::table('stock_data')
                        ->where('material', $formattedMaterial)
                        ->select('id', 'material_description', 'hu_created') // TAMBAHKAN 'id' DI SINI
                        ->first();
                }

                // Fallback: cari dengan material original
                if (!$stockItem) {
                    $stockItem = DB::table('stock_data')
                        ->where('material', $hu['material'])
                        ->select('id', 'material_description', 'hu_created') // TAMBAHKAN 'id' DI SINI
                        ->first();
                }

                $materialDescription = $stockItem->material_description ?? 'Material description not found in database';
                $stockId = $stockItem->id ?? null; // AMBIL ID DARI STOCK_ITEM

                // Update stock status for each HU - PASTIKAN hu_created diupdate menjadi true
                $stockUpdated = false;
                if ($stockItem) {
                    $stockUpdated = DB::table('stock_data')
                        ->where('material', $formattedMaterial)
                        ->where(function($query) use ($hu) {
                            if (!empty($hu['batch'])) {
                                $query->where('batch', $hu['batch']);
                            } else {
                                $query->whereNull('batch')->orWhere('batch', '');
                            }
                        })
                        ->where('plant', $hu['plant'])
                        ->where('storage_location', $hu['stge_loc'])
                        ->update([
                            'hu_created' => true, // INI YANG PENTING - SET TRUE
                            'hu_created_at' => now(),
                            'hu_number' => $hu['hu_exid']
                        ]);

                    Log::info('Multiple HU stock update for material ' . $formattedMaterial . ': ' . ($stockUpdated ? 'Success - hu_created set to true' : 'Failed'));
                }

                // Convert pack_qty to integer
                $quantity = (int) round($hu['pack_qty']);

                // Format material untuk display
                $displayMaterial = $this->formatMaterialForDisplay($hu['material']);

                // Create history record for each HU - TAMBAHKAN stock_id
                $history = HuHistory::create([
                    'stock_id' => $stockId, // TAMBAHKAN INI
                    'hu_number' => $hu['hu_exid'],
                    'material' => $displayMaterial,
                    'material_description' => $materialDescription,
                    'batch' => $hu['batch'] ?? null,
                    'quantity' => $quantity,
                    'unit' => 'PC',
                    'plant' => $hu['plant'],
                    'storage_location' => $hu['stge_loc'],
                    'sales_document' => $hu['sp_stck_no'] ?? null,
                    'scenario_type' => $scenarioType,
                    'created_by' => Auth::check() ? Auth::user()->name : 'System',
                    'created_at' => Carbon::now('Asia/Jakarta')
                ]);

                if ($history) {
                    $successCount++;
                    Log::info('Multiple HU history created for: ' . $hu['hu_exid'] .
                            ', Material: ' . $displayMaterial .
                            ', Stock ID: ' . $stockId . // TAMBAHKAN LOG INI
                            ', Quantity: ' . $quantity .
                            ', Description: ' . $materialDescription);
                }
            }

            Log::info('Multiple HU history creation completed. Success: ' . $successCount . ' of ' . count($request->hus));
            return $successCount > 0;

        } catch (\Exception $e) {
            Log::error('Error updating stock and history for multiple: ' . $e->getMessage());
            return false;
        }
    }
}
