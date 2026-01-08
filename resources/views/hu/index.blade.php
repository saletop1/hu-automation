<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAP HU Automation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* CSS tetap sama seperti sebelumnya */
        .card-hover {
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            min-height: 180px;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #3b82f6;
        }
        .stock-table {
            font-size: 0.875rem;
        }
        .refresh-btn {
            transition: all 0.3s ease;
        }
        .refresh-btn:hover {
            transform: rotate(180deg);
        }
        .loading-spinner {
            display: none;
        }
        .btn-purple {
            background-color: #8b5cf6;
            border-color: #8b5cf6;
            color: white;
        }
        .btn-purple:hover {
            background-color: #7c3aed;
            border-color: #7c3aed;
            color: white;
        }
        .material-number {
            font-family: 'Courier New', monospace;
            font-weight: 500;
            background-color: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid #e9ecef;
        }
        .material-results-container {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            width: 100%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .material-result-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }
        .material-result-item:hover {
            background-color: #f5f5f5;
        }
        .material-result-item.no-result {
            color: #999;
            cursor: default;
        }
        .material-result-item:last-child {
            border-bottom: none;
        }
        .material-result-item strong {
            color: #333;
        }
        .material-result-item small {
            color: #666;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .table-responsive {
            max-height: 75vh;
            overflow-y: auto;
        }
        .sales-document {
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
        }
        .table-responsive thead th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 10;
            border-bottom: 2px solid #dee2e6;
        }
        .drop-zone {
            position: relative;
            min-height: 100px;
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            transition: all 0.3s ease;
            background: #fafafa;
            font-size: 0.75rem;
        }
        .drop-zone.drag-over {
            border-color: #3b82f6;
            background: #f0f7ff;
        }
        .drop-zone.has-items {
            border-color: #10b981;
            background: #f0fdf4;
        }
        .scenario-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px;
            margin: 3px 0;
            font-size: 0.7rem;
        }
        .scenario-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            font-weight: bold;
            z-index: 5;
        }
        .pagination-container.d-none {
            display: none !important;
        }
        .compact-card .card-body {
            padding: 0.75rem;
        }
        .compact-card .card-title {
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        .compact-card .card-text {
            font-size: 0.7rem;
        }
        .compact-card .icon-container {
            width: 40px;
            height: 40px;
        }
        .compact-card .icon-container i {
            font-size: 1.25rem;
        }
        .compact-card .btn {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
        .scenario-sidebar {
            position: sticky;
            top: 50px;
            height: fit-content;
            max-height: 95vh;
            overflow-y: auto;
        }
        .selected-material-s1 {
            background-color: #e6f7ff !important;
            border-left: 3px solid #1890ff !important;
        }
        .selected-material-s2 {
            background-color: #f6ffed !important;
            border-left: 3px solid #52c41a !important;
        }
        .selected-material-s3 {
            background-color: #f9f0ff !important;
            border-left: 3px solid #722ed1 !important;
        }
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-s1 {
            background-color: #1890ff;
        }
        .status-s2 {
            background-color: #52c41a;
        }
        .status-s3 {
            background-color: #722ed1;
        }
        .material-item-compact {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 4px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 3px;
            font-size: 0.65rem;
            min-height: 32px;
        }
        .material-code-compact {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #1f2937;
            font-size: 0.6rem;
            line-height: 1.2;
        }
        .material-qty-compact {
            font-weight: bold;
            color: #001610ff;
            font-size: 0.6rem;
            line-height: 1.2;
            background-color: #d1fae5;
            padding: 1px 4px;
            border-radius: 3px;
            border: 1px solid #000000ff;
            min-width: 30px;
            text-align: center;
        }
        .table-checkbox {
            width: 14px;
            height: 14px;
            cursor: pointer;
            accent-color: #3b82f6;
        }
        .table-checkbox:checked {
            background-color: #3b82f6;
        }
        .select-all-checkbox input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #3b82f6;
        }
        .row-selected {
            background-color: #f0f7ff !important;
        }
        #stockCountBadge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border: 1px solid #3b82f6;
        }
        .selection-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
        }
        .selected-count {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 500;
        }
        .clear-selection-btn {
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
            opacity: 0.5;
            cursor: not-allowed;
        }
        .clear-selection-btn.active {
            opacity: 1;
            cursor: pointer;
            color: #dc3545;
            border-color: #dc3545;
        }
        .clear-selection-btn.active:hover {
            background-color: #dc3545;
            color: white;
        }
        .card-header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            width: 100%;
        }
        .card-header-left {
            flex: 1;
        }
        .card-header-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .plant-option small, .storage-option small {
            font-size: 0.7rem;
            opacity: 0.7;
        }
        .dropdown-menu {
            max-height: 300px;
            overflow-y: auto;
        }
        .plant-option[data-plant="2000"] {
            border-left: 3px solid #ff6b35;
        }
        .plant-option[data-plant="3000"] {
            border-left: 3px solid #3b82f6;
        }
        .zero-stock {
            opacity: 0.6;
            background-color: #f8f9fa !important;
        }
        .zero-stock .material-number {
            background-color: #e9ecef;
            color: #6c757d;
        }
        .zero-stock .badge {
            background-color: #6c757d !important;
            color: white !important;
        }
        .plant-option.active {
            background-color: #e6f7ff !important;
            font-weight: 600 !important;
        }
        .plant-option[data-plant="2000"].active {
            background-color: #fff7e6 !important;
            border-left: 3px solid #ff6b35 !important;
        }
        .plant-option[data-plant="3000"].active {
            background-color: #e6f7ff !important;
            border-left: 3px solid #3b82f6 !important;
        }
        #plantDropdown.active-2000 {
            background-color: #ff6b35 !important;
            color: white !important;
            border-color: #ff6b35 !important;
        }
        #plantDropdown.active-3000 {
            background-color: #3b82f6 !important;
            color: white !important;
            border-color: #3b82f6 !important;
        }
        .loading-visible {
            display: flex !important;
        }
        .loading-overlay .spinner-border {
            width: 4rem;
            height: 4rem;
        }
        .loading-overlay p {
            font-size: 1.2rem;
            font-weight: 500;
        }
        .no-plant-selected {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        .no-plant-selected i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .no-plant-selected h5 {
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .no-plant-selected p {
            margin-bottom: 1.5rem;
        }
        .no-plant-selected .btn {
            min-width: 150px;
        }
        .material-warning {
            background-color: #fff3cd !important;
            border-left: 3px solid #ffc107 !important;
        }
        .material-warning .material-code-compact {
            color: #856404;
        }
        .warning-badge {
            background-color: #ffc107 !important;
            color: #856404 !important;
            font-size: 0.6rem;
            padding: 1px 4px;
            border-radius: 3px;
            margin-left: 4px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient-to-r from-green-700 to-yellow-800 shadow-lg">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('hu.index') }}">
                <i class="fas fa-cubes me-2"></i>SAP HU Automation
            </a>
            <div class="d-flex align-items-center">
                <span class="navbar-text text-light me-3">
                    <i class="fas fa-user me-1"></i>{{ Auth::user()->name }}
                </span>
                <div class="d-flex gap-2">
                    <a href="{{ route('hu.history') }}" class="btn btn-outline-light btn-sm d-flex align-items-center">
                        <i class="fas fa-history me-1"></i> History HU
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline m-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm d-flex align-items-center">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-10 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <div class="card-header-content">
                            <div class="card-header-left">
                                <h5 class="card-title mb-0 fw-bold text-gray-800">
                                    <i class="fas fa-warehouse me-2 text-blue-500"></i>
                                    Data Stock Tersedia
                                <span class="badge bg-primary bg-opacity-10 text-primary fs-6 ms-2" id="stockCountBadge">
                                    @php
                                        $availableStockCount = 0;
                                        if ($stockData['success'] && count($stockData['data']) > 0) {
                                            $availableStockCount = collect($stockData['data'])->filter(function($item) {
                                                return !$item->hu_created && $item->stock_quantity > 0;
                                            })->count();
                                        }
                                    @endphp
                                    {{ $availableStockCount }} items
                                </span>
                                </h5>
                                <small class="text-muted">Hanya menampilkan material dengan stock > 0 dan belum dibuat HU</small>
                                <div class="selection-info">
                                    <span class="selected-count" id="selectedCount">0 item terpilih</span>
                                </div>
                            </div>
                            <div class="card-header-right">
                                <button class="btn btn-outline-danger btn-sm clear-selection-btn" id="clearSelection" title="Hapus pilihan">
                                    <i class="fas fa-trash"></i>
                                </button>

                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="plantDropdown" data-bs-toggle="dropdown">
                                        <i class="fas fa-industry me-1"></i>
                                        <span id="selectedPlant">Pilih Plant</span>
                                    </button>
                                    <ul class="dropdown-menu" id="plantList">
                                        @foreach($plantsData as $plant => $locations)
                                            <li><a class="dropdown-item plant-option" href="#" data-plant="{{ $plant }}">
                                                Plant {{ $plant }}
                                                @if($plant == '2000')
                                                    <small class="text-muted d-block">Lokasi: 21HU, 21LK, 21NH</small>
                                                @elseif($plant == '3000')
                                                    <small class="text-muted d-block">Lokasi: 3D10, 3DH1, 3DH2</small>
                                                @endif
                                            </a></li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="storageLocationDropdown" data-bs-toggle="dropdown" disabled>
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <span id="selectedStorageLocation">Pilih Plant Terlebih Dahulu</span>
                                    </button>
                                    <ul class="dropdown-menu" id="storageLocationList">
                                    </ul>
                                </div>

                                <div class="input-group input-group-sm" style="width: 300px;">
                                    <input type="text" id="materialSearch" class="form-control" placeholder="Cari material, deskripsi, batch, customer..." disabled>
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" title="Clear search" disabled>
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <button class="btn btn-outline-primary" type="button" id="searchBtn" disabled>
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <button id="refreshStock" class="btn btn-outline-primary btn-sm refresh-btn" disabled>
                                    <i class="fas fa-sync-alt me-1"></i> Sync Sekarang
                                </button>
                                <div class="loading-spinner spinner-border spinner-border-sm text-primary me-2" role="status" style="display: none;">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="stockTableContainer">
                            <div class="no-plant-selected" id="noPlantSelectedMessage">
                                <i class="fas fa-industry"></i>
                                <h5>Pilih Plant Terlebih Dahulu</h5>
                                <p>Silakan pilih plant dari dropdown di atas untuk melihat data stock</p>
                                <button class="btn btn-primary" id="selectPlantBtn">
                                    <i class="fas fa-chevron-down me-1"></i> Pilih Plant
                                </button>
                            </div>
                            <div class="table-responsive d-none" id="stockTableWrapper">
                                <table class="table table-hover stock-table mb-0">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="border-0" style="width: 40px;">
                                                <input type="checkbox" id="selectAllHeader" class="table-checkbox">
                                            </th>
                                            <th class="border-0">Status</th>
                                            <th class="border-0">Material</th>
                                            <th class="border-0">Deskripsi</th>
                                            <th class="border-0">Batch</th>
                                            <th class="border-0 text-end">Qty Stock</th>
                                            <th class="border-0">Unit</th>
                                            <th class="border-0">Dokumen Penjualan</th>
                                            <th class="border-0">Customer</th>
                                            <th class="border-0">Update Terakhir</th>
                                        </tr>
                                    </thead>
                                    <tbody id="stockTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-2 mb-4">
                <div class="scenario-sidebar">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="card card-hover h-100 border-0 shadow-sm compact-card">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-blue-100 rounded-circle d-inline-flex align-items-center justify-content-center me-2 icon-container">
                                            <i class="fas fa-cube text-blue-600"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title fw-bold text-gray-800 mb-0">Skenario 1</h5>
                                            <p class="card-text text-gray-600 small">1 HU dengan 1 Material</p>
                                        </div>
                                    </div>

                                    <div class="drop-zone mb-2" data-scenario="single" id="dropZoneSingle">
                                        <i class="fas fa-arrow-down text-gray-400 mb-1"></i>
                                        <p class="text-muted small mb-1">Seret material ke sini</p>
                                        <div id="scenarioSingleItems"></div>
                                    </div>

                                    <div class="d-grid gap-1">
                                        <a href="{{ route('hu.create-single') }}" class="btn btn-primary btn-sm" id="goToScenario1">
                                            <i class="fas fa-arrow-right me-1"></i>Lanjut
                                        </a>
                                        <button class="btn btn-outline-secondary btn-sm" onclick="clearScenario('single')">
                                            <i class="fas fa-trash me-1"></i>Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 mb-3">
                            <div class="card card-hover h-100 border-0 shadow-sm compact-card">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-green-100 rounded-circle d-inline-flex align-items-center justify-content-center me-2 icon-container">
                                            <i class="fas fa-boxes text-green-600"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title fw-bold text-gray-800 mb-0">Skenario 2</h5>
                                            <p class="card-text text-gray-600 small">1 HU dengan Multiple Material</p>
                                        </div>
                                    </div>

                                    <div class="drop-zone mb-2" data-scenario="single-multi" id="dropZoneSingleMulti">
                                        <i class="fas fa-arrow-down text-gray-400 mb-1"></i>
                                        <p class="text-muted small mb-1">Seret material ke sini</p>
                                        <div id="scenarioSingleMultiItems"></div>
                                    </div>

                                    <div class="d-grid gap-1">
                                        <a href="{{ route('hu.create-single-multi') }}" class="btn btn-success btn-sm" id="goToScenario2">
                                            <i class="fas fa-arrow-right me-1"></i>Lanjut
                                        </a>
                                        <button class="btn btn-outline-secondary btn-sm" onclick="clearScenario('single-multi')">
                                            <i class="fas fa-trash me-1"></i>Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 mb-3">
                            <div class="card card-hover h-100 border-0 shadow-sm compact-card">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-purple-100 rounded-circle d-inline-flex align-items-center justify-content-center me-2 icon-container">
                                            <i class="fas fa-pallet text-purple-600"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title fw-bold text-gray-800 mb-0">Skenario 3</h5>
                                            <p class="card-text text-gray-600 small">Multiple HU (Setiap HU 1 Material)</p>
                                        </div>
                                    </div>

                                    <div class="drop-zone mb-2" data-scenario="multiple" id="dropZoneMultiple">
                                        <i class="fas fa-arrow-down text-gray-400 mb-1"></i>
                                        <p class="text-muted small mb-1">Seret material ke sini</p>
                                        <div id="scenarioMultipleItems"></div>
                                    </div>

                                    <div class="d-grid gap-1">
                                        <a href="{{ route('hu.create-multiple') }}" class="btn btn-purple btn-sm" id="goToScenario3">
                                            <i class="fas fa-arrow-right me-1"></i>Lanjut
                                        </a>
                                        <button class="btn btn-outline-secondary btn-sm" onclick="clearScenario('multiple')">
                                            <i class="fas fa-trash me-1"></i>Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="loading-overlay" style="display: none;">
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted">Memuat data...</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
    // Global variables
    let allStockData = [];
    const plantsData = @json($plantsData ?? []);
    // PERBAIKAN: Pastikan scenarioData selalu berupa array
    const scenarioData = {
        'single': [],
        'single-multi': [],
        'multiple': []
    };
    let selectedRows = new Set();
    let searchTimeout;
    let isSyncing = false;

    // Plant locations data
    const manualPlantLocations = {
        '2000': ['21HU', '21LK', '21NH'],
        '3000': ['3D10', '3DH1', '3DH2']
    };

    // State management - TIDAK ADA DEFAULT PLANT
    let selectedPlant = sessionStorage.getItem('selectedPlant') || '';
    let selectedStorageLocation = sessionStorage.getItem('selectedStorageLocation') || '';

    // ===== FUNGSI UTILITAS =====
    function formatMaterialNumber(material) {
        if (!material) return '';
        if (/^\d+$/.test(material)) {
            return material.replace(/^0+/, '') || '0';
        }
        return material;
    }

    function convertUnit(unit) {
        return unit === 'ST' ? 'PC' : unit;
    }

    function combineSalesDocument(salesDoc, itemNumber) {
        if (!salesDoc && !itemNumber) return '-';
        if (!salesDoc) return itemNumber;
        if (!itemNumber) return salesDoc;
        return salesDoc + itemNumber;
    }

    function getCustomerName(vendorName) {
        if (!vendorName) return '-';
        const words = vendorName.split(' ');
        if (words.length <= 2) {
            return vendorName;
        } else {
            return words.slice(0, 2).join(' ') + '*';
        }
    }

    // ===== FUNGSI VALIDASI PLANT =====
    function validateMaterialPlant(item) {
        console.log('DEBUG validateMaterialPlant:', {
            itemPlant: item.plant,
            selectedPlant: selectedPlant,
            itemPlantType: typeof item.plant,
            selectedPlantType: typeof selectedPlant,
            material: item.material
        });

        if (!selectedPlant) {
            console.log('DEBUG: Plant belum dipilih');
            return { valid: false, message: 'Plant belum dipilih' };
        }

        // Konversi kedua nilai ke string untuk perbandingan yang konsisten
        const itemPlantStr = String(item.plant || '');
        const selectedPlantStr = String(selectedPlant || '');

        if (itemPlantStr !== selectedPlantStr) {
            console.log('DEBUG: Plant tidak sama', itemPlantStr, '!==', selectedPlantStr);
            return {
                valid: false,
                message: `Material ${formatMaterialNumber(item.material)} berasal dari Plant ${item.plant}, sedangkan plant yang aktif adalah ${selectedPlant}.`
            };
        }

        console.log('DEBUG: Plant valid');
        return { valid: true };
    }

    function validateScenarioPlant(scenario) {
        console.log('DEBUG validateScenarioPlant:', {
            scenario: scenario,
            selectedPlant: selectedPlant,
            itemsCount: scenarioData[scenario] ? scenarioData[scenario].length : 0
        });

        if (!selectedPlant) {
            return { valid: false, message: 'Plant belum dipilih' };
        }

        const items = scenarioData[scenario];
        // PERBAIKAN: Pastikan items adalah array
        if (!Array.isArray(items) || items.length === 0) {
            return { valid: true };
        }

        const selectedPlantStr = String(selectedPlant || '');

        for (let item of items) {
            const itemPlantStr = String(item.plant || '');

            console.log('DEBUG checking item:', {
                itemPlant: item.plant,
                selectedPlant: selectedPlant,
                itemPlantStr: itemPlantStr,
                selectedPlantStr: selectedPlantStr,
                comparison: itemPlantStr !== selectedPlantStr
            });

            if (itemPlantStr !== selectedPlantStr) {
                return {
                    valid: false,
                    message: `Material ${formatMaterialNumber(item.material)} berasal dari Plant ${item.plant}, sedangkan plant yang aktif adalah ${selectedPlant}.`
                };
            }
        }
        return { valid: true };
    }

    // ===== FUNGSI STATE MANAGEMENT =====
    function saveSelectedState() {
        try {
            sessionStorage.setItem('selectedPlant', selectedPlant);
            sessionStorage.setItem('selectedStorageLocation', selectedStorageLocation || '');
            console.log('State saved:', selectedPlant, selectedStorageLocation);
        } catch (error) {
            console.error('Error saving state:', error);
        }
    }

    function updatePlantVisualIndicator() {
        $('.plant-option').removeClass('active');
        $('#plantDropdown').removeClass('active-2000 active-3000');

        if (selectedPlant) {
            $(`.plant-option[data-plant="${selectedPlant}"]`).addClass('active');

            if (selectedPlant === '2000') {
                $('#plantDropdown').addClass('active-2000');
            } else if (selectedPlant === '3000') {
                $('#plantDropdown').addClass('active-3000');
            }
        }
    }

    // ===== PERBAIKAN UTAMA: updateMaterialStatus() =====
    function updateMaterialStatus() {
        // Reset semua status
        $('.draggable-row').removeClass('selected-material-s1 selected-material-s2 selected-material-s3');
        $('.material-status').empty();

        // Tandai material berdasarkan skenario
        Object.keys(scenarioData).forEach(scenario => {
            const items = scenarioData[scenario];

            // PERBAIKAN: Pastikan items adalah array sebelum menggunakan forEach
            if (!Array.isArray(items)) {
                console.warn(`scenarioData.${scenario} bukan array:`, items);
                // Reset ke array kosong jika bukan array
                scenarioData[scenario] = [];
                return;
            }

            const scenarioClass = getScenarioClass(scenario);
            items.forEach(item => {
                if (!item || !item.material) {
                    console.warn(`Item tidak valid di scenario ${scenario}:`, item);
                    return;
                }

                $(`.draggable-row[data-material="${item.material}"][data-batch="${item.batch}"][data-plant="${item.plant}"][data-storage-location="${item.storage_location}"]`)
                    .addClass(scenarioClass)
                    .find('.material-status')
                    .append(`<span class="status-indicator ${getStatusClass(scenario)}" title="Material dipilih di ${getScenarioName(scenario)}"></span>`);
            });
        });
    }

    function getScenarioClass(scenario) {
        switch(scenario) {
            case 'single': return 'selected-material-s1';
            case 'single-multi': return 'selected-material-s2';
            case 'multiple': return 'selected-material-s3';
            default: return '';
        }
    }

    function getStatusClass(scenario) {
        switch(scenario) {
            case 'single': return 'status-s1';
            case 'single-multi': return 'status-s2';
            case 'multiple': return 'status-s3';
            default: return '';
        }
    }

    function getScenarioName(scenario) {
        switch(scenario) {
            case 'single': return 'Skenario 1';
            case 'single-multi': return 'Skenario 2';
            case 'multiple': return 'Skenario 3';
            default: return '';
        }
    }

    // ===== FUNGSI UI TOGGLE =====
    function toggleUIForPlantSelection(hasPlantSelected) {
        if (hasPlantSelected) {
            $('#noPlantSelectedMessage').addClass('d-none');
            $('#stockTableWrapper').removeClass('d-none');
            $('#materialSearch').prop('disabled', false);
            $('#clearSearchBtn').prop('disabled', false);
            $('#searchBtn').prop('disabled', false);
            $('#refreshStock').prop('disabled', false);
            $('#storageLocationDropdown').prop('disabled', false);
        } else {
            $('#noPlantSelectedMessage').removeClass('d-none');
            $('#stockTableWrapper').addClass('d-none');
            $('#materialSearch').prop('disabled', true);
            $('#clearSearchBtn').prop('disabled', true);
            $('#searchBtn').prop('disabled', true);
            $('#refreshStock').prop('disabled', true);
            $('#storageLocationDropdown').prop('disabled', true);
            $('#selectedStorageLocation').text('Pilih Plant Terlebih Dahulu');
            $('#storageLocationList').empty();
        }
    }

    // ===== EVENT HANDLERS =====
    document.getElementById('goToScenario1').addEventListener('click', function(e) {
        const items = scenarioData.single;

        // PERBAIKAN: Pastikan items adalah array dan memiliki panjang > 0
        if (!Array.isArray(items) || items.length === 0) {
            e.preventDefault();
            showMessage('Silakan pilih material terlebih dahulu dengan menyeret ke area Skenario 1', 'warning');
            return;
        }

        // Validasi plant sebelum navigasi
        const validation = validateScenarioPlant('single');
        if (!validation.valid) {
            e.preventDefault();
            showMessage(validation.message, 'error');
            return;
        }

        try {
            sessionStorage.setItem('scenario1_data', JSON.stringify(items[0])); // Hanya item pertama untuk skenario 1
            sessionStorage.setItem('scenario_plant', selectedPlant);
            console.log('Data saved for scenario 1');
        } catch (error) {
            console.error('Error saving to sessionStorage:', error);
            showMessage('Error menyimpan data material', 'error');
            e.preventDefault();
        }
    });

    // ===== FUNGSI SELEKSI =====
    function updateSelectionCount() {
        const count = selectedRows.size;
        $('#selectedCount').text(`${count} item terpilih`);

        const clearBtn = $('#clearSelection');
        if (count > 0) {
            clearBtn.addClass('active');
            $('.row-select:checked').closest('tr').addClass('row-selected');
        } else {
            clearBtn.removeClass('active');
            $('.draggable-row').removeClass('row-selected');
        }
    }

    function addSelectedItemsToScenario(scenario) {
        if (selectedRows.size === 0) {
            showMessage('Tidak ada item yang dipilih', 'warning');
            return;
        }

        let addedCount = 0;
        let invalidPlantItems = 0;

        selectedRows.forEach(index => {
            if (allStockData[index]) {
                const item = allStockData[index];

                // Validasi plant
                const plantValidation = validateMaterialPlant(item);
                if (!plantValidation.valid) {
                    invalidPlantItems++;
                    return;
                }

                if (parseFloat(item.stock_quantity) > 0) {
                    // PERBAIKAN: Pastikan scenarioData[scenario] adalah array
                    if (!Array.isArray(scenarioData[scenario])) {
                        scenarioData[scenario] = [];
                    }

                    const existingIndex = scenarioData[scenario].findIndex(i =>
                        i.material === item.material &&
                        i.batch === item.batch &&
                        i.plant === item.plant &&
                        i.storage_location === item.storage_location
                    );

                    if (existingIndex === -1) {
                        if (scenario === 'single' && scenarioData[scenario].length > 0) {
                            showMessage('Skenario 1 hanya boleh berisi 1 material', 'warning');
                            return;
                        }

                        scenarioData[scenario].push(item);
                        addedCount++;
                    }
                } else {
                    showMessage('Material dengan stock 0 tidak dapat dipilih', 'warning');
                }
            }
        });

        if (invalidPlantItems > 0) {
            showMessage(`${invalidPlantItems} item tidak ditambahkan karena berasal dari plant yang berbeda`, 'warning');
        }

        if (addedCount > 0) {
            updateScenarioDisplay(scenario);
            saveScenarioDataToSession(scenario);
            updateMaterialStatus();
            showMessage(`${addedCount} material ditambahkan ke ${getScenarioName(scenario)}`, 'success');
            clearSelection();
        } else if (invalidPlantItems === 0) {
            showMessage('Tidak ada material baru yang ditambahkan', 'warning');
        }
    }

    function clearSelection() {
        selectedRows.clear();
        $('.row-select').prop('checked', false);
        $('#selectAllHeader').prop('checked', false);
        updateSelectionCount();
    }

    // ===== FUNGSI SEARCH =====
    function setupLiveSearch() {
        const searchInput = document.getElementById('materialSearch');

        if (!searchInput) {
            console.error('Search input element not found');
            return;
        }

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        const performSearch = debounce(function(searchTerm) {
            console.log('Searching for:', searchTerm);

            const rows = document.querySelectorAll('#stockTableBody tr.draggable-row');
            let visibleCount = 0;

            rows.forEach(row => {
                if (row.style.display === 'none') {
                    row.style.display = '';
                }

                const rowText = row.textContent.toLowerCase();
                const matches = rowText.includes(searchTerm.toLowerCase());

                if (matches || searchTerm === '') {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const noResultsRow = document.getElementById('noResultsMessage');
            if (visibleCount === 0 && searchTerm.length > 0) {
                if (!noResultsRow) {
                    const tbody = document.getElementById('stockTableBody');
                    const newRow = document.createElement('tr');
                    newRow.id = 'noResultsMessage';
                    newRow.innerHTML = `
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="fas fa-search me-2"></i>
                            Tidak ada material yang cocok dengan "${searchTerm}"
                        </td>
                    `;
                    tbody.appendChild(newRow);
                } else {
                    noResultsRow.style.display = '';
                }
            } else if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }

            updateVisibleStockCount(visibleCount, searchTerm);
            console.log('Search completed. Visible rows:', visibleCount);
        }, 300);

        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.trim();
            performSearch(searchTerm);
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                searchInput.value = '';
                performSearch('');
                searchInput.blur();
            }
        });

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = e.target.value.trim();
                performSearch(searchTerm);
            }
        });
    }

    function updateVisibleStockCount(visibleCount, searchTerm) {
        const badge = document.getElementById('stockCountBadge');
        if (!badge) return;

        if (searchTerm && searchTerm.length > 0) {
            badge.textContent = `${visibleCount} items (filtered)`;
            badge.className = 'badge bg-warning bg-opacity-10 text-warning fs-6 ms-2';
        } else {
            const availableStockCount = allStockData.filter(item =>
                !item.hu_created && parseFloat(item.stock_quantity) > 0
            ).length;
            badge.textContent = `${availableStockCount} items`;
            badge.className = 'badge bg-primary bg-opacity-10 text-primary fs-6 ms-2';
        }
    }

    function updateStockCountBadge(count) {
        let badgeText = `${count} items`;
        let badgeClass = 'badge bg-primary bg-opacity-10 text-primary fs-6 ms-2';

        if (selectedPlant) {
            badgeText += ` (Plant ${selectedPlant}`;
            if (selectedStorageLocation) {
                badgeText += ` / ${selectedStorageLocation}`;
            } else {
                badgeText += ' / Semua Lokasi';
            }
            badgeText += ')';

            if (selectedPlant === '2000') {
                badgeClass = 'badge bg-warning bg-opacity-10 text-warning fs-6 ms-2';
            }
        }

        $('#stockCountBadge').text(badgeText).attr('class', badgeClass);
    }

    function clearSearch() {
        const searchInput = document.getElementById('materialSearch');
        if (searchInput) {
            searchInput.value = '';

            const rows = document.querySelectorAll('#stockTableBody tr.draggable-row');
            rows.forEach(row => {
                row.style.display = '';
            });

            const noResultsRow = document.getElementById('noResultsMessage');
            if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }

            updateVisibleStockCount(rows.length, '');
        }
    }

    // ===== FUNGSI STORAGE LOCATION =====
    function updateStorageLocations(plant) {
        const locationList = $('#storageLocationList');
        locationList.empty();
        locationList.append('<li><a class="dropdown-item storage-option active" href="#" data-location="">Semua Lokasi</a></li>');

        let locations = [];

        if (manualPlantLocations[plant]) {
            locations = manualPlantLocations[plant];
        } else if (plantsData[plant]) {
            locations = plantsData[plant];
        }

        locations.forEach(location => {
            locationList.append(`
                <li>
                    <a class="dropdown-item storage-option" href="#" data-location="${location}">
                        ${location}
                    </a>
                </li>
            `);
        });

        console.log('Updated locations for plant', plant, ':', locations);
    }

    // ===== FUNGSI LOAD DATA =====
    function loadStockData(forceRefresh = false) {
        if (!selectedPlant) {
            console.log('Plant belum dipilih, tidak dapat memuat data.');
            return;
        }

        console.log('Loading stock data for plant:', selectedPlant, 'location:', selectedStorageLocation);

        showLoading(true);
        const searchTerm = $('#materialSearch').val();
        const timestamp = new Date().getTime();

        $('.loading-overlay').addClass('loading-visible');

        $.ajax({
            url: "{{ route('hu.get-stock') }}",
            type: 'GET',
            data: {
                search: searchTerm,
                plant: selectedPlant,
                storage_location: selectedStorageLocation,
                _: timestamp,
                force_refresh: forceRefresh ? 1 : 0,
                filter_available: 1
            },
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            cache: false,
            success: function(response) {
                console.log('Stock data response:', response);

                if (response.success) {
                    allStockData = response.data || [];

                    const filteredData = allStockData.filter(item =>
                        !item.hu_created && parseFloat(item.stock_quantity) > 0
                    );

                    console.log('Filtered data:', filteredData.length);

                    // Debug: tampilkan beberapa item pertama
                    if (filteredData.length > 0) {
                        console.log('Sample filtered items:', filteredData.slice(0, 3).map(item => ({
                            material: item.material,
                            plant: item.plant,
                            type: typeof item.plant
                        })));
                    }

                    populateStockTable(filteredData);
                    updateStockCountBadge(filteredData.length);

                } else {
                    showError('Gagal memuat data stock: ' + (response.error || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Stock data error:', error);
                showError('Error memuat data stock: ' + error);
            },
            complete: function() {
                showLoading(false);
                $('.loading-overlay').removeClass('loading-visible');
            }
        });
    }

    // ===== FUNGSI SYNC =====
    function syncStockData() {
        if (isSyncing) {
            console.log('Sync already in progress');
            return;
        }

        if (!selectedPlant) {
            showError('Pilih plant sebelum sync');
            return;
        }

        saveSelectedState();

        if (!confirm('PERINGATAN: Data stock lama untuk plant ' + selectedPlant +
                    ' dan lokasi ' + (selectedStorageLocation || 'Semua Lokasi') +
                    ' akan dihapus dan diganti dengan data baru dari SAP. Lanjutkan?')) {
            return;
        }

        isSyncing = true;

        $('#refreshStock').prop('disabled', true).addClass('disabled');
        showLoading(true);

        console.log('Syncing stock data for plant:', selectedPlant, 'location:', selectedStorageLocation);

        $.ajax({
            url: "{{ route('hu.sync-stock') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                plant: selectedPlant,
                storage_location: selectedStorageLocation || ''
            },
            success: function(response) {
                console.log('Sync response:', response);

                if (response.success) {
                    showMessage('Sync berhasil! Memuat data terbaru...', 'success');

                    setTimeout(function() {
                        loadStockData(true);
                        updatePlantVisualIndicator();
                        updateStorageLocations(selectedPlant);
                        clearSearch();
                    }, 1500);

                } else {
                    showError(response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('Sync error:', error);
                let errorMessage = 'Error sync data stock';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }
                showError(errorMessage);
            },
            complete: function() {
                isSyncing = false;
                showLoading(false);
                setTimeout(() => {
                    $('#refreshStock').prop('disabled', false).removeClass('disabled');
                }, 1000);
            }
        });
    }

    // ===== FUNGSI POPULATE TABLE =====
    function populateStockTable(data) {
        const tbody = $('#stockTableBody');
        tbody.empty();

        console.log('Populating table with', data.length, 'items');

        if (data.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        Tidak ada data stock tersedia untuk Plant ${selectedPlant}
                        ${selectedStorageLocation ? `, Lokasi ${selectedStorageLocation}` : ''}
                    </td>
                </tr>
            `);
            return;
        }

        data.forEach(function(item, index) {
            const originalIndex = allStockData.findIndex(i =>
                i.material === item.material &&
                i.batch === item.batch &&
                i.plant === item.plant &&
                i.storage_location === item.storage_location
            );

            const formattedMaterial = formatMaterialNumber(item.material);
            const originalMaterial = item.material;
            const showTooltip = formattedMaterial !== originalMaterial;
            const convertedUnit = convertUnit(item.base_unit);
            const combinedSalesDoc = combineSalesDocument(item.sales_document, item.item_number);
            const customerName = getCustomerName(item.vendor_name);

            const row = `
                <tr class="hover:bg-gray-50 draggable-row"
                    draggable="true"
                    data-index="${originalIndex}"
                    data-material="${item.material}"
                    data-batch="${item.batch}"
                    data-plant="${item.plant}"
                    data-storage-location="${item.storage_location}"
                    data-stock-quantity="${item.stock_quantity}">
                    <td class="border-0">
                        <input type="checkbox" class="table-checkbox row-select" data-index="${originalIndex}">
                    </td>
                    <td class="border-0">
                        <span class="material-status" id="status-${item.material}-${item.batch}"></span>
                    </td>
                    <td class="border-0">
                        <span class="material-number ${showTooltip ? 'has-tooltip' : ''}"
                            ${showTooltip ? `title="Original: ${originalMaterial}"` : ''}>
                            ${formattedMaterial}
                        </span>
                    </td>
                    <td class="border-0 text-gray-600">${item.material_description || '-'}</td>
                    <td class="border-0 text-gray-600">${item.batch || '-'}</td>
                    <td class="border-0 text-end">
                        <span class="badge bg-success bg-opacity-10 text-success fs-6">
                            ${parseFloat(item.stock_quantity || 0).toLocaleString()}
                        </span>
                    </td>
                    <td class="border-0 text-gray-600">${convertedUnit}</td>
                    <td class="border-0">
                        <span class="sales-document">${combinedSalesDoc}</span>
                    </td>
                    <td class="border-0 text-gray-600" title="${item.vendor_name || '-'}">${customerName}</td>
                    <td class="border-0 text-gray-600">
                        ${item.last_updated ? new Date(item.last_updated).toLocaleString() : '-'}
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        setupRowDragEvents();
        updateMaterialStatus();
        clearSelection();
    }

    // ===== FUNGSI DRAG & DROP =====
    function setupRowDragEvents() {
        $('.draggable-row').each(function() {
            const row = $(this)[0];
            const stockQty = parseFloat($(this).data('stock-quantity') || 0);

            if (stockQty <= 0) {
                $(this).css('opacity', '0.6');
                $(this).css('cursor', 'not-allowed');
                row.draggable = false;
                $(this).attr('title', 'Stock tidak tersedia');
            } else {
                row.draggable = true;
                row.addEventListener('dragstart', function(e) {
                    const index = $(this).data('index');
                    console.log('DEBUG dragstart - index:', index, 'item:', allStockData[index]);

                    if (selectedRows.size > 0) {
                        e.dataTransfer.setData('text/plain', 'multiple');
                    } else {
                        e.dataTransfer.setData('text/plain', index);
                    }
                    e.dataTransfer.effectAllowed = 'copy';
                });
            }
        });
    }

    function setupDragAndDrop() {
        const dropZones = document.querySelectorAll('.drop-zone');
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });

            zone.addEventListener('dragleave', function(e) {
                $(this).removeClass('drag-over');
            });

            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
                const scenario = $(this).data('scenario');

                console.log('DEBUG drop event - selectedRows:', selectedRows.size);

                if (selectedRows.size > 0) {
                    addSelectedItemsToScenario(scenario);
                } else {
                    const itemIndex = e.dataTransfer.getData('text/plain');
                    console.log('DEBUG drop itemIndex:', itemIndex);

                    if (itemIndex !== '' && itemIndex !== 'multiple' && allStockData[itemIndex]) {
                        const item = allStockData[itemIndex];
                        console.log('DEBUG drop item:', item);

                        if (parseFloat(item.stock_quantity) > 0) {
                            // Validasi plant sebelum menambahkan
                            const plantValidation = validateMaterialPlant(item);
                            console.log('DEBUG plant validation result:', plantValidation);

                            if (!plantValidation.valid) {
                                showMessage(plantValidation.message, 'error');
                                return;
                            }
                            addItemToScenario(scenario, item);
                        } else {
                            showMessage('Material dengan stock 0 tidak dapat dipilih', 'warning');
                        }
                    }
                }
            });
        });
    }

    function addItemToScenario(scenario, item) {
        console.log('DEBUG addItemToScenario - item:', item);

        if (parseFloat(item.stock_quantity) <= 0) {
            showMessage('Material dengan stock 0 tidak dapat dipilih', 'warning');
            return;
        }

        // Validasi plant
        const plantValidation = validateMaterialPlant(item);
        console.log('DEBUG addItemToScenario plant validation:', plantValidation);

        if (!plantValidation.valid) {
            showMessage(plantValidation.message, 'error');
            return;
        }

        item.combined_sales_doc = combineSalesDocument(item.sales_document, item.item_number);
        item.magry = item.magry || '';
        item.suggested_pack_mat = item.suggested_pack_mat || '';

        // PERBAIKAN: Pastikan scenarioData[scenario] adalah array
        if (!Array.isArray(scenarioData[scenario])) {
            scenarioData[scenario] = [];
        }

        const existingIndex = scenarioData[scenario].findIndex(i =>
            i.material === item.material &&
            i.batch === item.batch &&
            i.plant === item.plant &&
            i.storage_location === item.storage_location
        );

        if (existingIndex !== -1) {
            showMessage('Material sudah ada di skenario ini', 'warning');
            return;
        }

        if (scenario === 'single' && scenarioData[scenario].length > 0) {
            showMessage('Skenario 1 hanya boleh berisi 1 material. Hapus item lama terlebih dahulu.', 'warning');
            return;
        }

        scenarioData[scenario].push(item);
        updateScenarioDisplay(scenario);
        saveScenarioDataToSession(scenario);
        updateMaterialStatus();
        showMessage(`Material ${formatMaterialNumber(item.material)} ditambahkan ke ${getScenarioName(scenario)}`, 'success');
    }

    // ===== FUNGSI SCENARIO =====
    function updateScenarioDisplay(scenario) {
        let containerId = '';
        let dropZoneId = '';

        if (scenario === 'single') {
            containerId = 'scenarioSingleItems';
            dropZoneId = 'dropZoneSingle';
        } else if (scenario === 'single-multi') {
            containerId = 'scenarioSingleMultiItems';
            dropZoneId = 'dropZoneSingleMulti';
        } else if (scenario === 'multiple') {
            containerId = 'scenarioMultipleItems';
            dropZoneId = 'dropZoneMultiple';
        } else {
            return;
        }

        const container = $(`#${containerId}`);
        const dropZone = $(`#${dropZoneId}`);

        container.empty();

        const items = scenarioData[scenario];

        // PERBAIKAN: Pastikan items adalah array
        if (!Array.isArray(items)) {
            console.warn(`scenarioData.${scenario} bukan array di updateScenarioDisplay:`, items);
            scenarioData[scenario] = []; // Reset ke array kosong
            return;
        }

        if (items.length > 0) {
            dropZone.addClass('has-items');

            items.forEach((item, index) => {
                if (!item || !item.material) {
                    console.warn(`Item tidak valid di scenario ${scenario}:`, item);
                    return;
                }

                const formattedMaterial = formatMaterialNumber(item.material);
                const isDifferentPlant = String(item.plant || '') !== String(selectedPlant || '');
                const itemElement = `
                    <div class="material-item-compact position-relative d-flex justify-content-between align-items-center ${isDifferentPlant ? 'material-warning' : ''}">
                        <button type="button" class="btn-close btn-close-sm" style="font-size: 0.5rem; padding: 2px;" onclick="removeItemFromScenario('${scenario}', ${index})"></button>
                        <div class="d-flex flex-column flex-grow-1 ms-1" style="min-width: 0;">
                            <div class="material-code-compact text-truncate">
                                ${formattedMaterial}
                                ${isDifferentPlant ? '<span class="warning-badge">Plant ' + item.plant + '</span>' : ''}
                            </div>
                            <div class="material-qty-compact text-end">${parseFloat(item.stock_quantity || 0).toLocaleString('id-ID')}</div>
                        </div>
                    </div>
                `;
                container.append(itemElement);
            });

            let badge = dropZone.find('.scenario-badge');
            if (badge.length === 0) {
                badge = $('<div class="scenario-badge"></div>');
                dropZone.append(badge);
            }
            badge.text(items.length)
                 .css({
                     'background': '#ef4444',
                     'color': 'white',
                     'border-radius': '50%',
                     'width': '20px',
                     'height': '20px',
                     'font-size': '0.7rem',
                     'display': 'flex',
                     'align-items': 'center',
                     'justify-content': 'center',
                     'position': 'absolute',
                     'top': '-8px',
                     'right': '-8px',
                     'border': '2px solid white',
                     'font-weight': 'bold'
                 });
        } else {
            dropZone.removeClass('has-items');
            dropZone.find('.scenario-badge').remove();
        }
    }

    function removeItemFromScenario(scenario, index) {
        if (!Array.isArray(scenarioData[scenario])) {
            scenarioData[scenario] = [];
        }

        scenarioData[scenario].splice(index, 1);
        updateScenarioDisplay(scenario);
        saveScenarioDataToSession(scenario);
        updateMaterialStatus();
    }

    function clearScenario(scenario) {
        scenarioData[scenario] = [];
        updateScenarioDisplay(scenario);
        saveScenarioDataToSession(scenario);
        updateMaterialStatus();
        showMessage('Semua material dihapus dari skenario', 'success');
    }

    function saveScenarioDataToSession(scenario) {
        let key = '';
        if (scenario === 'single') key = 'scenario1_data';
        else if (scenario === 'single-multi') key = 'scenario2_data';
        else if (scenario === 'multiple') key = 'scenario3_data';
        else return;

        try {
            const dataToSave = scenarioData[scenario];
            // PERBAIKAN: Pastikan data yang disimpan adalah array
            if (!Array.isArray(dataToSave)) {
                console.warn(`Data untuk ${key} bukan array, mengubah menjadi array kosong`);
                scenarioData[scenario] = [];
                sessionStorage.setItem(key, JSON.stringify([]));
            } else {
                sessionStorage.setItem(key, JSON.stringify(dataToSave));
            }

            sessionStorage.setItem('scenario_plant', selectedPlant);
            console.log(`Data untuk ${key} berhasil disimpan.`);
        } catch (err) {
            console.error('Gagal menyimpan ke sessionStorage:', err);
            showError('Gagal menyimpan data. Penyimpanan browser mungkin penuh.');
        }
    }

    // ===== PERBAIKAN UTAMA: loadScenariosFromSession() =====
    function loadScenariosFromSession() {
        try {
            const data1 = sessionStorage.getItem('scenario1_data');
            const data2 = sessionStorage.getItem('scenario2_data');
            const data3 = sessionStorage.getItem('scenario3_data');
            const savedPlant = sessionStorage.getItem('scenario_plant');

            console.log('Loading scenarios from session:', {
                data1: data1 ? 'exists' : 'null',
                data2: data2 ? 'exists' : 'null',
                data3: data3 ? 'exists' : 'null',
                savedPlant: savedPlant
            });

            // Reset semua scenario data terlebih dahulu
            scenarioData.single = [];
            scenarioData['single-multi'] = [];
            scenarioData.multiple = [];

            // Hanya load scenario data jika plant yang tersimpan sama dengan plant yang aktif
            if (savedPlant === selectedPlant) {
                // Load data untuk skenario 1 (bisa berupa object tunggal atau array)
                if (data1) {
                    try {
                        const parsed = JSON.parse(data1);
                        // PERBAIKAN: Skenario 1 bisa berupa object tunggal atau array
                        if (Array.isArray(parsed)) {
                            scenarioData.single = parsed;
                        } else if (parsed && typeof parsed === 'object') {
                            scenarioData.single = [parsed]; // Jadikan array dengan 1 item
                        }
                        console.log('Loaded scenario1_data:', scenarioData.single);
                    } catch (e) {
                        console.error('Error parsing scenario1_data:', e);
                        scenarioData.single = [];
                    }
                }

                // Load data untuk skenario 2 (harus berupa array)
                if (data2) {
                    try {
                        const parsed = JSON.parse(data2);
                        if (Array.isArray(parsed)) {
                            scenarioData['single-multi'] = parsed;
                        } else {
                            console.warn('scenario2_data bukan array:', parsed);
                            scenarioData['single-multi'] = [];
                        }
                        console.log('Loaded scenario2_data:', scenarioData['single-multi']);
                    } catch (e) {
                        console.error('Error parsing scenario2_data:', e);
                        scenarioData['single-multi'] = [];
                    }
                }

                // Load data untuk skenario 3 (harus berupa array)
                if (data3) {
                    try {
                        const parsed = JSON.parse(data3);
                        if (Array.isArray(parsed)) {
                            scenarioData.multiple = parsed;
                        } else {
                            console.warn('scenario3_data bukan array:', parsed);
                            scenarioData.multiple = [];
                        }
                        console.log('Loaded scenario3_data:', scenarioData.multiple);
                    } catch (e) {
                        console.error('Error parsing scenario3_data:', e);
                        scenarioData.multiple = [];
                    }
                }

                console.log('Scenario data dimuat dari session.');

                // Update display setelah semua data diload
                updateScenarioDisplay('single');
                updateScenarioDisplay('single-multi');
                updateScenarioDisplay('multiple');
            } else {
                // Jika plant berbeda, clear semua scenario data
                console.log('Scenario data dihapus karena plant berbeda (saved:', savedPlant, 'vs selected:', selectedPlant, ')');

                // Hapus dari sessionStorage
                sessionStorage.removeItem('scenario1_data');
                sessionStorage.removeItem('scenario2_data');
                sessionStorage.removeItem('scenario3_data');
            }

            updateMaterialStatus();
        } catch (err) {
            console.error('Gagal memuat dari sessionStorage:', err);
            // Reset semua data scenario
            scenarioData.single = [];
            scenarioData['single-multi'] = [];
            scenarioData.multiple = [];
            sessionStorage.removeItem('scenario1_data');
            sessionStorage.removeItem('scenario2_data');
            sessionStorage.removeItem('scenario3_data');
            sessionStorage.removeItem('scenario_plant');
        }
    }

    // ===== FUNGSI UTILITAS UI =====
    function showLoading(show) {
        if (show) {
            $('.loading-spinner').show();
            $('.loading-overlay').show();
            $('#refreshStock').prop('disabled', true);
        } else {
            $('.loading-spinner').hide();
            $('.loading-overlay').hide();
            $('#refreshStock').prop('disabled', false);
        }
    }

    function showMessage(message, type) {
        $('.container-fluid .alert').remove();

        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="fas ${icon} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('.container-fluid').prepend(alertHtml);
        setTimeout(() => {
            $('.alert').alert('close');
        }, 5000);
    }

    function showError(message) {
        showMessage(message, 'error');
    }

    // ===== VALIDASI TOMBOL LANJUT =====
    function setupScenarioValidation() {
        // Validasi untuk skenario 2
        $('#goToScenario2').on('click', function(e) {
            const items = scenarioData['single-multi'];

            // PERBAIKAN: Pastikan items adalah array dan memiliki panjang > 0
            if (!Array.isArray(items) || items.length === 0) {
                e.preventDefault();
                showMessage('Silakan pilih material terlebih dahulu dengan menyeret ke area Skenario 2', 'warning');
                return;
            }

            const validation = validateScenarioPlant('single-multi');
            if (!validation.valid) {
                e.preventDefault();
                showMessage(validation.message, 'error');
                return;
            }

            try {
                sessionStorage.setItem('scenario2_data', JSON.stringify(items));
                sessionStorage.setItem('scenario_plant', selectedPlant);
                console.log('Data saved for scenario 2');
            } catch (error) {
                console.error('Error saving to sessionStorage:', error);
                showMessage('Error menyimpan data material', 'error');
                e.preventDefault();
            }
        });

        // Validasi untuk skenario 3
        $('#goToScenario3').on('click', function(e) {
            const items = scenarioData.multiple;

            // PERBAIKAN: Pastikan items adalah array dan memiliki panjang > 0
            if (!Array.isArray(items) || items.length === 0) {
                e.preventDefault();
                showMessage('Silakan pilih material terlebih dahulu dengan menyeret ke area Skenario 3', 'warning');
                return;
            }

            const validation = validateScenarioPlant('multiple');
            if (!validation.valid) {
                e.preventDefault();
                showMessage(validation.message, 'error');
                return;
            }

            try {
                sessionStorage.setItem('scenario3_data', JSON.stringify(items));
                sessionStorage.setItem('scenario_plant', selectedPlant);
                console.log('Data saved for scenario 3');
            } catch (error) {
                console.error('Error saving to sessionStorage:', error);
                showMessage('Error menyimpan data material', 'error');
                e.preventDefault();
            }
        });
    }

    // ===== INITIALIZATION =====
    $(document).ready(function() {
        console.log('Document ready, selectedPlant:', selectedPlant, 'selectedStorageLocation:', selectedStorageLocation);

        // Setup UI berdasarkan state
        if (selectedPlant) {
            $('#selectedPlant').text(`Plant: ${selectedPlant}`);
            $('#selectedStorageLocation').text(
                selectedStorageLocation ? `Lokasi: ${selectedStorageLocation}` : 'Semua Lokasi'
            );
            updatePlantVisualIndicator();
            updateStorageLocations(selectedPlant);
            toggleUIForPlantSelection(true);

            // Load data
            setTimeout(() => {
                loadStockData();
            }, 100);
        } else {
            toggleUIForPlantSelection(false);
        }

        // Load scenario data
        loadScenariosFromSession();
        setupRowDragEvents();

        // Setup validasi tombol lanjut
        setupScenarioValidation();

        // Event handlers
        $('#refreshStock').off('click').on('click', function() {
            if (!isSyncing) {
                syncStockData();
            }
        });

        $('#searchBtn').off('click').on('click', function() {
            const searchTerm = $('#materialSearch').val().trim();
            if (searchTerm) {
                loadStockData();
            } else {
                loadStockData();
            }
        });

        $('#clearSearchBtn').off('click').on('click', function() {
            clearSearch();
            loadStockData();
        });

        $('#selectPlantBtn').off('click').on('click', function() {
            $('#plantDropdown').dropdown('toggle');
        });

        setupLiveSearch();

        // Plant selection handler
        $(document).on('click', '#plantList .dropdown-item', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const newPlant = $(this).data('plant');
            console.log('Plant dipilih:', newPlant, 'sebelumnya:', selectedPlant);

            if (newPlant === selectedPlant) {
                console.log('Plant sama, tidak perlu load data');
                return;
            }

            // Update state
            selectedPlant = newPlant;
            selectedStorageLocation = '';

            // Update UI
            $('#selectedPlant').text(`Plant: ${selectedPlant}`);
            $('#selectedStorageLocation').text('Semua Lokasi');

            updatePlantVisualIndicator();
            updateStorageLocations(selectedPlant);
            toggleUIForPlantSelection(true);

            // Clear scenario data karena plant berubah
            scenarioData.single = [];
            scenarioData['single-multi'] = [];
            scenarioData.multiple = [];

            // Update display
            updateScenarioDisplay('single');
            updateScenarioDisplay('single-multi');
            updateScenarioDisplay('multiple');

            // Hapus sessionStorage untuk scenario data
            sessionStorage.removeItem('scenario1_data');
            sessionStorage.removeItem('scenario2_data');
            sessionStorage.removeItem('scenario3_data');
            sessionStorage.removeItem('scenario_plant');

            // Simpan state plant dan location
            saveSelectedState();

            // Load data
            console.log('Memuat data untuk plant baru:', selectedPlant);
            loadStockData(true);

            // Tutup dropdown
            $('#plantDropdown').dropdown('toggle');
        });

        // Location selection handler
        $(document).on('click', '#storageLocationList .dropdown-item', function(e) {
            e.preventDefault();
            e.stopPropagation();

            $('.storage-option').removeClass('active');
            $(this).addClass('active');

            const newLocation = $(this).data('location');
            console.log('Location dipilih:', newLocation, 'sebelumnya:', selectedStorageLocation);

            if (newLocation === selectedStorageLocation) {
                console.log('Location sama, tidak perlu load data');
                return;
            }

            selectedStorageLocation = newLocation;
            $('#selectedStorageLocation').text(
                selectedStorageLocation ? `Lokasi: ${selectedStorageLocation}` : 'Semua Lokasi'
            );

            saveSelectedState();

            console.log('Memuat data untuk location baru:', selectedStorageLocation);
            loadStockData(true);

            $('#storageLocationDropdown').dropdown('toggle');
        });

        setupDragAndDrop();
        updateMaterialStatus();

        // Selection handlers
        $('#selectAllHeader').change(function() {
            const isChecked = $(this).prop('checked');
            $('.row-select').prop('checked', isChecked);

            if (isChecked) {
                $('.draggable-row').each(function() {
                    selectedRows.add(parseInt($(this).data('index')));
                });
            } else {
                selectedRows.clear();
            }
            updateSelectionCount();
        });

        $('tbody').on('change', '.row-select', function() {
            const index = parseInt($(this).data('index'));
            if ($(this).prop('checked')) {
                selectedRows.add(index);
            } else {
                selectedRows.delete(index);
                $('#selectAllHeader').prop('checked', false);
            }
            updateSelectionCount();
        });

        $('#clearSelection').click(function() {
            if (selectedRows.size > 0) {
                clearSelection();
                showMessage('Pilihan berhasil dihapus', 'success');
            }
        });
    });
    </script>
</body>
</html>
