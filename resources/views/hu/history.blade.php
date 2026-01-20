<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History HU - SAP HU Automation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Tambahkan library QRCode -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        .table-responsive {
            max-height: 70vh;
            position: relative;
        }
        .history-table {
            font-size: 0.875rem;
        }
        .search-box {
            transition: all 0.3s ease;
        }
        .search-box:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }
        .select-all-checkbox {
            margin-right: 8px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }
        .material-description {
            min-width: 250px;
            max-width: 400px;
            white-space: normal;
            word-wrap: break-word;
            line-height: 1.4;
        }
        .filter-container {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }
        .date-filter-group {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }
        .date-filter {
            min-width: 140px;
        }
        .date-filter-label {
            font-size: 0.75rem;
            margin-bottom: 2px;
            color: #6c757d;
            font-weight: 500;
        }
        .reset-btn {
            height: 31px;
            margin-bottom: 1px;
        }

        /* Sembunyikan tampilan print secara default */
        #printView {
            display: none;
        }

        /* STICKY HEADER */
        .table-responsive thead tr:nth-child(1) th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f8f9fa !important;
            border-bottom: 2px solid #dee2e6 !important;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
        }
        
        /* STYLE UNTUK PRINT */
        @media print {
            body * {
                visibility: hidden;
            }
            #printView, #printView * {
                visibility: visible;
            }
            #printView {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                background: white;
                display: block !important;
            }
            .no-print {
                display: none !important;
            }
            .print-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 12px;
            }
            .print-table th {
                background-color: #f8f9fa !important;
                border: 1px solid #000;
                padding: 8px;
                text-align: left;
                font-weight: bold;
            }
            .print-table td {
                border: 1px solid #000;
                padding: 8px;
                vertical-align: top;
            }
            .qrcode-cell {
                text-align: center;
                width: 80px;
            }
            .qrcode-container {
                display: inline-block;
                text-align: center;
            }
            .qrcode-text {
                font-size: 10px;
                margin-top: 2px;
                font-weight: bold;
            }

            /* STYLE KHUSUS UNTUK PRINT DENGAN QR CODE */
            .print-header {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
            }
            .print-title {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .print-subtitle {
                font-size: 14px;
            }
            .print-footer {
                margin-top: 20px;
                text-align: right;
                font-size: 11px;
                color: #666;
            }
            .material-info {
                font-weight: bold;
            }
            .quantity-cell {
                text-align: right;
            }
        }

        @media (max-width: 768px) {
            .filter-container {
                flex-direction: column;
                gap: 12px;
            }
            .date-filter-group {
                width: 100%;
            }
            .date-filter {
                flex: 1;
            }
            .material-description {
                min-width: 200px;
                max-width: 300px;
            }
        }

        /* Pagination styling */
        .pagination {
            margin-bottom: 0;
        }
        .page-link {
            color: #3b82f6;
            border: 1px solid #dee2e6;
        }
        .page-item.active .page-link {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }
        .page-item.disabled .page-link {
            color: #6c757d;
        }

        /* Loading indicator */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            display: none;
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- NAVBAR DENGAN TOMBOL KEMBALI -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient-to-r from-blue-600 to-purple-600 shadow-lg no-print">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('hu.index') }}">
                <i class="fas fa-cubes me-2"></i>SAP HU Automation
            </a>
            <div class="d-flex">
                <!-- ✅ TOMBOL KEMBALI -->
                <a href="{{ route('hu.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4 no-print" role="alert" id="successAlert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4 no-print" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- TAMPILAN NORMAL -->
        <div class="card border-0 shadow-sm no-print" id="mainCard">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0 fw-bold text-gray-800">
                        <i class="fas fa-history me-2 text-blue-500"></i>
                        History Handling Units (HU)
                    </h5>
                    <div>
                        <button id="printBtn" class="btn btn-info btn-sm me-2">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                        <button id="exportBtn" class="btn btn-success btn-sm" disabled>
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </button>
                        <button id="refreshBtn" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-sync-alt me-1"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-container mb-3">
                    <div class="w-100">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="date-filter-label">Pencarian</label>
                                <input type="text" name="search" id="searchInput" class="form-control form-control-sm search-box"
                                       placeholder="Cari HU Number, material, deskripsi, sales order..."
                                       value="{{ request('search', '') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="date-filter-label">Tanggal Mulai</label>
                                <input type="date" name="start_date" id="startDate" class="form-control form-control-sm date-filter"
                                       value="{{ request('start_date', '') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="date-filter-label">Tanggal Akhir</label>
                                <input type="date" name="end_date" id="endDate" class="form-control form-control-sm date-filter"
                                       value="{{ request('end_date', '') }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button id="filterBtn" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-search me-1"></i> Filter
                                </button>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button id="resetFilterBtn" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="fas fa-refresh me-1"></i> Reset Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center mb-2">
                    <input type="checkbox" id="selectAll" class="form-check-input select-all-checkbox">
                    <label for="selectAll" class="form-check-label text-muted small ms-1">
                        Pilih Semua
                    </label>
                    <span id="selectedCount" class="badge bg-primary ms-2">0 terpilih</span>
                </div>
                <small class="text-muted d-block" id="summaryText">
                    Menampilkan {{ $historyData->count() }} dari {{ $historyData->total() }} data (50 data per halaman)
                </small>
            </div>
            <div class="card-body p-0 position-relative">
                <!-- Loading Overlay -->
                <div class="loading-overlay" id="loadingOverlay">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <form id="exportForm" action="{{ route('hu.export') }}" method="POST">
                    @csrf
                    <input type="hidden" name="selected_data" id="selectedData">
                </form>

                <div class="table-responsive" id="tableContainer">
                    <table class="table table-hover history-table mb-0">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="border-0" width="40">
                                    <input type="checkbox" id="selectAllHeader" class="form-check-input">
                                </th>
                                <th class="border-0">HU Number</th>
                                <th class="border-0">Material</th>
                                <th class="border-0" width="300">Deskripsi Material</th>
                                <th class="border-0">Batch</th>
                                <th class="border-0 text-end">Qty</th>
                                <th class="border-0">Unit</th>
                                <th class="border-0">Dokumen Penjualan</th>
                                <th class="border-0">Lokasi</th>
                                <th class="border-0">Skenario</th>
                                <th class="border-0">Dibuat Oleh</th>
                                <th class="border-0">Tanggal Dibuat (WIB)</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            @include('hu.history_table_body', ['historyData' => $historyData])
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Section -->
                <div id="paginationContainer">
                    @include('hu.history_pagination', ['historyData' => $historyData])
                </div>
            </div>
        </div>

        <!-- TAMPILAN PRINT DENGAN QR CODE -->
        <div id="printView">
            <div class="print-header">
                <div class="print-title">HANDLING UNIT (HU) HISTORY REPORT</div>
                <div class="print-subtitle">SAP HU Automation System</div>
                <div class="print-subtitle" style="font-size: 12px; margin-top: 5px;">
                    @if(request('search'))
                        Filter Pencarian: {{ request('search') }}
                    @endif
                    @if(request('start_date') || request('end_date'))
                        | Tanggal: {{ request('start_date', '') }} s/d {{ request('end_date', '') }}
                    @endif
                    | Dicetak pada: {{ \Carbon\Carbon::now()->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB
                </div>
            </div>

            <table class="print-table">
                <thead>
                    <tr>
                        <th width="80">QR Code</th>
                        <th>HU Number</th>
                        <th>Material</th>
                        <th width="180">Deskripsi Material</th>
                        <th>Batch</th>
                        <th width="60">Qty</th>
                        <th width="60">Unit</th>
                        <th>Dokumen Penjualan</th>
                        <th>Lokasi</th>
                        <th width="80">Skenario</th>
                        <th width="100">Dibuat Oleh</th>
                        <th width="120">Tanggal Dibuat</th>
                    </tr>
                </thead>
                <tbody id="printTableBody">
                    @if($historyData->count() > 0)
                        @foreach($historyData as $item)
                            <tr>
                                <td class="qrcode-cell">
                                    <div class="qrcode-container">
                                        <div id="qrcode-{{ $item->hu_number }}" class="qrcode"></div>
                                        <div class="qrcode-text">{{ substr($item->hu_number, 0, 8) }}</div>
                                    </div>
                                </td>
                                <td class="material-info">{{ $item->hu_number }}</td>
                                <td>
                                    {{ preg_match('/^\d+$/', $item->material) ? ltrim($item->material, '0') : $item->material }}
                                </td>
                                <td>{{ $item->material_description ?: '-' }}</td>
                                <td>{{ $item->batch ?: '-' }}</td>
                                <td class="quantity-cell">{{ number_format((float)($item->quantity ?? 0), 0, ',', '.') }}</td>
                                <td>{{ $item->unit == 'ST' ? 'PC' : ($item->unit ?: '-') }}</td>
                                <td>{{ $item->sales_document ?: '-' }}</td>
                                <td>{{ $item->storage_location ?: '-' }}</td>
                                <td>
                                    @if($item->scenario_type == 'single')
                                        Skenario 1
                                    @elseif($item->scenario_type == 'single-multi')
                                        Skenario 2
                                    @elseif($item->scenario_type == 'multiple')
                                        Skenario 3
                                    @else
                                        {{ $item->scenario_type ?: '-' }}
                                    @endif
                                </td>
                                <td>
                                    {{ $item->created_by ?: 'System' }}
                                </td>
                                <td>
                                    @php
                                        try {
                                            $createdAt = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : '-';
                                        } catch (Exception $e) {
                                            $createdAt = '-';
                                        }
                                    @endphp
                                    {{ $createdAt }}
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="12" class="text-center py-4">Tidak ada data history HU</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <div class="print-footer">
                Halaman {{ $historyData->currentPage() }} dari {{ $historyData->lastPage() }} |
                Total Data: {{ $historyData->total() }} |
                Dicetak oleh: {{ Auth::check() ? Auth::user()->name : 'System' }}
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            let selectedHUs = [];
            let searchTimeout = null;
            let currentPage = {{ $historyData->currentPage() }};
            let isLiveSearchEnabled = true;

            // Set default dates (last 30 days) jika tidak ada filter
            @if(!request('start_date') && !request('end_date'))
                const today = new Date();
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(today.getDate() - 30);

                $('#startDate').val(thirtyDaysAgo.toISOString().split('T')[0]);
                $('#endDate').val(today.toISOString().split('T')[0]);
            @endif

            // Refresh button
            $('#refreshBtn').on('click', function() {
                window.location.reload();
            });

            // Print Functionality
            $('#printBtn').on('click', function() {
                console.log('Print button clicked');

                // Generate QR codes
                generateQRCodes();

                // Tunggu sebentar untuk memastikan QR code tergenerate
                setTimeout(function() {
                    console.log('Opening print dialog...');
                    window.print();
                }, 500);
            });

            // Handle after print event
            window.addEventListener('afterprint', function() {
                console.log('Print completed or cancelled');
            });

            // Select All Checkbox
            $('#selectAll, #selectAllHeader').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.row-checkbox').prop('checked', isChecked);
                updateSelectedData();
            });

            // Individual Row Checkbox
            $(document).on('change', '.row-checkbox', function() {
                updateSelectedData();
            });

            // Update Selected Data
            function updateSelectedData() {
                selectedHUs = [];
                $('.row-checkbox:checked').each(function() {
                    selectedHUs.push($(this).val());
                });

                updateSelectionUI();
            }

            // Update Selection UI
            function updateSelectionUI() {
                const visibleCount = $('.row-checkbox').length;
                const checkedCount = $('.row-checkbox:checked').length;

                $('#selectedCount').text(checkedCount + ' terpilih');

                if (checkedCount === 0) {
                    $('#selectAll').prop('checked', false).prop('indeterminate', false);
                    $('#selectAllHeader').prop('checked', false).prop('indeterminate', false);
                } else if (checkedCount === visibleCount && visibleCount > 0) {
                    $('#selectAll').prop('checked', true).prop('indeterminate', false);
                    $('#selectAllHeader').prop('checked', true).prop('indeterminate', false);
                } else {
                    $('#selectAll').prop('checked', false).prop('indeterminate', true);
                    $('#selectAllHeader').prop('checked', false).prop('indeterminate', true);
                }

                if (selectedHUs.length > 0) {
                    $('#exportBtn').prop('disabled', false);
                } else {
                    $('#exportBtn').prop('disabled', true);
                }
            }

            // Export Functionality
            $('#exportBtn').on('click', function() {
                if (selectedHUs.length === 0) {
                    alert('Pilih minimal satu data untuk di-export');
                    return;
                }

                $('#selectedData').val(JSON.stringify(selectedHUs));
                $('#exportForm').submit();
            });

            // Live Search Functionality
            function performSearch(page = 1) {
                const search = $('#searchInput').val();
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                
                showLoading();
                
                $.ajax({
                    url: '{{ route("hu.history") }}',
                    method: 'GET',
                    data: {
                        search: search,
                        start_date: startDate,
                        end_date: endDate,
                        page: page,
                        ajax: 1
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#historyTableBody').html(response.tableBody);
                            $('#paginationContainer').html(response.pagination);
                            $('#summaryText').text(response.summary);
                            updateSelectionUI();
                            
                            // Update current page
                            currentPage = response.currentPage;
                        } else {
                            alert('Error: ' + response.message);
                        }
                        hideLoading();
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        alert('Terjadi kesalahan saat memuat data.');
                        hideLoading();
                    }
                });
            }

            // Live Search with debounce
            $('#searchInput').on('keyup', function() {
                if (!isLiveSearchEnabled) return;
                
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    performSearch(1);
                }, 500);
            });

            // Date filter change
            $('#startDate, #endDate').on('change', function() {
                if (!isLiveSearchEnabled) return;
                
                if ($('#startDate').val() && $('#endDate').val()) {
                    performSearch(1);
                }
            });

            // Manual filter button
            $('#filterBtn').on('click', function() {
                performSearch(1);
            });

            // Reset filter button
            $('#resetFilterBtn').on('click', function() {
                $('#searchInput').val('');
                $('#startDate').val('');
                $('#endDate').val('');
                
                // Set default dates
                const today = new Date();
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(today.getDate() - 30);
                
                $('#startDate').val(thirtyDaysAgo.toISOString().split('T')[0]);
                $('#endDate').val(today.toISOString().split('T')[0]);
                
                performSearch(1);
            });

            // Handle pagination clicks
            $(document).on('click', '.pagination .page-link', function(e) {
                e.preventDefault();
                
                const url = $(this).attr('href');
                if (!url || url === '#') return;
                
                const pageMatch = url.match(/page=(\d+)/);
                if (pageMatch) {
                    const page = pageMatch[1];
                    performSearch(page);
                } else {
                    // Try to extract page from URL structure
                    const urlObj = new URL(url, window.location.origin);
                    const page = urlObj.searchParams.get('page') || 1;
                    performSearch(page);
                }
            });

            // Loading functions
            function showLoading() {
                $('#loadingOverlay').fadeIn();
            }

            function hideLoading() {
                $('#loadingOverlay').fadeOut();
            }

            // Initialize UI
            updateSelectionUI();

            // Generate QR codes saat halaman pertama kali load
            generateQRCodes();

            // Auto-hide success alert setelah 5 detik
            setTimeout(function() {
                $('#successAlert').fadeOut();
            }, 5000);
        });

        // Generate QR Codes untuk semua HU number - Dipisahkan dari fungsi utama
        function generateQRCodes() {
            // Data HU numbers dari PHP
            const huNumbers = [
                @foreach($historyData as $item)
                    '{{ $item->hu_number }}',
                @endforeach
            ];

            huNumbers.forEach(function(huNumber) {
                try {
                    const canvas = document.createElement('canvas');
                    const qrcodeElement = document.getElementById('qrcode-' + huNumber);
                    
                    if (!qrcodeElement) return;
                    
                    QRCode.toCanvas(canvas, huNumber, {
                        width: 60,
                        height: 60,
                        margin: 1,
                        color: {
                            dark: '#000000',
                            light: '#FFFFFF'
                        }
                    }, function(error) {
                        if (error) {
                            console.error('QR Code error:', error);
                            qrcodeElement.innerHTML = '<div style="width:60px;height:60px;border:1px solid #000;display:flex;align-items:center;justify-content:center;font-size:8px;">' + huNumber.substring(0, 8) + '</div>';
                        } else {
                            const dataURL = canvas.toDataURL('image/png');
                            qrcodeElement.innerHTML = '<img src="' + dataURL + '" width="60" height="60" alt="QR Code">';
                        }
                    });
                } catch (error) {
                    console.error('Error generating QR code:', error);
                    const qrcodeElement = document.getElementById('qrcode-' + huNumber);
                    if (qrcodeElement) {
                        qrcodeElement.innerHTML = '<div style="width:60px;height:60px;border:1px solid #000;display:flex;align-items:center;justify-content:center;font-size:8px;">' + huNumber.substring(0, 8) + '</div>';
                    }
                }
            });
        }
    </script>
</body>
</html>