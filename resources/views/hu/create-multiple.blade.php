@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Alert Messages -->
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

    <!-- Action Buttons -->
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
                        <i class="fas fa-save me-2"></i>Create All HUs
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-purple-100 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                    <i class="fas fa-pallet text-purple-600 fa-lg"></i>
                </div>
                <div>
                    <h1 class="h3 fw-bold text-gray-800 mb-1">Skenario 3</h1>
                    <p class="text-muted mb-0">Buat Multiple HU (Flexible Quantity)</p>
                </div>
            </div>
        </div>
    </div>

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
                    <!-- Mode Selection -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h6 class="fw-semibold text-gray-700 mb-3">
                                        <i class="fas fa-cogs me-2 text-purple-500"></i>
                                        Pilih Mode Pembuatan HU
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check card-mode-selector">
                                                <input class="form-check-input" type="radio" name="creationMode" id="modeSplit" value="split" checked>
                                                <label class="form-check-label w-100" for="modeSplit">
                                                    <div class="card border-2">
                                                        <div class="card-body text-center">
                                                            <i class="fas fa-cubes fa-2x text-primary mb-2"></i>
                                                            <h6 class="fw-bold">Split Quantity</h6>
                                                            <small class="text-muted">1 HU = 1 PC</small>
                                                            <div class="mt-2">
                                                                <span class="badge bg-primary">Auto</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check card-mode-selector">
                                                <input class="form-check-input" type="radio" name="creationMode" id="modeSingle" value="single">
                                                <label class="form-check-label w-100" for="modeSingle">
                                                    <div class="card border-2">
                                                        <div class="card-body text-center">
                                                            <i class="fas fa-cube fa-2x text-success mb-2"></i>
                                                            <h6 class="fw-bold">Single HU</h6>
                                                            <small class="text-muted">1 HU = Total Qty</small>
                                                            <div class="mt-2">
                                                                <span class="badge bg-success">Auto</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check card-mode-selector">
                                                <input class="form-check-input" type="radio" name="creationMode" id="modePartial" value="partial">
                                                <label class="form-check-label w-100" for="modePartial">
                                                    <div class="card border-2">
                                                        <div class="card-body text-center">
                                                            <i class="fas fa-sliders fa-2x text-warning mb-2"></i>
                                                            <h6 class="fw-bold">Partial Quantity</h6>
                                                            <small class="text-muted">Custom Qty per HU</small>
                                                            <div class="mt-2">
                                                                <span class="badge bg-warning">Manual</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <div id="modeSplitDesc" class="mode-description">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <strong>Split Quantity:</strong> Setiap 1 PC akan dibuat sebagai HU terpisah (maksimal sesuai stock tersedia)
                                            </small>
                                        </div>
                                        <div id="modeSingleDesc" class="mode-description d-none">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <strong>Single HU:</strong> Semua quantity untuk material yang sama akan digabung dalam 1 HU
                                            </small>
                                        </div>
                                        <div id="modePartialDesc" class="mode-description d-none">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <strong>Partial Quantity:</strong> Buat HU dengan quantity custom (bisa kurang dari stock tersedia)
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Partial Quantity Settings -->
                    <div id="partialSettings" class="row mb-4 d-none">
                        <div class="col-12">
                            <div class="card bg-warning bg-opacity-10 border-warning">
                                <div class="card-body">
                                    <h6 class="fw-semibold text-gray-700 mb-3">
                                        <i class="fas fa-edit me-2 text-warning"></i>
                                        Pengaturan Quantity Parsial
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Total Quantity yang Akan Dibuat</label>
                                                <input type="number" class="form-control" id="partialTotalQty"
                                                       placeholder="Masukkan jumlah PC yang ingin dibuat" min="1" step="1">
                                                <small class="text-muted">Jumlah total PC yang akan dibuat sebagai HU (bisa kurang dari stock tersedia)</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Quantity per HU</label>
                                                <select class="form-select" id="partialQtyPerHU">
                                                    <option value="1">1 PC per HU</option>
                                                    <option value="2">2 PC per HU</option>
                                                    <option value="5">5 PC per HU</option>
                                                    <option value="10">10 PC per HU</option>
                                                    <option value="custom">Custom...</option>
                                                </select>
                                                <div id="customQtyContainer" class="mt-2 d-none">
                                                    <input type="number" class="form-control" id="customQtyPerHU"
                                                           placeholder="Masukkan quantity per HU" min="1" step="1">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="alert alert-info mt-2">
                                        <small>
                                            <i class="fas fa-lightbulb me-1"></i>
                                            <strong>Tips:</strong> Mode ini memungkinkan Anda membuat HU dengan quantity tertentu meskipun stock tersedia lebih banyak.
                                            Contoh: Stock 80 PC, bisa buat 40 PC dulu.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Auto Sequence Settings -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h6 class="fw-semibold text-gray-700 mb-3">
                                        <i class="fas fa-magic me-2 text-purple-500"></i>
                                        Auto Sequence Settings
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-gray-700">
                                                    <i class="fas fa-barcode me-1 text-purple-500"></i>
                                                    HU External ID
                                                </label>
                                                <div class="alert alert-info p-2">
                                                    <small>
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        HU External ID harus 10 digit angka (contoh: 9900000014).
                                                        Isi manual pada HU pertama, maka HU berikutnya akan otomatis terisi secara sequence.
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-gray-700">
                                                    <i class="fas fa-box me-1 text-purple-500"></i>
                                                    Packaging Material
                                                </label>
                                                <select class="form-select" id="globalPackMat">
                                                    <option value="">Pilih Packaging Material (Apply ke Semua)</option>
                                                    <option value="VSTDPLTBW01">VSTDPLTBW01</option>
                                                    <option value="VSTDPLBW002">VSTDPLBW002</option>
                                                    <option value="50016873">50016873</option>
                                                </select>
                                                <small class="text-muted">
                                                    Pilih sekali, semua HU akan menggunakan packaging material yang sama
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('hu.store-multiple') }}" method="POST" id="huForm">
                        @csrf
                        <input type="hidden" id="base_unit_qty" name="base_unit_qty" value="">
                        <input type="hidden" id="sap_user" name="sap_user" value="">
                        <input type="hidden" id="sap_password" name="sap_password" value="">
                        <input type="hidden" id="creation_mode" name="creation_mode" value="split">
                        <input type="hidden" id="total_hus" name="total_hus" value="0">

                        <!-- HU List Section -->
                        <div class="row">
                            <div class="col-12 mb-3">
                                <h6 class="fw-semibold text-gray-700 mb-3 border-bottom pb-2">
                                    <i class="fas fa-list-ol me-2 text-purple-500"></i>
                                    Daftar Handling Units
                                    <span id="huCount" class="badge bg-purple ms-2">0 HUs</span>
                                    <span id="totalQty" class="badge bg-success ms-2">0 PC</span>
                                </h6>
                                <div class="alert alert-info bg-light border-0 py-2">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <span id="modeDescription">Setiap 1 PC akan dibuat sebagai HU terpisah untuk material dengan quantity > 1</span>
                                        <br>
                                        <i class="fas fa-lightbulb me-1 text-warning"></i>
                                        <strong>Tip:</strong> Isi HU External ID dan Packaging Material pada HU pertama, yang lain akan mengikuti secara otomatis
                                    </small>
                                </div>
                            </div>

                            <div class="col-12">
                                <div id="hus-container" class="compact-list-container mb-3">
                                    <!-- HUs will be dynamically added here -->
                                </div>

                                <div id="husPreview" class="text-center py-5 border-2 border-dashed rounded bg-light">
                                    <i class="fas fa-pallet fa-3x text-gray-400 mb-3"></i>
                                    <h6 class="text-muted mb-2">Belum Ada Handling Unit</h6>
                                    <p class="text-muted small mb-0">Data HU akan ditampilkan di sini setelah material dipilih dari halaman utama</p>
                                </div>

                                <!-- Stock Validation Info -->
                                <div id="stockValidation" class="alert alert-warning mt-3 d-none">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
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
                    <i class="fas fa-check me-2"></i>Confirm & Create All HUs
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
    background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 12px; margin-bottom: 12px;
    border-left: 4px solid #8b5cf6; position: relative;
}
.compact-hu-item:hover { background: #e9ecef; border-color: #dee2e6; }
.compact-hu-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.compact-hu-title { font-weight: 600; color: #333; font-size: 0.9rem; }
.compact-hu-badge { background: #8b5cf6; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 500; }
.compact-hu-content { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.8rem; }
.compact-hu-field { display: flex; flex-direction: column; }
.compact-hu-label { font-weight: 500; color: #6c757d; font-size: 0.75rem; margin-bottom: 4px; }
.compact-hu-value { color: #333; font-weight: 400; }
.compact-hu-input { width: 100%; padding: 6px 8px; border: 1px solid #ced4da; border-radius: 4px; font-size: 0.8rem; }
.compact-hu-input:focus { border-color: #8b5cf6; outline: none; box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.25); }
.compact-hu-select { width: 100%; padding: 6px 8px; border: 1px solid #ced4da; border-radius: 4px; font-size: 0.8rem; background: white; }
.compact-hu-select:focus { border-color: #8b5cf6; outline: none; box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.25); }
.quantity-badge { background: #10b981; color: white; padding: 2px 6px; border-radius: 8px; font-size: 0.7rem; font-weight: 500; margin-left: 4px; }
.split-indicator { background: #f59e0b; color: white; padding: 2px 6px; border-radius: 8px; font-size: 0.7rem; font-weight: 500; margin-left: 4px; }
.auto-sequence-hint { background: #e9d5ff; border: 1px solid #c4b5fd; border-radius: 4px; padding: 4px 8px; font-size: 0.7rem; color: #6d28d9; margin-top: 2px; }

/* Mode Selector Styles */
.card-mode-selector .form-check-input {
    position: absolute;
    opacity: 0;
}
.card-mode-selector .card {
    cursor: pointer;
    transition: all 0.3s ease;
    border-color: #dee2e6 !important;
    border-width: 2px !important;
}
.card-mode-selector .form-check-input:checked + .card {
    border-color: #8b5cf6 !important;
    box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.25);
}
.card-mode-selector #modeSplit:checked + .card { border-color: #3b82f6 !important; }
.card-mode-selector #modeSingle:checked + .card { border-color: #10b981 !important; }
.card-mode-selector #modePartial:checked + .card { border-color: #f59e0b !important; }

.mode-description { display: block; }
</style>
@endpush

@push('scripts')
<script>
// ===== GLOBAL VARIABLES =====
var huCount = 0;
var totalQuantity = 0;
let creationMode = 'split'; // split, single, partial
let lastPackMat = '';
let availableStocks = {}; // Menyimpan informasi stock tersedia
let currentMaterials = []; // Menyimpan data material saat ini

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== SKENARIO 3 LOADED ===');
    initializeApp();
});

function initializeApp() {
    setupEventListeners();
    loadInitialData();
    updateModeDisplay();
}

function setupEventListeners() {
    // Mode selection - FIXED EVENT LISTENER
    document.querySelectorAll('input[name="creationMode"]').forEach(radio => {
        radio.addEventListener('change', function() {
            creationMode = this.value;
            updateModeDisplay();
            // Process materials immediately when mode changes
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

        // Validasi stock untuk partial mode
        if (creationMode === 'partial') {
            if (!validatePartialStock()) return;
        }

        showMessage('Melanjutkan pembuatan HU...', 'info');
        const sapModal = new bootstrap.Modal(document.getElementById('sapCredentialsModal'));
        sapModal.show();
    });

    // Confirm SAP Credentials
    document.getElementById('confirmSapCredentials').addEventListener('click', function() {
        const modalSapUser = document.querySelector('#sapCredentialsModal input[name="sap_user_modal"]').value;
        const modalSapPassword = document.querySelector('#sapCredentialsModal input[name="sap_password_modal"]').value;

        if (!modalSapUser || !modalSapPassword) {
            showMessage('SAP User dan Password harus diisi', 'error');
            return;
        }

        document.getElementById('sap_user').value = modalSapUser;
        document.getElementById('sap_password').value = modalSapPassword;
        document.getElementById('creation_mode').value = creationMode;
        document.getElementById('total_hus').value = huCount;

        const confirmBtn = document.getElementById('confirmSapCredentials');
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating All HUs...';
        confirmBtn.disabled = true;

        const sapModal = bootstrap.Modal.getInstance(document.getElementById('sapCredentialsModal'));
        sapModal.hide();

        setTimeout(() => {
            document.getElementById('huForm').submit();
        }, 500);
    });

    // Reset modal
    document.getElementById('sapCredentialsModal').addEventListener('hidden.bs.modal', function() {
        document.querySelector('#sapCredentialsModal form').reset();
        const confirmBtn = document.getElementById('confirmSapCredentials');
        confirmBtn.innerHTML = '<i class="fas fa-check me-2"></i>Confirm & Create All HUs';
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
            modeDescription.textContent = 'Setiap 1 PC akan dibuat sebagai HU terpisah untuk material dengan quantity > 1';
            break;
        case 'single':
            modeDescription.textContent = 'Setiap material akan dibuat sebagai HU terpisah (quantity digabung per material)';
            break;
        case 'partial':
            modeDescription.textContent = 'Buat HU dengan quantity custom sesuai pengaturan di atas';
            break;
    }

    // Show/hide partial settings
    const partialSettings = document.getElementById('partialSettings');
    if (creationMode === 'partial') {
        partialSettings.classList.remove('d-none');
    } else {
        partialSettings.classList.add('d-none');
    }

    // Update form action based on mode
    document.getElementById('creation_mode').value = creationMode;
}

function getModeColor(mode) {
    switch(mode) {
        case 'split': return '#3b82f6';
        case 'single': return '#10b981';
        case 'partial': return '#f59e0b';
        default: return '#8b5cf6';
    }
}

function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function loadInitialData() {
    const serverSuccessAlert = document.querySelector('.alert-success');
    const serverErrorAlert = document.querySelector('.alert-danger');

    if (serverSuccessAlert) {
        sessionStorage.removeItem('scenario3_data');
        setTimeout(() => serverSuccessAlert.remove(), 5000);
    }

    if (serverErrorAlert) {
        setTimeout(() => serverErrorAlert.remove(), 8000);
    }

    const scenarioDataRaw = sessionStorage.getItem('scenario3_data');
    if (scenarioDataRaw && !serverSuccessAlert && !serverErrorAlert) {
        processMaterialsFromSessionStorage();
    } else if (!scenarioDataRaw && !serverSuccessAlert && !serverErrorAlert) {
        showMessage('Silakan pilih material dari halaman utama dengan drag & drop terlebih dahulu.', 'info');
    }

    setTimeout(autoSetPackagingMaterialForAllHUs, 500);
}

// ===== CORE FUNCTIONS =====
function processMaterialsFromSessionStorage() {
    const scenarioDataRaw = sessionStorage.getItem('scenario3_data');
    if (!scenarioDataRaw) {
        showMessage('Tidak ada data material. Silakan pilih material dari halaman utama.', 'warning');
        return;
    }

    try {
        const materials = JSON.parse(scenarioDataRaw);
        if (!materials || !Array.isArray(materials) || materials.length === 0) {
            showMessage('Data material tidak valid.', 'warning');
            return;
        }

        currentMaterials = materials; // Store current materials

        // Filter out materials with zero stock
        const materialsWithStock = materials.filter(item => {
            const stockQty = parseFloat(item.stock_quantity || '0');
            return stockQty > 0;
        });

        if (materialsWithStock.length === 0) {
            showMessage('Tidak ada material dengan stock tersedia. Silakan pilih material lain.', 'error');
            // Clear invalid data from session storage
            sessionStorage.removeItem('scenario3_data');
            return;
        }

        // If some materials were filtered out, show warning
        if (materialsWithStock.length < materials.length) {
            const filteredCount = materials.length - materialsWithStock.length;
            showMessage(`${filteredCount} material dengan stock 0 telah di-filter. Hanya material dengan stock tersedia yang akan diproses.`, 'warning');

            // Update session storage with filtered data
            sessionStorage.setItem('scenario3_data', JSON.stringify(materialsWithStock));
        }

        processMaterials(materialsWithStock);

    } catch (error) {
        console.error('Error parsing materials:', error);
        showMessage('Error memuat data material. Silakan pilih ulang.', 'error');
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

        // Use the stock quantity from the dragged item directly
        // This is more reliable than fetching from server
        availableStocks[materialKey] = {
            available: stockQty,
            message: `Stock tersedia: ${stockQty} PC`
        };

        materialsProcessed++;
        if (materialsProcessed === materials.length) callback();
    });
}

function processMaterials(materials) {
    // Clear existing HUs
    huCount = 0;
    totalQuantity = 0;
    document.getElementById('hus-container').innerHTML = '';

    // Load available stocks first
    loadAvailableStocks(materials, () => {
        const groupedMaterials = groupMaterials(materials);

        Object.values(groupedMaterials).forEach(group => {
            addHUGroupToForm(group);
        });

        setupAutoSequenceListeners();

        // Show/Hide containers based on HU count
        if (huCount > 0) {
            document.getElementById('husPreview').style.display = 'none';
            document.getElementById('hus-container').style.display = 'block';
        } else {
            document.getElementById('husPreview').style.display = 'block';
            document.getElementById('hus-container').style.display = 'none';
        }

        updateHUSummary();

        // Show stock validation if needed
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

    console.log(`Processing group: ${formattedMaterial}, Available: ${availableStock}, Mode: ${creationMode}`);

    switch(creationMode) {
        case 'split':
            // 1 HU = 1 PC
            const splitCount = Math.min(availableStock, group.totalQuantity);
            console.log(`Split mode: creating ${splitCount} HUs`);
            for (let i = 0; i < splitCount; i++) {
                addSingleHUToForm(group, i + 1, splitCount, true, 1);
            }
            break;

        case 'single':
            // 1 HU = Total Qty
            console.log(`Single mode: creating 1 HU with ${availableStock} PC`);
            addSingleHUToForm(group, 1, 1, false, availableStock);
            break;

        case 'partial':
            // Custom quantity
            const partialSettings = getPartialSettings();
            if (partialSettings) {
                const { totalQty, qtyPerHU } = partialSettings;
                const actualTotalQty = Math.min(totalQty, availableStock);
                const numberOfHUs = Math.ceil(actualTotalQty / qtyPerHU);

                console.log(`Partial mode: creating ${numberOfHUs} HUs, total ${actualTotalQty} PC, ${qtyPerHU} PC per HU`);

                for (let i = 0; i < numberOfHUs; i++) {
                    const isLastHU = i === numberOfHUs - 1;
                    const huQty = isLastHU ? actualTotalQty - (i * qtyPerHU) : qtyPerHU;
                    addSingleHUToForm(group, i + 1, numberOfHUs, numberOfHUs > 1, huQty);
                }
            } else {
                console.log('Partial settings not valid');
            }
            break;
    }
}

function getPartialSettings() {
    const totalQtyInput = document.getElementById('partialTotalQty');
    const qtyPerHUSelect = document.getElementById('partialQtyPerHU');

    if (!totalQtyInput.value) {
        // Don't show error message here, just return null
        return null;
    }

    const totalQty = parseInt(totalQtyInput.value);
    if (totalQty <= 0) {
        showMessage('Total quantity harus lebih dari 0', 'warning');
        return null;
    }

    let qtyPerHU;
    if (qtyPerHUSelect.value === 'custom') {
        const customQty = document.getElementById('customQtyPerHU').value;
        if (!customQty || customQty <= 0) {
            showMessage('Quantity per HU harus lebih dari 0', 'warning');
            return null;
        }
        qtyPerHU = parseInt(customQty);
    } else {
        qtyPerHU = parseInt(qtyPerHUSelect.value);
    }

    if (qtyPerHU <= 0) {
        showMessage('Quantity per HU harus lebih dari 0', 'warning');
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
            <div class="compact-hu-field mb-2">
                <span class="compact-hu-label">Deskripsi</span>
                <span class="compact-hu-value">${group.materialDescription}</span>
            </div>
        ` : ''}

        <div class="compact-hu-content">
            <div class="compact-hu-field">
                <span class="compact-hu-label">HU External ID <span class="text-danger">*</span></span>
                <input type="text" class="compact-hu-input hu-exid-input" name="hus[${huCount}][hu_exid]"
                       value="${autoHuExid}" required placeholder="10 digit angka" maxlength="10"
                       pattern="\\d{10}" title="Harus 10 digit angka">
                ${huCount === 0 ? '<div class="auto-sequence-hint">Isi manual 10 digit angka, HU berikutnya akan auto sequence</div>' : ''}
            </div>

            <div class="compact-hu-field">
                <span class="compact-hu-label">Packaging Material <span class="text-danger">*</span></span>
                <select class="compact-hu-select pack-mat-select" name="hus[${huCount}][pack_mat]" required>
                    <option value="">Pilih Pack Mat</option>
                    <option value="VSTDPLTBW01" ${lastPackMat === 'VSTDPLTBW01' ? 'selected' : ''}>VSTDPLTBW01</option>
                    <option value="VSTDPLBW002" ${lastPackMat === 'VSTDPLBW002' ? 'selected' : ''}>VSTDPLBW002</option>
                    <option value="50016873" ${lastPackMat === '50016873' ? 'selected' : ''}>50016873</option>
                </select>
                ${huCount === 0 ? '<div class="auto-sequence-hint">Pilih sekali, apply ke semua HU</div>' : ''}
            </div>

            <div class="compact-hu-field">
                <span class="compact-hu-label">Plant <span class="text-danger">*</span></span>
                <input type="text" class="compact-hu-input bg-light" name="hus[${huCount}][plant]"
                       value="${group.plant}" readonly>
            </div>

            <div class="compact-hu-field">
                <span class="compact-hu-label">Storage Location <span class="text-danger">*</span></span>
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
                <span class="compact-hu-label">Pack Quantity <span class="text-danger">*</span></span>
                <input type="number" class="compact-hu-input" name="hus[${huCount}][pack_qty]"
                       value="${quantity}" step="0.001" min="0.001"
                       required data-max-qty="${quantity}" readonly>
                <small class="text-muted mt-1">
                    ${isSplit && totalHUs > 1 ?
                        `Part ${sequence} of ${totalHUs} (${quantity} PC per HU)` :
                        `Quantity: ${quantity.toLocaleString('id-ID')} PC`}
                </small>
            </div>

            <div class="compact-hu-field">
                <span class="compact-hu-label">Sales Order No</span>
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

    // Check for stock issues
    for (const [key, stock] of Object.entries(availableStocks)) {
        const [material, batch] = key.split('_');
        if (stock.available <= 0) {
            hasStockIssue = true;
            message += `Material ${material} (Batch: ${batch || '-'}): Stock tidak tersedia<br>`;
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
        showMessage('Silakan isi pengaturan quantity untuk mode partial', 'warning');
        return false;
    }

    const { totalQty } = partialSettings;

    // Check if any material has insufficient stock
    for (const [key, stock] of Object.entries(availableStocks)) {
        if (stock.available < totalQty) {
            showMessage(`Stock tidak mencukupi untuk membuat ${totalQty} PC. Stock tersedia: ${stock.available} PC`, 'warning');
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
            showMessage(`Sequence melebihi 9999999999. Tidak dapat generate HU External ID.`, 'error');
            return;
        }
        input.value = newNumber.toString().padStart(10, '0');
    });
    showMessage(`Sequence HU External ID berhasil diupdate mulai dari ${startNumber}`, 'success');
}

function applyPackMatToAll() {
    const packMatSelects = document.querySelectorAll('#hus-container .pack-mat-select');
    packMatSelects.forEach(select => select.value = lastPackMat);
    if (lastPackMat) {
        showMessage(`Packaging Material "${lastPackMat}" berhasil diterapkan ke semua HU`, 'success');
    }
}

function validateForm() {
    if (huCount === 0) {
        showMessage('Tidak ada HU yang ditambahkan. Silakan pilih dari halaman utama.', 'error');
        return false;
    }

    let validationError = false;

    // Validasi HU External ID
    const huExidInputs = document.querySelectorAll('#hus-container input[name*="[hu_exid]"]');
    huExidInputs.forEach((input, index) => {
        const huExid = input.value.trim();
        if (!/^\d{10}$/.test(huExid)) {
            showMessage(`HU External ID untuk HU ${index + 1} harus 10 digit angka`, 'error');
            input.focus();
            validationError = true;
            return;
        }
    });

    if (validationError) return false;

    // Validasi Packaging Material
    const packMatSelects = document.querySelectorAll('#hus-container select[name*="[pack_mat]"]');
    packMatSelects.forEach((select, index) => {
        if (!select.value) {
            showMessage(`Packaging Material untuk HU ${index + 1} harus dipilih`, 'error');
            select.focus();
            validationError = true;
            return;
        }
    });

    return !validationError;
}

function resetForm() {
    if (confirm('Apakah Anda yakin ingin membatalkan? Semua data yang telah diisi akan hilang.')) {
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
            showMessage(`Packaging Material otomatis di-set ke "50016873" untuk semua HU (ZMG1)`, 'success');
        } else if (magry === 'ZMG2') {
            globalPackMatSelect.value = 'VSTDPLTBW01';
            lastPackMat = 'VSTDPLTBW01';
            applyPackMatToAll();
            showMessage(`Packaging Material otomatis di-set ke "VSTDPLTBW01" untuk semua HU (ZMG2)`, 'success');
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
        <div class="alert ${alertClass} alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fas ${iconClass} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);

    // Auto-hide based on type
    const autoHideTime = type === 'error' ? 8000 :
                        type === 'warning' ? 6000 : 4000;

    setTimeout(() => {
        const alert = document.querySelector('.alert.' + alertClass);
        if (alert && !alert.classList.contains('alert-success') && !alert.classList.contains('alert-danger')) {
            alert.remove();
        }
    }, autoHideTime);
}
</script>
@endpush
