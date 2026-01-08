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
                    <form method="GET" action="{{ route('hu.history') }}" id="filterForm" class="w-100">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="date-filter-label">Pencarian</label>
                                <input type="text" name="search" id="searchInput" class="form-control form-control-sm search-box"
                                       placeholder="Cari HU Number, material, deskripsi, sales order..."
                                       value="{{ request('search', '') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="date-filter-label">Tanggal Mulai</label>
                                <input type="date" name="start_date" id="startDate" class="form-control form-control-sm"
                                       value="{{ request('start_date', '') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="date-filter-label">Tanggal Akhir</label>
                                <input type="date" name="end_date" id="endDate" class="form-control form-control-sm"
                                       value="{{ request('end_date', '') }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-search me-1"></i> Filter
                                </button>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <a href="{{ route('hu.history') }}" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="fas fa-refresh me-1"></i> Reset Filter
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="d-flex align-items-center mb-2">
                    <input type="checkbox" id="selectAll" class="form-check-input select-all-checkbox">
                    <label for="selectAll" class="form-check-label text-muted small ms-1">
                        Pilih Semua
                    </label>
                    <span id="selectedCount" class="badge bg-primary ms-2">0 terpilih</span>
                </div>
                <small class="text-muted d-block">Menampilkan {{ $historyData->count() }} dari {{ $historyData->total() }} data (50 data per halaman)</small>
            </div>
            <div class="card-body p-0">
                <form id="exportForm" action="{{ route('hu.export') }}" method="POST">
                    @csrf
                    <input type="hidden" name="selected_data" id="selectedData">
                </form>

                <div class="table-responsive">
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
                                <th class="border-0">Dibuat Oleh</th> <!-- ✅ KOLOM BARU: CREATED BY -->
                                <th class="border-0">Tanggal Dibuat (WIB)</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            @if($historyData->count() > 0)
                                @foreach($historyData as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="border-0">
                                            <!-- ✅ PERBAIKAN: Gunakan $item->id bukan $item->hu_number -->
                                            <input type="checkbox" class="form-check-input row-checkbox"
                                                   value="{{ $item->id }}" data-hu="{{ $item->hu_number }}">
                                        </td>
                                        <td class="border-0">
                                            <span class="fw-bold text-primary">{{ $item->hu_number }}</span>
                                        </td>
                                        <td class="border-0">
                                            <span class="material-number">
                                                {{ preg_match('/^\d+$/', $item->material) ? ltrim($item->material, '0') : $item->material }}
                                            </span>
                                        </td>
                                        <td class="border-0 text-gray-600 material-description">
                                            {{ $item->material_description ?: '-' }}
                                        </td>
                                        <td class="border-0 text-gray-600">{{ $item->batch ?: '-' }}</td>
                                        <td class="border-0 text-end">
                                            <span class="badge bg-success bg-opacity-10 text-success fs-6">
                                                {{ number_format((float)($item->quantity ?? 0), 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td class="border-0 text-gray-600">{{ $item->unit == 'ST' ? 'PC' : ($item->unit ?: '-') }}</td>
                                        <td class="border-0">
                                            <span class="sales-document">{{ $item->sales_document ?: '-' }}</span>
                                        </td>
                                        <td class="border-0 text-gray-600">{{ $item->storage_location ?: '-' }}</td>
                                        <td class="border-0">
                                            @if($item->scenario_type == 'single')
                                                <span class="badge bg-primary text-white">Skenario 1</span>
                                            @elseif($item->scenario_type == 'single-multi')
                                                <span class="badge bg-success text-white">Skenario 2</span>
                                            @elseif($item->scenario_type == 'multiple')
                                                <span class="badge bg-purple-600 text-white">Skenario 3</span>
                                            @else
                                                <span class="badge bg-secondary text-white">{{ $item->scenario_type ?: '-' }}</span>
                                            @endif
                                        </td>
                                        <td class="border-0 text-gray-600">
                                            <!-- ✅ KOLOM BARU: CREATED BY -->
                                            <span class="created-by" title="{{ $item->created_by ?: 'System' }}">
                                                {{ $item->created_by ?: 'System' }}
                                            </span>
                                        </td>
                                        <td class="border-0 text-gray-600">
                                            @php
                                                try {
                                                    $createdAt = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s') : '-';
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
                                    <td colspan="12" class="text-center py-4 text-muted"> <!-- ✅ UPDATE COLSPAN MENJADI 12 -->
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        Tidak ada data history HU
                                        @if(request('search') || request('start_date') || request('end_date'))
                                            <br>
                                            <small class="text-muted mt-2">Coba gunakan filter yang berbeda atau <a href="{{ route('hu.history') }}" class="text-primary">reset filter</a></small>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Section -->
                @if($historyData->hasPages())
                <div class="card-footer bg-white py-3 no-print">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Menampilkan {{ $historyData->firstItem() ?? 0 }} - {{ $historyData->lastItem() ?? 0 }} dari {{ $historyData->total() }} data
                        </div>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                {{-- Previous Page Link --}}
                                @if($historyData->onFirstPage())
                                    <li class="page-item disabled">
                                        <span class="page-link">&laquo; Sebelumnya</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $historyData->previousPageUrl() . '&' . http_build_query(request()->except('page')) }}" rel="prev">&laquo; Sebelumnya</a>
                                    </li>
                                @endif

                                {{-- Pagination Elements --}}
                                @foreach($historyData->getUrlRange(1, $historyData->lastPage()) as $page => $url)
                                    @if($page == $historyData->currentPage())
                                        <li class="page-item active">
                                            <span class="page-link">{{ $page }}</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $url . '&' . http_build_query(request()->except('page')) }}">{{ $page }}</a>
                                        </li>
                                    @endif
                                @endforeach

                                {{-- Next Page Link --}}
                                @if($historyData->hasMorePages())
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $historyData->nextPageUrl() . '&' . http_build_query(request()->except('page')) }}" rel="next">Selanjutnya &raquo;</a>
                                    </li>
                                @else
                                    <li class="page-item disabled">
                                        <span class="page-link">Selanjutnya &raquo;</span>
                                    </li>
                                @endif
                            </ul>
                        </nav>
                        <div class="text-muted small">
                            Halaman {{ $historyData->currentPage() }} dari {{ $historyData->lastPage() }}
                        </div>
                    </div>
                </div>
                @endif
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
                        <th width="180">Deskripsi Material</th> <!-- ✅ Dikurangi lebar untuk ruang kolom baru -->
                        <th>Batch</th>
                        <th width="60">Qty</th>
                        <th width="60">Unit</th>
                        <th>Dokumen Penjualan</th>
                        <th>Lokasi</th>
                        <th width="80">Skenario</th>
                        <th width="100">Dibuat Oleh</th> <!-- ✅ KOLOM BARU: CREATED BY -->
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
                                <td> <!-- ✅ KOLOM BARU: CREATED BY -->
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
                            <td colspan="12" class="text-center py-4">Tidak ada data history HU</td> <!-- ✅ UPDATE COLSPAN MENJADI 12 -->
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

            // Set default dates (last 30 days) jika tidak ada filter
            @if(!request('start_date') && !request('end_date'))
                const today = new Date();
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(today.getDate() - 30);

                $('#startDate').val(thirtyDaysAgo.toISOString().split('T')[0]);
                $('#endDate').val(today.toISOString().split('T')[0]);
            @endif

            // Generate QR Codes untuk semua HU number
            function generateQRCodes() {
                @foreach($historyData as $item)
                    try {
                        // Buat canvas untuk QR code
                        const canvas = document.createElement('canvas');
                        QRCode.toCanvas(canvas, '{{ $item->hu_number }}', {
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
                                // Fallback: tampilkan text jika QR code gagal
                                $('#qrcode-{{ $item->hu_number }}').html('<div style="width:60px;height:60px;border:1px solid #000;display:flex;align-items:center;justify-content:center;font-size:8px;">{{ $item->hu_number }}</div>');
                            } else {
                                // Convert canvas to image
                                const dataURL = canvas.toDataURL('image/png');
                                $('#qrcode-{{ $item->hu_number }}').html('<img src="' + dataURL + '" width="60" height="60" alt="QR Code">');
                            }
                        });
                    } catch (error) {
                        console.error('Error generating QR code:', error);
                        $('#qrcode-{{ $item->hu_number }}').html('<div style="width:60px;height:60px;border:1px solid #000;display:flex;align-items:center;justify-content:center;font-size:8px;">{{ $item->hu_number }}</div>');
                    }
                @endforeach
            }

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

            // Initialize UI
            updateSelectionUI();

            // Generate QR codes saat halaman pertama kali load
            generateQRCodes();

            // Auto-hide success alert setelah 5 detik
            setTimeout(function() {
                $('#successAlert').fadeOut();
            }, 5000);

            // Auto submit filter form ketika input tanggal berubah
            $('#startDate, #endDate').on('change', function() {
                if ($('#startDate').val() && $('#endDate').val()) {
                    $('#filterForm').submit();
                }
            });
        });
    </script>
</body>
</html>
