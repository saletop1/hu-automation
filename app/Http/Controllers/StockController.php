<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StockController extends Controller
{
    public function getStockData(Request $request)
    {
        $query = Stock::where('hu_created', false);

        if ($request->has('material') && !empty($request->material)) {
            $query->where('material', 'like', '%' . $request->material . '%');
        }

        if ($request->has('plant') && !empty($request->plant)) {
            $query->where('plant', $request->plant);
        }

        if ($request->has('storage_location') && !empty($request->storage_location)) {
            $query->where('storage_location', $request->storage_location);
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $data->count()
            ]
        ]);
    }

    public function syncStock(Request $request)
    {
        try {
            // Contoh integrasi dengan SAP API
            // Ganti dengan koneksi SAP yang sesungguhnya
            $response = Http::withHeaders([
                'Authorization' => 'Bearer your-sap-token',
            ])->get('https://your-sap-api-url/stock', [
                'plant' => $request->plant,
                'storage_location' => $request->storage_location
            ]);

            if ($response->successful()) {
                $sapData = $response->json();

                foreach ($sapData as $item) {
                    Stock::updateOrCreate(
                        [
                            'material' => $item['material'],
                            'batch' => $item['batch'],
                            'plant' => $item['plant'],
                            'storage_location' => $item['storage_location']
                        ],
                        [
                            'material_description' => $item['material_description'],
                            'stock_quantity' => $item['stock_quantity'],
                            'base_unit' => $item['base_unit'],
                            'sales_document' => $item['sales_document'],
                            'item_number' => $item['item_number'],
                            'vendor_name' => $item['vendor_name'],
                            'last_updated' => now(),
                            'hu_created' => false // Reset status HU untuk data baru
                        ]
                    );
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Data stock berhasil di-sync'
                ]);

            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Gagal mengambil data dari SAP'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
