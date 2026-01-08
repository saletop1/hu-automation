@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Header & Navigation -->
    <div class="row align-items-center mb-3">
        <div class="col-md-8">
            <div class="d-flex align-items-center">
                <a href="{{ route('hu.index') }}" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h4 class="fw-bold text-gray-800 mb-0">Skenario 1: Single HU</h4>
                    <small class="text-muted">1 HU = 1 Material</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-outline-danger btn-sm me-2" onclick="resetForm()">
                <i class="fas fa-times me-1"></i>Cancel
            </button>
            <button type="button" class="btn btn-primary btn-sm" id="createHuButton" disabled>
                <i class="fas fa-save me-1"></i>Create HU
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Material Information (Left Side) -->
        <div class="col-lg-5 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2">
                    <h6 class="fw-bold text-gray-800 mb-0">
                        <i class="fas fa-box me-2 text-primary"></i>Informasi Material
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-12">
                            <div id="material-status" class="alert alert-info py-2 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <div class="small">
                                        <strong>Status:</strong>
                                        <span id="status-text">Menunggu data material...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Material</label>
                            <input type="text" class="form-control form-control-sm bg-light" id="material" readonly>
                            <div class="form-text small" id="material-description"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Batch</label>
                            <input type="text" class="form-control form-control-sm bg-light" id="batch" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Plant</label>
                            <input type="text" class="form-control form-control-sm bg-light" id="plant" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Storage Location</label>
                            <input type="text" class="form-control form-control-sm bg-light" id="stge_loc" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Stock Quantity</label>
                            <input type="text" class="form-control form-control-sm bg-light" id="stock_quantity" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Sales Order No</label>
                            <input type="text" class="form-control form-control-sm bg-light" id="sp_stck_no" readonly>
                        </div>
                    </div>

                    <!-- Material Preview -->
                    <div class="mt-3 pt-3 border-top">
                        <h6 class="fw-bold small text-gray-800 mb-2">Preview Material</h6>
                        <div id="materialPreview" class="text-muted small">
                            <div class="text-center py-2">
                                <i class="fas fa-box-open text-gray-400 mb-1"></i>
                                <p class="mb-0">Data material akan ditampilkan di sini</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- HU Creation Form (Right Side) -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-2">
                    <h6 class="fw-bold text-gray-800 mb-0">
                        <i class="fas fa-edit me-2 text-primary"></i>Informasi Pembuatan HU
                    </h6>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('hu.store-single') }}" method="POST" id="huForm">
                        @csrf
                        <input type="hidden" id="base_unit_qty" name="base_unit_qty" value="">
                        <input type="hidden" id="sap_user" name="sap_user" value="">
                        <input type="hidden" id="sap_password" name="sap_password" value="">

                        <!-- Hidden fields -->
                        <input type="hidden" name="material" id="hidden_material">
                        <input type="hidden" name="plant" id="hidden_plant">
                        <input type="hidden" name="stge_loc" id="hidden_stge_loc">
                        <input type="hidden" name="batch" id="hidden_batch">
                        <input type="hidden" name="sp_stck_no" id="hidden_sp_stck_no">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="hu_exid" class="form-label small fw-bold">
                                    HU External ID <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-barcode text-primary"></i>
                                    </span>
                                    <input type="text" class="form-control hu-exid-input"
                                           id="hu_exid" name="hu_exid" maxlength="10"
                                           placeholder="10 digit angka"
                                           oninput="validateHuExid(this)">
                                </div>
                                <div class="form-text small">
                                    <span id="hu_exid_status" class="text-muted">Masukkan 10 digit angka</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="pack_mat" class="form-label small fw-bold">
                                    Packaging Material <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm" id="pack_mat" name="pack_mat">
                                    <option value="">Pilih Packaging Material</option>
                                    <option value="VSTDPLTBW01">VSTDPLTBW01</option>
                                    <option value="VSTDPLBW002">VSTDPLBW002</option>
                                    <option value="50016873">50016873</option>
                                </select>
                                <div class="form-text small" id="pack_mat_suggestion">
                                    Pilih packaging material
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="pack_qty" class="form-label small fw-bold">
                                    Pack Quantity <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control" id="pack_qty" name="pack_qty"
                                           step="0.001" min="0.001" value="">
                                    <span class="input-group-text">PC</span>
                                </div>
                                <div class="form-text small" id="pack_qty_text">
                                    Quantity akan terisi otomatis dari stock
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="alert alert-info py-2 mt-2">
                                    <small>
                                        <i class="fas fa-info-circle me-1"></i>
                                        <strong>Tip:</strong> Quantity akan otomatis terisi dari stock material.
                                        Packaging Material akan otomatis terisi berdasarkan tipe material (ZMG1/ZMG2).
                                    </small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-6">
                                <button class="btn btn-outline-secondary w-100 btn-sm" onclick="autoFillFromStock()">
                                    <i class="fas fa-magic me-1"></i> Auto Fill
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-warning w-100 btn-sm" onclick="validateBeforeSubmit()">
                                    <i class="fas fa-check-circle me-1"></i> Validasi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal SAP Credentials -->
<div class="modal fade" id="sapCredentialsModal" tabindex="-1" aria-labelledby="sapCredentialsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title" id="sapCredentialsModalLabel">
                    <i class="fas fa-key me-2"></i>SAP Credentials
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="alert alert-info py-2 mb-2">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Masukkan kredensial SAP untuk membuat HU
                    </small>
                </div>
                <form id="sapCredentialsForm">
                    <div class="mb-2">
                        <label for="sap_user_modal" class="form-label small">SAP User <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="sap_user_modal" required>
                    </div>
                    <div class="mb-2">
                        <label for="sap_password_modal" class="form-label small">SAP Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-sm" id="sap_password_modal" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="confirmSapCredentials">
                    <i class="fas fa-check me-1"></i>Confirm & Create
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.hu-exid-input {
    border-color: #6c757d !important;
}
.hu-exid-input.valid {
    border-color: #198754 !important;
    box-shadow: 0 0 0 2px rgba(25, 135, 84, 0.1);
}
.hu-exid-input.warning {
    border-color: #ffc107 !important;
    box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.1);
}
.hu-exid-input.invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.1);
}
.status-valid { color: #198754; font-weight: 600; }
.status-warning { color: #ffc107; font-weight: 600; }
.status-invalid { color: #dc3545; font-weight: 600; }
.card-header { border-bottom: 1px solid rgba(0,0,0,.125) !important; }
</style>
@endpush

@push('scripts')
<script>
let currentMaterialData = null;

document.addEventListener('DOMContentLoaded', function() {
    loadMaterialData();

    document.getElementById('createHuButton').addEventListener('click', function() {
        if (validateForm()) {
            const modal = new bootstrap.Modal(document.getElementById('sapCredentialsModal'));
            modal.show();
        }
    });

    document.getElementById('confirmSapCredentials').addEventListener('click', confirmSapCredentials);
    document.getElementById('huForm').addEventListener('input', checkFormValidity);
});

function loadMaterialData() {
    const scenarioDataRaw = sessionStorage.getItem('scenario1_data');
    if (scenarioDataRaw) {
        try {
            const materialData = JSON.parse(scenarioDataRaw);
            if (materialData && materialData.material) {
                currentMaterialData = materialData;
                fillFormWithData(materialData);
                showMessage('Data material berhasil dimuat', 'success');
            }
        } catch (error) {
            showMessage('Error memuat data material', 'error');
        }
    } else {
        setTimeout(() => {
            window.location.href = "{{ route('hu.index') }}";
        }, 2000);
    }
}

function fillFormWithData(materialData) {
    try {
        function formatMaterialNumber(material) {
            if (!material) return '';
            if (/^\d+$/.test(material)) {
                return material.replace(/^0+/, '') || '0';
            }
            return material;
        }

        function getSalesOrderNo(item) {
            if (item.combined_sales_doc && item.combined_sales_doc !== '-') return item.combined_sales_doc;
            if (item.sales_document && item.item_number) return item.sales_document + item.item_number;
            if (item.sales_document) return item.sales_document;
            return '';
        }

        const salesOrderNo = getSalesOrderNo(materialData);
        const formattedMaterial = formatMaterialNumber(materialData.material);
        const stockQty = parseFloat(materialData.stock_quantity || '0');

        document.getElementById('material').value = formattedMaterial;
        document.getElementById('material-description').textContent = materialData.material_description || '';
        document.getElementById('plant').value = materialData.plant || '';
        document.getElementById('stge_loc').value = materialData.storage_location || '';
        document.getElementById('batch').value = materialData.batch || '';
        document.getElementById('sp_stck_no').value = salesOrderNo;
        document.getElementById('stock_quantity').value = stockQty.toLocaleString('id-ID') + ' PC';

        document.getElementById('hidden_material').value = formattedMaterial;
        document.getElementById('hidden_plant').value = materialData.plant || '';
        document.getElementById('hidden_stge_loc').value = materialData.storage_location || '';
        document.getElementById('hidden_batch').value = materialData.batch || '';
        document.getElementById('hidden_sp_stck_no').value = salesOrderNo;

        document.getElementById('pack_qty').value = stockQty;
        document.getElementById('pack_qty_text').textContent = `Auto dari stock: ${stockQty.toLocaleString('id-ID')} PC`;

        const magry = materialData.magry || '';
        const packMatSelect = document.getElementById('pack_mat');
        const suggestionElement = document.getElementById('pack_mat_suggestion');

        if (magry === 'ZMG1') {
            packMatSelect.value = '50016873';
            suggestionElement.innerHTML = `<span class="text-success small"><i class="fas fa-check-circle me-1"></i>Auto-set: 50016873 (ZMG1)</span>`;
        } else if (magry === 'ZMG2') {
            packMatSelect.value = 'VSTDPLTBW01';
            suggestionElement.innerHTML = `<span class="text-success small"><i class="fas fa-check-circle me-1"></i>Auto-set: VSTDPLTBW01 (ZMG2)</span>`;
        }

        const previewHtml = `
            <div class="small">
                <strong>Material:</strong> ${formattedMaterial}<br>
                <strong>Deskripsi:</strong> ${materialData.material_description || '-'}<br>
                <strong>Plant:</strong> ${materialData.plant || '-'} | <strong>SLoc:</strong> ${materialData.storage_location || '-'}<br>
                <strong>Batch:</strong> ${materialData.batch || '-'}<br>
                <strong>Stock:</strong> ${stockQty.toLocaleString('id-ID')} PC
                ${magry ? `<br><strong>Type:</strong> <span class="badge bg-primary">${magry}</span>` : ''}
            </div>
        `;
        document.getElementById('materialPreview').innerHTML = previewHtml;

        document.getElementById('status-text').textContent = `Material ${formattedMaterial} loaded`;
        document.getElementById('material-status').className = 'alert alert-success py-2 mb-3';

        checkFormValidity();
    } catch (error) {
        showMessage('Error memuat data: ' + error.message, 'error');
    }
}

function checkFormValidity() {
    const hasMaterial = currentMaterialData !== null;
    const huExid = document.getElementById('hu_exid').value.trim();
    const packMat = document.getElementById('pack_mat').value;
    const packQty = document.getElementById('pack_qty').value;

    const isValid = hasMaterial &&
                   huExid.length === 10 &&
                   /^\d+$/.test(huExid) &&
                   packMat &&
                   packQty &&
                   parseFloat(packQty) > 0;

    document.getElementById('createHuButton').disabled = !isValid;
}

function confirmSapCredentials() {
    const sapUser = document.getElementById('sap_user_modal').value.trim();
    const sapPassword = document.getElementById('sap_password_modal').value;

    if (!sapUser || !sapPassword) {
        showMessage('SAP User dan Password harus diisi', 'error');
        return;
    }

    document.getElementById('sap_user').value = sapUser;
    document.getElementById('sap_password').value = sapPassword;

    const confirmBtn = document.getElementById('confirmSapCredentials');
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creating...';
    confirmBtn.disabled = true;

    const sapModal = bootstrap.Modal.getInstance(document.getElementById('sapCredentialsModal'));
    sapModal.hide();

    setTimeout(() => {
        document.getElementById('huForm').submit();
    }, 500);
}

function validateForm() {
    if (!currentMaterialData) {
        showMessage('Silakan pilih material terlebih dahulu', 'error');
        return false;
    }

    const huExid = document.getElementById('hu_exid').value.trim();
    const packMat = document.getElementById('pack_mat').value;
    const packQty = document.getElementById('pack_qty').value;

    if (!huExid || huExid.length !== 10 || !/^\d+$/.test(huExid)) {
        showMessage('HU External ID harus 10 digit angka', 'error');
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

function validateHuExid(input) {
    const value = input.value;
    const statusElement = document.getElementById('hu_exid_status');

    const numericValue = value.replace(/[^0-9]/g, '');
    if (value !== numericValue) {
        input.value = numericValue;
    }

    const length = numericValue.length;
    input.classList.remove('valid', 'warning', 'invalid');

    if (length === 0) {
        statusElement.textContent = 'Masukkan 10 digit angka';
        statusElement.className = 'text-muted';
    } else if (length < 10) {
        input.classList.add('warning');
        statusElement.textContent = `Kurang ${10 - length} digit`;
        statusElement.className = 'status-warning';
    } else if (length === 10) {
        input.classList.add('valid');
        statusElement.textContent = '✓ Format valid';
        statusElement.className = 'status-valid';
    } else {
        input.classList.add('invalid');
        statusElement.textContent = 'Maksimal 10 digit';
        statusElement.className = 'status-invalid';
        input.value = numericValue.slice(0, 10);
    }

    checkFormValidity();
}

function autoFillFromStock() {
    if (!currentMaterialData) return;

    const stockQty = parseFloat(currentMaterialData.stock_quantity || '0');
    document.getElementById('pack_qty').value = stockQty;

    const timestamp = new Date().getTime();
    const huExid = timestamp.toString().slice(-10);
    document.getElementById('hu_exid').value = huExid;
    validateHuExid(document.getElementById('hu_exid'));

    showMessage('Data otomatis terisi dari stock', 'success');
}

function validateBeforeSubmit() {
    if (validateForm()) {
        showMessage('Semua data valid. Klik "Create HU" untuk melanjutkan.', 'success');
    }
}

function resetForm() {
    if (confirm('Batalkan pembuatan HU?')) {
        sessionStorage.removeItem('scenario1_data');
        window.location.href = "{{ route('hu.index') }}";
    }
}

function showMessage(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';

    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show shadow-sm mb-3" role="alert">
            <i class="fas ${icon} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);

    setTimeout(() => {
        const alert = document.querySelector('.alert.' + alertClass);
        if (alert) alert.remove();
    }, 4000);
}
</script>
@endpush
