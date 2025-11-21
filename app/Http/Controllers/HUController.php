<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\HuHistory;
use App\Models\Stock;
use App\Exports\HuHistoryExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class HUController extends Controller
{
    private $pythonBaseUrl;

    public function __construct()
    {
        $this->pythonBaseUrl = env('PYTHON_API_URL', 'http://localhost:5000');
    }

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

            // ✅ TAMBAHKAN PLANT 2000 DENGAN LOKASI MANUAL JIKA KOSONG
            if ($plantsData->isEmpty()) {
                $plantsData = [
                    '2000' => ['21HU', '21LK', '21NH'],
                    '3000' => ['3D10', '3DH1', '3DH2']
                ];
            } else {
                // ✅ TAMBAHKAN PLANT 2000 JIKA BELUM ADA
                if (!isset($plantsData['2000'])) {
                    $plantsData['2000'] = ['21HU', '21LK', '21NH'];
                }

                // ✅ TAMBAHKAN LOKASI DEFAULT UNTUK PLANT 3000 JIKA BELUM ADA
                if (!isset($plantsData['3000'])) {
                    $plantsData['3000'] = ['3D10', '3DH1', '3DH2'];
                }
            }

            // ✅ URUTKAN PLANT
            $plantsData = $plantsData->sortKeys();

            return view('hu.index', compact('stockData', 'plantsData'));

        } catch (\Exception $e) {
            Log::error('Index page error: ' . $e->getMessage());
            return view('hu.index', [
                'stockData' => ['success' => false, 'data' => [], 'pagination' => []],
                'plantsData' => [
                    '2000' => ['21HU', '21LK', '21NH'],
                    '3000' => ['3D10', '3DH1', '3DH2']
                ]
            ])->with('error', 'Failed to load page: ' . $e->getMessage());
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
            $historyData = HuHistory::with('stock')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            // Untuk setiap history yang material_description-nya tidak ada, coba ambil dari stock_data
            foreach ($historyData as $item) {
                if (empty($item->material_description) || str_contains($item->material_description, 'not found')) {
                    $this->fixMissingMaterialDescription($item);
                }
            }

            Log::info('History data loaded: ' . $historyData->total() . ' records');

            return view('hu.history', compact('historyData'));
        } catch (\Exception $e) {
            Log::error('History fetch error: ' . $e->getMessage());
            return view('hu.history', ['historyData' => []])
                ->with('error', 'Failed to load history: ' . $e->getMessage());
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
        $validator = Validator::make($request->all(), [
            'plant' => 'required|string',
            'storage_location' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . $validator->errors()->first()
            ], 422);
        }

        try {
            Log::info('Starting manual stock sync', [
                'plant' => $request->plant,
                'storage_location' => $request->storage_location,
                'python_url' => $this->pythonBaseUrl
            ]);

            $response = Http::timeout(1000)->post($this->pythonBaseUrl . '/stock/sync', [
                'plant' => $request->plant,
                'storage_location' => $request->storage_location
            ]);

            Log::info('Python API sync response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $result = $response->json();

                return response()->json([
                    'success' => true,
                    'message' => $result['message'] ?? 'Stock data synced successfully!'
                ]);
            } else {
                $error = $response->json()['error'] ?? 'Failed to sync stock data';
                Log::error('Stock sync failed', ['error' => $error]);
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

            // Pastikan semua field required ada termasuk magry
            $stockData->each(function ($item) {
                $item->suggested_pack_mat = $this->getPackagingMaterialByMagry($item->magry ?? '');
                $item->magry_type = $item->magry ?? '';

                // Pastikan field required ada
                $item->material = $item->material ?? '';
                $item->material_description = $item->material_description ?? '';
                $item->plant = $item->plant ?? '';
                $item->storage_location = $item->storage_location ?? '';
                $item->batch = $item->batch ?? '';
                $item->sales_document = $item->sales_document ?? '';
                $item->magry = $item->magry ?? ''; // ✅ PASTIKAN magry ADA
            });

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
                'error' => 'Failed to fetch stock data: ' . $e->getMessage(),
                'data' => []
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
        $validator = Validator::make($request->all(), [
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

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Clean data sebelum dikirim ke Python API
            $data = $this->cleanHuData($request->all());

            // ✅ PERBAIKAN: Pastikan SAP credentials termasuk dalam data yang dikirim
            $data['sap_user'] = $request->sap_user;
            $data['sap_password'] = $request->sap_password;

            Log::info('Sending HU creation request to Python API', [
                'endpoint' => $this->pythonBaseUrl . '/hu/create-single',
                'sap_user' => $data['sap_user'], // Log user saja (jangan password)
                'data_keys' => array_keys($data)
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
                    Log::warning('History creation had issues for HU: ' . $request->hu_exid);
                }

                return back()->with('success', $result['message'] ?? 'HU Created Successfully');
            } else {
                $error = $response->json()['error'] ?? 'Unknown error occurred';
                Log::error('HU Creation failed', ['error' => $error]);
                return back()->with('error', $error)->withInput();
            }
        } catch (\Exception $e) {
            Log::error('HU Creation Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to connect to SAP system: ' . $e->getMessage())->withInput();
        }
    }

    public function storeSingleMulti(Request $request)
    {
        $validator = Validator::make($request->all(), [
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

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Clean data sebelum dikirim ke Python API
            $data = $this->cleanHuData($request->all());

            // ✅ PERBAIKAN: Pastikan SAP credentials termasuk dalam data yang dikirim
            $data['sap_user'] = $request->sap_user;
            $data['sap_password'] = $request->sap_password;

            $baseUnit = $request->input('base_unit_qty', '');
            foreach ($data['items'] as &$item) {
                $item['base_unit_qty'] = $baseUnit;
            }

            Log::info('Sending HU creation (multi) request to Python API', [
                'endpoint' => $this->pythonBaseUrl . '/hu/create-single-multi',
                'sap_user' => $data['sap_user'], // Log user saja (jangan password)
                'items_count' => count($data['items'])
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
                    Log::warning('History creation had issues for multi HU: ' . $request->hu_exid);
                }

                return back()->with('success', $result['message'] ?? 'HU with multiple materials created successfully');
            } else {
                $error = $response->json()['error'] ?? 'Unknown error occurred';
                Log::error('HU Creation (multi) failed', ['error' => $error]);
                return back()->with('error', $error)->withInput();
            }
        } catch (\Exception $e) {
            Log::error('HU Creation (multi) Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to connect to SAP system: ' . $e->getMessage())->withInput();
        }
    }

    public function storeMultiple(Request $request)
    {
        $validator = Validator::make($request->all(), [
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

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Clean data sebelum dikirim ke Python API
            $data = $this->cleanHuData($request->all());

            // ✅ PERBAIKAN: Pastikan SAP credentials termasuk dalam data yang dikirim
            $data['sap_user'] = $request->sap_user;
            $data['sap_password'] = $request->sap_password;

            $baseUnit = $request->input('base_unit_qty', '');
            foreach ($data['hus'] as &$hu) {
                $hu['base_unit_qty'] = $baseUnit;
            }

            Log::info('Sending multiple HU creation request to Python API', [
                'endpoint' => $this->pythonBaseUrl . '/hu/create-multiple',
                'sap_user' => $data['sap_user'], // Log user saja (jangan password)
                'hus_count' => count($data['hus'])
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
                    Log::warning('History creation had issues for multiple HUs');
                }

                return back()->with('success', $result['message'] ?? 'Multiple HUs created successfully');
            } else {
                $error = $response->json()['error'] ?? 'Unknown error occurred';
                Log::error('Multiple HU Creation failed', ['error' => $error]);
                return back()->with('error', $error)->withInput();
            }
        } catch (\Exception $e) {
            Log::error('Multiple HU Creation Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to connect to SAP system: ' . $e->getMessage())->withInput();
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
     * Clean HU data untuk menghindari None values di Python
     */
    private function cleanHuData($data)
    {
        $cleaned = $data;

        // Clean main level fields
        $optionalFields = ['batch', 'sp_stck_no', 'base_unit_qty'];
        foreach ($optionalFields as $field) {
            if (isset($cleaned[$field]) && $cleaned[$field] === null) {
                $cleaned[$field] = '';
            }
        }

        // Clean items array jika ada
        if (isset($cleaned['items']) && is_array($cleaned['items'])) {
            foreach ($cleaned['items'] as &$item) {
                foreach ($optionalFields as $field) {
                    if (isset($item[$field]) && $item[$field] === null) {
                        $item[$field] = '';
                    }
                }
            }
        }

        // Clean hus array jika ada
        if (isset($cleaned['hus']) && is_array($cleaned['hus'])) {
            foreach ($cleaned['hus'] as &$hu) {
                foreach ($optionalFields as $field) {
                    if (isset($hu[$field]) && $hu[$field] === null) {
                        $hu[$field] = '';
                    }
                }
            }
        }

        Log::info('Data cleaned for Python API', ['cleaned_data_keys' => array_keys($cleaned)]);

        return $cleaned;
    }

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

    /**
     * Fix missing material description in history
     */
    private function fixMissingMaterialDescription($historyItem)
    {
        try {
            Log::info('Trying to fix missing material_description for history ID: ' . $historyItem->id);

            // Format material untuk query (tambah leading zero)
            $formattedMaterial = $this->formatMaterialForQuery($historyItem->material);

            $stockItem = DB::table('stock_data')
                ->where('material', $formattedMaterial)
                ->select('material_description')
                ->first();

            if ($stockItem && !empty($stockItem->material_description)) {
                // Update history record
                HuHistory::where('id', $historyItem->id)
                    ->update(['material_description' => $stockItem->material_description]);

                // Update object untuk tampilan saat ini
                $historyItem->material_description = $stockItem->material_description;

                Log::info('Fixed material_description for history ID: ' . $historyItem->id . ' - ' . $stockItem->material_description);
            }
        } catch (\Exception $e) {
            Log::error('Error fixing material description: ' . $e->getMessage());
        }
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
                ->select('id', 'material_description', 'stock_quantity', 'hu_created')
                ->first();

            Log::info('DEBUG - Stock data check result:', [
                'search_material' => $formattedMaterial,
                'batch' => $request->batch,
                'exists' => $stockCheck ? 'YES' : 'NO',
                'stock_id' => $stockCheck->id ?? 'NOT FOUND',
                'material_description' => $stockCheck->material_description ?? 'NOT FOUND',
                'stock_quantity' => $stockCheck->stock_quantity ?? 0,
                'hu_created' => $stockCheck->hu_created ?? 'unknown'
            ]);

            // Jika data tidak ditemukan, cari dengan kriteria yang lebih longgar
            if (!$stockCheck) {
                $stockCheck = $this->findStockWithFallback($formattedMaterial, $request->plant, $request->stge_loc, $request->batch);
            }

            $materialDescription = $stockCheck->material_description ?? 'Material description not found in database';
            $stockId = $stockCheck->id ?? null;

            // Update stock status - PASTIKAN hu_created diupdate menjadi true
            $stockUpdated = false;
            if ($stockCheck) {
                $stockUpdated = $this->updateStockStatus($stockId, $formattedMaterial, $request, $stockCheck);
            }

            // Convert pack_qty to integer
            $quantity = (int) round($request->pack_qty);

            // Format material untuk display (hapus leading zero)
            $displayMaterial = $this->formatMaterialForDisplay($request->material);

            // Create history record
            $history = HuHistory::create([
                'stock_id' => $stockId,
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
                    ', Stock ID: ' . $stockId .
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

    /**
     * Find stock with fallback methods
     */
    private function findStockWithFallback($formattedMaterial, $plant, $storageLocation, $batch = null)
    {
        $queries = [
            // Cari tanpa batch
            [
                'where' => [
                    ['material', $formattedMaterial],
                    ['plant', $plant],
                    ['storage_location', $storageLocation]
                ],
                'description' => 'without batch'
            ],
            // Cari hanya berdasarkan material
            [
                'where' => [
                    ['material', $formattedMaterial]
                ],
                'description' => 'material only'
            ],
            // Cari dengan material original
            [
                'where' => [
                    ['material', $this->formatMaterialForDisplay($formattedMaterial)]
                ],
                'description' => 'original material'
            ]
        ];

        foreach ($queries as $query) {
            $stockItem = DB::table('stock_data')
                ->where($query['where'])
                ->select('id', 'material_description', 'stock_quantity', 'hu_created')
                ->first();

            if ($stockItem) {
                Log::info('DEBUG - Stock data found ' . $query['description'] . ':', [
                    'stock_id' => $stockItem->id ?? 'NOT FOUND',
                    'material_description' => $stockItem->material_description ?? 'NOT FOUND'
                ]);
                return $stockItem;
            }
        }

        return null;
    }

    /**
     * Update stock status
     */
    private function updateStockStatus($stockId, $formattedMaterial, $request, $stockCheck)
    {
        $updateData = [
            'hu_created' => true,
            'hu_created_at' => now(),
            'hu_number' => $request->hu_exid
        ];

        // Try update by ID first
        if ($stockId) {
            $updated = DB::table('stock_data')
                ->where('id', $stockId)
                ->update($updateData);

            if ($updated) {
                Log::info('Stock updated by ID for material ' . $formattedMaterial);
                return true;
            }
        }

        // Fallback to criteria-based update
        $updated = DB::table('stock_data')
            ->where('material', $formattedMaterial)
            ->where('batch', $request->batch)
            ->where('plant', $request->plant)
            ->where('storage_location', $request->stge_loc)
            ->update($updateData);

        Log::info('Stock update result for material ' . $formattedMaterial . ': ' . ($updated ? 'Success' : 'Failed'));
        return $updated;
    }

    private function getPackagingMaterialByMagry($magry, $currentPackMat = '')
    {
        // Jika sudah ada pilihan dari user, prioritaskan pilihan user
        if (!empty($currentPackMat)) {
            return $currentPackMat;
        }

        // Otomatis pilih berdasarkan magry
        switch ($magry) {
            case 'ZMG1':
                return '50016873';
            case 'ZMG2':
                return 'VSTDPLTBW01'; // Default pertama untuk ZMG2
            default:
                return $currentPackMat;
        }
    }

    // Function baru untuk mendapatkan semua opsi packaging material berdasarkan magry
    private function getPackagingMaterialOptions($magry)
    {
        switch ($magry) {
            case 'ZMG1':
                return [
                    'default' => '50016873',
                    'options' => ['50016873']
                ];
            case 'ZMG2':
                return [
                    'default' => 'VSTDPLTBW01',
                    'options' => ['VSTDPLTBW01', 'VSTDPLTBW02'] // ✅ 2 OPSI UNTUK ZMG2
                ];
            default:
                return [
                    'default' => '',
                    'options' => []
                ];
        }
    }

    private function updateStockAndHistoryMulti($request, $result, $scenarioType)
    {
        try {
            $successCount = 0;

            foreach ($request->items as $item) {
                $success = $this->processMultiItem($request, $item, $scenarioType);
                if ($success) $successCount++;
            }

            Log::info('Multi HU history creation completed. Success: ' . $successCount . ' of ' . count($request->items));
            return $successCount > 0;

        } catch (\Exception $e) {
            Log::error('Error updating stock and history for multi: ' . $e->getMessage());
            Log::error('Stack trace for multi: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Process individual item for multi HU
     */
    private function processMultiItem($request, $item, $scenarioType)
    {
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

        // Find stock item
        $stockItem = $this->findStockItemForMulti($formattedMaterial, $request->plant, $request->stge_loc, $item['batch'] ?? null);

        $materialDescription = $stockItem->material_description ?? 'Material description not found in database';
        $stockId = $stockItem->id ?? null;

        // Update stock status for each item
        $stockUpdated = false;
        if ($stockItem && $stockId) {
            $stockUpdated = $this->updateMultiStockStatus($stockId, $formattedMaterial, $request, $item);
        }

        // Convert pack_qty to integer
        $quantity = (int) round($item['pack_qty']);

        // Format material untuk display
        $displayMaterial = $this->formatMaterialForDisplay($item['material']);

        // Create history record
        $history = HuHistory::create([
            'stock_id' => $stockId,
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
            Log::info('Multi HU History created successfully:', [
                'hu_number' => $request->hu_exid,
                'stock_id' => $stockId,
                'material' => $displayMaterial,
                'history_id' => $history->id,
                'quantity' => $quantity
            ]);
            return true;
        }

        Log::error('Failed to create Multi HU History:', [
            'hu_number' => $request->hu_exid,
            'stock_id' => $stockId,
            'material' => $displayMaterial
        ]);
        return false;
    }

    /**
     * Find stock item for multi HU with fallback
     */
    private function findStockItemForMulti($formattedMaterial, $plant, $storageLocation, $batch = null)
    {
        $queries = [
            // First query with batch
            [
                'where' => [
                    ['material', $formattedMaterial],
                    ['plant', $plant],
                    ['storage_location', $storageLocation]
                ],
                'batch' => $batch,
                'description' => 'first query'
            ],
            // Second query without batch filter
            [
                'where' => [
                    ['material', $formattedMaterial],
                    ['plant', $plant],
                    ['storage_location', $storageLocation]
                ],
                'batch' => null,
                'description' => 'second query (no batch)'
            ],
            // Third query material only
            [
                'where' => [
                    ['material', $formattedMaterial]
                ],
                'batch' => null,
                'description' => 'third query (material only)'
            ],
            // Fourth query original material
            [
                'where' => [
                    ['material', $this->formatMaterialForDisplay($formattedMaterial)]
                ],
                'batch' => null,
                'description' => 'fourth query (original material)'
            ]
        ];

        foreach ($queries as $query) {
            $dbQuery = DB::table('stock_data')->where($query['where']);

            if ($query['batch']) {
                $dbQuery->where('batch', $query['batch']);
            }

            $stockItem = $dbQuery->select('id', 'material_description', 'hu_created')->first();

            if ($stockItem) {
                Log::info('Stock found in ' . $query['description'] . ' for Multi:', [
                    'stock_id' => $stockItem->id
                ]);
                return $stockItem;
            }
        }

        return null;
    }

    /**
     * Update stock status for multi HU
     */
    private function updateMultiStockStatus($stockId, $formattedMaterial, $request, $item)
    {
        $updateData = [
            'hu_created' => true,
            'hu_created_at' => now(),
            'hu_number' => $request->hu_exid
        ];

        // Try update by ID first
        $updated = DB::table('stock_data')
            ->where('id', $stockId)
            ->update($updateData);

        if ($updated) {
            Log::info('Multi stock updated by ID:', [
                'stock_id' => $stockId,
                'material' => $formattedMaterial
            ]);
            return true;
        }

        // Fallback to criteria-based update
        $updated = DB::table('stock_data')
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
            ->update($updateData);

        Log::info('Multi stock update result:', [
            'stock_id' => $stockId,
            'material' => $formattedMaterial,
            'updated' => $updated
        ]);

        return $updated;
    }

    private function updateStockAndHistoryMultiple($request, $result, $scenarioType)
    {
        try {
            $successCount = 0;

            foreach ($request->hus as $hu) {
                $success = $this->processMultipleHUItem($hu, $scenarioType);
                if ($success) $successCount++;
            }

            Log::info('Multiple HU history creation completed. Success: ' . $successCount . ' of ' . count($request->hus));
            return $successCount > 0;

        } catch (\Exception $e) {
            Log::error('Error updating stock and history for multiple: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process individual HU for multiple HUs
     */
    private function processMultipleHUItem($hu, $scenarioType)
    {
        // Format material untuk query database
        $formattedMaterial = $this->formatMaterialForQuery($hu['material']);

        // Find stock item
        $stockItem = $this->findStockItemForMultiple($formattedMaterial, $hu['plant'], $hu['stge_loc'], $hu['batch'] ?? null);

        $materialDescription = $stockItem->material_description ?? 'Material description not found in database';
        $stockId = $stockItem->id ?? null;

        // Update stock status
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
                    'hu_created' => true,
                    'hu_created_at' => now(),
                    'hu_number' => $hu['hu_exid']
                ]);

            Log::info('Multiple HU stock update for material ' . $formattedMaterial . ': ' . ($stockUpdated ? 'Success' : 'Failed'));
        }

        // Convert pack_qty to integer
        $quantity = (int) round($hu['pack_qty']);

        // Format material untuk display
        $displayMaterial = $this->formatMaterialForDisplay($hu['material']);

        // Create history record
        $history = HuHistory::create([
            'stock_id' => $stockId,
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
            Log::info('Multiple HU history created for: ' . $hu['hu_exid'] .
                    ', Material: ' . $displayMaterial .
                    ', Stock ID: ' . $stockId .
                    ', Quantity: ' . $quantity .
                    ', Description: ' . $materialDescription);
            return true;
        }

        return false;
    }

    /**
     * Find stock item for multiple HUs
     */
    private function findStockItemForMultiple($formattedMaterial, $plant, $storageLocation, $batch = null)
    {
        $queries = [
            // First try with all criteria including batch
            [
                'where' => [
                    ['material', $formattedMaterial],
                    ['plant', $plant],
                    ['storage_location', $storageLocation]
                ],
                'batch' => $batch,
                'description' => 'all criteria with batch'
            ],
            // Try without batch
            [
                'where' => [
                    ['material', $formattedMaterial],
                    ['plant', $plant],
                    ['storage_location', $storageLocation]
                ],
                'batch' => null,
                'description' => 'all criteria without batch'
            ],
            // Try material only
            [
                'where' => [
                    ['material', $formattedMaterial]
                ],
                'batch' => null,
                'description' => 'material only'
            ],
            // Try original material
            [
                'where' => [
                    ['material', $this->formatMaterialForDisplay($formattedMaterial)]
                ],
                'batch' => null,
                'description' => 'original material'
            ]
        ];

        foreach ($queries as $query) {
            $dbQuery = DB::table('stock_data')->where($query['where']);

            if ($query['batch']) {
                $dbQuery->where('batch', $query['batch']);
            }

            $stockItem = $dbQuery->select('id', 'material_description', 'hu_created')->first();

            if ($stockItem) {
                return $stockItem;
            }
        }

        return null;
    }
}
