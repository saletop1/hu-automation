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

        // ✅ SET DEFAULT TIMEZONE KE JAKARTA
        date_default_timezone_set('Asia/Jakarta');
    }

    public function index()
    {
        try {
            // Ambil data stock yang BELUM dibuat HU dengan filter yang benar
            $stockData = $this->getStockDataFromDB(1, null, '', '3000', '3D10', '');

            // Data plants untuk dropdown - hanya yang belum dibuat HU
            $plantsData = Stock::select('plant', 'storage_location')
                ->where(function($query) {
                    $query->where('hu_created', false)
                          ->orWhere(function($q) {
                              $q->where('hu_created', true)
                                ->where('stock_quantity', '>', 0);
                          });
                })
                ->where('stock_quantity', '>', 0)
                ->where('is_active', 1) // ✅ HANYA YANG AKTIF
                ->distinct()
                ->get()
                ->groupBy('plant')
                ->map(function ($item) {
                    return $item->pluck('storage_location')->unique()->values();
                });

            // ✅ TAMBAHKAN PLANT 2000 DENGAN LOKASI MANUAL JIKA KOSONG
            if ($plantsData->isEmpty()) {
                $plantsData = collect([
                    '2000' => ['21HU', '21LK', '21NH'],
                    '3000' => ['3D10', '3DH1', '3DH2']
                ]);
            } else {
                // ✅ TAMBAHKAN PLANT 2000 JIKA BELUM ADA
                if (!$plantsData->has('2000')) {
                    $plantsData['2000'] = ['21HU', '21LK', '21NH'];
                }

                // ✅ TAMBAHKAN LOKASI DEFAULT UNTUK PLANT 3000 JIKA BELUM ADA
                if (!$plantsData->has('3000')) {
                    $plantsData['3000'] = ['3D10', '3DH1', '3DH2'];
                }
            }

            // ✅ PERBAIKAN: URUTKAN plantsData, BUKAN $data
            $plantsData = $plantsData->sortKeys();

            return view('hu.index', compact('stockData', 'plantsData'));

        } catch (\Exception $e) {
            Log::error('Index page error: ' . $e->getMessage());
            return view('hu.index', [
                'stockData' => ['success' => false, 'data' => [], 'pagination' => []],
                'plantsData' => collect([
                    '2000' => ['21HU', '21LK', '21NH'],
                    '3000' => ['3D10', '3DH1', '3DH2']
                ])->sortKeys()
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

    public function history(Request $request)
    {
        try {
            // Ambil parameter filter dari request
            $search = $request->get('search', '');
            $startDate = $request->get('start_date', '');
            $endDate = $request->get('end_date', '');

            // Query dasar - HANYA dari hu_histories table, TIDAK bergantung pada stock
            $query = HuHistory::query()
                ->orderBy('created_at', 'desc');

            // Filter pencarian
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('hu_number', 'like', "%{$search}%")
                    ->orWhere('material', 'like', "%{$search}%")
                    ->orWhere('material_description', 'like', "%{$search}%")
                    ->orWhere('sales_document', 'like', "%{$search}%")
                    ->orWhere('batch', 'like', "%{$search}%");
                });
            }

            // Filter tanggal - ✅ PERBAIKAN: Handle timezone Jakarta
            if ($startDate) {
                $startDateJakarta = Carbon::createFromFormat('Y-m-d', $startDate, 'Asia/Jakarta')
                    ->startOfDay()
                    ->setTimezone('UTC');
                $query->where('created_at', '>=', $startDateJakarta);
            }

            if ($endDate) {
                $endDateJakarta = Carbon::createFromFormat('Y-m-d', $endDate, 'Asia/Jakarta')
                    ->endOfDay()
                    ->setTimezone('UTC');
                $query->where('created_at', '<=', $endDateJakarta);
            }

            // Pagination 50 baris per halaman
            $historyData = $query->paginate(50);

            Log::info('History data loaded: ' . $historyData->total() . ' records');

            return view('hu.history', compact('historyData', 'search', 'startDate', 'endDate'));
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
            // PERBAIKAN: Hanya ambil data yang BELUM dibuat HU ATAU MASIH ADA SISA STOCK
            $query = DB::table('stock_data')
                ->where(function($q) {
                    $q->where('hu_created', false)
                      ->orWhere(function($q2) {
                          $q2->where('hu_created', true)
                             ->where('stock_quantity', '>', 0);
                      });
                })
                ->where('stock_quantity', '>', 0) // Hanya yang masih ada stock-nya
                ->where('is_active', 1); // ✅ HANYA YANG AKTIF

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
                    'per_page' => $perPage ?? 50,
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

            // STEP 1: HAPUS DATA LAMA untuk plant dan storage_location yang dipilih
            Log::info('Deleting old stock data for plant: ' . $request->plant .
                      ', storage_location: ' . $request->storage_location);

            $deletedCount = DB::table('stock_data')
                ->where('plant', $request->plant)
                ->where('storage_location', $request->storage_location)
                ->delete();

            Log::info('Deleted ' . $deletedCount . ' old stock records');

            // STEP 2: Panggil Python API untuk sync data baru
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
                    'message' => $result['message'] ?? 'Stock data synced successfully! ' .
                                '(Deleted ' . $deletedCount . ' old records)'
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
            // PERBAIKAN: Query untuk menampilkan material yang masih ada stock-nya
            // DAN yang belum dibuat HU atau masih ada sisa stock setelah dibuat HU
            $query = DB::table('stock_data')
                ->where(function($q) {
                    $q->where('hu_created', false)
                      ->orWhere(function($q2) {
                          $q2->where('hu_created', true)
                             ->where('stock_quantity', '>', 0);
                      });
                })
                ->where('stock_quantity', '>', 0)
                ->where('is_active', 1); // ✅ HANYA YANG AKTIF

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

            // PERBAIKAN: Handle magry field yang mungkin tidak ada
            $stockData->each(function ($item) {
                // Periksa apakah field magry ada, jika tidak gunakan default
                $magry = property_exists($item, 'magry') ? $item->magry : '';
                $item->suggested_pack_mat = $this->getPackagingMaterialByMagry($magry);
                $item->magry_type = $magry;

                // Pastikan field required ada
                $item->material = $item->material ?? '';
                $item->material_description = $item->material_description ?? '';
                $item->plant = $item->plant ?? '';
                $item->storage_location = $item->storage_location ?? '';
                $item->batch = $item->batch ?? '';
                $item->sales_document = $item->sales_document ?? '';
                $item->magry = $magry; // ✅ PASTIKAN magry ADA
            });

            Log::info('Stock data retrieved', [
                'count' => $stockData->count(),
                'plant' => $request->plant ?? 'all',
                'storage_location' => $request->storage_location ?? 'all'
            ]);

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
            // PERBAIKAN: Hanya ambil plants dari data yang belum dibuat HU atau masih ada sisa stock
            $plants = DB::table('stock_data')
                        ->where(function($q) {
                            $q->where('hu_created', false)
                              ->orWhere(function($q2) {
                                  $q2->where('hu_created', true)
                                     ->where('stock_quantity', '>', 0);
                              });
                        })
                        ->where('stock_quantity', '>', 0)
                        ->where('is_active', 1) // ✅ HANYA YANG AKTIF
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
            // PERBAIKAN: Hanya ambil storage locations dari data yang belum dibuat HU atau masih ada sisa stock
            $query = DB::table('stock_data')
                        ->where(function($q) {
                            $q->where('hu_created', false)
                              ->orWhere(function($q2) {
                                  $q2->where('hu_created', true)
                                     ->where('stock_quantity', '>', 0);
                              });
                        })
                        ->where('stock_quantity', '>', 0)
                        ->where('is_active', 1) // ✅ HANYA YANG AKTIF
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

                // ✅ PERBAIKAN: Simpan history ke database dengan deskripsi material
                $historyCreated = $this->saveSingleHuHistory($request, $result, 'single');

                if ($historyCreated) {
                    Log::info('History saved successfully for HU: ' . $request->hu_exid);
                } else {
                    Log::warning('History saving had issues for HU: ' . $request->hu_exid);
                }

                return redirect()->route('hu.history')->with('success', $result['message'] ?? 'HU Created Successfully');
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

                // ✅ PERBAIKAN: Simpan history untuk multiple items dengan deskripsi material
                $historyCreated = $this->saveSingleMultiHuHistory($request, $result, 'single-multi');

                if ($historyCreated) {
                    Log::info('History saved successfully for multi HU: ' . $request->hu_exid);
                } else {
                    Log::warning('History saving had issues for multi HU: ' . $request->hu_exid);
                }

                return redirect()->route('hu.history')->with('success', $result['message'] ?? 'HU with multiple materials created successfully');
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
        try {
            // Validasi input untuk semua mode
            $validated = $request->validate([
                'hus' => 'required|array|min:1',
                'hus.*.hu_exid' => 'required|string|size:10|regex:/^\d{10}$/',
                'hus.*.pack_mat' => 'required|string',
                'hus.*.plant' => 'required|string|size:4',
                'hus.*.stge_loc' => 'required|string|max:10',
                'hus.*.material' => 'required|string|max:50',
                'hus.*.pack_qty' => 'required|numeric|min:0.001',
                'hus.*.batch' => 'nullable|string|max:20',
                'hus.*.sp_stck_no' => 'nullable|string|max:30',
                'sap_user' => 'required|string',
                'sap_password' => 'required|string',
                'creation_mode' => 'required|string|in:split,single,partial',
                'total_hus' => 'required|integer|min:1'
            ]);

            $invalidMaterials = [];
            foreach ($validated['hus'] as $index => $hu) {
                if (floatval($hu['pack_qty']) <= 0) {
                    $invalidMaterials[] = "HU {$index}: Quantity must be greater than 0";
                }
            }

            if (!empty($invalidMaterials)) {
                return redirect()->route('hu.create-multiple')
                    ->with('error', 'Beberapa material memiliki quantity tidak valid: ' . implode(', ', $invalidMaterials))
                    ->withInput();
            }

            $creationMode = $validated['creation_mode'] ?? 'split';
            $totalHUs = $validated['total_hus'] ?? count($validated['hus']);

            Log::info('Store Multiple HUs - Processing', [
                'mode' => $creationMode,
                'total_hus' => $totalHUs,
                'user' => $validated['sap_user'],
                'first_hu' => $validated['hus'][0]['hu_exid'] ?? 'N/A'
            ]);

            // Pilih endpoint berdasarkan mode
            $url = $this->pythonBaseUrl;
            $endpoint = $url . '/hu/create-multiple-flexible';

            // Untuk backward compatibility, jika menggunakan mode lama
            if (isset($validated['split_mode'])) {
                $endpoint = $url . '/hu/create-multiple';
                // Convert split_mode to creation_mode
                $validated['creation_mode'] = $validated['split_mode'] == '1' ? 'split' : 'single';
                unset($validated['split_mode']);
            }

            Log::info('Sending to Flask API', [
                'endpoint' => $endpoint,
                'mode' => $validated['creation_mode']
            ]);

            // Kirim request ke Flask API
            $response = Http::timeout(120)
                ->retry(3, 1000)
                ->post($endpoint, $validated);

            if ($response->successful()) {
                $result = $response->json();

                if ($result['success']) {
                    $successCount = $result['summary']['success'] ?? 0;
                    $failedCount = $result['summary']['failed'] ?? 0;
                    $totalCount = $result['summary']['total'] ?? 0;

                    $message = "Berhasil membuat {$successCount} dari {$totalCount} HU";
                    if ($failedCount > 0) {
                        $message .= " ({$failedCount} gagal)";
                    }

                    if (isset($result['summary']['creation_mode'])) {
                        $message .= " - Mode: " . ucfirst($result['summary']['creation_mode']);
                    }

                    Log::info('HU Creation Success', [
                        'success_count' => $successCount,
                        'failed_count' => $failedCount,
                        'mode' => $creationMode
                    ]);

                    // ✅ PERBAIKAN: Simpan history untuk multiple HUs dengan deskripsi material
                    $historySaved = $this->saveMultipleHusHistory($validated['hus'], $result, $creationMode);

                    if ($historySaved) {
                        Log::info('History saved successfully for multiple HUs');
                    } else {
                        Log::warning('History saving had issues for multiple HUs');
                    }

                    // Clear session data setelah berhasil
                    session()->forget('scenario3_data');

                    return redirect()->route('hu.history')->with('success', $message);
                } else {
                    $errorMsg = $result['error'] ?? 'Gagal membuat HU';
                    Log::error('HU Creation Failed', [
                        'error' => $errorMsg,
                        'mode' => $creationMode
                    ]);

                    return redirect()->route('hu.create-multiple')
                        ->with('error', $errorMsg)
                        ->withInput();
                }
            } else {
                $statusCode = $response->status();
                $errorMsg = "Error connecting to SAP service (Status: {$statusCode})";

                Log::error('SAP Service Error', [
                    'status' => $statusCode,
                    'response' => $response->body()
                ]);

                return redirect()->route('hu.create-multiple')
                    ->with('error', $errorMsg)
                    ->withInput();
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation Error in storeMultiple', [
                'errors' => $e->errors()
            ]);

            return redirect()->route('hu.create-multiple')
                ->withErrors($e->validator)
                ->withInput();

        } catch (\Exception $e) {
            Log::error('System Error in storeMultiple', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('hu.create-multiple')
                ->with('error', 'System error: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Untuk backward compatibility - handle request dari form lama
     */
    public function storeMultipleOld(Request $request)
    {
        // Redirect ke method baru dengan parameter yang sesuai
        $request->merge([
            'creation_mode' => $request->split_mode == '1' ? 'split' : 'single'
        ]);

        return $this->storeMultiple($request);
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
     * Format material number untuk query database (tambah leading zero jika perlu)
     */
    private function formatMaterialForQuery($material)
    {
        // Jika material hanya berisi angka dan panjangnya kurang dari 18 digit
        if (preg_match('/^\d+$/', $material)) {
            // Tambah leading zero hingga 18 digit (SAP standard)
            return str_pad($material, 18, '0', STR_PAD_LEFT);
        }

        // Jika bukan angka, return as-is
        return $material;
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

    /**
     * Ambil deskripsi material dari database stock_data
     * ✅ PERBAIKAN: Tangani leading zero dengan benar
     */
    private function getMaterialDescriptionFromStock($material, $plant = null, $storageLocation = null, $batch = null)
    {
        try {
            // Format material untuk query: dengan leading zero
            $materialForQuery = $this->formatMaterialForQuery($material);

            // Juga coba dengan format asli (tanpa leading zero)
            $materialOriginal = $material;

            $query = DB::table('stock_data')
                        ->where(function($q) use ($materialForQuery, $materialOriginal) {
                            // Coba dengan format dengan leading zero
                            $q->where('material', $materialForQuery)
                              // Juga coba dengan format asli
                              ->orWhere('material', $materialOriginal);
                        });

            if ($plant) {
                $query->where('plant', $plant);
            }

            if ($storageLocation) {
                $query->where('storage_location', $storageLocation);
            }

            if ($batch) {
                $query->where('batch', $batch);
            }

            // Log query untuk debugging
            Log::info('Querying material description', [
                'material_input' => $material,
                'material_for_query' => $materialForQuery,
                'material_original' => $materialOriginal,
                'plant' => $plant,
                'storage_location' => $storageLocation,
                'batch' => $batch
            ]);

            // Prioritaskan data yang aktif, tapi jika tidak ada ambil yang tidak aktif
            $stock = $query->orderBy('is_active', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->first();

            if ($stock) {
                Log::info('Found material description', [
                    'material' => $stock->material,
                    'description' => $stock->material_description,
                    'found_by_format' => $stock->material === $materialForQuery ? 'with_leading_zero' : 'original'
                ]);
            } else {
                Log::warning('Material description not found', [
                    'material' => $material,
                    'plant' => $plant,
                    'storage_location' => $storageLocation
                ]);
            }

            return $stock->material_description ?? 'Material description not found';
        } catch (\Exception $e) {
            Log::error('Error getting material description: ' . $e->getMessage());
            return 'Material description not found';
        }
    }

    // ==================== HISTORY SAVING METHODS ====================

    /**
     * Simpan single HU history ke database
     */
    private function saveSingleHuHistory($request, $result, $scenarioType)
    {
        try {
            Log::info('Saving single HU history: ' . $request->hu_exid);

            $quantity = (int) round($request->pack_qty);
            $displayMaterial = $this->formatMaterialForDisplay($request->material);

            // ✅ PERBAIKAN: Ambil deskripsi material dari database
            $materialDescription = $this->getMaterialDescriptionFromStock(
                $request->material,
                $request->plant,
                $request->stge_loc,
                $request->batch
            );

            // ✅ PERBAIKAN: Gunakan Carbon dengan timezone Jakarta
            $jakartaTime = Carbon::now('Asia/Jakarta');

            // Simpan ke database - ✅ TAMBAHKAN stock_id => null
            DB::table('hu_histories')->insert([
                'stock_id' => null, // ✅ PERBAIKAN: Tambahkan stock_id NULL
                'hu_number' => $request->hu_exid,
                'material' => $displayMaterial,
                'material_description' => $materialDescription, // ✅ Deskripsi dari database
                'batch' => $request->batch,
                'quantity' => $quantity,
                'unit' => 'PC',
                'plant' => $request->plant,
                'storage_location' => $request->stge_loc,
                'sales_document' => $request->sp_stck_no,
                'scenario_type' => $scenarioType,
                'created_by' => Auth::check() ? Auth::user()->name : 'System',
                'created_at' => $jakartaTime,
                'updated_at' => $jakartaTime
            ]);

            Log::info('Single HU history saved successfully for HU: ' . $request->hu_exid);
            return true;

        } catch (\Exception $e) {
            Log::error('Error saving single HU history: ' . $e->getMessage());
            Log::error('Error details: ', [
                'hu_exid' => $request->hu_exid ?? 'null',
                'material' => $request->material ?? 'null'
            ]);
            return false;
        }
    }

    /**
     * Simpan single-multi HU history ke database
     */
    private function saveSingleMultiHuHistory($request, $result, $scenarioType)
    {
        try {
            Log::info('Saving single-multi HU history: ' . $request->hu_exid);

            $successCount = 0;
            $records = [];

            // ✅ PERBAIKAN: Gunakan Carbon dengan timezone Jakarta
            $jakartaTime = Carbon::now('Asia/Jakarta');

            foreach ($request->items as $item) {
                $quantity = (int) round($item['pack_qty']);
                $displayMaterial = $this->formatMaterialForDisplay($item['material']);

                // ✅ PERBAIKAN: Ambil deskripsi material dari database
                $materialDescription = $this->getMaterialDescriptionFromStock(
                    $item['material'],
                    $request->plant,
                    $request->stge_loc,
                    $item['batch'] ?? null
                );

                $records[] = [
                    'stock_id' => null, // ✅ PERBAIKAN: Tambahkan stock_id NULL
                    'hu_number' => $request->hu_exid,
                    'material' => $displayMaterial,
                    'material_description' => $materialDescription, // ✅ Deskripsi dari database
                    'batch' => $item['batch'] ?? null,
                    'quantity' => $quantity,
                    'unit' => 'PC',
                    'plant' => $request->plant,
                    'storage_location' => $request->stge_loc,
                    'sales_document' => $item['sp_stck_no'] ?? null,
                    'scenario_type' => $scenarioType,
                    'created_by' => Auth::check() ? Auth::user()->name : 'System',
                    'created_at' => $jakartaTime,
                    'updated_at' => $jakartaTime
                ];

                $successCount++;
            }

            // Insert batch ke database
            if (!empty($records)) {
                DB::table('hu_histories')->insert($records);
            }

            Log::info('Single-multi HU history saved. Items: ' . $successCount);
            return $successCount > 0;

        } catch (\Exception $e) {
            Log::error('Error saving single-multi HU history: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Simpan multiple HUs history ke database
     */
    private function saveMultipleHusHistory($husData, $result, $scenarioType)
    {
        try {
            Log::info('Saving multiple HUs history. Total HUs: ' . count($husData));

            $successCount = 0;
            $records = [];

            // ✅ PERBAIKAN: Gunakan Carbon dengan timezone Jakarta
            $jakartaTime = Carbon::now('Asia/Jakarta');

            foreach ($husData as $hu) {
                $quantity = (int) round($hu['pack_qty']);
                $displayMaterial = $this->formatMaterialForDisplay($hu['material']);

                // ✅ PERBAIKAN: Ambil deskripsi material dari database
                $materialDescription = $this->getMaterialDescriptionFromStock(
                    $hu['material'],
                    $hu['plant'],
                    $hu['stge_loc'],
                    $hu['batch'] ?? null
                );

                $records[] = [
                    'stock_id' => null, // ✅ PERBAIKAN: Tambahkan stock_id NULL
                    'hu_number' => $hu['hu_exid'],
                    'material' => $displayMaterial,
                    'material_description' => $materialDescription, // ✅ Deskripsi dari database
                    'batch' => $hu['batch'] ?? null,
                    'quantity' => $quantity,
                    'unit' => 'PC',
                    'plant' => $hu['plant'],
                    'storage_location' => $hu['stge_loc'],
                    'sales_document' => $hu['sp_stck_no'] ?? null,
                    'scenario_type' => $scenarioType,
                    'created_by' => Auth::check() ? Auth::user()->name : 'System',
                    'created_at' => $jakartaTime,
                    'updated_at' => $jakartaTime
                ];

                $successCount++;
            }

            // Insert batch ke database
            if (!empty($records)) {
                DB::table('hu_histories')->insert($records);
            }

            Log::info('Multiple HUs history saved. Total: ' . $successCount);
            return $successCount > 0;

        } catch (\Exception $e) {
            Log::error('Error saving multiple HUs history: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }
}
