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
                    <button type="button" class="btn btn-primary px-4" id="createHuButton">
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
                <div class="bg-blue-100 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                    <i class="fas fa-cube text-blue-600 fa-lg"></i>
                </div>
                <div>
                    <h1 class="h3 fw-bold text-gray-800 mb-1">Skenario 1</h1>
                    <p class="text-muted mb-0">Buat Single HU (1 HU = 1 Material)</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xxl-10 col-xl-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-gray-800">
                        <i class="fas fa-info-circle me-2 text-blue-500"></i>
                        Informasi Handling Unit
                    </h5>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('hu.store-single') }}" method="POST" id="huForm">
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
                                        <i class="fas fa-barcode text-blue-500"></i>
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

                        <!-- Material Information -->
                        <div class="row">
                            <div class="col-12 mb-3">
                                <h6 class="fw-semibold text-gray-700 mb-3 border-bottom pb-2">
                                    <i class="fas fa-box me-2 text-green-500"></i>
                                    Informasi Material
                                </h6>
                                <div class="alert alert-info bg-light border-0 py-2">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Data material akan terisi otomatis dari drag & drop di halaman utama
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="material" class="form-label fw-semibold text-gray-700">
                                    Material <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control bg-light" id="material" name="material"
                                       value="{{ old('material') }}" required readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="batch" class="form-label fw-semibold text-gray-700">Batch</label>
                                <input type="text" class="form-control bg-light" id="batch" name="batch"
                                       value="{{ old('batch') }}" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="pack_qty" class="form-label fw-semibold text-gray-700">
                                    Pack Quantity <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control bg-light" id="pack_qty" name="pack_qty"
                                       step="0.001" min="0.001" value="{{ old('pack_qty') }}" required readonly>
                                <div class="form-text text-muted" id="pack_qty_text">Quantity akan terisi otomatis</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="sp_stck_no" class="form-label fw-semibold text-gray-700">Sales Order No</label>
                                <input type="text" class="form-control bg-light" id="sp_stck_no" name="sp_stck_no"
                                       value="{{ old('sp_stck_no') }}" readonly>
                            </div>
                        </div>

                        <!-- Preview Section -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="fw-semibold text-gray-700 mb-3">
                                            <i class="fas fa-eye me-2 text-purple-500"></i>
                                            Preview Data Material
                                        </h6>
                                        <div id="materialPreview" class="text-muted">
                                            <div class="text-center py-4">
                                                <i class="fas fa-box-open fa-2x text-gray-400 mb-2"></i>
                                                <p class="mb-0">Data material akan ditampilkan di sini setelah dipilih dari halaman utama</p>
                                            </div>
                                        </div>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== SKENARIO 1 LOADED ===');

    // Cek jika ada pesan sukses dari server dan clear sessionStorage
    const serverSuccessAlert = document.querySelector('.alert-success');
    const serverErrorAlert = document.querySelector('.alert-danger');

    if (serverSuccessAlert) {
        console.log('✅ HU created successfully, clearing sessionStorage');
        sessionStorage.removeItem('scenario1_data');

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
    const scenarioDataRaw = sessionStorage.getItem('scenario1_data');
    console.log('Raw data from sessionStorage:', scenarioDataRaw);

    if (scenarioDataRaw && !serverSuccessAlert && !serverErrorAlert) {
        try {
            const materials = JSON.parse(scenarioDataRaw);
            console.log('Parsed materials:', materials);

            if (materials && Array.isArray(materials) && materials.length > 0) {
                console.log('✅ Data valid, processing materials...');
                fillFormWithData(materials[0]);
                console.log('✅ Data loaded from sessionStorage');
            } else {
                console.warn('❌ Data invalid');
                showMessage('Data material tidak valid. Silakan pilih ulang dari halaman utama.', 'warning');
            }
        } catch (error) {
            console.error('❌ Error parsing scenario data:', error);
            showMessage('Error memuat data material. Silakan pilih ulang.', 'error');
        }
    } else {
        if (!scenarioDataRaw && !serverSuccessAlert && !serverErrorAlert) {
            console.warn('❌ No data found in sessionStorage');
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
            this.value = 'HU1_' + timestamp.toString().slice(-8);
        }
    });
});

function fillFormWithData(material) {
    console.log('Filling form with data:', material);

    function formatMaterialNumber(material) {
        if (!material) return '';
        if (/^\d+$/.test(material)) {
            return material.replace(/^0+/, '') || '0';
        }
        return material;
    }

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

    const salesOrderNo = getSalesOrderNo(material);

    // Isi form fields
    document.getElementById('plant').value = material.plant || '';
    document.getElementById('material').value = formatMaterialNumber(material.material) || '';
    document.getElementById('batch').value = material.batch || '';
    document.getElementById('stge_loc').value = material.storage_location || '';

    // Isi Qty otomatis
    const stockQty = parseFloat(material.stock_quantity || '0');
    document.getElementById('pack_qty').value = stockQty;
    document.getElementById('pack_qty_text').textContent = `Stock tersedia: ${stockQty.toLocaleString('id-ID')}`;

    document.getElementById('sp_stck_no').value = salesOrderNo;

    // Update preview
    const previewHtml = `
        <div class="row">
            <div class="col-md-6">
                <strong>Material:</strong> ${formatMaterialNumber(material.material)}<br>
                <strong>Deskripsi:</strong> ${material.material_description || '-'}<br>
                <strong>Plant:</strong> ${material.plant || '-'}
            </div>
            <div class="col-md-6">
                <strong>Storage Location:</strong> ${material.storage_location || '-'}<br>
                <strong>Batch:</strong> ${material.batch || '-'}<br>
                <strong>Stock Quantity:</strong> ${stockQty.toLocaleString('id-ID')} PC
            </div>
        </div>
        ${salesOrderNo ?
            `<div class="row mt-2">
                <div class="col-12">
                    <strong>Sales Order No:</strong> ${salesOrderNo}
                </div>
            </div>` : ''
        }
    `;
    document.getElementById('materialPreview').innerHTML = previewHtml;
    document.getElementById('materialPreview').className = 'text-gray-800';
}

function validateForm() {
    const huExid = document.getElementById('hu_exid').value.trim();
    const packMat = document.getElementById('pack_mat').value;
    const packQty = document.getElementById('pack_qty').value;
    const material = document.getElementById('material').value.trim();

    if (!material) {
        showMessage('Material kosong. Silakan pilih material dari halaman utama.', 'error');
        return false;
    }
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
    if (!packQty || parseFloat(packQty) <= 0) {
        showMessage('Pack Quantity harus lebih dari 0', 'error');
        document.getElementById('pack_qty').focus();
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
