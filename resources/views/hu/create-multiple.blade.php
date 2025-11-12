@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-purple-100 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                    <i class="fas fa-pallet text-purple-600 fa-lg"></i>
                </div>
                <div>
                    <h1 class="h3 fw-bold text-gray-800 mb-1">Skenario 3</h1>
                    <p class="text-muted mb-0">Buat Multiple HU (1 HU = 1 Material)</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications -->
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

    <div class="row justify-content-center">
        <div class="col-xxl-10 col-xl-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-gray-800">
                        <i class="fas fa-pallet me-2 text-purple-500"></i>
                        Buat Multiple Handling Units
                    </h5>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('hu.store-multiple') }}" method="POST" id="huForm">
                        @csrf
                        <input type="hidden" id="base_unit_qty" name="base_unit_qty" value="">

                        <!-- HU List Section -->
                        <div class="row">
                            <div class="col-12 mb-3">
                                <h6 class="fw-semibold text-gray-700 mb-3 border-bottom pb-2">
                                    <i class="fas fa-list-ol me-2 text-purple-500"></i>
                                    Daftar Handling Units
                                    <span id="huCount" class="badge bg-purple ms-2">0 HUs</span>
                                </h6>
                                <div class="alert alert-info bg-light border-0 py-2">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Setiap material akan dibuat sebagai HU terpisah
                                    </small>
                                </div>
                            </div>

                            <div class="col-12">
                                <!-- Placeholder when no HUs -->
                                <div id="husPreview" class="text-center py-5 border-2 border-dashed rounded bg-light mb-4">
                                    <i class="fas fa-pallet fa-3x text-gray-400 mb-3"></i>
                                    <h6 class="text-muted mb-2">Belum Ada Handling Unit</h6>
                                    <p class="text-muted small mb-0">Data HU akan ditampilkan di sini setelah material dipilih dari halaman utama</p>
                                </div>

                                <!-- HUs Container -->
                                <div id="hus-container" class="mb-3">
                                    <!-- HUs will be dynamically added here -->
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row mt-5">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('hu.index') }}" class="btn btn-outline-secondary px-4">
                                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Home
                                    </a>
                                    <div class="d-flex gap-2">
                                        <button type="reset" class="btn btn-outline-danger px-4">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </button>
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="fas fa-save me-2"></i>Create All HUs
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.border-dashed {
    border-style: dashed !important;
}
.hu-card {
    border-left: 4px solid #8b5cf6;
    transition: all 0.3s ease;
}
.hu-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.1);
}
.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}
</style>
@endpush

@push('scripts')
<script>
// Global variable untuk menghitung HUs
let huCount = 0;

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== SKENARIO 3 LOADED ===');

    // Cek jika ada pesan sukses dari server
    const serverSuccessAlert = document.querySelector('.alert-success');
    const serverErrorAlert = document.querySelector('.alert-danger');

    // Jika ada pesan sukses, skip loading dari sessionStorage
    if (serverSuccessAlert) {
        console.log('✅ HUs created successfully, skipping sessionStorage load');
        sessionStorage.removeItem('scenario3_data');
        return;
    }

    // Debug: Tampilkan semua sessionStorage
    console.log('All sessionStorage keys:', Object.keys(sessionStorage));

    // Ambil data dari sessionStorage hanya jika tidak ada pesan sukses
    const scenarioDataRaw = sessionStorage.getItem('scenario3_data');
    console.log('Raw data from sessionStorage:', scenarioDataRaw);

    if (scenarioDataRaw && !serverSuccessAlert) {
        try {
            const materials = JSON.parse(scenarioDataRaw);
            console.log('Parsed materials:', materials);
            console.log('Number of materials:', materials.length);
            console.log('Materials data type:', typeof materials);
            console.log('Is array?', Array.isArray(materials));

            if (materials && Array.isArray(materials) && materials.length > 0) {
                console.log('✅ Data valid, processing materials...');

                // Reset counter
                huCount = 0;

                // Clear container
                document.getElementById('hus-container').innerHTML = '';

                // Tambahkan setiap material sebagai HU terpisah
                materials.forEach((item, index) => {
                    console.log(`Processing material ${index}:`, item);
                    addHUToForm(item, index);
                });

                // Sembunyikan placeholder dan tampilkan HUs
                document.getElementById('husPreview').style.display = 'none';
                document.getElementById('huCount').textContent = `${materials.length} HUs`;

                console.log(`✅ Successfully loaded ${materials.length} HUs`);

            } else {
                console.warn('❌ Data invalid - empty or not array');
                if (!serverErrorAlert) {
                    showMessage('Data material tidak valid. Silakan pilih ulang dari halaman utama.', 'warning');
                }
            }
        } catch (error) {
            console.error('❌ Error parsing scenario data:', error);
            console.error('Problematic data:', scenarioDataRaw);
            if (!serverErrorAlert) {
                showMessage('Error memuat data material. Data mungkin korup. Silakan pilih ulang.', 'error');
            }

            // Hapus data yang korup
            sessionStorage.removeItem('scenario3_data');
        }
    } else {
        // Tampilkan pesan ini HANYA jika tidak ada data DAN tidak ada pesan sukses
        if (!scenarioDataRaw && !serverSuccessAlert && !serverErrorAlert) {
            console.warn('❌ No data found in sessionStorage for scenario3_data');
            showMessage('Silakan pilih material dari halaman utama dengan drag & drop terlebih dahulu.', 'info');
        }
    }

    // Fungsi format material number
    function formatMaterialNumber(material) {
        if (!material) return '';
        if (/^\d+$/.test(material)) {
            return material.replace(/^0+/, '') || '0';
        }
        return material;
    }

    // Fungsi get sales order number
    function getSalesOrderNo(item) {
        if (item.combined_sales_doc && item.combined_sales_doc !== '-') {
            return item.combined_sales_doc;
        }
        if (item.sales_document && item.item_number) {
            return item.sales_document + item.item_number;
        }
        if (item.sales_document) {
            return item.sales_document;
        }
        return '';
    }

    // Fungsi add HU to form
    function addHUToForm(item, index) {
        const container = document.getElementById('hus-container');

        // Validasi data
        const material = item.material || '';
        const batch = item.batch || '';
        const plant = item.plant || '3000';
        const storageLocation = item.storage_location || '3D10';

        // Handle stock quantity
        let stockQty = 0;
        if (item.stock_quantity !== undefined && item.stock_quantity !== null) {
            stockQty = parseFloat(item.stock_quantity);
        }
        if (isNaN(stockQty)) stockQty = 0;

        const salesOrderNo = getSalesOrderNo(item);
        const formattedMaterial = formatMaterialNumber(material);
        const materialDescription = item.material_description || '';

        // Generate HU External ID otomatis
        const timestamp = new Date().getTime().toString().slice(-4);
        const autoHuExid = `HU3_${formattedMaterial.slice(-6)}_${huCount}_${timestamp}`;

        console.log(`📝 Creating HU ${huCount}:`, {
            material: formattedMaterial,
            batch: batch,
            stockQty: stockQty,
            huExid: autoHuExid
        });

        const newHU = document.createElement('div');
        newHU.className = 'hu-card card bg-light mb-4';
        newHU.innerHTML = `
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-gray-800 mb-0">
                        <i class="fas fa-pallet me-2 text-purple-500"></i>
                        HU #${huCount + 1}: ${formattedMaterial}
                    </h6>
                    <span class="badge bg-purple">${stockQty.toLocaleString('id-ID')} PC</span>
                </div>
            </div>
            <div class="card-body p-4">
                ${materialDescription ? `<p class="text-muted small mb-3">${materialDescription}</p>` : ''}

                <div class="row">
                    <div class="col-md-6 col-lg-4 mb-3">
                        <label class="form-label fw-semibold small">HU External ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="hus[${huCount}][hu_exid]"
                               value="${autoHuExid}" required placeholder="HU External ID">
                        <div class="form-text text-muted small">ID unik untuk Handling Unit</div>
                    </div>

                    <div class="col-md-6 col-lg-4 mb-3">
                        <label class="form-label fw-semibold small">Packaging Material <span class="text-danger">*</span></label>
                        <select class="form-select" name="hus[${huCount}][pack_mat]" required>
                            <option value="">Pilih Pack Mat</option>
                            <option value="VSTDPLTBW01" selected>VSTDPLTBW01</option>
                            <option value="VSTDPLBW002">VSTDPLBW002</option>
                            <option value="50016873">50016873</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-4 mb-3">
                        <label class="form-label fw-semibold small">Plant <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-light" name="hus[${huCount}][plant]"
                               value="${plant}" readonly>
                    </div>

                    <div class="col-md-6 col-lg-4 mb-3">
                        <label class="form-label fw-semibold small">Storage Location <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-light" name="hus[${huCount}][stge_loc]"
                               value="${storageLocation}" readonly>
                    </div>

                    <div class="col-md-6 col-lg-4 mb-3">
                        <label class="form-label fw-semibold small">Material <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-light" name="hus[${huCount}][material]"
                               value="${formattedMaterial}" readonly>
                    </div>

                    <div class="col-md-6 col-lg-4 mb-3">
                        <label class="form-label fw-semibold small">Batch</label>
                        <input type="text" class="form-control bg-light" name="hus[${huCount}][batch]"
                               value="${batch}" readonly>
                    </div>

                    <div class="col-md-6 col-lg-4 mb-3">
                        <label class="form-label fw-semibold small">Pack Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="hus[${huCount}][pack_qty]"
                               value="" placeholder="Masukkan quantity"
                               step="0.001" min="0.001" max="${stockQty}"
                               required data-max-qty="${stockQty}">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Stock tersedia: <strong>${stockQty.toLocaleString('id-ID')}</strong> PC
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 mb-3">
                        <label class="form-label fw-semibold small">Sales Order No</label>
                        <input type="text" class="form-control bg-light" name="hus[${huCount}][sp_stck_no]"
                               value="${salesOrderNo}" readonly>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(newHU);
        huCount++;

        console.log(`✅ HU ${huCount} added to form`);
    }

    // Validasi form sebelum submit
    document.getElementById('huForm').addEventListener('submit', function(e) {
        console.log('🚀 Form submission started');
        console.log('Total HUs:', huCount);

        // Validasi: pastikan ada HU
        if (huCount === 0) {
            e.preventDefault();
            showMessage('Tidak ada HU yang ditambahkan. Silakan pilih dari halaman utama.', 'error');
            return;
        }

        // Validasi HU External ID dan Pack Quantity untuk setiap HU
        let validationError = false;
        const huExidInputs = document.querySelectorAll('#hus-container input[name*="[hu_exid]"]');
        const packQtyInputs = document.querySelectorAll('#hus-container input[name*="[pack_qty]"]');
        const packMatSelects = document.querySelectorAll('#hus-container select[name*="[pack_mat]"]');

        console.log('HU External ID inputs found:', huExidInputs.length);
        console.log('Pack Quantity inputs found:', packQtyInputs.length);
        console.log('Packaging Material selects found:', packMatSelects.length);

        // Validasi HU External ID
        huExidInputs.forEach((input, index) => {
            const huExid = input.value.trim();
            if (!huExid) {
                showMessage(`HU External ID untuk HU ${index + 1} harus diisi`, 'error');
                input.focus();
                validationError = true;
                return;
            }
        });

        if (validationError) {
            e.preventDefault();
            return;
        }

        // Validasi Packaging Material
        packMatSelects.forEach((select, index) => {
            const packMat = select.value;
            if (!packMat) {
                showMessage(`Packaging Material untuk HU ${index + 1} harus dipilih`, 'error');
                select.focus();
                validationError = true;
                return;
            }
        });

        if (validationError) {
            e.preventDefault();
            return;
        }

        // Validasi Quantity
        packQtyInputs.forEach((input, index) => {
            const maxQty = parseFloat(input.dataset.maxQty);
            let enteredQty = input.value.replace(/,/g, '.');
            const qty = parseFloat(enteredQty);

            console.log(`Validating HU ${index}:`, { enteredQty, qty, maxQty });

            if (!enteredQty || isNaN(qty) || qty <= 0) {
                showMessage(`Pack Quantity untuk HU ${index + 1} harus lebih dari 0`, 'error');
                input.focus();
                validationError = true;
                return;
            }

            if (qty > maxQty) {
                showMessage(`Pack Quantity (${qty.toLocaleString('id-ID')}) melebihi stok tersedia (${maxQty.toLocaleString('id-ID')}) untuk HU ${index + 1}`, 'error');
                input.focus();
                validationError = true;
                return;
            }

            // Set nilai yang sudah dikonversi
            input.value = enteredQty;
        });

        if (validationError) {
            e.preventDefault();
            return;
        }

        // JANGAN hapus sessionStorage di sini - biarkan server redirect dengan pesan sukses
        console.log('✅ Form validation passed, proceeding with submission');

        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating All HUs...';
        submitBtn.disabled = true;
    });

    function showMessage(message, type) {
        console.log(`💬 Message: ${message} (${type})`);

        // Hapus alert existing
        const existingAlerts = document.querySelectorAll('.alert.alert-dismissible');
        existingAlerts.forEach(alert => alert.remove());

        const alertClass = type === 'error' ? 'alert-danger' :
                         type === 'warning' ? 'alert-warning' :
                         type === 'success' ? 'alert-success' : 'alert-info';

        const iconClass = type === 'error' ? 'fa-exclamation-triangle' :
                         type === 'warning' ? 'fa-exclamation-triangle' :
                         type === 'success' ? 'fa-check-circle' : 'fa-info-circle';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="fas ${iconClass} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        const cardHeader = document.querySelector('.card-header');
        cardHeader.insertAdjacentHTML('afterend', alertHtml);

        // Auto-close untuk success/info
        if (type === 'success' || type === 'info') {
            setTimeout(() => {
                const alert = document.querySelector('.alert.' + alertClass);
                if (alert) alert.remove();
            }, 5000);
        }
    }

    console.log('=== SKENARIO 3 INITIALIZATION COMPLETE ===');
});
</script>
@endpush
