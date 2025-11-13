<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // TAMBAHKAN INI
use App\Models\HuHistory;

class HUController extends Controller
{
    private $pythonBaseUrl;

    public function __construct()
    {
        $this->pythonBaseUrl = env('PYTHON_API_URL', 'http://localhost:5000');
    }

    public function index()
    {
        $stockData = $this->getStockDataFromDB(1, null, '', '3000', '3D10');

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

    public function history()
    {
        try {
            $historyData = HuHistory::orderBy('created_at', 'desc')->get();
            return view('hu.history', compact('historyData'));
        } catch (\Exception $e) {
            Log::error('History fetch error: ' . $e->getMessage());

            // Fallback jika tabel belum ada
            $historyData = collect();
            return view('hu.history', compact('historyData'));
        }
    }

    // ==================== STOCK DATA METHODS ====================

    private function getStockDataFromDB($page = 1, $perPage = null, $material = '', $plant = '', $storageLocation = '')
    {
        try {
            $query = DB::table('stock_data')->where('hu_created', false);

            if ($material) {
                $query->where('material', 'like', "%{$material}%");
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
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', null);
        $material = $request->get('material', '');
        $plant = $request->get('plant', '');
        $storageLocation = $request->get('storage_location', '');

        Log::info('Get Stock Request (Local DB):', [
            'material' => $material,
            'plant' => $plant,
            'storage_location' => $storageLocation
        ]);

        $result = $this->getStockDataFromDB($page, $perPage, $material, $plant, $storageLocation);

        return response()->json($result);
    }

    public function getPlants(Request $request)
    {
        try {
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

    public function getStorageLocations(Request $request)
    {
        $plant = $request->get('plant', '');

        try {
            $query = DB::table('stock_data')
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
            'hu_exid' => 'required|string',
            'pack_mat' => 'required|string',
            'plant' => 'required|string',
            'stge_loc' => 'required|string',
            'material' => 'required|string',
            'pack_qty' => 'required|numeric|min:0.001',
            'batch' => 'nullable|string',
            'sp_stck_no' => 'nullable|string',
            'base_unit_qty' => 'nullable|string',
            'sap_user' => 'required|string', // TAMBAHKAN VALIDASI
            'sap_password' => 'required|string', // TAMBAHKAN VALIDASI
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
                $this->updateStockAndHistory($request, $result, 'single');

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
            'base_unit_qty' => 'nullable|string',
            'sap_user' => 'required|string', // TAMBAHKAN VALIDASI
            'sap_password' => 'required|string', // TAMBAHKAN VALIDASI
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
                $this->updateStockAndHistoryMulti($request, $result, 'single-multi');

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
            'base_unit_qty' => 'nullable|string',
            'sap_user' => 'required|string', // TAMBAHKAN VALIDASI
            'sap_password' => 'required|string', // TAMBAHKAN VALIDASI
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
                $this->updateStockAndHistoryMultiple($request, $result, 'multiple');

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

    // ==================== HELPER METHODS ====================

    private function updateStockAndHistory($request, $result, $scenarioType)
    {
        try {
            // Update stock status
            DB::table('stock_data')
                ->where('material', $request->material)
                ->where('batch', $request->batch)
                ->where('plant', $request->plant)
                ->where('storage_location', $request->stge_loc)
                ->update([
                    'hu_created' => true,
                    'hu_created_at' => now(),
                    'hu_number' => $request->hu_exid
                ]);

            // Create history record
            HuHistory::create([
                'hu_number' => $request->hu_exid,
                'material' => $request->material,
                'material_description' => DB::table('stock_data')->where('material', $request->material)->value('material_description'),
                'batch' => $request->batch,
                'quantity' => $request->pack_qty,
                'unit' => 'PC',
                'plant' => $request->plant,
                'storage_location' => $request->stge_loc,
                'sales_document' => $request->sp_stck_no,
                'scenario_type' => $scenarioType,
                'created_by' => Auth::check() ? Auth::user()->name : 'System' // PERBAIKAN: Gunakan Auth facade
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating stock and history: ' . $e->getMessage());
        }
    }

    private function updateStockAndHistoryMulti($request, $result, $scenarioType)
    {
        try {
            foreach ($request->items as $item) {
                // Update stock status for each item
                DB::table('stock_data')
                    ->where('material', $item['material'])
                    ->where('batch', $item['batch'] ?? null)
                    ->where('plant', $request->plant)
                    ->where('storage_location', $request->stge_loc)
                    ->update([
                        'hu_created' => true,
                        'hu_created_at' => now(),
                        'hu_number' => $request->hu_exid
                    ]);

                // Create history record for each item
                HuHistory::create([
                    'hu_number' => $request->hu_exid,
                    'material' => $item['material'],
                    'material_description' => DB::table('stock_data')->where('material', $item['material'])->value('material_description'),
                    'batch' => $item['batch'] ?? null,
                    'quantity' => $item['pack_qty'],
                    'unit' => 'PC',
                    'plant' => $request->plant,
                    'storage_location' => $request->stge_loc,
                    'sales_document' => $item['sp_stck_no'] ?? null,
                    'scenario_type' => $scenarioType,
                    'created_by' => Auth::check() ? Auth::user()->name : 'System' // PERBAIKAN: Gunakan Auth facade
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error updating stock and history for multi: ' . $e->getMessage());
        }
    }

    private function updateStockAndHistoryMultiple($request, $result, $scenarioType)
    {
        try {
            foreach ($request->hus as $hu) {
                // Update stock status for each HU
                DB::table('stock_data')
                    ->where('material', $hu['material'])
                    ->where('batch', $hu['batch'] ?? null)
                    ->where('plant', $hu['plant'])
                    ->where('storage_location', $hu['stge_loc'])
                    ->update([
                        'hu_created' => true,
                        'hu_created_at' => now(),
                        'hu_number' => $hu['hu_exid']
                    ]);

                // Create history record for each HU
                HuHistory::create([
                    'hu_number' => $hu['hu_exid'],
                    'material' => $hu['material'],
                    'material_description' => DB::table('stock_data')->where('material', $hu['material'])->value('material_description'),
                    'batch' => $hu['batch'] ?? null,
                    'quantity' => $hu['pack_qty'],
                    'unit' => 'PC',
                    'plant' => $hu['plant'],
                    'storage_location' => $hu['stge_loc'],
                    'sales_document' => $hu['sp_stck_no'] ?? null,
                    'scenario_type' => $scenarioType,
                    'created_by' => Auth::check() ? Auth::user()->name : 'System' // PERBAIKAN: Gunakan Auth facade
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error updating stock and history for multiple: ' . $e->getMessage());
        }
    }
}
