@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Alert Messages -->
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

    <!-- Action Buttons -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('hu.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger btn-sm px-3" onclick="resetForm()">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-primary btn-sm px-3" id="createHuButton">
                        <i class="fas fa-save me-1"></i>Create All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Header Section -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex align-items-center">
                <div class="bg-purple-100 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                    <i class="fas fa-pallet text-purple-600"></i>
                </div>
                <div>
                    <h1 class="h4 fw-bold text-gray-800 mb-0">Skenario 3</h1>
                    <p class="text-muted small mb-0">Create Multiple HUs (Flexible Quantity)</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xxl-10 col-xl-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-2 border-bottom">
                    <h6 class="card-title mb-0 fw-bold text-gray-800">
                        <i class="fas fa-pallet me-1 text-purple-500"></i>
                        Create Multiple Handling Units
                    </h6>
                </div>

                <div class="card-body p-3">
                    <!-- Form harus mencakup kedua kolom -->
                    <form action="{{ route('hu.store-multiple') }}" method="POST" id="huForm">
                        @csrf
                        <input type="hidden" id="base_unit_qty" name="base_unit_qty" value="">
                        <input type="hidden" id="sap_user" name="sap_user" value="">
                        <input type="hidden" id="sap_password" name="sap_password" value="">
                        <input type="hidden" id="creation_mode" name="creation_mode" value="split">
                        <input type="hidden" id="total_hus" name="total_hus" value="0">

                        <div class="row">
                            <!-- Left Column: Creation Mode Settings -->
                            <div class="col-lg-5 col-xl-4 mb-3 mb-lg-0">
                                <!-- Mode Selection -->
                                <div class="card bg-light border-0 mb-3">
                                    <div class="card-body p-2">
                                        <h6 class="fw-semibold text-gray-700 mb-2">
                                            <i class="fas fa-cogs me-1 text-purple-500"></i>
                                            Creation Mode
                                        </h6>
                                        <div class="row g-1 mb-2">
                                            <div class="col-4">
                                                <div class="form-check card-mode-selector">
                                                    <input class="form-check-input" type="radio" name="creationMode" id="modeSplit" value="split" checked>
                                                    <label class="form-check-label w-100" for="modeSplit">
                                                        <div class="card border-2">
                                                            <div class="card-body p-1 text-center">
                                                                <i class="fas fa-cubes fa-lg text-primary mb-1"></i>
                                                                <h6 class="fw-bold small mb-1">Split</h6>
                                                                <small class="text-muted">1 HU = 1 PC</small>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-check card-mode-selector">
                                                    <input class="form-check-input" type="radio" name="creationMode" id="modeSingle" value="single">
                                                    <label class="form-check-label w-100" for="modeSingle">
                                                        <div class="card border-2">
                                                            <div class="card-body p-1 text-center">
                                                                <i class="fas fa-cube fa-lg text-success mb-1"></i>
                                                                <h6 class="fw-bold small mb-1">Single</h6>
                                                                <small class="text-muted">1 HU = Total</small>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-check card-mode-selector">
                                                    <input class="form-check-input" type="radio" name="creationMode" id="modePartial" value="partial">
                                                    <label class="form-check-label w-100" for="modePartial">
                                                        <div class="card border-2">
                                                            <div class="card-body p-1 text-center">
                                                                <i class="fas fa-sliders fa-lg text-warning mb-1"></i>
                                                                <h6 class="fw-bold small mb-1">Partial</h6>
                                                                <small class="text-muted">Custom Qty</small>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-2">
                                            <div id="modeSplitDesc" class="mode-description">
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    <strong>Split:</strong> Each PC becomes separate HU
                                                </small>
                                            </div>
                                            <div id="modeSingleDesc" class="mode-description d-none">
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    <strong>Single:</strong> All quantity merged in 1 HU
                                                </small>
                                            </div>
                                            <div id="modePartialDesc" class="mode-description d-none">
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    <strong>Partial:</strong> Create HUs with custom quantity
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Partial Quantity Settings -->
                                <div id="partialSettings" class="card bg-warning bg-opacity-10 border-warning mb-3 d-none">
                                    <div class="card-body p-2">
                                        <h6 class="fw-semibold text-gray-700 mb-2">
                                            <i class="fas fa-edit me-1 text-warning"></i>
                                            Partial Quantity Settings
                                        </h6>
                                        <div class="mb-2">
                                            <label class="form-label fw-semibold small">Total Qty to Create</label>
                                            <input type="number" class="form-control form-control-sm" id="partialTotalQty"
                                                   placeholder="Enter total PCs" min="1" step="1">
                                            <small class="text-muted">Total PCs to create as HUs</small>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label fw-semibold small">Qty per HU</label>
                                            <select class="form-select form-select-sm" id="partialQtyPerHU">
                                                <option value="1">1 PC/HU</option>
                                                <option value="2">2 PC/HU</option>
                                                <option value="5">5 PC/HU</option>
                                                <option value="10">10 PC/HU</option>
                                                <option value="custom">Custom...</option>
                                            </select>
                                            <div id="customQtyContainer" class="mt-1 d-none">
                                                <input type="number" class="form-control form-control-sm" id="customQtyPerHU"
                                                       placeholder="Custom qty per HU" min="1" step="1">
                                            </div>
                                        </div>
                                        <div class="alert alert-info py-1 px-2 mt-1 small">
                                            <i class="fas fa-lightbulb me-1"></i>
                                            Create partial HUs even if more stock available
                                        </div>
                                    </div>
                                </div>

                                <!-- Auto Sequence Settings -->
                                <div class="card bg-light border-0">
                                    <div class="card-body p-2">
                                        <h6 class="fw-semibold text-gray-700 mb-2">
                                            <i class="fas fa-magic me-1 text-purple-500"></i>
                                            Auto Sequence Settings
                                        </h6>
                                        <div class="mb-2">
                                            <label class="form-label fw-semibold small">
                                                <i class="fas fa-barcode me-1 text-purple-500"></i>
                                                HU External ID
                                            </label>
                                            <div class="alert alert-info p-1 small">
                                                <i class="fas fa-info-circle me-1"></i>
                                                10 digits number. Fill first HU manually, others auto-sequence.
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label fw-semibold small">
                                                <i class="fas fa-box me-1 text-purple-500"></i>
                                                Packaging Material
                                            </label>
                                            <select class="form-select form-select-sm" id="globalPackMat">
                                                <option value="">Select Pack Mat (Apply to All)</option>
                                                <option value="VSTDPLTBW01">VSTDPLTBW01</option>
                                                <option value="VSTDPLBW002">VSTDPLBW002</option>
                                                <option value="50016873">50016873</option>
                                            </select>
                                            <small class="text-muted">Select once, applies to all HUs</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: HU List -->
                            <div class="col-lg-7 col-xl-8">
                                <!-- Summary and Info -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-semibold text-gray-700 mb-0">
                                        <i class="fas fa-list-ol me-1 text-purple-500"></i>
                                        Handling Units List
                                    </h6>
                                    <div>
                                        <span id="huCount" class="badge bg-purple me-1">0 HUs</span>
                                        <span id="totalQty" class="badge bg-success">0 PC</span>
                                    </div>
                                </div>

                                <div class="alert alert-info bg-light border-0 py-1 px-2 small mb-2">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <span id="modeDescription">Each PC will be separate HU for material with qty > 1</span>
                                    <br>
                                    <i class="fas fa-lightbulb me-1 text-warning"></i>
                                    <strong>Tip:</strong> Fill first HU's External ID & Pack Mat, others auto-follow
                                </div>

                                <!-- HU List Container -->
                                <div id="hus-container" class="compact-list-container mb-2">
                                    <!-- HUs will be dynamically added here -->
                                </div>

                                <!-- Empty State -->
                                <div id="husPreview" class="text-center py-4 border-2 border-dashed rounded bg-light">
                                    <i class="fas fa-pallet fa-2x text-gray-400 mb-2"></i>
                                    <h6 class="text-muted mb-1">No Handling Units</h6>
                                    <p class="text-muted small mb-0">HU data will appear here after selecting materials from main page</p>
                                </div>

                                <!-- Stock Validation Info -->
                                <div id="stockValidation" class="alert alert-warning mt-2 d-none py-1 px-2 small">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <span id="stockValidationMessage"></span>
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
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title mb-0" id="sapCredentialsModalLabel">
                    <i class="fas fa-key me-1"></i>SAP Credentials
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Progress Bar (Hidden by default) -->
                <div id="sapProgressBar" class="d-none">
                    <div class="text-center mb-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                    </div>
                    <p class="text-center small mt-2 text-muted">Creating All HUs. Please wait...</p>
                </div>

                <!-- Form (Shown by default) -->
                <div id="sapCredentialsForm">
                    <form id="sapCredentialsFormInner">
                        <div class="mb-2">
                            <label for="sap_user_modal" class="form-label small">SAP User <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="sap_user_modal" name="sap_user_modal" required>
                        </div>
                        <div class="mb-2">
                            <label for="sap_password_modal" class="form-label small">SAP Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control form-control-sm" id="sap_password_modal" name="sap_password_modal" required>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" id="cancelSapCredentials">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="confirmSapCredentials">
                    <i class="fas fa-check me-1"></i>Confirm & Create All HUs
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.border-dashed { border-style: dashed !important; }
.compact-list-container { display: none; }
.compact-hu-item {
    background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; padding: 8px; margin-bottom: 8px;
    border-left: 3px solid #8b5cf6;
}
.compact-hu-item:hover { background: #e9ecef; border-color: #dee2e6; }
.compact-hu-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.compact-hu-title { font-weight: 600; color: #333; font-size: 0.8rem; }
.compact-hu-badge { background: #8b5cf6; color: white; padding: 1px 6px; border-radius: 10px; font-size: 0.7rem; font-weight: 500; }
.compact-hu-content { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; font-size: 0.75rem; }
.compact-hu-field { display: flex; flex-direction: column; }
.compact-hu-label { font-weight: 500; color: #6c757d; font-size: 0.7rem; margin-bottom: 2px; }
.compact-hu-value { color: #333; font-weight: 400; }
.compact-hu-input { width: 100%; padding: 4px 6px; border: 1px solid #ced4da; border-radius: 3px; font-size: 0.75rem; }
.compact-hu-input:focus { border-color: #8b5cf6; outline: none; box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.15); }
.compact-hu-select { width: 100%; padding: 4px 6px; border: 1px solid #ced4da; border-radius: 3px; font-size: 0.75rem; background: white; }
.compact-hu-select:focus { border-color: #8b5cf6; outline: none; box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.15); }
.quantity-badge { background: #10b981; color: white; padding: 1px 4px; border-radius: 6px; font-size: 0.65rem; margin-left: 3px; }
.split-indicator { background: #f59e0b; color: white; padding: 1px 4px; border-radius: 6px; font-size: 0.65rem; margin-left: 3px; }
.auto-sequence-hint { background: #e9d5ff; border: 1px solid #c4b5fd; border-radius: 3px; padding: 2px 6px; font-size: 0.65rem; color: #6d28d9; margin-top: 2px; }
.field-description { font-size: 0.65rem; color: #6c757d; margin-top: 1px; }

/* Mode Selector Styles */
.card-mode-selector .form-check-input {
    position: absolute;
    opacity: 0;
}
.card-mode-selector .card {
    cursor: pointer;
    transition: all 0.2s ease;
    border-color: #dee2e6 !important;
    border-width: 1px !important;
    height: 100%;
}
.card-mode-selector .form-check-input:checked + .card {
    border-color: #8b5cf6 !important;
    box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.1);
}
.card-mode-selector #modeSplit:checked + .card { border-color: #3b82f6 !important; }
.card-mode-selector #modeSingle:checked + .card { border-color: #10b981 !important; }
.card-mode-selector #modePartial:checked + .card { border-color: #f59e0b !important; }

.mode-description { display: block; }
.form-control-sm, .form-select-sm { font-size: 0.8rem; }
.alert { font-size: 0.8rem; }
.small { font-size: 0.8rem; }

/* Responsive adjustments */
@media (max-width: 991.98px) {
    .compact-hu-content {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@push('scripts')
<script>
// ===== GLOBAL VARIABLES =====
var huCount = 0;
var totalQuantity = 0;
let creationMode = 'split';
let lastPackMat = '';
let availableStocks = {};
let currentMaterials = [];

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    setupEventListeners();
    loadInitialData();
    updateModeDisplay();
}

function setupEventListeners() {
    // Mode selection
    document.querySelectorAll('input[name="creationMode"]').forEach(radio => {
        radio.addEventListener('change', function() {
            creationMode = this.value;
            updateModeDisplay();
            if (currentMaterials.length > 0) {
                processMaterials(currentMaterials);
            } else {
                processMaterialsFromSessionStorage();
            }
        });
    });

    // Partial quantity settings
    document.getElementById('partialQtyPerHU').addEventListener('change', function() {
        const customContainer = document.getElementById('customQtyContainer');
        if (this.value === 'custom') {
            customContainer.classList.remove('d-none');
        } else {
            customContainer.classList.add('d-none');
        }
        if (creationMode === 'partial' && currentMaterials.length > 0) {
            processMaterials(currentMaterials);
        }
    });

    document.getElementById('partialTotalQty').addEventListener('input', function() {
        if (creationMode === 'partial' && currentMaterials.length > 0) {
            processMaterials(currentMaterials);
        }
    });

    document.getElementById('customQtyPerHU').addEventListener('input', function() {
        if (creationMode === 'partial' && document.getElementById('partialQtyPerHU').value === 'custom' && currentMaterials.length > 0) {
            processMaterials(currentMaterials);
        }
    });

    // Global packaging material
    document.getElementById('globalPackMat').addEventListener('change', function() {
        lastPackMat = this.value;
        applyPackMatToAll();
    });

    // Create HU Button
    document.getElementById('createHuButton').addEventListener('click', function() {
        if (!validateForm()) return;
        if (creationMode === 'partial' && !validatePartialStock()) return;

        showMessage('Proceeding to create HUs...', 'info');
        const sapModal = new bootstrap.Modal(document.getElementById('sapCredentialsModal'));
        sapModal.show();
    });

    // Confirm SAP Credentials
    document.getElementById('confirmSapCredentials').addEventListener('click', function() {
        const modalSapUser = document.querySelector('#sapCredentialsModal input[name="sap_user_modal"]').value;
        const modalSapPassword = document.querySelector('#sapCredentialsModal input[name="sap_password_modal"]').value;

        if (!modalSapUser || !modalSapPassword) {
            showMessage('SAP User and Password required', 'error');
            return;
        }

        // Sembunyikan form dan tombol, tampilkan progress bar
        document.getElementById('sapCredentialsForm').classList.add('d-none');
        document.getElementById('confirmSapCredentials').classList.add('d-none');
        document.getElementById('cancelSapCredentials').classList.add('d-none');
        document.getElementById('sapProgressBar').classList.remove('d-none');

        // Set nilai ke hidden input
        document.getElementById('sap_user').value = modalSapUser;
        document.getElementById('sap_password').value = modalSapPassword;
        document.getElementById('creation_mode').value = creationMode;
        document.getElementById('total_hus').value = huCount;

        // Submit form secara langsung (modal akan tetap terbuka sampai redirect)
        // Progress bar akan tetap terlihat
        document.getElementById('huForm').submit();
    });

    // Reset modal
    document.getElementById('sapCredentialsModal').addEventListener('hidden.bs.modal', function() {
        // Reset form
        document.querySelector('#sapCredentialsModal form').reset();

        // Sembunyikan progress bar, tampilkan form dan tombol
        document.getElementById('sapProgressBar').classList.add('d-none');
        document.getElementById('sapCredentialsForm').classList.remove('d-none');
        document.getElementById('confirmSapCredentials').classList.remove('d-none');
        document.getElementById('cancelSapCredentials').classList.remove('d-none');

        // Reset tombol
        const confirmBtn = document.getElementById('confirmSapCredentials');
        confirmBtn.innerHTML = '<i class="fas fa-check me-1"></i>Confirm & Create All HUs';
        confirmBtn.disabled = false;
    });
}

function updateModeDisplay() {
    // Update descriptions
    document.querySelectorAll('.mode-description').forEach(desc => {
        desc.classList.add('d-none');
    });
    document.getElementById(`mode${capitalizeFirst(creationMode)}Desc`).classList.remove('d-none');

    // Update mode description
    const modeDescription = document.getElementById('modeDescription');
    switch(creationMode) {
        case 'split':
            modeDescription.textContent = 'Each PC becomes separate HU for material with qty > 1';
            break;
        case 'single':
            modeDescription.textContent = 'Each material becomes separate HU (quantity merged per material)';
            break;
        case 'partial':
            modeDescription.textContent = 'Create HUs with custom quantity as per settings above';
            break;
    }

    // Show/hide partial settings
    const partialSettings = document.getElementById('partialSettings');
    if (creationMode === 'partial') {
        partialSettings.classList.remove('d-none');
    } else {
        partialSettings.classList.add('d-none');
    }

    document.getElementById('creation_mode').value = creationMode;
}

function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function loadInitialData() {
    const serverSuccessAlert = document.querySelector('.alert-success');
    const serverErrorAlert = document.querySelector('.alert-danger');

    if (serverSuccessAlert) {
        sessionStorage.removeItem('scenario3_data');
        setTimeout(() => serverSuccessAlert.remove(), 4000);
    }

    if (serverErrorAlert) {
        setTimeout(() => serverErrorAlert.remove(), 6000);
    }

    const scenarioDataRaw = sessionStorage.getItem('scenario3_data');
    if (scenarioDataRaw && !serverSuccessAlert && !serverErrorAlert) {
        processMaterialsFromSessionStorage();
    } else if (!scenarioDataRaw && !serverSuccessAlert && !serverErrorAlert) {
        showMessage('Please select materials from main page first.', 'info');
    }

    setTimeout(autoSetPackagingMaterialForAllHUs, 300);
}

// ===== CORE FUNCTIONS =====
function processMaterialsFromSessionStorage() {
    const scenarioDataRaw = sessionStorage.getItem('scenario3_data');
    if (!scenarioDataRaw) {
        showMessage('No material data. Please select from main page.', 'warning');
        return;
    }

    try {
        const materials = JSON.parse(scenarioDataRaw);
        if (!materials || !Array.isArray(materials) || materials.length === 0) {
            showMessage('Invalid material data.', 'warning');
            return;
        }

        currentMaterials = materials;

        const materialsWithStock = materials.filter(item => {
            const stockQty = parseFloat(item.stock_quantity || '0');
            return stockQty > 0;
        });

        if (materialsWithStock.length === 0) {
            showMessage('No materials with available stock. Please select other materials.', 'error');
            sessionStorage.removeItem('scenario3_data');
            return;
        }

        if (materialsWithStock.length < materials.length) {
            const filteredCount = materials.length - materialsWithStock.length;
            showMessage(`${filteredCount} materials with zero stock filtered.`, 'warning');
            sessionStorage.setItem('scenario3_data', JSON.stringify(materialsWithStock));
        }

        processMaterials(materialsWithStock);

    } catch (error) {
        console.error('Error parsing materials:', error);
        showMessage('Error loading material data. Please reselect.', 'error');
        sessionStorage.removeItem('scenario3_data');
    }
}

function loadAvailableStocks(materials, callback) {
    availableStocks = {};

    if (materials.length === 0) {
        callback();
        return;
    }

    let materialsProcessed = 0;

    materials.forEach(item => {
        const materialKey = `${item.material}_${item.batch || ''}`;
        const stockQty = parseFloat(item.stock_quantity || '0');

        availableStocks[materialKey] = {
            available: stockQty,
            message: `Available stock: ${stockQty} PC`
        };

        materialsProcessed++;
        if (materialsProcessed === materials.length) callback();
    });
}

function processMaterials(materials) {
    huCount = 0;
    totalQuantity = 0;
    document.getElementById('hus-container').innerHTML = '';

    loadAvailableStocks(materials, () => {
        const groupedMaterials = groupMaterials(materials);

        Object.values(groupedMaterials).forEach(group => {
            addHUGroupToForm(group);
        });

        setupAutoSequenceListeners();

        if (huCount > 0) {
            document.getElementById('husPreview').style.display = 'none';
            document.getElementById('hus-container').style.display = 'block';
        } else {
            document.getElementById('husPreview').style.display = 'block';
            document.getElementById('hus-container').style.display = 'none';
        }

        updateHUSummary();
        showStockValidation();
    });
}

function groupMaterials(materials) {
    const groups = {};
    materials.forEach(item => {
        const key = `${item.material}_${item.batch || ''}_${getSalesOrderNo(item)}`;
        if (!groups[key]) {
            groups[key] = {
                material: item.material,
                batch: item.batch || '',
                salesOrderNo: getSalesOrderNo(item),
                plant: item.plant || '3000',
                storageLocation: item.storage_location || '3D10',
                materialDescription: item.material_description || '',
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

function addHUGroupToForm(group) {
    const formattedMaterial = formatMaterialNumber(group.material);
    const materialKey = `${group.material}_${group.batch || ''}`;
    const availableStock = availableStocks[materialKey] ? availableStocks[materialKey].available : group.totalQuantity;

    switch(creationMode) {
        case 'split':
            const splitCount = Math.min(availableStock, group.totalQuantity);
            for (let i = 0; i < splitCount; i++) {
                addSingleHUToForm(group, i + 1, splitCount, true, 1);
            }
            break;

        case 'single':
            addSingleHUToForm(group, 1, 1, false, availableStock);
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
                    addSingleHUToForm(group, i + 1, numberOfHUs, numberOfHUs > 1, huQty);
                }
            }
            break;
    }
}

function getPartialSettings() {
    const totalQtyInput = document.getElementById('partialTotalQty');
    const qtyPerHUSelect = document.getElementById('partialQtyPerHU');

    if (!totalQtyInput.value) return null;

    const totalQty = parseInt(totalQtyInput.value);
    if (totalQty <= 0) {
        showMessage('Total quantity must be > 0', 'warning');
        return null;
    }

    let qtyPerHU;
    if (qtyPerHUSelect.value === 'custom') {
        const customQty = document.getElementById('customQtyPerHU').value;
        if (!customQty || customQty <= 0) {
            showMessage('Qty per HU must be > 0', 'warning');
            return null;
        }
        qtyPerHU = parseInt(customQty);
    } else {
        qtyPerHU = parseInt(qtyPerHUSelect.value);
    }

    if (qtyPerHU <= 0) {
        showMessage('Qty per HU must be > 0', 'warning');
        return null;
    }

    return { totalQty, qtyPerHU };
}

function addSingleHUToForm(group, sequence, totalHUs, isSplit, quantity) {
    const container = document.getElementById('hus-container');
    const formattedMaterial = formatMaterialNumber(group.material);

    const defaultStartNumber = 9900000000;
    const sequenceNumber = huCount + 1;
    const autoHuExid = (defaultStartNumber + sequenceNumber).toString().padStart(10, '0');

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
                <i class="fas fa-pallet me-1 text-purple-500"></i>
                HU ${huCount + 1}: ${formattedMaterial}
                ${splitIndicator}
                ${quantityBadge}
            </div>
            <div class="compact-hu-badge">${quantity.toLocaleString('id-ID')} PC</div>
        </div>

        ${group.materialDescription ? `
            <div class="compact-hu-field mb-1">
                <span class="compact-hu-label">Description</span>
                <span class="compact-hu-value">${group.materialDescription}</span>
            </div>
        ` : ''}

        <div class="compact-hu-content">
            <div class="compact-hu-field">
                <span class="compact-hu-label">HU External ID <span class="text-danger">*</span></span>
                <input type="text" class="compact-hu-input hu-exid-input" name="hus[${huCount}][hu_exid]"
                       value="${autoHuExid}" required placeholder="10 digits" maxlength="10"
                       pattern="\\d{10}" title="10 digits required">
                ${huCount === 0 ? '<div class="auto-sequence-hint">Fill manually, others auto-sequence</div>' : ''}
            </div>

            <div class="compact-hu-field">
                <span class="compact-hu-label">Pack Mat <span class="text-danger">*</span></span>
                <select class="compact-hu-select pack-mat-select" name="hus[${huCount}][pack_mat]" required>
                    <option value="">Select Pack Mat</option>
                    <option value="VSTDPLTBW01" ${lastPackMat === 'VSTDPLTBW01' ? 'selected' : ''}>VSTDPLTBW01</option>
                    <option value="VSTDPLBW002" ${lastPackMat === 'VSTDPLBW002' ? 'selected' : ''}>VSTDPLBW002</option>
                    <option value="50016873" ${lastPackMat === '50016873' ? 'selected' : ''}>50016873</option>
                </select>
                ${huCount === 0 ? '<div class="auto-sequence-hint">Select once, applies to all</div>' : ''}
            </div>

            <div class="compact-hu-field">
                <span class="compact-hu-label">Plant <span class="text-danger">*</span></span>
                <input type="text" class="compact-hu-input bg-light" name="hus[${huCount}][plant]"
                       value="${group.plant}" readonly>
            </div>

            <div class="compact-hu-field">
                <span class="compact-hu-label">Storage Loc <span class="text-danger">*</span></span>
                <input type="text" class="compact-hu-input bg-light" name="hus[${huCount}][stge_loc]"
                       value="${group.storageLocation}" readonly>
            </div>

            <div class="compact-hu-field">
                <span class="compact-hu-label">Material <span class="text-danger">*</span></span>
                <input type="text" class="compact-hu-input bg-light" name="hus[${huCount}][material]"
                       value="${formattedMaterial}" readonly>
            </div>

            <div class="compact-hu-field">
                <span class="compact-hu-label">Batch</span>
                <input type="text" class="compact-hu-input bg-light" name="hus[${huCount}][batch]"
                       value="${group.batch}" readonly>
            </div>

            <div class="compact-hu-field">
                <span class="compact-hu-label">Pack Qty <span class="text-danger">*</span></span>
                <input type="number" class="compact-hu-input" name="hus[${huCount}][pack_qty]"
                       value="${quantity}" step="0.001" min="0.001"
                       required data-max-qty="${quantity}" readonly>
                <div class="field-description">
                    ${isSplit && totalHUs > 1 ?
                        `Part ${sequence}/${totalHUs}` :
                        `${quantity.toLocaleString('id-ID')} PC`}
                </div>
            </div>

            <div class="compact-hu-field">
                <span class="compact-hu-label">Sales Order</span>
                <input type="text" class="compact-hu-input bg-light" name="hus[${huCount}][sp_stck_no]"
                       value="${group.salesOrderNo}" readonly>
            </div>
        </div>
    `;
    container.appendChild(newHU);
    huCount++;
    totalQuantity += quantity;
}

function updateHUSummary() {
    document.getElementById('huCount').textContent = `${huCount} HUs`;
    document.getElementById('totalQty').textContent = `${totalQuantity.toLocaleString('id-ID')} PC`;
}

function showStockValidation() {
    const validationDiv = document.getElementById('stockValidation');
    const messageSpan = document.getElementById('stockValidationMessage');

    let hasStockIssue = false;
    let message = '';

    for (const [key, stock] of Object.entries(availableStocks)) {
        const [material, batch] = key.split('_');
        if (stock.available <= 0) {
            hasStockIssue = true;
            message += `Material ${material} (Batch: ${batch || '-'}): No stock<br>`;
        }
    }

    if (hasStockIssue) {
        validationDiv.classList.remove('d-none');
        messageSpan.innerHTML = message;
    } else {
        validationDiv.classList.add('d-none');
    }
}

function validatePartialStock() {
    const partialSettings = getPartialSettings();
    if (!partialSettings) {
        showMessage('Please set partial quantity settings', 'warning');
        return false;
    }

    const { totalQty } = partialSettings;

    for (const [key, stock] of Object.entries(availableStocks)) {
        if (stock.available < totalQty) {
            showMessage(`Insufficient stock for ${totalQty} PC. Available: ${stock.available} PC`, 'warning');
            return false;
        }
    }

    return true;
}

// ===== EXISTING FUNCTIONS =====
function setupAutoSequenceListeners() {
    document.getElementById('hus-container').addEventListener('blur', function(e) {
        if (e.target.classList.contains('hu-exid-input')) {
            const input = e.target;
            const currentValue = input.value.trim();
            if (currentValue && /^\d{10}$/.test(currentValue)) {
                updateAllHuExidSequence(parseInt(currentValue));
            }
        }
    }, true);

    document.getElementById('hus-container').addEventListener('change', function(e) {
        if (e.target.classList.contains('pack-mat-select')) {
            const select = e.target;
            const index = Array.from(document.querySelectorAll('.pack-mat-select')).indexOf(select);
            if (index === 0 && select.value) {
                lastPackMat = select.value;
                document.getElementById('globalPackMat').value = lastPackMat;
                applyPackMatToAll();
            }
        }
    }, true);
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

function updateAllHuExidSequence(startNumber) {
    const huExidInputs = document.querySelectorAll('#hus-container .hu-exid-input');
    huExidInputs.forEach((input, index) => {
        const newNumber = startNumber + index;
        if (newNumber > 9999999999) {
            showMessage(`Sequence exceeds 9999999999. Cannot generate HU External ID.`, 'error');
            return;
        }
        input.value = newNumber.toString().padStart(10, '0');
    });
    showMessage(`HU External ID sequence updated from ${startNumber}`, 'success');
}

function applyPackMatToAll() {
    const packMatSelects = document.querySelectorAll('#hus-container .pack-mat-select');
    packMatSelects.forEach(select => select.value = lastPackMat);
    if (lastPackMat) {
        showMessage(`Pack Mat "${lastPackMat}" applied to all HUs`, 'success');
    }
}

function validateForm() {
    if (huCount === 0) {
        showMessage('No HUs added. Please select from main page.', 'error');
        return false;
    }

    let validationError = false;

    const huExidInputs = document.querySelectorAll('#hus-container input[name*="[hu_exid]"]');
    huExidInputs.forEach((input, index) => {
        const huExid = input.value.trim();
        if (!/^\d{10}$/.test(huExid)) {
            showMessage(`HU ${index + 1}: External ID must be 10 digits`, 'error');
            input.focus();
            validationError = true;
            return;
        }
    });

    if (validationError) return false;

    const packMatSelects = document.querySelectorAll('#hus-container select[name*="[pack_mat]"]');
    packMatSelects.forEach((select, index) => {
        if (!select.value) {
            showMessage(`HU ${index + 1}: Pack Mat required`, 'error');
            select.focus();
            validationError = true;
            return;
        }
    });

    return !validationError;
}

function resetForm() {
    if (confirm('Cancel? All data will be lost.')) {
        window.location.href = "{{ route('hu.index') }}";
    }
}

function autoSetPackagingMaterialForAllHUs() {
    const scenarioDataRaw = sessionStorage.getItem('scenario3_data');
    if (!scenarioDataRaw) return;

    try {
        const materials = JSON.parse(scenarioDataRaw);
        if (materials.length === 0) return;

        const firstItem = materials[0];
        const magry = firstItem.magry || '';

        const globalPackMatSelect = document.getElementById('globalPackMat');
        if (!globalPackMatSelect) return;

        globalPackMatSelect.value = '';
        lastPackMat = '';

        if (magry === 'ZMG1') {
            globalPackMatSelect.value = '50016873';
            lastPackMat = '50016873';
            applyPackMatToAll();
            showMessage(`Pack Mat auto-set to "50016873" for all HUs (ZMG1)`, 'success');
        } else if (magry === 'ZMG2') {
            globalPackMatSelect.value = 'VSTDPLTBW01';
            lastPackMat = 'VSTDPLTBW01';
            applyPackMatToAll();
            showMessage(`Pack Mat auto-set to "VSTDPLTBW01" for all HUs (ZMG2)`, 'success');
        }
    } catch (error) {
        console.error('Error in autoSetPackagingMaterialForAllHUs:', error);
    }
}

function showMessage(message, type) {
    const existingAlerts = document.querySelectorAll('.alert.alert-dismissible:not(.alert-success):not(.alert-danger)');
    existingAlerts.forEach(alert => alert.remove());

    const alertClass = type === 'error' ? 'alert-danger' :
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    const iconClass = type === 'error' ? 'fa-exclamation-triangle' :
                     type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';

    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show shadow-sm mb-3 py-1 px-2" role="alert">
            <i class="fas ${iconClass} me-1"></i>${message}
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
        </div>
    `;

    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);

    const autoHideTime = type === 'error' ? 6000 :
                        type === 'warning' ? 5000 : 3000;

    setTimeout(() => {
        const alert = document.querySelector('.alert.' + alertClass);
        if (alert && !alert.classList.contains('alert-success') && !alert.classList.contains('alert-danger')) {
            alert.remove();
        }
    }, autoHideTime);
}
</script>
@endpush
