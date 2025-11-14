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
            top: -6px;
            right: -6px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pagination-container.d-none {
            display: none !important;
        }
        /* Style untuk card yang lebih ringkas */
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
        /* Sticky positioning untuk kolom kanan */
        .scenario-sidebar {
            position: sticky;
            top: 50px;
            height: fit-content;
            max-height: 95vh;
            overflow-y: auto;
        }
        /* Tanda untuk material yang sudah dipilih - BERBEDA UNTUK SETIAP SKENARIO */
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
        /* Style untuk item material di card skenario (lebih rapi) */
        .material-item-compact {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 6px;
            background: #f8f9fa;
            border-radius: 3px;
            margin-bottom: 3px;
            font-size: 0.7rem;
        }
        .material-code-compact {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #333;
        }
        .material-qty-compact {
            font-weight: bold;
            color: #10b981;
        }
        /* Style untuk checkbox yang lebih jelas */
        .selection-controls {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .select-all-checkbox {
            margin-right: 10px;
        }
        .selected-count {
            font-size: 0.8rem;
            color: #6c757d;
            margin-left: 10px;
        }
        .drag-selected-btn {
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
        }
        /* Style untuk checkbox yang lebih jelas */
        .table-checkbox {
            width: 18px;
            height: 18px;
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
        /* Style untuk baris yang dipilih */
        .row-selected {
            background-color: #f0f7ff !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient-to-r from-blue-600 to-purple-600 shadow-lg">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('hu.index') }}">
                <i class="fas fa-cubes me-2"></i>SAP HU Automation
            </a>
            <div class="d-flex">
                <a href="{{ route('hu.history') }}" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-history me-1"></i> History HU
                </a>
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
        <!-- Layout utama dengan kolom kiri lebih lebar dan kanan lebih sempit -->
        <div class="row">
            <!-- Kolom kiri untuk data stock (lebih lebar) -->
            <div class="col-lg-10 mb-4">
                <!-- Kontrol seleksi -->
                <div class="selection-controls mb-3">
                    <div class="d-flex align-items-center">
                        <div class="form-check select-all-checkbox">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label small fw-bold text-dark" for="selectAll">
                                Pilih Semua
                            </label>
                        </div>
                        <button class="btn btn-outline-primary btn-sm drag-selected-btn me-2" id="dragSelected">
                            <i class="fas fa-arrows-alt me-1"></i> Drag Item Terpilih
                        </button>
                        <button class="btn btn-outline-secondary btn-sm drag-selected-btn" id="clearSelection">
                            <i class="fas fa-times me-1"></i> Hapus Pilihan
                        </button>
                        <span class="selected-count fw-medium" id="selectedCount">0 item terpilih</span>
                    </div>
                </div>

                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0 fw-bold text-gray-800">
                                    <i class="fas fa-warehouse me-2 text-blue-500"></i>
                                    Data Stock Tersedia
                                </h5>
                                <small class="text-muted">Hanya menampilkan material yang belum dibuat HU</small>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="plantDropdown" data-bs-toggle="dropdown">
                                        <i class="fas fa-industry me-1"></i>
                                        <span id="selectedPlant">Plant: 3000</span>
                                    </button>
                                    <ul class="dropdown-menu" id="plantList">
                                        @foreach($plantsData as $plant => $locations)
                                            <li><a class="dropdown-item" href="#" data-plant="{{ $plant }}">{{ $plant }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="storageLocationDropdown" data-bs-toggle="dropdown">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <span id="selectedStorageLocation">Lokasi: 3D10</span>
                                    </button>
                                    <ul class="dropdown-menu" id="storageLocationList">
                                        <li><a class="dropdown-item" href="#" data-location="">Semua Lokasi</a></li>
                                    </ul>
                                </div>
                                <div class="input-group input-group-sm" style="width: 300px;">
                                    <input type="text" id="materialSearch" class="form-control" placeholder="Cari material, deskripsi, atau sales order...">
                                    <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <button id="refreshStock" class="btn btn-outline-primary btn-sm refresh-btn">
                                    <i class="fas fa-sync-alt me-1"></i> Sync Sekarang
                                </button>
                                <div class="loading-spinner spinner-border spinner-border-sm text-primary me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
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
                                    @if($stockData['success'] && count($stockData['data']) > 0)
                                        @foreach($stockData['data'] as $index => $item)
                                            @if(!$item->hu_created)
                                                <tr class="hover:bg-gray-50 draggable-row" draggable="true" data-index="{{ $index }}" data-material="{{ $item->material }}" data-batch="{{ $item->batch }}" data-plant="{{ $item->plant }}" data-storage-location="{{ $item->storage_location }}">
                                                    <td class="border-0">
                                                        <input type="checkbox" class="table-checkbox row-select" data-index="{{ $index }}">
                                                    </td>
                                                    <td class="border-0">
                                                        <span class="material-status" id="status-{{ $item->material }}-{{ $item->batch }}"></span>
                                                    </td>
                                                    <td class="border-0">
                                                        <span class="material-number">
                                                            {{ preg_match('/^\d+$/', $item->material) ? ltrim($item->material, '0') : $item->material }}
                                                        </span>
                                                    </td>
                                                    <td class="border-0 text-gray-600">{{ $item->material_description ?? '-' }}</td>
                                                    <td class="border-0 text-gray-600">{{ $item->batch ?? '-' }}</td>
                                                    <td class="border-0 text-end">
                                                        <span class="badge bg-success bg-opacity-10 text-success fs-6">
                                                            {{ number_format((float)($item->stock_quantity ?? 0), 0, ',', '.') }}
                                                        </span>
                                                    </td>
                                                    <td class="border-0 text-gray-600">{{ $item->base_unit == 'ST' ? 'PC' : ($item->base_unit ?? '-') }}</td>
                                                    <td class="border-0">
                                                        <span class="sales-document">{{ $item->sales_document && $item->item_number ? $item->sales_document . $item->item_number : '-' }}</span>
                                                    </td>
                                                    <td class="border-0 text-gray-600">
                                                        @if($item->vendor_name)
                                                            {{ implode(' ', array_slice(explode(' ', $item->vendor_name), 0, 2)) }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="border-0 text-gray-600">
                                                        {{ $item->last_updated ? \Carbon\Carbon::parse($item->last_updated)->format('d/m/Y H:i:s') : '-' }}
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="10" class="text-center py-4 text-muted">
                                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                                Tidak ada data stock untuk filter yang dipilih
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white pagination-container {{ ($stockData['success'] && $stockData['pagination']['total'] > 0) ? '' : 'd-none' }}">
                         <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted" id="paginationInfo">
                                @if($stockData['success'] && $stockData['pagination']['total'] > 0)
                                    Showing {{ $stockData['pagination']['total'] }} items
                                @else
                                    Showing 0 items
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom kanan untuk skenario (lebih sempit) - Tanpa judul dan deskripsi -->
            <div class="col-lg-2 mb-4">
                <div class="scenario-sidebar">
                    <!-- Hanya card skenario tanpa judul dan deskripsi -->
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
    let selectedPlant = '3000';
    let selectedStorageLocation = '3D10';
    let allStockData = @json($stockData['success'] ? $stockData['data'] : []);
    const plantsData = @json($plantsData ?? []);
    const scenarioData = {
        'single': [],
        'single-multi': [],
        'multiple': []
    };
    let selectedRows = new Set(); // Untuk menyimpan indeks baris yang dipilih
    let searchTimeout; // Untuk live search debounce

    // ===== SEMUA FUNGSI UTILITAS =====
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
        const words = vendorName.split(' ').slice(0, 2);
        return words.join(' ');
    }

    // Fungsi untuk menandai material yang sudah dipilih PER SKENARIO
    function updateMaterialStatus() {
        // Reset semua status
        $('.draggable-row').removeClass('selected-material-s1 selected-material-s2 selected-material-s3');
        $('.material-status').empty();

        // Tandai material berdasarkan skenario
        Object.keys(scenarioData).forEach(scenario => {
            const scenarioClass = getScenarioClass(scenario);
            scenarioData[scenario].forEach(item => {
                $(`.draggable-row[data-material="${item.material}"][data-batch="${item.batch}"][data-plant="${item.plant}"][data-storage-location="${item.storage_location}"]`)
                    .addClass(scenarioClass)
                    .find('.material-status')
                    .append(`<span class="status-indicator ${getStatusClass(scenario)}" title="Material dipilih di ${getScenarioName(scenario)}"></span>`);
            });
        });
    }

    // Helper functions untuk status per skenario
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

    // Fungsi untuk mengelola seleksi baris
    function updateSelectionCount() {
        const count = selectedRows.size;
        $('#selectedCount').text(`${count} item terpilih`);

        // Update tombol drag selected
        if (count > 0) {
            $('#dragSelected').prop('disabled', false);
            // Tambahkan class untuk baris yang dipilih
            $('.row-select:checked').closest('tr').addClass('row-selected');
        } else {
            $('#dragSelected').prop('disabled', true);
            // Hapus class untuk baris yang dipilih
            $('.draggable-row').removeClass('row-selected');
        }
    }

    // Fungsi untuk menambahkan item terpilih ke skenario
    function addSelectedItemsToScenario(scenario) {
        if (selectedRows.size === 0) {
            showMessage('Tidak ada item yang dipilih', 'warning');
            return;
        }

        let addedCount = 0;
        selectedRows.forEach(index => {
            if (allStockData[index]) {
                const item = allStockData[index];
                const existingIndex = scenarioData[scenario].findIndex(i =>
                    i.material === item.material &&
                    i.batch === item.batch &&
                    i.plant === item.plant &&
                    i.storage_location === item.storage_location
                );

                if (existingIndex === -1) {
                    // Cek batasan untuk Skenario 1
                    if (scenario === 'single' && scenarioData[scenario].length > 0) {
                        showMessage('Skenario 1 hanya boleh berisi 1 material', 'warning');
                        return;
                    }

                    scenarioData[scenario].push(item);
                    addedCount++;
                }
            }
        });

        if (addedCount > 0) {
            updateScenarioDisplay(scenario);
            saveScenarioDataToSession(scenario);
            updateMaterialStatus();
            showMessage(`${addedCount} material ditambahkan ke ${getScenarioName(scenario)}`, 'success');

            // Reset seleksi
            clearSelection();
        } else {
            showMessage('Tidak ada material baru yang ditambahkan', 'warning');
        }
    }

    // Fungsi untuk menghapus seleksi
    function clearSelection() {
        selectedRows.clear();
        $('.row-select').prop('checked', false);
        $('#selectAll').prop('checked', false);
        $('#selectAllHeader').prop('checked', false);
        updateSelectionCount();
    }

    // Fungsi untuk live search dengan debounce
    function setupLiveSearch() {
        $('#materialSearch').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                loadStockData();
            }, 500); // Delay 500ms setelah user berhenti mengetik
        });

        // Enter key juga tetap berfungsi
        $('#materialSearch').on('keypress', function(e) {
            if (e.which === 13) {
                clearTimeout(searchTimeout);
                loadStockData();
            }
        });
    }

    $(document).ready(function() {
        $('#selectedPlant').text(`Plant: ${selectedPlant}`);
        $('#selectedStorageLocation').text(`Lokasi: ${selectedStorageLocation}`);
        updateStorageLocations(selectedPlant);
        loadScenariosFromSession();
        setupRowDragEvents();

        // Setup event handlers untuk search
        $('#refreshStock').click(function() { syncStockData(); });
        $('#searchBtn').click(function() { loadStockData(); });
        setupLiveSearch(); // Setup live search

        $(document).on('click', '#plantList .dropdown-item', function(e) {
            e.preventDefault();
            selectedPlant = $(this).data('plant');
            $('#selectedPlant').text(`Plant: ${selectedPlant}`);
            updateStorageLocations(selectedPlant);
            selectedStorageLocation = '';
            $('#selectedStorageLocation').text('Pilih Lokasi');
            loadStockData();
        });

        $(document).on('click', '#storageLocationList .dropdown-item', function(e) {
            e.preventDefault();
            selectedStorageLocation = $(this).data('location');
            $('#selectedStorageLocation').text(selectedStorageLocation ? `Lokasi: ${selectedStorageLocation}` : 'Semua Lokasi');
            loadStockData();
        });

        setupDragAndDrop();

        // Update status material saat pertama kali load
        updateMaterialStatus();

        // Setup event handlers untuk seleksi
        $('#selectAll, #selectAllHeader').change(function() {
            const isChecked = $(this).prop('checked');
            $('.row-select').prop('checked', isChecked);

            if (isChecked) {
                // Tambahkan semua indeks ke selectedRows
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
                $('#selectAll').prop('checked', false);
                $('#selectAllHeader').prop('checked', false);
            }
            updateSelectionCount();
        });

        $('#clearSelection').click(function() {
            clearSelection();
        });

        $('#dragSelected').click(function() {
            if (selectedRows.size === 0) {
                showMessage('Pilih minimal 1 item untuk drag & drop', 'warning');
                return;
            }

            // Buat elemen draggable untuk multiple items
            const dragIndicator = $('<div>')
                .addClass('alert alert-info')
                .css({
                    position: 'fixed',
                    top: '50%',
                    left: '50%',
                    transform: 'translate(-50%, -50%)',
                    zIndex: 10000,
                    padding: '10px 20px',
                    borderRadius: '5px',
                    boxShadow: '0 4px 12px rgba(0,0,0,0.15)'
                })
                .html(`<i class="fas fa-arrows-alt me-2"></i>Drag ${selectedRows.size} item ke skenario yang diinginkan`)
                .appendTo('body');

            // Setup event untuk menghapus indicator saat selesai drag
            $(document).one('mouseup', function() {
                setTimeout(() => {
                    dragIndicator.remove();
                }, 500);
            });

            showMessage(`Siap untuk drag ${selectedRows.size} item. Seret ke skenario yang diinginkan`, 'info');
        });

        // Modifikasi drop event untuk menerima multiple items
        $('.drop-zone').each(function() {
            const dropZone = $(this)[0];
            const $dropZone = $(this);

            dropZone.addEventListener('dragover', e => {
                e.preventDefault();
                $dropZone.addClass('drag-over');
            });

            dropZone.addEventListener('dragleave', e => {
                $dropZone.removeClass('drag-over');
            });

            dropZone.addEventListener('drop', e => {
                e.preventDefault();
                $dropZone.removeClass('drag-over');
                const scenario = $dropZone.data('scenario');

                // Jika ada item yang dipilih, gunakan selectedRows
                if (selectedRows.size > 0) {
                    addSelectedItemsToScenario(scenario);
                } else {
                    // Fallback ke drag single item
                    const itemIndex = e.dataTransfer.getData('text/plain');
                    if (itemIndex !== '' && allStockData[itemIndex]) {
                        addItemToScenario(scenario, allStockData[itemIndex]);
                    }
                }
            });
        });
    });

    function updateStorageLocations(plant) {
        const locationList = $('#storageLocationList');
        locationList.empty();
        locationList.append('<li><a class="dropdown-item" href="#" data-location="">Semua Lokasi</a></li>');
        if (plant && plantsData[plant]) {
            plantsData[plant].forEach(location => {
                locationList.append(`<li><a class="dropdown-item" href="#" data-location="${location}">${location}</a></li>`);
            });
        }
    }

    function loadStockData() {
        showLoading(true);
        const searchTerm = $('#materialSearch').val();

        $.ajax({
            url: "{{ route('hu.get-stock') }}",
            type: 'GET',
            data: {
                search: searchTerm, // Parameter baru untuk search general
                material: searchTerm, // Tetap pertahankan untuk backward compatibility
                plant: selectedPlant,
                storage_location: selectedStorageLocation
            },
            success: function(response) {
                if (response.success) {
                    allStockData = response.data || [];
                    populateStockTable(allStockData);
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
            }
        });
    }

    function syncStockData() {
        if (!selectedPlant) {
            showError('Pilih plant sebelum sync');
            return;
        }
        showLoading(true);
        $.ajax({
            url: "{{ route('hu.sync-stock') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                plant: selectedPlant,
                storage_location: selectedStorageLocation || '3D10'
            },
            success: function(response) {
                if (response.success) {
                    loadStockData();
                    showMessage(response.message, 'success');
                } else {
                    showError(response.error || 'Gagal sync data stock');
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Error sync data stock';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }
                showError(errorMessage);
            },
            complete: function() {
                showLoading(false);
            }
        });
    }

    function populateStockTable(data) {
        const tbody = $('#stockTableBody');
        tbody.empty();
        const paginationContainer = $('.pagination-container');
        const paginationInfo = $('#paginationInfo');

        // Filter data untuk hanya menampilkan yang belum dibuat HU
        const availableData = data.filter(item => !item.hu_created);

        if (availableData.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        Tidak ada data stock tersedia
                    </td>
                </tr>
            `);
            paginationInfo.text('Showing 0 items');
            paginationContainer.addClass('d-none');
            return;
        }
        paginationContainer.removeClass('d-none');
        paginationInfo.text(`Showing ${availableData.length} items`);
        availableData.forEach(function(item, index) {
            const formattedMaterial = formatMaterialNumber(item.material);
            const originalMaterial = item.material;
            const showTooltip = formattedMaterial !== originalMaterial;
            const convertedUnit = convertUnit(item.base_unit);
            const combinedSalesDoc = combineSalesDocument(item.sales_document, item.item_number);
            const customerName = getCustomerName(item.vendor_name);
            const row = `
                <tr class="hover:bg-gray-50 draggable-row" draggable="true" data-index="${index}" data-material="${item.material}" data-batch="${item.batch}" data-plant="${item.plant}" data-storage-location="${item.storage_location}">
                    <td class="border-0">
                        <input type="checkbox" class="table-checkbox row-select" data-index="${index}">
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
                    <td class="border-0 text-gray-600">${customerName}</td>
                    <td class="border-0 text-gray-600">
                        ${item.last_updated ? new Date(item.last_updated).toLocaleString() : '-'}
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
        setupRowDragEvents();
        updateMaterialStatus(); // Update status setelah tabel di-render ulang
        clearSelection(); // Reset seleksi setelah data dimuat ulang
    }

    // ===== FUNGSI DRAG & DROP DAN SESSION =====

    function setupDragAndDrop() {
        // Event handlers sudah ditambahkan di $(document).ready()
    }

    function setupRowDragEvents() {
        $('.draggable-row').each(function() {
            const row = $(this)[0];
            row.addEventListener('dragstart', function(e) {
                // Jika ada item yang dipilih, set data untuk multiple items
                if (selectedRows.size > 0) {
                    e.dataTransfer.setData('text/plain', 'multiple');
                } else {
                    // Fallback ke single item
                    e.dataTransfer.setData('text/plain', $(this).data('index'));
                }
                e.dataTransfer.effectAllowed = 'copy';
            });
        });
    }

    function addItemToScenario(scenario, item) {
        item.combined_sales_doc = combineSalesDocument(item.sales_document, item.item_number);
        const existingIndex = scenarioData[scenario].findIndex(i => i.material === item.material && i.batch === item.batch && i.plant === item.plant && i.storage_location === item.storage_location);
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
        updateMaterialStatus(); // Update status material setelah menambah
        showMessage(`Material ${formatMaterialNumber(item.material)} ditambahkan ke ${getScenarioName(scenario)}`, 'success');
    }

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

        if (scenarioData[scenario].length > 0) {
            dropZone.addClass('has-items');

            scenarioData[scenario].forEach((item, index) => {
                const formattedMaterial = formatMaterialNumber(item.material);
                // Tampilan yang lebih rapi - hanya kode material dan qty
                const itemElement = `
                    <div class="material-item-compact position-relative">
                        <button type="button" class="btn-close btn-close-sm position-absolute" style="top: 2px; right: 2px;" onclick="removeItemFromScenario('${scenario}', ${index})"></button>
                        <div class="material-code-compact">${formattedMaterial}</div>
                        <div class="material-qty-compact">${parseFloat(item.stock_quantity || 0).toLocaleString('id-ID')}</div>
                    </div>
                `;
                container.append(itemElement);
            });

            let badge = dropZone.find('.scenario-badge');
            if (badge.length === 0) {
                dropZone.append(`<div class="scenario-badge">${scenarioData[scenario].length}</div>`);
            } else {
                badge.text(scenarioData[scenario].length);
            }
        } else {
            dropZone.removeClass('has-items');
            dropZone.find('.scenario-badge').remove();
        }
    }

    function removeItemFromScenario(scenario, index) {
        scenarioData[scenario].splice(index, 1);
        updateScenarioDisplay(scenario);
        saveScenarioDataToSession(scenario);
        updateMaterialStatus(); // Update status material setelah menghapus
    }

    function clearScenario(scenario) {
        scenarioData[scenario] = [];
        updateScenarioDisplay(scenario);
        saveScenarioDataToSession(scenario);
        updateMaterialStatus(); // Update status material setelah menghapus semua
        showMessage('Semua material dihapus dari skenario', 'success');
    }

    function saveScenarioDataToSession(scenario) {
        let key = '';
        if (scenario === 'single') key = 'scenario1_data';
        else if (scenario === 'single-multi') key = 'scenario2_data';
        else if (scenario === 'multiple') key = 'scenario3_data';
        else return;
        try {
            sessionStorage.setItem(key, JSON.stringify(scenarioData[scenario]));
            console.log(`Data untuk ${key} berhasil disimpan ke session.`);
        } catch (err) {
            console.error('Gagal menyimpan ke sessionStorage:', err);
            showError('Gagal menyimpan data. Penyimpanan browser mungkin penuh.');
        }
    }

    function loadScenariosFromSession() {
        try {
            const data1 = sessionStorage.getItem('scenario1_data');
            const data2 = sessionStorage.getItem('scenario2_data');
            const data3 = sessionStorage.getItem('scenario3_data');
            if (data1) {
                scenarioData.single = JSON.parse(data1);
                updateScenarioDisplay('single');
            }
            if (data2) {
                scenarioData['single-multi'] = JSON.parse(data2);
                updateScenarioDisplay('single-multi');
            }
            if (data3) {
                scenarioData.multiple = JSON.parse(data3);
                updateScenarioDisplay('multiple');
            }
            console.log('Scenario data dimuat dari session.');
            updateMaterialStatus(); // Update status material setelah load dari session
        } catch (err) {
            console.error('Gagal memuat dari sessionStorage:', err);
            sessionStorage.removeItem('scenario1_data');
            sessionStorage.removeItem('scenario2_data');
            sessionStorage.removeItem('scenario3_data');
        }
    }

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
        const alertClass = type === 'success' ? 'alert-success' :
                          type === 'warning' ? 'alert-warning' : 'alert-danger';
        const icon = type === 'success' ? 'fa-check-circle' :
                    type === 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-triangle';
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas ${icon} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('.container-fluid .alert').remove();
        $('.container-fluid').prepend(alertHtml);
        setTimeout(() => {
            $('.alert').alert('close');
        }, 5000);
    }

    function showError(message) {
        showMessage(message, 'error');
    }
</script>
</body>
</html>
