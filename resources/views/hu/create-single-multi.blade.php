@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-green-100 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                    <i class="fas fa-boxes text-green-600 fa-lg"></i>
                </div>
                <div>
                    <h1 class="h3 fw-bold text-gray-800 mb-1">Skenario 2</h1>
                    <p class="text-muted mb-0">Buat Single HU dengan Multiple Material</p>
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
                        <i class="fas fa-boxes me-2 text-green-500"></i>
                        Buat Single HU dengan Multiple Material
                    </h5>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('hu.store-single-multi') }}" method="POST" id="huForm">
                        @csrf
                        <input type="hidden" id="base_unit_qty" name="base_unit_qty" value="">

                        <!-- Header Information -->
                        <div class="row mb-4">
                            <div class="col-12 mb-3">
                                <h6 class="fw-semibold text-gray-700 mb-3 border-bottom pb-2">
                                    <i class="fas fa-header me-2 text-blue-500"></i>
                                    Informasi Header HU
                                </h6>
                            </div>

                            <div class="col-md-6 col-lg-4 mb-3">
                                <label for="hu_exid" class="form-label fw-semibold text-gray-700">
                                    HU External ID <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-barcode text-green-500"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" id="hu_exid" name="hu_exid"
                                           value="{{ old('hu_exid') }}" required placeholder="Masukkan HU External ID">
                                </div>
                                <div class="form-text text-muted small">ID unik untuk identifikasi Handling Unit</div>
                            </div>

                            <div class="col-md-6 col-lg-4 mb-3">
                                <label for="pack_mat" class="form-label fw-semibold text-gray-700">
                                    Packaging Material <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="pack_mat" name="pack_mat" required>
                                    <option value="">Pilih Packaging Material</option>
                                    <option value="VSTDPLTBW01" {{ old('pack_mat') == 'VSTDPLTBW01' ? 'selected' : 'selected' }}>VSTDPLTBW01</option>
                                    <option value="VSTDPLBW002" {{ old('pack_mat') == 'VSTDPLBW002' ? 'selected' : '' }}>VSTDPLBW002</option>
                                    <option value="50016873" {{ old('pack_mat') == '50016873' ? 'selected' : '' }}>50016873</option>
                                </select>
                            </div>

                            <div class="col-md-6 col-lg-4 mb-3">
                                <label for="plant" class="form-label fw-semibold text-gray-700">
                                    Plant <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control bg-light" id="plant" name="plant"
                                       value="{{ old('plant') }}" required readonly>
                            </div>

                            <div class="col-md-6 col-lg-4 mb-3">
                                <label for="stge_loc" class="form-label fw-semibold text-gray-700">
                                    Storage Location <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control bg-light" id="stge_loc" name="stge_loc"
                                       value="{{ old('stge_loc') }}" required readonly>
                            </div>
                        </div>

                        <!-- Materials Information -->
                        <div class="row">
                            <div class="col-12 mb-3">
                                <h6 class="fw-semibold text-gray-700 mb-3 border-bottom pb-2">
                                    <i class="fas fa-list me-2 text-orange-500"></i>
                                    Daftar Material
                                    <span id="materialCount" class="badge bg-success ms-2">0 items</span>
                                </h6>
                                <div class="alert alert-info bg-light border-0 py-2">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Data material akan terisi otomatis dari drag & drop di halaman utama
                                    </small>
                                </div>
                            </div>

                            <div class="col-12">
                                <!-- Placeholder when no items -->
                                <div id="itemsPreview" class="text-center py-5 border-2 border-dashed rounded bg-light mb-4">
                                    <i class="fas fa-boxes fa-3x text-gray-400 mb-3"></i>
                                    <h6 class="text-muted mb-2">Belum Ada Material</h6>
                                    <p class="text-muted small mb-0">Data material akan ditampilkan di sini setelah dipilih dari halaman utama</p>
                                </div>

                                <!-- Items Container -->
                                <div id="items-container" class="mb-3">
                                    <!-- Items will be dynamically added here -->
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
                                        <button type="submit" class="btn btn-success px-4">
                                            <i class="fas fa-save me-2"></i>Create HU
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
.item-card {
    border-left: 4px solid #28a745;
    transition: all 0.3s ease;
}
.item-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style>
@endpush

@push('scripts')
<script>
// Global variable untuk menghitung items
let itemCount = 0;

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== SKENARIO 2 LOADED ===');

    // Cek jika ada pesan sukses dari server
    const serverSuccessAlert = document.querySelector('.alert-success');
    const serverErrorAlert = document.querySelector('.alert-danger');

    // Jika ada pesan sukses, skip loading dari sessionStorage
    if (serverSuccessAlert) {
        console.log('✅ HU created successfully, skipping sessionStorage load');
        sessionStorage.removeItem('scenario2_data');
        return;
    }

    // Debug: Tampilkan semua sessionStorage
    console.log('All sessionStorage keys:', Object.keys(sessionStorage));

    // Ambil data dari sessionStorage hanya jika tidak ada pesan sukses
    const scenarioDataRaw = sessionStorage.getItem('scenario2_data');
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
                itemCount = 0;

                // Clear container
                document.getElementById('items-container').innerHTML = '';

                // Isi header dengan data dari item pertama
                const firstItem = materials[0];
                document.getElementById('plant').value = firstItem.plant || '3000';
                document.getElementById('stge_loc').value = firstItem.storage_location || '3D10';
                document.getElementById('pack_mat').value = 'VSTDPLTBW01';

                // Tambahkan setiap item ke form
                materials.forEach((item, index) => {
                    console.log(`Processing item ${index}:`, item);
                    addItemToForm(item, index);
                });

                // Sembunyikan placeholder dan tampilkan items
                document.getElementById('itemsPreview').style.display = 'none';
                document.getElementById('materialCount').textContent = `${materials.length} items`;

                console.log(`✅ Successfully loaded ${materials.length} materials`);

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
            sessionStorage.removeItem('scenario2_data');
        }
    } else {
        // Tampilkan pesan ini HANYA jika tidak ada data DAN tidak ada pesan sukses
        if (!scenarioDataRaw && !serverSuccessAlert && !serverErrorAlert) {
            console.warn('❌ No data found in sessionStorage for scenario2_data');
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

    // Fungsi add item to form
    function addItemToForm(item, index) {
        const container = document.getElementById('items-container');

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

        console.log(`📝 Creating form item ${itemCount}:`, {
            material: formattedMaterial,
            batch: batch,
            stockQty: stockQty
        });

        const newItem = document.createElement('div');
        newItem.className = 'item-card card bg-light mb-3';
        newItem.innerHTML = `
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="fw-bold text-gray-800 mb-0">
                        <i class="fas fa-box me-1 text-green-500"></i>
                        Item ${itemCount + 1}: ${formattedMaterial}
                    </h6>
                    <span class="badge bg-success">${stockQty.toLocaleString('id-ID')} PC</span>
                </div>

                ${materialDescription ? `<p class="text-muted small mb-3">${materialDescription}</p>` : ''}

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold small">Material <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-light" name="items[${itemCount}][material]"
                               value="${formattedMaterial}" readonly required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold small">Batch</label>
                        <input type="text" class="form-control bg-light" name="items[${itemCount}][batch]"
                               value="${batch}" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold small">Pack Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="items[${itemCount}][pack_qty]"
                               value="" placeholder="Masukkan quantity"
                               step="0.001" min="0.001" max="${stockQty}"
                               required data-max-qty="${stockQty}">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Stock tersedia: <strong>${stockQty.toLocaleString('id-ID')}</strong> PC
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold small">Sales Order No</label>
                        <input type="text" class="form-control bg-light" name="items[${itemCount}][sp_stck_no]"
                               value="${salesOrderNo}" readonly>
                        <input type="hidden" name="items[${itemCount}][plant]" value="${plant}">
                        <input type="hidden" name="items[${itemCount}][storage_location]" value="${storageLocation}">
                    </div>
                </div>
            </div>
        `;
        container.appendChild(newItem);
        itemCount++;

        console.log(`✅ Item ${itemCount} added to form`);
    }

    // Validasi form sebelum submit
    document.getElementById('huForm').addEventListener('submit', function(e) {
        console.log('🚀 Form submission started');
        console.log('Total items:', itemCount);

        // Validasi: pastikan ada material
        if (itemCount === 0) {
            e.preventDefault();
            showMessage('Tidak ada material yang ditambahkan. Silakan pilih dari halaman utama.', 'error');
            return;
        }

        const huExid = document.getElementById('hu_exid').value.trim();
        const packMat = document.getElementById('pack_mat').value;

        if (!huExid) {
            e.preventDefault();
            showMessage('HU External ID harus diisi', 'error');
            document.getElementById('hu_exid').focus();
            return;
        }
        if (!packMat) {
            e.preventDefault();
            showMessage('Packaging Material harus dipilih', 'error');
            document.getElementById('pack_mat').focus();
            return;
        }

        // Validasi Qty
        let qtyError = false;
        const qtyInputs = document.querySelectorAll('#items-container input[name*="[pack_qty]"]');
        console.log('Quantity inputs found:', qtyInputs.length);

        qtyInputs.forEach((input, index) => {
            const maxQty = parseFloat(input.dataset.maxQty);
            let enteredQty = input.value.replace(/,/g, '.');
            const qty = parseFloat(enteredQty);

            console.log(`Validating item ${index}:`, { enteredQty, qty, maxQty });

            if (!enteredQty || isNaN(qty) || qty <= 0) {
                showMessage(`Pack Quantity untuk item ${index + 1} harus lebih dari 0`, 'error');
                input.focus();
                qtyError = true;
                return;
            }

            if (qty > maxQty) {
                showMessage(`Pack Quantity (${qty.toLocaleString('id-ID')}) melebihi stok tersedia (${maxQty.toLocaleString('id-ID')}) untuk item ${index + 1}`, 'error');
                input.focus();
                qtyError = true;
                return;
            }

            // Set nilai yang sudah dikonversi
            input.value = enteredQty;
        });

        if (qtyError) {
            e.preventDefault();
            return;
        }

        // JANGAN hapus sessionStorage di sini - biarkan server redirect dengan pesan sukses
        console.log('✅ Form validation passed, proceeding with submission');

        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating HU...';
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

    // Auto-generate HU External ID
    document.getElementById('hu_exid').addEventListener('focus', function() {
        if (!this.value) {
            const timestamp = new Date().getTime();
            this.value = 'HU2_' + timestamp.toString().slice(-8);
        }
    });

    console.log('=== SKENARIO 2 INITIALIZATION COMPLETE ===');
});
</script>
@endpush
