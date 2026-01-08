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
                    <h4 class="fw-bold text-gray-800 mb-0">Skenario 2: Single HU Multi Material</h4>
                    <small class="text-muted">1 HU = Multiple Materials</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-outline-danger btn-sm me-2" onclick="resetForm()">
                <i class="fas fa-times me-1"></i>Cancel
            </button>
            <button type="button" class="btn btn-success btn-sm" id="createHuButton">
                <i class="fas fa-save me-1"></i>Create HU
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Header Information -->
        <div class="col-lg-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2">
                    <h6 class="fw-bold text-gray-800 mb-0">
                        <i class="fas fa-header me-2 text-success"></i>Header HU
                    </h6>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('hu.store-single-multi') }}" method="POST" id="huForm">
                        @csrf
                        <input type="hidden" id="base_unit_qty" name="base_unit_qty" value="">
                        <input type="hidden" id="sap_user" name="sap_user" value="">
                        <input type="hidden" id="sap_password" name="sap_password" value="">

                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small fw-bold">
                                    HU External ID <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-barcode text-success"></i>
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

                            <div class="col-12">
                                <label class="form-label small fw-bold">
                                    Packaging Material <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm" id="pack_mat" name="pack_mat">
                                    <option value="">Pilih Packaging Material</option>
                                    <option value="VSTDPLTBW01">VSTDPLTBW01</option>
                                    <option value="VSTDPLTBW02">VSTDPLTBW02</option>
                                    <option value="50016873">50016873</option>
                                </select>
                                <div class="form-text small" id="pack_mat_suggestion">
                                    Pilih packaging material
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Plant <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm bg-light"
                                       id="plant" name="plant" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Storage Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm bg-light"
                                       id="stge_loc" name="stge_loc" readonly>
                            </div>

                            <div class="col-12 mt-2">
                                <div class="alert alert-info py-2">
                                    <small>
                                        <i class="fas fa-info-circle me-1"></i>
                                        <strong>Tip:</strong> Packaging Material akan otomatis terisi berdasarkan tipe material pertama.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Materials List -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-gray-800 mb-0">
                            <i class="fas fa-list me-2 text-success"></i>Daftar Material
                            <span id="materialCount" class="badge bg-success ms-2">0 items</span>
                        </h6>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="autoFillAllQuantities()">
                            <i class="fas fa-magic me-1"></i> Auto Fill Qty
                        </button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="alert alert-info py-2 mb-3">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Data material akan terisi otomatis dari drag & drop di halaman utama
                        </small>
                    </div>

                    <!-- Items Container -->
                    <div id="items-container" class="compact-list-container">
                        <!-- Items will be dynamically added here -->
                    </div>

                    <!-- Placeholder -->
                    <div id="itemsPreview" class="text-center py-4 border-2 border-dashed rounded bg-light">
                        <i class="fas fa-boxes fa-2x text-gray-400 mb-2"></i>
                        <h6 class="text-muted mb-1">Belum Ada Material</h6>
                        <p class="text-muted small mb-0">Data material akan muncul di sini</p>
                    </div>

                    <!-- Summary -->
                    <div class="row mt-3 g-2">
                        <div class="col-md-6">
                            <div class="card bg-light border-0">
                                <div class="card-body py-2">
                                    <div class="small">
                                        <strong>Total Items:</strong> <span id="totalItemsCount">0</span><br>
                                        <strong>Total Quantity:</strong> <span id="totalQuantity">0</span> PC
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light border-0">
                                <div class="card-body py-2">
                                    <div class="small">
                                        <strong>Plant:</strong> <span id="summaryPlant">-</span><br>
                                        <strong>Storage Location:</strong> <span id="summaryStorage">-</span>
                                    </div>
                                </div>
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
.compact-list-container { display: none; }
.compact-item {
    background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; padding: 8px; margin-bottom: 6px;
    border-left: 3px solid #28a745;
}
.compact-item-header {
    display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;
}
.compact-item-title {
    font-weight: 600; color: #333; font-size: 0.8rem;
}
.compact-item-badge {
    background: #28a745; color: white; padding: 1px 6px; border-radius: 10px; font-size: 0.7rem;
}
.compact-item-content {
    display: grid; grid-template-columns: 1fr 1fr; gap: 6px; font-size: 0.75rem;
}
.compact-item-field { display: flex; flex-direction: column; }
.compact-item-label { font-weight: 500; color: #6c757d; font-size: 0.7rem; }
.compact-item-value { color: #333; }
.compact-item-input {
    width: 100%; padding: 4px 6px; border: 1px solid #ced4da; border-radius: 3px; font-size: 0.75rem;
}
.border-dashed { border-style: dashed !important; }
</style>
@endpush

@push('scripts')
<script>
let itemCount = 0;
let materialsData = [];

document.addEventListener('DOMContentLoaded', function() {
    loadMaterialsFromSession();

    document.getElementById('createHuButton').addEventListener('click', function() {
        if (validateForm()) {
            const modal = new bootstrap.Modal(document.getElementById('sapCredentialsModal'));
            modal.show();
        }
    });

    document.getElementById('confirmSapCredentials').addEventListener('click', confirmSapCredentials);
    document.getElementById('hu_exid').addEventListener('focus', function() {
        if (!this.value) {
            const timestamp = new Date().getTime();
            this.value = timestamp.toString().slice(-10);
            validateHuExid(this);
        }
    });
});

function loadMaterialsFromSession() {
    const scenarioDataRaw = sessionStorage.getItem('scenario2_data');
    if (scenarioDataRaw) {
        try {
            materialsData = JSON.parse(scenarioDataRaw);
            if (Array.isArray(materialsData) && materialsData.length > 0) {
                renderMaterials();
            }
        } catch (error) {
            showMessage('Error memuat data material', 'error');
        }
    }
}

function renderMaterials() {
    const container = document.getElementById('items-container');
    const placeholder = document.getElementById('itemsPreview');

    container.innerHTML = '';
    itemCount = 0;

    if (materialsData.length === 0) {
        placeholder.style.display = 'block';
        container.style.display = 'none';
        updateSummary();
        return;
    }

    placeholder.style.display = 'none';
    container.style.display = 'block';

    materialsData.forEach((item, index) => {
        addItemToForm(item, index);
    });

    updateSummary();
}

function addItemToForm(item, index) {
    const container = document.getElementById('items-container');

    const formattedMaterial = formatMaterialNumber(item.material);
    const stockQty = parseFloat(item.stock_quantity || '0');
    const salesOrderNo = getSalesOrderNo(item);

    const newItem = document.createElement('div');
    newItem.className = 'compact-item';
    newItem.innerHTML = `
        <div class="compact-item-header">
            <div class="compact-item-title">
                <i class="fas fa-box me-1 text-success"></i>
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
                <span class="compact-item-value">${item.batch || '-'}</span>
                <input type="hidden" name="items[${itemCount}][batch]" value="${item.batch || ''}">
            </div>
            <div class="compact-item-field">
                <span class="compact-item-label">Pack Quantity <span class="text-danger">*</span></span>
                <input type="number" class="compact-item-input" name="items[${itemCount}][pack_qty]"
                       placeholder="Qty" step="0.001" min="0.001" max="${stockQty}"
                       required data-max-qty="${stockQty}" value="${stockQty}">
            </div>
            <div class="compact-item-field">
                <span class="compact-item-label">Sales Order</span>
                <span class="compact-item-value">${salesOrderNo || '-'}</span>
                <input type="hidden" name="items[${itemCount}][sp_stck_no]" value="${salesOrderNo || ''}">
                <input type="hidden" name="items[${itemCount}][plant]" value="${item.plant || ''}">
                <input type="hidden" name="items[${itemCount}][storage_location]" value="${item.storage_location || ''}">
            </div>
        </div>
        ${item.material_description ? `
            <div class="compact-item-field mt-1">
                <span class="compact-item-label">Deskripsi</span>
                <span class="compact-item-value small">${item.material_description}</span>
            </div>
        ` : ''}
    `;
    container.appendChild(newItem);
    itemCount++;

    document.getElementById('materialCount').textContent = `${materialsData.length} items`;
}

function updateSummary() {
    const plant = materialsData[0]?.plant || '-';
    const storage = materialsData[0]?.storage_location || '-';

    document.getElementById('plant').value = plant;
    document.getElementById('stge_loc').value = storage;
    document.getElementById('summaryPlant').textContent = plant;
    document.getElementById('summaryStorage').textContent = storage;
    document.getElementById('totalItemsCount').textContent = materialsData.length;

    let totalQty = 0;
    document.querySelectorAll('.compact-item-input[name*="[pack_qty]"]').forEach(input => {
        totalQty += parseFloat(input.value || 0);
    });

    document.getElementById('totalQuantity').textContent = totalQty.toLocaleString('id-ID');

    autoSetPackagingMaterial();
}

function autoSetPackagingMaterial() {
    if (materialsData.length === 0) return;

    const firstItem = materialsData[0];
    const magry = firstItem.magry || '';
    const packMatSelect = document.getElementById('pack_mat');
    const suggestionElement = document.getElementById('pack_mat_suggestion');

    packMatSelect.value = '';
    suggestionElement.innerHTML = '<span class="text-muted small">Pilih packaging material</span>';

    if (magry === 'ZMG1') {
        packMatSelect.value = '50016873';
        suggestionElement.innerHTML = `<span class="text-success small"><i class="fas fa-check-circle me-1"></i>Auto-set: 50016873 (ZMG1)</span>`;
    } else if (magry === 'ZMG2') {
        packMatSelect.value = 'VSTDPLTBW01';
        suggestionElement.innerHTML = `<span class="text-success small"><i class="fas fa-check-circle me-1"></i>Auto-set: VSTDPLTBW01 (ZMG2)</span>`;
    }
}

function autoFillAllQuantities() {
    document.querySelectorAll('.compact-item-input[name*="[pack_qty]"]').forEach(input => {
        const maxQty = parseFloat(input.dataset.maxQty || '0');
        input.value = maxQty;
    });
    updateSummary();
    showMessage('Semua quantity diisi dengan stock maksimum', 'success');
}

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
    const huExid = document.getElementById('hu_exid').value.trim();
    if (!huExid || huExid.length !== 10 || !/^\d+$/.test(huExid)) {
        showMessage('HU External ID harus 10 digit angka', 'error');
        return false;
    }

    if (materialsData.length === 0) {
        showMessage('Tidak ada material yang ditambahkan', 'error');
        return false;
    }

    const packMat = document.getElementById('pack_mat').value;
    if (!packMat) {
        showMessage('Packaging Material harus dipilih', 'error');
        return false;
    }

    let qtyError = false;
    document.querySelectorAll('.compact-item-input[name*="[pack_qty]"]').forEach((input, index) => {
        const maxQty = parseFloat(input.dataset.maxQty);
        const qty = parseFloat(input.value || 0);

        if (!input.value || isNaN(qty) || qty <= 0) {
            showMessage(`Pack Quantity untuk item ${index + 1} harus lebih dari 0`, 'error');
            input.focus();
            qtyError = true;
            return;
        }

        if (qty > maxQty) {
            showMessage(`Quantity (${qty}) melebihi stock (${maxQty}) untuk item ${index + 1}`, 'error');
            input.focus();
            qtyError = true;
            return;
        }
    });

    return !qtyError;
}

function resetForm() {
    if (confirm('Batalkan pembuatan HU?')) {
        sessionStorage.removeItem('scenario2_data');
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
