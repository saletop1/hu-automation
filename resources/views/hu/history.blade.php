<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History HU - SAP HU Automation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .table-responsive {
            max-height: 70vh;
        }
        .history-table {
            font-size: 0.875rem;
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
                <h5 class="card-title mb-0 fw-bold text-gray-800">
                    <i class="fas fa-history me-2 text-blue-500"></i>
                    History Handling Units (HU)
                </h5>
                <small class="text-muted">Daftar material yang sudah dibuat Handling Unit</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover history-table mb-0">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="border-0">HU Number</th>
                                <th class="border-0">Material</th>
                                <th class="border-0">Deskripsi</th>
                                <th class="border-0">Batch</th>
                                <th class="border-0 text-end">Qty</th>
                                <th class="border-0">Unit</th>
                                <th class="border-0">Dokumen Penjualan</th>
                                <th class="border-0">Plant</th>
                                <th class="border-0">Lokasi</th>
                                <th class="border-0">Skenario</th>
                                <th class="border-0">Dibuat Oleh</th>
                                <th class="border-0">Tanggal Dibuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($historyData->count() > 0)
                                @foreach($historyData as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="border-0">
                                            <span class="fw-bold text-primary">{{ $item->hu_number }}</span>
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
                                                {{ number_format((float)($item->quantity ?? 0), 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td class="border-0 text-gray-600">{{ $item->unit == 'ST' ? 'PC' : ($item->unit ?? '-') }}</td>
                                        <td class="border-0">
                                            <span class="sales-document">{{ $item->sales_document ?? '-' }}</span>
                                        </td>
                                        <td class="border-0 text-gray-600">{{ $item->plant ?? '-' }}</td>
                                        <td class="border-0 text-gray-600">{{ $item->storage_location ?? '-' }}</td>
                                        <td class="border-0">
                                            @if($item->scenario_type == 'single')
                                                <span class="badge bg-primary">Skenario 1</span>
                                            @elseif($item->scenario_type == 'single-multi')
                                                <span class="badge bg-success">Skenario 2</span>
                                            @else
                                                <span class="badge bg-purple">Skenario 3</span>
                                            @endif
                                        </td>
                                        <td class="border-0 text-gray-600">{{ $item->created_by ?? '-' }}</td>
                                        <td class="border-0 text-gray-600">
                                            {{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i:s') : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="12" class="text-center py-4 text-muted">
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
</body>
</html>
