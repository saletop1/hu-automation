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
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
                <a href="{{ route('hu.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
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

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0 fw-bold text-gray-800">
                        <i class="fas fa-history me-2 text-blue-500"></i>
                        History Handling Units (HU)
                    </h5>
                    <div>
                        <button id="exportBtn" class="btn btn-success btn-sm" disabled>
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </button>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-8">
                        <input type="text" id="searchInput" class="form-control form-control-sm search-box"
                               placeholder="Cari berdasarkan HU Number, material, deskripsi, sales order, atau plant...">
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <input type="checkbox" id="selectAll" class="form-check-input select-all-checkbox">
                            <label for="selectAll" class="form-check-label text-muted small">
                                Pilih Semua
                            </label>
                            <span id="selectedCount" class="badge bg-primary ms-2">0 terpilih</span>
                        </div>
                    </div>
                </div>
                <small class="text-muted">Daftar material yang sudah dibuat Handling Unit</small>
            </div>
            <div class="card-body p-0">
                <!-- Form untuk export -->
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
                                <th class="border-0">Deskripsi Material</th>
                                <th class="border-0">Batch</th>
                                <th class="border-0 text-end">Qty</th>
                                <th class="border-0">Unit</th>
                                <th class="border-0">Dokumen Penjualan</th>
                                <th class="border-0">Plant</th>
                                <th class="border-0">Lokasi</th>
                                <th class="border-0">Skenario</th>
                                <th class="border-0">Dibuat Oleh</th>
                                <th class="border-0">Tanggal Dibuat (WIB)</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            @if($historyData->count() > 0)
                                @foreach($historyData as $item)
                                    <tr class="hover:bg-gray-50" data-search="{{ strtolower(($item->hu_number ?? '') . ' ' . ($item->material ?? '') . ' ' . ($item->material_description ?? '') . ' ' . ($item->sales_document ?? '') . ' ' . ($item->plant ?? '') . ' ' . ($item->created_at ? \Carbon\Carbon::parse($item->created_at)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s') : '')) }}">
                                        <td class="border-0">
                                            <input type="checkbox" class="form-check-input row-checkbox"
                                                   value="{{ $item->id ?? $item->hu_number }}" data-hu="{{ $item->hu_number }}">
                                        </td>
                                        <td class="border-0">
                                            <span class="fw-bold text-primary">{{ $item->hu_number }}</span>
                                        </td>
                                        <td class="border-0">
                                            <span class="material-number">
                                                {{ preg_match('/^\d+$/', $item->material) ? ltrim($item->material, '0') : $item->material }}
                                            </span>
                                        </td>
                                        <td class="border-0 text-gray-600 material-description" title="{{ $item->material_description ?: '-' }}">
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
                                        <td class="border-0 text-gray-600">{{ $item->plant ?: '-' }}</td>
                                        <td class="border-0 text-gray-600">{{ $item->storage_location ?: '-' }}</td>
                                        <td class="border-0">
                                            @if($item->scenario_type == 'single')
                                                <span class="badge bg-primary text-white">Skenario 1</span>
                                            @elseif($item->scenario_type == 'single-multi')
                                                <span class="badge bg-success text-white">Skenario 2</span>
                                            @elseif($item->scenario_type == 'multiple')
                                                <span class="badge bg-purple text-white">Skenario 3</span>
                                            @else
                                                <span class="badge bg-secondary text-white">{{ $item->scenario_type ?: '-' }}</span>
                                            @endif
                                        </td>
                                        <td class="border-0 text-gray-600">{{ $item->created_by ?: '-' }}</td>
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
                                    <td colspan="13" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        Tidak ada data history HU
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            let selectedHUs = [];

            // Live Search Functionality
            $('#searchInput').on('input', function() {
                const searchText = $(this).val().toLowerCase();
                $('#historyTableBody tr').each(function() {
                    const searchData = $(this).data('search') || '';
                    if (searchData.includes(searchText)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                updateSelectionUI();
            });

            // Select All Checkbox
            $('#selectAll, #selectAllHeader').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.row-checkbox:visible').prop('checked', isChecked);
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
                const visibleCount = $('.row-checkbox:visible').length;
                const checkedCount = $('.row-checkbox:checked:visible').length;

                $('#selectedCount').text(checkedCount + ' terpilih');

                // Update select all checkbox state
                if (checkedCount === 0) {
                    $('#selectAll, #selectAllHeader').prop('checked', false).prop('indeterminate', false);
                } else if (checkedCount === visibleCount && visibleCount > 0) {
                    $('#selectAll, #selectAllHeader').prop('checked', true).prop('indeterminate', false);
                } else {
                    $('#selectAll, #selectAllHeader').prop('checked', false).prop('indeterminate', true);
                }

                // Enable/disable export button
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
        });
    </script>
</body>
</html>
