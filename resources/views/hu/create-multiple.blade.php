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
                    <h4 class="fw-bold text-gray-800 mb-0">Skenario 3: Multiple HU</h4>
                    <small class="text-muted">Flexible Quantity - Multiple HUs</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-outline-danger btn-sm me-2" onclick="resetForm()">
                <i class="fas fa-times me-1"></i>Cancel
            </button>
            <button type="button" class="btn btn-purple btn-sm" id="createHuButton">
                <i class="fas fa-save me-1"></i>Create All HUs
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Control Panel -->
        <div class="col-lg-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2">
                    <h6 class="fw-bold text-gray-800 mb-0">
                        <i class="fas fa-cogs me-2 text-purple"></i>Control Panel
                    </h6>
                </div>
                <div class="card-body p-3">
                    <!-- Mode Selection -->
                    <div class="mb-3">
                        <h6 class="fw-bold small text-gray-800 mb-2">Pilih Mode</h6>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="creationMode" id="modeSplit" value="split" checked>
                            <label class="btn btn-outline-primary btn-sm" for="modeSplit">
                                <i class="fas fa-cubes me-1"></i>Split
                            </label>

                            <input type="radio" class="btn-check" name="creationMode" id="modeSingle" value="single">
                            <label class="btn btn-outline-success btn-sm" for="modeSingle">
                                <i class="fas fa-cube me-1"></i>Single
                            </label>

                            <input type="radio" class="btn-check" name="creationMode" id="modePartial" value="partial">
                            <label class="btn btn-outline-warning btn-sm" for="modePartial">
                                <i class="fas fa-sliders me-1"></i>Partial
                            </label>
                        </div>
                        <div class="form-text small mt-1">
                            <span id="modeDescription">Setiap 1 PC = 1 HU terpisah</span>
                        </div>
                    </div>

                    <!-- Partial Settings -->
                    <div id="partialSettings" class="mb-3 d-none">
                        <h6 class="fw-bold small text-gray-800 mb-2">Pengaturan Partial</h6>
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small">Total Quantity</label>
                                <input type="number" class="form-control form-control-sm" id="partialTotalQty"
                                       placeholder="Jumlah PC" min="1">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Quantity per HU</label>
                                <select class="form-select form-select-sm" id="partialQtyPerHU">
                                    <option value="1">1 PC per HU</option>
                                    <option value="2">2 PC per HU</option>
                                    <option value="5">5 PC per HU</option>
                                    <option value="10">10 PC per HU</option>
                                    <option value="custom">Custom...</option>
                                </select>
                                <div id="customQtyContainer" class="mt-1 d-none">
                                    <input type="number" class="form-control form-control-sm" id="customQtyPerHU"
                                           placeholder="Custom Qty" min="1">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Global Settings -->
                    <div class="mb-3">
                        <h6 class="fw-bold small text-gray-800 mb-2">Global Settings</h6>
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small">Start HU External ID</label>
                                <input type="text" class="form-control form-control-sm" id="startHuExid"
                                       placeholder="9900000001" maxlength="10" pattern="\d{10}">
                                <div class="form-text small">Isi manual, akan auto sequence</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Global Packaging Material</label>
                                <select class="form-select form-select-sm" id="globalPackMat">
                                    <option value="">Pilih (Apply ke Semua)</option>
                                    <option value="VSTDPLTBW01">VSTDPLTBW01</option>
                                    <option value="VSTDPLBW002">VSTDPLBW002</option>
                                    <option value="50016873">50016873</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-3">
                        <button class="btn btn-outline-primary w-100 btn-sm mb-2" onclick="generateHUs()">
                            <i class="fas fa-sync me-1"></i> Generate HUs
                        </button>
                        <button class="btn btn-outline-success w-100 btn-sm" onclick="applyGlobalSettings()">
                            <i class="fas fa-magic me-1"></i> Apply Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- HUs Preview -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-gray-800 mb-0">
                            <i class="fas fa-pallet me-2 text-purple"></i>Preview HUs
                            <span id="huCount" class="badge bg-purple ms-2">0 HUs</span>
                            <span id="totalQty" class="badge bg-success ms-2">0 PC</span>
                        </h6>
                        <div class="small text-muted">
                            <span id="modeBadge" class="badge bg-primary">Split Mode</span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="alert alert-info py-2 mb-3">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            HUs akan digenerate berdasarkan mode dan material yang dipilih
                        </small>
                    </div>

                    <!-- HUs Container -->
                    <div id="hus-container" class="compact-hus-container">
                        <!-- HUs will be dynamically added here -->
                    </div>

                    <!-- Placeholder -->
                    <div id="husPreview" class="text-center py-4 border-2 border-dashed rounded bg-light">
                        <i class="fas fa-pallet fa-2x text-gray-400 mb-2"></i>
                        <h6 class="text-muted mb-1">Belum Ada HU</h6>
                        <p class="text-muted small mb-0">Generate HUs terlebih dahulu</p>
                    </div>

                    <!-- Stock Validation -->
                    <div id="stockValidation" class="alert alert-warning mt-3 d-none">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="stockValidationMessage"></span>
                    </div>

                    <!-- Summary -->
                    <div class="row mt-3 g-2">
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body py-2 text-center">
                                    <div class="small">
                                        <strong>Total HUs</strong><br>
                                        <span class="fw-bold fs-5" id="summaryHuCount">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body py-2 text-center">
                                    <div class="small">
                                        <strong>Total Quantity</strong><br>
                                        <span class="fw-bold fs-5" id="summaryTotalQty">0</span> PC
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body py-2 text-center">
                                    <div class="small">
                                        <strong>Mode</strong><br>
                                        <span class="badge bg-primary" id="summaryMode">Split</span>
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

<!-- Form Hidden -->
<form action="{{ route('hu.store-multiple') }}" method="POST" id="huForm" class="d-none">
    @csrf
    <input type="hidden" id="base_unit_qty" name="base_unit_qty" value="">
    <input type="hidden" id="sap_user" name="sap_user" value="">
    <input type="hidden" id="sap_password" name="sap_password" value="">
    <input type="hidden" id="creation_mode" name="creation_mode" value="split">
    <input type="hidden" id="total_hus" name="total_hus" value="0">
    <!-- HUs will be dynamically added -->
</form>

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
                    <i class="fas fa-check me-1"></i>Confirm & Create All
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.btn-purple { background-color: #8b5cf6; border-color: #8b5cf6; color: white; }
.btn-purple:hover { background-color: #7c3aed; border-color: #7c3aed; }
.compact-hus-container { display: none; }
.compact-hu-item {
    background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; padding: 8px; margin-bottom: 6px;
    border-left: 3px solid #8b5cf6;
}
.compact-hu-header {
    display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;
}
.compact-hu-title {
    font-weight: 600; color: #333; font-size: 0.8rem;
}
.compact-hu-badge {
    background: #8b5cf6; color: white; padding: 1px 6px; border-radius: 10px; font-size: 0.7rem;
}
.compact-hu-content {
    display: grid; grid-template-columns: 1fr 1fr; gap: 6px; font-size: 0.75rem;
}
.compact-hu-field { display: flex; flex-direction: column; }
.compact-hu-label { font-weight: 500; color: #6c757d; font-size: 0.7rem; }
.compact-hu-value { color: #333; }
.compact-hu-input {
    width: 100%; padding: 4px 6px; border: 1px solid #ced4da; border-radius: 3px; font-size: 0.75rem;
}
.quantity-badge {
    background: #10b981; color: white; padding: 1px 4px; border-radius: 8px; font-size: 0.65rem;
    margin-left: 4px;
}
.split-indicator {
    background: #f59e0b; color: white; padding: 1px 4px; border-radius: 8px; font-size: 0.65rem;
    margin-left: 4px;
}
.border-dashed { border-style: dashed !important; }
</style>
@endpush

@push('scripts')
<script>
let huCount = 0;
let totalQuantity = 0;
let creationMode = 'split';
let currentMaterials = [];

document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    setupEventListeners();
    loadMaterialsFromSession();
}

function setupEventListeners() {
    // Mode selection
    document.querySelectorAll('input[name="creationMode"]').forEach(radio => {
        radio.addEventListener('change', function() {
            creationMode = this.value;
            updateModeDisplay();
            if (currentMaterials.length > 0) {
                generateHUs();
            }
        });
    });

    // Partial settings
    document.getElementById('partialQtyPerHU').addEventListener('change', function() {
        document.getElementById('customQtyContainer').classList.toggle('d-none', this.value !== 'custom');
    });

    // Create button
    document.getElementById('createHuButton').addEventListener('click', function() {
        if (validateForm()) {
            const modal = new bootstrap.Modal(document.getElementById('sapCredentialsModal'));
            modal.show();
        }
    });

    // SAP credentials
    document.getElementById('confirmSapCredentials').addEventListener('click', function() {
        const sapUser = document.getElementById('sap_user_modal').value.trim();
        const sapPassword = document.getElementById('sap_password_modal').value;

        if (!sapUser || !sapPassword) {
            showMessage('SAP User dan Password harus diisi', 'error');
            return;
        }

        document.getElementById('sap_user').value = sapUser;
        document.getElementById('sap_password').value = sapPassword;
        document.getElementById('creation_mode').value = creationMode;
        document.getElementById('total_hus').value = huCount;

        const confirmBtn = document.getElementById('confirmSapCredentials');
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creating...';
        confirmBtn.disabled = true;

        const sapModal = bootstrap.Modal.getInstance(document.getElementById('sapCredentialsModal'));
        sapModal.hide();

        setTimeout(() => {
            document.getElementById('huForm').submit();
        }, 500);
    });
}

function loadMaterialsFromSession() {
    const scenarioDataRaw = sessionStorage.getItem('scenario3_data');
    if (scenarioDataRaw) {
        try {
            currentMaterials = JSON.parse(scenarioDataRaw);
            if (Array.isArray(currentMaterials) && currentMaterials.length > 0) {
                showMessage(`${currentMaterials.length} material loaded`, 'success');
                generateHUs();
            }
        } catch (error) {
            showMessage('Error memuat data material', 'error');
        }
    }
}

function updateModeDisplay() {
    const modeDescriptions = {
        'split': 'Setiap 1 PC = 1 HU terpisah',
        'single': 'Semua quantity digabung dalam 1 HU per material',
        'partial': 'Buat HU dengan quantity custom'
    };

    document.getElementById('modeDescription').textContent = modeDescriptions[creationMode];
    document.getElementById('modeBadge').textContent = creationMode.charAt(0).toUpperCase() + creationMode.slice(1) + ' Mode';
    document.getElementById('summaryMode').textContent = creationMode.charAt(0).toUpperCase() + creationMode.slice(1);

    document.getElementById('partialSettings').classList.toggle('d-none', creationMode !== 'partial');
}

function generateHUs() {
    if (currentMaterials.length === 0) {
        showMessage('Tidak ada material yang dipilih', 'warning');
        return;
    }

    clearHUs();

    const groupedMaterials = groupMaterials(currentMaterials);
    Object.values(groupedMaterials).forEach(group => {
        processMaterialGroup(group);
    });

    updateSummary();
    setupAutoSequence();
}

function groupMaterials(materials) {
    const groups = {};
    materials.forEach(item => {
        const key = `${item.material}_${item.batch || ''}_${item.plant}_${item.storage_location}`;
        if (!groups[key]) {
            groups[key] = {
                material: item.material,
                materialDescription: item.material_description || '',
                batch: item.batch || '',
                plant: item.plant || '3000',
                storageLocation: item.storage_location || '3D10',
                salesOrderNo: getSalesOrderNo(item),
                totalQuantity: 0,
                items: []
            };
        }
        const quantity = parseFloat(item.stock_quantity || '0');
        groups[key].totalQuantity += quantity;
        groups[key].items.push({ ...item, quantity: quantity });
    });
    return groups;
}

function processMaterialGroup(group) {
    const availableStock = group.totalQuantity;

    switch(creationMode) {
        case 'split':
            for (let i = 0; i < availableStock; i++) {
                addHU(group, i + 1, availableStock, true, 1);
            }
            break;

        case 'single':
            addHU(group, 1, 1, false, availableStock);
            break;

        case 'partial':
            const partialSettings = getPartialSettings();
            if (partialSettings) {
                const { totalQty, qtyPerHU } = partialSettings;
                const actualTotalQty = Math.min(totalQty, availableStock);
                const numberOfHUs = Math.ceil(actualTotalQty / qtyPerHU);

                for (let i = 0; i < numberOfHUs; i++) {
                    const isLastHU = i === numberOfHUs - 1;
                    const huQty = isLastHU ? actualTotalQty - (i * qtyPerHU) : qtyPerHU;
                    addHU(group, i + 1, numberOfHUs, numberOfHUs > 1, huQty);
                }
            }
            break;
    }
}

function getPartialSettings() {
    const totalQtyInput = document.getElementById('partialTotalQty');
    const qtyPerHUSelect = document.getElementById('partialQtyPerHU');

    if (!totalQtyInput.value) {
        showMessage('Silakan isi total quantity untuk mode partial', 'warning');
        return null;
    }

    const totalQty = parseInt(totalQtyInput.value);
    if (totalQty <= 0) return null;

    let qtyPerHU;
    if (qtyPerHUSelect.value === 'custom') {
        const customQty = document.getElementById('customQtyPerHU').value;
        if (!customQty || customQty <= 0) return null;
        qtyPerHU = parseInt(customQty);
    } else {
        qtyPerHU = parseInt(qtyPerHUSelect.value);
    }

    if (qtyPerHU <= 0) return null;

    return { totalQty, qtyPerHU };
}

function addHU(group, sequence, totalHUs, isSplit, quantity) {
    const container = document.getElementById('hus-container');
    const formattedMaterial = formatMaterialNumber(group.material);

    const startHuExid = document.getElementById('startHuExid').value || '9900000000';
    const huExid = (parseInt(startHuExid) + huCount).toString().padStart(10, '0');

    const newHU = document.createElement('div');
    newHU.className = 'compact-hu-item';

    let splitIndicator = '';
    if (isSplit && totalHUs > 1) {
        splitIndicator = `<span class="split-indicator">${sequence}/${totalHUs}</span>`;
    }

    let quantityBadge = '';
    if (quantity > 1 && !isSplit) {
        quantityBadge = `<span class="quantity-badge">${quantity} PCs</span>`;
    }

    newHU.innerHTML = `
        <div class="compact-hu-header">
            <div class="compact-hu-title">
                <i class="fas fa-pallet me-1 text-purple"></i>
                HU ${huCount + 1}: ${formattedMaterial}
                ${splitIndicator}
                ${quantityBadge}
            </div>
            <div class="compact-hu-badge">${quantity.toLocaleString('id-ID')} PC</div>
        </div>
        <div class="compact-hu-content">
            <div class="compact-hu-field">
                <span class="compact-hu-label">HU External ID</span>
                <input type="text" class="compact-hu-input hu-exid-input"
                       name="hus[${huCount}][hu_exid]" value="${huExid}"
                       required maxlength="10" pattern="\\d{10}">
            </div>
            <div class="compact-hu-field">
                <span class="compact-hu-label">Packaging Material</span>
                <select class="compact-hu-input pack-mat-select"
                        name="hus[${huCount}][pack_mat]">
                    <option value="">Pilih</option>
                    <option value="VSTDPLTBW01">VSTDPLTBW01</option>
                    <option value="VSTDPLBW002">VSTDPLBW002</option>
                    <option value="50016873">50016873</option>
                </select>
            </div>
            <div class="compact-hu-field">
                <span class="compact-hu-label">Material</span>
                <input type="text" class="compact-hu-input bg-light"
                       name="hus[${huCount}][material]" value="${formattedMaterial}" readonly>
            </div>
            <div class="compact-hu-field">
                <span class="compact-hu-label">Batch</span>
                <input type="text" class="compact-hu-input bg-light"
                       name="hus[${huCount}][batch]" value="${group.batch}" readonly>
            </div>
            <div class="compact-hu-field">
                <span class="compact-hu-label">Pack Quantity</span>
                <input type="number" class="compact-hu-input"
                       name="hus[${huCount}][pack_qty]" value="${quantity}" step="0.001" readonly>
            </div>
            <div class="compact-hu-field">
                <span class="compact-hu-label">Sales Order</span>
                <input type="text" class="compact-hu-input bg-light"
                       name="hus[${huCount}][sp_stck_no]" value="${group.salesOrderNo}" readonly>
            </div>
            <div class="compact-hu-field">
                <span class="compact-hu-label">Plant</span>
                <input type="text" class="compact-hu-input bg-light"
                       name="hus[${huCount}][plant]" value="${group.plant}" readonly>
            </div>
            <div class="compact-hu-field">
                <span class="compact-hu-label">Storage Location</span>
                <input type="text" class="compact-hu-input bg-light"
                       name="hus[${huCount}][stge_loc]" value="${group.storageLocation}" readonly>
            </div>
        </div>
        ${group.materialDescription ? `
            <div class="compact-hu-field mt-1">
                <span class="compact-hu-label">Deskripsi</span>
                <span class="compact-hu-value small">${group.materialDescription}</span>
            </div>
        ` : ''}
    `;

    container.appendChild(newHU);

    // Add to form
    const form = document.getElementById('huForm');
    const huDiv = document.createElement('div');
    huDiv.innerHTML = newHU.innerHTML;
    form.appendChild(huDiv);

    huCount++;
    totalQuantity += quantity;

    // Update container visibility
    document.getElementById('husPreview').style.display = 'none';
    document.getElementById('hus-container').style.display = 'block';
}

function clearHUs() {
    huCount = 0;
    totalQuantity = 0;
    document.getElementById('hus-container').innerHTML = '';
    document.getElementById('huForm').querySelectorAll('.compact-hu-item').forEach(el => el.remove());
    document.getElementById('husPreview').style.display = 'block';
    document.getElementById('hus-container').style.display = 'none';
}

function updateSummary() {
    document.getElementById('huCount').textContent = `${huCount} HUs`;
    document.getElementById('totalQty').textContent = `${totalQuantity.toLocaleString('id-ID')} PC`;
    document.getElementById('summaryHuCount').textContent = huCount;
    document.getElementById('summaryTotalQty').textContent = totalQuantity.toLocaleString('id-ID');
}

function setupAutoSequence() {
    const startHuExid = document.getElementById('startHuExid').value;
    if (startHuExid && /^\d{10}$/.test(startHuExid)) {
        document.querySelectorAll('.hu-exid-input').forEach((input, index) => {
            const newNumber = parseInt(startHuExid) + index;
            input.value = newNumber.toString().padStart(10, '0');
        });
    }
}

function applyGlobalSettings() {
    const globalPackMat = document.getElementById('globalPackMat').value;
    if (globalPackMat) {
        document.querySelectorAll('.pack-mat-select').forEach(select => {
            select.value = globalPackMat;
        });
        showMessage(`Packaging Material "${globalPackMat}" diterapkan ke semua HU`, 'success');
    }
}

function validateForm() {
    if (huCount === 0) {
        showMessage('Tidak ada HU yang digenerate', 'error');
        return false;
    }

    let validationError = false;

    document.querySelectorAll('.hu-exid-input').forEach((input, index) => {
        if (!/^\d{10}$/.test(input.value.trim())) {
            showMessage(`HU External ID untuk HU ${index + 1} harus 10 digit angka`, 'error');
            input.focus();
            validationError = true;
        }
    });

    if (validationError) return false;

    document.querySelectorAll('.pack-mat-select').forEach((select, index) => {
        if (!select.value) {
            showMessage(`Packaging Material untuk HU ${index + 1} harus dipilih`, 'error');
            select.focus();
            validationError = true;
        }
    });

    return !validationError;
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

function resetForm() {
    if (confirm('Batalkan pembuatan HU?')) {
        sessionStorage.removeItem('scenario3_data');
        window.location.href = "{{ route('hu.index') }}";
    }
}

function showMessage(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : type === 'warning' ? 'alert-warning' : 'alert-danger';
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
