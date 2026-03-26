@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-2 py-1 px-2 small" role="alert">
            <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-2 py-1 px-2 small" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Top Bar -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-purple-100 rounded-circle d-flex align-items-center justify-content-center" style="width:30px;height:30px;min-width:30px">
                <i class="fas fa-pallet text-purple-600" style="font-size:0.85rem"></i>
            </div>
            <div>
                <h1 class="h6 fw-bold text-gray-800 mb-0 lh-1">Skenario 3 &mdash; Create Multiple HUs</h1>
                <span class="text-muted" style="font-size:0.8rem">Flexible Quantity per HU</span>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('hu.index') }}" class="btn btn-outline-secondary btn-sm py-1 px-2">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
            <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2" onclick="resetForm()">
                <i class="fas fa-times me-1"></i>Cancel
            </button>
            <button type="button" class="btn btn-primary btn-sm py-1 px-2" id="createHuButton">
                <i class="fas fa-save me-1"></i>Create All
            </button>
        </div>
    </div>

    <form action="{{ route('hu.store-multiple') }}" method="POST" id="huForm">
        @csrf
        <input type="hidden" id="base_unit_qty" name="base_unit_qty" value="">
        <input type="hidden" id="sap_user" name="sap_user" value="">
        <input type="hidden" id="sap_password" name="sap_password" value="">
        <input type="hidden" id="creation_mode" name="creation_mode" value="split">
        <input type="hidden" id="total_hus" name="total_hus" value="0">

        <div class="row g-2">

            <!-- KIRI: Settings -->
            <div class="col-lg-3 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-1 px-3 border-bottom d-flex align-items-center gap-1">
                        <i class="fas fa-cogs text-purple-500" style="font-size:0.85rem"></i>
                        <span class="fw-semibold" style="font-size:0.85rem">Creation Settings</span>
                    </div>
                    <div class="card-body p-2">

                        <p class="text-uppercase fw-bold mb-1" style="font-size:0.68rem;color:#8b5cf6;letter-spacing:0.05em">Mode</p>
                        <div class="row g-1 mb-2">
                            <div class="col-4">
                                <div class="form-check card-mode-selector p-0">
                                    <input class="form-check-input" type="radio" name="creationMode" id="modeSplit" value="split" checked>
                                    <label class="form-check-label w-100" for="modeSplit">
                                        <div class="card border mode-card text-center p-1">
                                            <i class="fas fa-cubes text-primary" style="font-size:0.9rem"></i>
                                            <div style="font-size:0.72rem;font-weight:700">Split</div>
                                            <div style="font-size:0.62rem;color:#6c757d">1 HU=1 PC</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check card-mode-selector p-0">
                                    <input class="form-check-input" type="radio" name="creationMode" id="modeSingle" value="single">
                                    <label class="form-check-label w-100" for="modeSingle">
                                        <div class="card border mode-card text-center p-1">
                                            <i class="fas fa-cube text-success" style="font-size:0.9rem"></i>
                                            <div style="font-size:0.72rem;font-weight:700">Single</div>
                                            <div style="font-size:0.62rem;color:#6c757d">1 HU=Total</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check card-mode-selector p-0">
                                    <input class="form-check-input" type="radio" name="creationMode" id="modePartial" value="partial">
                                    <label class="form-check-label w-100" for="modePartial">
                                        <div class="card border mode-card text-center p-1">
                                            <i class="fas fa-sliders text-warning" style="font-size:0.9rem"></i>
                                            <div style="font-size:0.72rem;font-weight:700">Partial</div>
                                            <div style="font-size:0.62rem;color:#6c757d">Custom Qty</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="modeSplitDesc" class="mode-description" style="font-size:0.72rem;color:#6c757d;margin-bottom:6px">
                            <i class="fas fa-info-circle me-1 text-primary"></i><strong>Split:</strong> Setiap PC jadi HU terpisah
                        </div>
                        <div id="modeSingleDesc" class="mode-description d-none" style="font-size:0.72rem;color:#6c757d;margin-bottom:6px">
                            <i class="fas fa-info-circle me-1 text-success"></i><strong>Single:</strong> Semua qty digabung dalam 1 HU
                        </div>
                        <div id="modePartialDesc" class="mode-description d-none" style="font-size:0.72rem;color:#6c757d;margin-bottom:6px">
                            <i class="fas fa-info-circle me-1 text-warning"></i><strong>Partial:</strong> Buat HU dengan qty custom
                        </div>

                        <!-- Partial Settings -->
                        <div id="partialSettings" class="d-none rounded p-2 mb-2" style="background:#fffbeb;border:1px solid #fde68a">
                            <p class="text-uppercase fw-bold mb-1" style="font-size:0.68rem;color:#d97706;letter-spacing:0.05em">Partial Settings</p>
                            <div class="mb-1">
                                <label class="form-label fw-semibold mb-0" style="font-size:0.75rem">Total Qty</label>
                                <input type="number" class="form-control form-control-sm" id="partialTotalQty"
                                       placeholder="Total PCs" min="1" step="1" style="font-size:0.83rem">
                            </div>
                            <div class="mb-1">
                                <label class="form-label fw-semibold mb-0" style="font-size:0.75rem">Qty per HU</label>
                                <select class="form-select form-select-sm" id="partialQtyPerHU" style="font-size:0.83rem">
                                    <option value="1">1 PC/HU</option>
                                    <option value="2">2 PC/HU</option>
                                    <option value="5">5 PC/HU</option>
                                    <option value="10">10 PC/HU</option>
                                    <option value="custom">Custom...</option>
                                </select>
                                <div id="customQtyContainer" class="mt-1 d-none">
                                    <input type="number" class="form-control form-control-sm" id="customQtyPerHU"
                                           placeholder="Custom qty/HU" min="1" step="1" style="font-size:0.83rem">
                                </div>
                            </div>
                        </div>

                        <hr class="my-2">

                        <p class="text-uppercase fw-bold mb-1" style="font-size:0.68rem;color:#8b5cf6;letter-spacing:0.05em">Auto Sequence</p>
                        <p style="font-size:0.72rem;color:#6c757d;margin-bottom:6px">
                            <i class="fas fa-barcode me-1 text-purple-500"></i>
                            Isi HU di line 1, sisanya auto-urut
                        </p>
                        <div>
                            <label class="form-label fw-semibold mb-1" style="font-size:0.75rem">
                                <i class="fas fa-box me-1 text-purple-500"></i>Pack Mat (All)
                            </label>
                            <select class="form-select form-select-sm" id="globalPackMat" style="font-size:0.83rem">
                                <option value="">— Apply to All —</option>
                                <option value="VSTDPLTBW01">VSTDPLTBW01</option>
                                <option value="VSTDPLBW002">VSTDPLBW002</option>
                                <option value="50016873">50016873</option>
                            </select>
                            <span style="font-size:0.72rem;color:#6c757d">Pilih sekali, berlaku ke semua HU</span>
                        </div>

                    </div>
                </div>
            </div>

            <!-- KANAN: HU Table -->
            <div class="col-lg-9 col-xl-9">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-1 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-list-ol text-purple-500" style="font-size:0.85rem"></i>
                            <span class="fw-semibold" style="font-size:0.85rem">Handling Units List</span>
                            <span style="font-size:0.75rem;color:#6c757d" id="modeDescription"></span>
                        </div>
                        <div class="d-flex gap-1 align-items-center">
                            <span id="huCount" class="badge bg-purple" style="font-size:0.75rem">0 HUs</span>
                            <span id="totalQty" class="badge bg-success" style="font-size:0.75rem">0 PC</span>
                        </div>
                    </div>
                    <div class="card-body p-2">

                        <div id="stockValidation" class="alert alert-warning d-none py-1 px-2 mb-2" style="font-size:0.75rem">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <span id="stockValidationMessage"></span>
                        </div>

                        <!-- Empty State -->
                        <div id="husPreview" class="text-center py-3" style="border:2px dashed #dee2e6;border-radius:4px">
                            <i class="fas fa-pallet fa-lg text-gray-400 mb-1"></i>
                            <p class="text-muted mb-0" style="font-size:0.83rem">Belum ada HU &mdash; pilih material dari main page</p>
                            <p style="font-size:0.72rem;color:#6c757d;margin:2px 0 0">
                                <i class="fas fa-lightbulb me-1 text-warning"></i>
                                Isi HU Ext ID &amp; Pack Mat di baris pertama, baris lain akan mengikuti otomatis
                            </p>
                        </div>

                        <!-- HU Table -->
                        <div id="hus-container" style="display:none">
                            <div class="hu-table-header">
                                <div class="hu-col hu-col-no">#</div>
                                <div class="hu-col hu-col-mat">Material</div>
                                <div class="hu-col hu-col-desc">Deskripsi</div>
                                <div class="hu-col hu-col-batch">Batch</div>
                                <div class="hu-col hu-col-so">Sales Order</div>
                                <div class="hu-col hu-col-qty">Qty</div>
                                <div class="hu-col hu-col-exid">HU Ext No <span class="text-danger">*</span></div>
                                <div class="hu-col hu-col-pm">Pack Mat <span class="text-danger">*</span></div>
                                <div class="hu-col hu-col-plant">Plant/Sloc</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </form>
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
/* === COLOR UTILS === */
.bg-purple-100  { background-color: #ede9fe; }
.text-purple-600{ color: #7c3aed; }
.text-purple-500{ color: #8b5cf6; }
.bg-purple      { background-color: #8b5cf6 !important; }

/* === MODE CARD SELECTOR === */
.card-mode-selector .form-check-input { position:absolute; opacity:0; }
.mode-card {
    cursor: pointer;
    transition: all 0.15s ease;
    border-color: #dee2e6 !important;
    border-width: 1px !important;
    border-radius: 5px;
}
.card-mode-selector .form-check-input:checked ~ label .mode-card { border-color: #8b5cf6 !important; box-shadow: 0 0 0 2px rgba(139,92,246,.15); }
#modeSplit:checked  ~ label .mode-card { border-color: #3b82f6 !important; background:#eff6ff; }
#modeSingle:checked ~ label .mode-card { border-color: #10b981 !important; background:#f0fdf4; }
#modePartial:checked~ label .mode-card { border-color: #f59e0b !important; background:#fffbeb; }
.mode-description { display: block; }

/* === HU TABLE === */
.hu-table-header {
    display: flex;
    align-items: center;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 4px 4px 0 0;
    padding: 5px 8px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.03em;
}
.hu-row {
    display: flex;
    align-items: center;
    border: 1px solid #e2e8f0;
    border-top: none;
    padding: 5px 8px;
    font-size: 0.82rem;
    background: #fff;
    transition: background 0.1s;
}
.hu-row:last-child  { border-radius: 0 0 4px 4px; }
.hu-row:nth-child(even) { background: #f8fafc; }
.hu-row:hover       { background: #faf5ff; }
.hu-col             { padding: 0 4px; overflow: hidden; }
.hu-col-no          { width: 26px;  flex-shrink:0; color:#94a3b8; font-size:0.72rem; text-align:center; }
.hu-col-mat         { width: 90px;  flex-shrink:0; font-weight:600; font-family:monospace; color:#6d28d9; font-size:0.8rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.hu-col-desc        { flex:1;       color:#4b5563; font-size:0.78rem; font-style:italic; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.hu-col-batch       { width: 82px;  flex-shrink:0; font-family:monospace; font-size:0.78rem; color:#374151; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.hu-col-so          { width: 110px; flex-shrink:0; font-family:monospace; font-size:0.75rem; color:#6b7280; white-space:normal; word-break:break-all; line-height:1.3; }
.hu-col-qty         { width: 68px;  flex-shrink:0; text-align:center; }
.hu-col-exid        { width: 115px; flex-shrink:0; }
.hu-col-pm          { width: 120px; flex-shrink:0; }
.hu-col-plant       { width: 80px;  flex-shrink:0; font-size:0.72rem; color:#6b7280; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

.qty-badge {
    background: #7c3aed;
    color: white;
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
}
.split-badge {
    background: #f59e0b;
    color: white;
    padding: 1px 4px;
    border-radius: 4px;
    font-size: 0.62rem;
    font-weight: 600;
    margin-left: 2px;
    vertical-align: middle;
}
.hu-input {
    width: 100%;
    padding: 3px 5px;
    border: 1px solid #ced4da;
    border-radius: 3px;
    font-size: 0.78rem;
    font-family: monospace;
}
.hu-input:focus {
    border-color: #8b5cf6;
    outline: none;
    box-shadow: 0 0 0 2px rgba(139,92,246,.15);
}
.hu-select {
    width: 100%;
    padding: 3px 5px;
    border: 1px solid #ced4da;
    border-radius: 3px;
    font-size: 0.75rem;
    background: white;
}
.hu-select:focus {
    border-color: #8b5cf6;
    outline: none;
    box-shadow: 0 0 0 2px rgba(139,92,246,.15);
}
.auto-hint {
    font-size: 0.62rem;
    color: #7c3aed;
    background: #ede9fe;
    border-radius: 3px;
    padding: 1px 4px;
    margin-top: 2px;
    display: block;
}

@media (max-width: 991.98px) {
    .hu-col-desc, .hu-col-so { display: none; }
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
    // Hapus hanya baris data, bukan header tabel
    document.querySelectorAll('#hus-container .hu-row').forEach(r => r.remove());

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

    const splitBadge = (isSplit && totalHUs > 1)
        ? `<span class="split-badge">${sequence}/${totalHUs}</span>` : '';

    const rowNum = huCount + 1;

    const row = document.createElement('div');
    row.className = 'hu-row';
    row.innerHTML = `
        <div class="hu-col hu-col-no">${rowNum}</div>
        <div class="hu-col hu-col-mat" title="${formattedMaterial}">
            ${formattedMaterial}${splitBadge}
            <input type="hidden" name="hus[${huCount}][material]" value="${formattedMaterial}">
            <input type="hidden" name="hus[${huCount}][plant]" value="${group.plant}">
            <input type="hidden" name="hus[${huCount}][stge_loc]" value="${group.storageLocation}">
            <input type="hidden" name="hus[${huCount}][batch]" value="${group.batch}">
            <input type="hidden" name="hus[${huCount}][sp_stck_no]" value="${group.salesOrderNo}">
            <input type="hidden" name="hus[${huCount}][pack_qty]" value="${quantity}">
        </div>
        <div class="hu-col hu-col-desc" title="${group.materialDescription || ''}">${group.materialDescription || '-'}</div>
        <div class="hu-col hu-col-batch" title="${group.batch}">${group.batch || '-'}</div>
        <div class="hu-col hu-col-so">${group.salesOrderNo || '-'}</div>
        <div class="hu-col hu-col-qty">
            <span class="qty-badge">${quantity.toLocaleString('id-ID')} PC</span>
        </div>
        <div class="hu-col hu-col-exid">
            <input type="text" class="hu-input hu-exid-input" name="hus[${huCount}][hu_exid]"
                   value="${autoHuExid}" required placeholder="10 digits" maxlength="10"
                   pattern="\\d{10}" title="10 digits required">
            ${huCount === 0 ? '<span class="auto-hint">Isi manual, lain auto-urut</span>' : ''}
        </div>
        <div class="hu-col hu-col-pm">
            <select class="hu-select pack-mat-select" name="hus[${huCount}][pack_mat]" required>
                <option value="">— Pilih —</option>
                <option value="VSTDPLTBW01" ${lastPackMat === 'VSTDPLTBW01' ? 'selected' : ''}>VSTDPLTBW01</option>
                <option value="VSTDPLBW002" ${lastPackMat === 'VSTDPLBW002' ? 'selected' : ''}>VSTDPLBW002</option>
                <option value="50016873"    ${lastPackMat === '50016873'    ? 'selected' : ''}>50016873</option>
            </select>
            ${huCount === 0 ? '<span class="auto-hint">Pilih sekali, berlaku semua</span>' : ''}
        </div>
        <div class="hu-col hu-col-plant" title="${group.plant} / ${group.storageLocation}">
            ${group.plant} / ${group.storageLocation}
        </div>
    `;
    container.appendChild(row);
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