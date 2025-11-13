@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Alert Messages dari Laravel Session -->
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

    <!-- Action Buttons di ATAS Container -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('hu.index') }}" class="btn btn-outline-secondary px-4">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Home
                </a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger px-4" onclick="resetForm()">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-success px-4" id="createHuButton">
                        <i class="fas fa-save me-2"></i>Create HU
                    </button>
                </div>
            </div>
        </div>
    </div>

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
                        <!-- Hidden inputs for SAP Credentials -->
                        <input type="hidden" id="sap_user" name="sap_user" value="">
                        <input type="hidden" id="sap_password" name="sap_password" value="">

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
                                    <option value="VSTDPLTBW01" {{ old('pack_mat') == 'VSTDPLTBW01' ? 'selected' : '' }}>VSTDPLTBW01</option>
                                    <option value="VSTDPLBW002" {{ old('pack_mat') == 'VSTDPLBW002' ? 'selected' : '' }}>VSTDPLBW002</option>
                                    <option value="50016873" {{ old('pack_mat') == '50016873' ? 'selected' : '' }}>50016873</option>
                                </select>
                                <div class="form-text text-muted small">Pilih packaging material</div>
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
                                <!-- Compact List Container -->
                                <div id="items-container" class="compact-list-container mb-3">
                                    <!-- Items will be dynamically added here -->
                                </div>

                                <!-- Placeholder when no items -->
                                <div id="itemsPreview" class="text-center py-5 border-2 border-dashed rounded bg-light">
                                    <i class="fas fa-boxes fa-3x text-gray-400 mb-3"></i>
                                    <h6 class="text-muted mb-2">Belum Ada Material</h6>
                                    <p class="text-muted small mb-0">Data material akan ditampilkan di sini setelah dipilih dari halaman utama</p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal SAP Credentials -->
<div class="modal fade" id="sapCredentialsModal" tabindex="-1" aria-labelledby="sapCredentialsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="sapCredentialsModalLabel">
                    <i class="fas fa-key me-2"></i>SAP Credentials
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="sapCredentialsForm">
                    <div class="mb-3">
                        <label for="sap_user_modal" class="form-label">SAP User <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sap_user_modal" name="sap_user_modal" required>
                    </div>
                    <div class="mb-3">
                        <label for="sap_password_modal" class="form-label">SAP Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="sap_password_modal" name="sap_password_modal" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmSapCredentials">
                    <i class="fas fa-check me-2"></i>Confirm & Create HU
                </button>
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
.compact-list-container {
    display: none; /* Sembunyikan saat kosong */
}
.compact-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 8px;
    transition: all 0.2s ease;
}
.compact-item:hover {
    background: #e9ecef;
    border-color: #dee2e6;
}
.compact-item-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 8px;
}
.compact-item-title {
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
}
.compact-item-badge {
    background: #28a745;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}
.compact-item-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    font-size: 0.8rem;
}
.compact-item-field {
    display: flex;
    flex-direction: column;
}
.compact-item-label {
    font-weight: 500;
    color: #6c757d;
    font-size: 0.75rem;
    margin-bottom: 2px;
}
.compact-item-value {
    color: #333;
    font-weight: 400;
}
.compact-item-input {
    width: 100%;
    padding: 4px 8px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 0.8rem;
}
.compact-item-input:focus {
    border-color: #28a745;
    outline: none;
    box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.25);
}
</style>
@endpush

@push('scripts')
<script>
// Global variable untuk menghitung items
let itemCount = 0;

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== SKENARIO 2 LOADED ===');

    // Cek jika ada pesan sukses dari server dan clear sessionStorage
    const serverSuccessAlert = document.querySelector('.alert-success');
    const serverErrorAlert = document.querySelector('.alert-danger');

    if (serverSuccessAlert) {
        console.log('✅ HU created successfully, clearing sessionStorage');
        sessionStorage.removeItem('scenario2_data');

        // Auto-hide success alert setelah 5 detik
        setTimeout(() => {
            serverSuccessAlert.remove();
        }, 5000);
    }

    if (serverErrorAlert) {
        console.log('❌ HU creation failed');
        // Auto-hide error alert setelah 8 detik
        setTimeout(() => {
            serverErrorAlert.remove();
        }, 8000);
    }

    // Ambil data dari sessionStorage hanya jika tidak ada pesan sukses/error dari server
    const scenarioDataRaw = sessionStorage.getItem('scenario2_data');
    console.log('Raw data from sessionStorage:', scenarioDataRaw);

    if (scenarioDataRaw && !serverSuccessAlert && !serverErrorAlert) {
        try {
            const materials = JSON.parse(scenarioDataRaw);
            console.log('Parsed materials:', materials);

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

                // Tambahkan setiap item ke form
                materials.forEach((item, index) => {
                    console.log(`Processing item ${index}:`, item);
                    addItemToForm(item, index);
                });

                // Tampilkan container dan sembunyikan placeholder
                document.getElementById('itemsPreview').style.display = 'none';
                document.getElementById('items-container').style.display = 'block';
                document.getElementById('materialCount').textContent = `${materials.length} items`;

                console.log(`✅ Successfully loaded ${materials.length} materials`);

            } else {
                console.warn('❌ Data invalid - empty or not array');
                showMessage('Data material tidak valid. Silakan pilih ulang dari halaman utama.', 'warning');
            }
        } catch (error) {
            console.error('❌ Error parsing scenario data:', error);
            showMessage('Error memuat data material. Data mungkin korup. Silakan pilih ulang.', 'error');
            sessionStorage.removeItem('scenario2_data');
        }
    } else {
        if (!scenarioDataRaw && !serverSuccessAlert && !serverErrorAlert) {
            console.warn('❌ No data found in sessionStorage for scenario2_data');
            showMessage('Silakan pilih material dari halaman utama dengan drag & drop terlebih dahulu.', 'info');
        }
    }

    // Handle Create HU Button Click
    document.getElementById('createHuButton').addEventListener('click', function() {
        if (!validateForm()) {
            return;
        }

        // Tampilkan modal SAP credentials
        const sapModal = new bootstrap.Modal(document.getElementById('sapCredentialsModal'));
        sapModal.show();
    });

    // Handle Confirm SAP Credentials
    document.getElementById('confirmSapCredentials').addEventListener('click', function() {
        const modalSapUser = document.querySelector('#sapCredentialsModal input[name="sap_user_modal"]').value;
        const modalSapPassword = document.querySelector('#sapCredentialsModal input[name="sap_password_modal"]').value;

        if (!modalSapUser || !modalSapPassword) {
            showMessage('SAP User dan Password harus diisi', 'error');
            return;
        }

        // Set nilai ke hidden input
        document.getElementById('sap_user').value = modalSapUser;
        document.getElementById('sap_password').value = modalSapPassword;

        // Tampilkan loading state
        const confirmBtn = document.getElementById('confirmSapCredentials');
        const originalText = confirmBtn.innerHTML;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating HU...';
        confirmBtn.disabled = true;

        // Tutup modal
        const sapModal = bootstrap.Modal.getInstance(document.getElementById('sapCredentialsModal'));
        sapModal.hide();

        // Submit form
        setTimeout(() => {
            document.getElementById('huForm').submit();
        }, 500);
    });

    // Reset modal ketika ditutup
    document.getElementById('sapCredentialsModal').addEventListener('hidden.bs.modal', function () {
        document.querySelector('#sapCredentialsModal form').reset();
        const confirmBtn = document.getElementById('confirmSapCredentials');
        confirmBtn.innerHTML = '<i class="fas fa-check me-2"></i>Confirm & Create HU';
        confirmBtn.disabled = false;
    });

    // Auto-generate HU External ID
    document.getElementById('hu_exid').addEventListener('focus', function() {
        if (!this.value) {
            const timestamp = new Date().getTime();
            this.value = 'HU2_' + timestamp.toString().slice(-8);
        }
    });
});

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
    newItem.className = 'compact-item';
    newItem.innerHTML = `
        <div class="compact-item-header">
            <div class="compact-item-title">
                <i class="fas fa-box me-1 text-green-500"></i>
                ${formattedMaterial}
            </div>
            <div class="compact-item-badge">${stockQty.toLocaleString('id-ID')} PC</div>
        </div>
        <div class="compact-item-content">
            <div class="compact-item-field">
                <span class="compact-item-label">Material</span>
                <span class="compact-item-value">${formattedMaterial}</span>
                <input type="hidden" name="items[${itemCount}][material]" value="${formattedMaterial}">
            </div>
            <div class="compact-item-field">
                <span class="compact-item-label">Batch</span>
                <span class="compact-item-value">${batch || '-'}</span>
                <input type="hidden" name="items[${itemCount}][batch]" value="${batch}">
            </div>
            <div class="compact-item-field">
                <span class="compact-item-label">Pack Quantity <span class="text-danger">*</span></span>
                <input type="number" class="compact-item-input" name="items[${itemCount}][pack_qty]"
                       placeholder="Masukkan quantity" step="0.001" min="0.001" max="${stockQty}"
                       required data-max-qty="${stockQty}">
            </div>
            <div class="compact-item-field">
                <span class="compact-item-label">Sales Order No</span>
                <span class="compact-item-value">${salesOrderNo || '-'}</span>
                <input type="hidden" name="items[${itemCount}][sp_stck_no]" value="${salesOrderNo}">
                <input type="hidden" name="items[${itemCount}][plant]" value="${plant}">
                <input type="hidden" name="items[${itemCount}][storage_location]" value="${storageLocation}">
            </div>
        </div>
        ${materialDescription ? `
            <div class="compact-item-field mt-2">
                <span class="compact-item-label">Deskripsi</span>
                <span class="compact-item-value">${materialDescription}</span>
            </div>
        ` : ''}
    `;
    container.appendChild(newItem);
    itemCount++;
}

function validateForm() {
    // Validasi: pastikan ada material
    if (itemCount === 0) {
        showMessage('Tidak ada material yang ditambahkan. Silakan pilih dari halaman utama.', 'error');
        return false;
    }

    const huExid = document.getElementById('hu_exid').value.trim();
    const packMat = document.getElementById('pack_mat').value;

    if (!huExid) {
        showMessage('HU External ID harus diisi', 'error');
        document.getElementById('hu_exid').focus();
        return false;
    }
    if (!packMat) {
        showMessage('Packaging Material harus dipilih', 'error');
        document.getElementById('pack_mat').focus();
        return false;
    }

    // Validasi Qty
    let qtyError = false;
    const qtyInputs = document.querySelectorAll('#items-container input[name*="[pack_qty]"]');

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

        input.value = enteredQty;
    });

    if (qtyError) {
        return false;
    }

    return true;
}

function resetForm() {
    if (confirm('Apakah Anda yakin ingin membatalkan? Semua data yang telah diisi akan hilang.')) {
        document.getElementById('huForm').reset();
        window.location.href = "{{ route('hu.index') }}";
    }
}

function showMessage(message, type) {
    // Hapus alert existing (kecuali yang dari Laravel session)
    const existingAlerts = document.querySelectorAll('.alert.alert-dismissible:not(.alert-success):not(.alert-danger)');
    existingAlerts.forEach(alert => alert.remove());

    const alertClass = type === 'error' ? 'alert-danger' : type === 'warning' ? 'alert-warning' : 'alert-info';
    const iconClass = type === 'error' ? 'fa-exclamation-triangle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';

    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fas ${iconClass} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);

    // Auto-hide setelah beberapa detik
    setTimeout(() => {
        const alert = document.querySelector('.alert.' + alertClass);
        if (alert && !alert.classList.contains('alert-success') && !alert.classList.contains('alert-danger')) {
            alert.remove();
        }
    }, type === 'error' ? 8000 : 5000);
}
</script>
@endpush
