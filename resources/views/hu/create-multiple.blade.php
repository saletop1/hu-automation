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
                    <p class="text-muted mb-0">Buat Multiple HU (1 HU = 1 Material)</p>
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
                    <!-- Toggle Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="fw-semibold text-gray-700 mb-1">
                                                <i class="fas fa-cogs me-2 text-purple-500"></i>
                                                Opsi Pembuatan HU
                                            </h6>
                                            <p class="text-muted small mb-0">
                                                Untuk material dengan quantity lebih dari 1, pilih mode pembuatan HU
                                            </p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="splitQuantityToggle" checked>
                                            <label class="form-check-label fw-semibold" for="splitQuantityToggle">
                                                <span id="toggleLabel">Split Quantity (1 HU = 1 PC)</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <span id="toggleDescription">
                                                <strong>Split Quantity:</strong> Setiap 1 PC akan dibuat sebagai HU terpisah
                                            </span>
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
                        <!-- Hidden inputs for SAP Credentials -->
                        <input type="hidden" id="sap_user" name="sap_user" value="">
                        <input type="hidden" id="sap_password" name="sap_password" value="">

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
                                        <span id="modeDescription">Setiap material akan dibuat sebagai HU terpisah</span>
                                        <br>
                                        <i class="fas fa-lightbulb me-1 text-warning"></i>
                                        <strong>Tip:</strong> Isi HU External ID dan Packaging Material pada HU pertama, yang lain akan mengikuti secara otomatis
                                    </small>
                                </div>
                            </div>

                            <div class="col-12">
                                <!-- Compact List Container -->
                                <div id="hus-container" class="compact-list-container mb-3">
                                    <!-- HUs will be dynamically added here -->
                                </div>

                                <!-- Placeholder when no HUs -->
                                <div id="husPreview" class="text-center py-5 border-2 border-dashed rounded bg-light">
                                    <i class="fas fa-pallet fa-3x text-gray-400 mb-3"></i>
                                    <h6 class="text-muted mb-2">Belum Ada Handling Unit</h6>
                                    <p class="text-muted small mb-0">Data HU akan ditampilkan di sini setelah material dipilih dari halaman utama</p>
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
.border-dashed {
    border-style: dashed !important;
}
.compact-list-container {
    display: none; /* Sembunyikan saat kosong */
}
.compact-hu-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 12px;
    transition: all 0.2s ease;
    border-left: 4px solid #8b5cf6;
}
.compact-hu-item:hover {
    background: #e9ecef;
    border-color: #dee2e6;
}
.compact-hu-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 12px;
}
.compact-hu-title {
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
}
.compact-hu-badge {
    background: #8b5cf6;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}
.compact-hu-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    font-size: 0.8rem;
}
.compact-hu-field {
    display: flex;
    flex-direction: column;
}
.compact-hu-label {
    font-weight: 500;
    color: #6c757d;
    font-size: 0.75rem;
    margin-bottom: 4px;
}
.compact-hu-value {
    color: #333;
    font-weight: 400;
}
.compact-hu-input {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 0.8rem;
}
.compact-hu-input:focus {
    border-color: #8b5cf6;
    outline: none;
    box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.25);
}
.compact-hu-select {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 0.8rem;
    background: white;
}
.compact-hu-select:focus {
    border-color: #8b5cf6;
    outline: none;
    box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.25);
}
.quantity-badge {
    background: #10b981;
    color: white;
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 500;
    margin-left: 4px;
}
.split-indicator {
    background: #f59e0b;
    color: white;
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 500;
    margin-left: 4px;
}
.btn-outline-purple {
    border-color: #8b5cf6;
    color: #8b5cf6;
}
.btn-outline-purple:hover {
    background-color: #8b5cf6;
    color: white;
}
.auto-sequence-hint {
    background: #e9d5ff;
    border: 1px solid #c4b5fd;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 0.7rem;
    color: #6d28d9;
    margin-top: 2px;
}
</style>
@endpush

@push('scripts')
<script>
// Global variable untuk menghitung HUs
let huCount = 0;
let splitQuantityMode = true; // Default mode: split quantity
let lastPackMat = '';
let lastHuExidBase = '';
let lastHuExidNumber = 0;

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== SKENARIO 3 LOADED ===');

    // Setup toggle event listener
    document.getElementById('splitQuantityToggle').addEventListener('change', function() {
        splitQuantityMode = this.checked;
        updateToggleDisplay();
        processMaterialsFromSessionStorage();
    });

    // Setup global packaging material change listener
    document.getElementById('globalPackMat').addEventListener('change', function() {
        lastPackMat = this.value;
        applyPackMatToAll();
    });

    // Cek jika ada pesan sukses dari server dan clear sessionStorage
    const serverSuccessAlert = document.querySelector('.alert-success');
    const serverErrorAlert = document.querySelector('.alert-danger');

    if (serverSuccessAlert) {
        console.log('✅ HUs created successfully, clearing sessionStorage');
        sessionStorage.removeItem('scenario3_data');

        // Auto-hide success alert setelah 5 detik
        setTimeout(() => {
            serverSuccessAlert.remove();
        }, 5000);
    }

    if (serverErrorAlert) {
        console.log('❌ HUs creation failed');
        // Auto-hide error alert setelah 8 detik
        setTimeout(() => {
            serverErrorAlert.remove();
        }, 8000);
    }

    // Ambil data dari sessionStorage hanya jika tidak ada pesan sukses/error dari server
    const scenarioDataRaw = sessionStorage.getItem('scenario3_data');
    console.log('Raw data from sessionStorage:', scenarioDataRaw);

    if (scenarioDataRaw && !serverSuccessAlert && !serverErrorAlert) {
        processMaterialsFromSessionStorage();
    } else {
        if (!scenarioDataRaw && !serverSuccessAlert && !serverErrorAlert) {
            console.warn('❌ No data found in sessionStorage for scenario3_data');
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
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating All HUs...';
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
        confirmBtn.innerHTML = '<i class="fas fa-check me-2"></i>Confirm & Create All HUs';
        confirmBtn.disabled = false;
    });
});

function processMaterialsFromSessionStorage() {
    const scenarioDataRaw = sessionStorage.getItem('scenario3_data');

    if (scenarioDataRaw) {
        try {
            const materials = JSON.parse(scenarioDataRaw);
            console.log('Parsed materials:', materials);

            if (materials && Array.isArray(materials) && materials.length > 0) {
                console.log('✅ Data valid, processing materials...');

                // Reset counter
                huCount = 0;

                // Clear container
                document.getElementById('hus-container').innerHTML = '';

                // Group materials by material+batch+salesOrder
                const groupedMaterials = groupMaterials(materials);

                // Tambahkan setiap material/group sebagai HU
                Object.values(groupedMaterials).forEach((group, index) => {
                    console.log(`Processing group ${index}:`, group);
                    addHUGroupToForm(group);
                });

                // Setup event listeners untuk auto sequence
                setupAutoSequenceListeners();

                // Tampilkan container dan sembunyikan placeholder
                document.getElementById('husPreview').style.display = 'none';
                document.getElementById('hus-container').style.display = 'block';
                document.getElementById('huCount').textContent = `${huCount} HUs`;

                console.log(`✅ Successfully loaded ${huCount} HUs`);

            } else {
                console.warn('❌ Data invalid - empty or not array');
                showMessage('Data material tidak valid. Silakan pilih ulang dari halaman utama.', 'warning');
            }
        } catch (error) {
            console.error('❌ Error parsing scenario data:', error);
            showMessage('Error memuat data material. Data mungkin korup. Silakan pilih ulang.', 'error');
            sessionStorage.removeItem('scenario3_data');
        }
    }
}

function setupAutoSequenceListeners() {
    // HU External ID auto sequence - FIXED: menggunakan event delegation yang benar
    document.getElementById('hus-container').addEventListener('blur', function(e) {
        if (e.target.classList.contains('hu-exid-input')) {
            const input = e.target;
            const currentValue = input.value.trim();

            if (currentValue) {
                // Validasi format 10 digit angka
                if (!/^\d{10}$/.test(currentValue)) {
                    showMessage('HU External ID harus 10 digit angka (contoh: 9900000014)', 'error');
                    input.focus();
                    return;
                }

                // Ekstrak base dan number dari input
                const baseNumber = parseInt(currentValue);

                console.log('HU External ID manual input detected:', {
                    input: currentValue,
                    baseNumber: baseNumber
                });

                // Update semua HU External ID berdasarkan sequence
                updateAllHuExidSequence(baseNumber);
            }
        }
    }, true);

    // Packaging Material auto apply - FIXED: gunakan event delegation
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
        groups[key].items.push({
            ...item,
            quantity: quantity
        });
    });

    return groups;
}

function updateToggleDisplay() {
    const toggleLabel = document.getElementById('toggleLabel');
    const toggleDescription = document.getElementById('toggleDescription');
    const modeDescription = document.getElementById('modeDescription');

    if (splitQuantityMode) {
        toggleLabel.textContent = 'Split Quantity (1 HU = 1 PC)';
        toggleDescription.innerHTML = '<strong>Split Quantity:</strong> Setiap 1 PC akan dibuat sebagai HU terpisah';
        modeDescription.textContent = 'Setiap 1 PC akan dibuat sebagai HU terpisah untuk material dengan quantity > 1';
    } else {
        toggleLabel.textContent = 'Single HU (1 HU = Total Qty)';
        toggleDescription.innerHTML = '<strong>Single HU:</strong> Semua quantity untuk material yang sama akan digabung dalam 1 HU';
        modeDescription.textContent = 'Setiap material akan dibuat sebagai HU terpisah (quantity digabung per material)';
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

// Fungsi add HU group to form
function addHUGroupToForm(group) {
    const container = document.getElementById('hus-container');
    const formattedMaterial = formatMaterialNumber(group.material);

    if (splitQuantityMode && group.totalQuantity > 1) {
        // Split mode: buat HU terpisah untuk setiap PC
        console.log(`Splitting ${group.totalQuantity} PCs into separate HUs for ${formattedMaterial}`);

        for (let i = 0; i < group.totalQuantity; i++) {
            addSingleHUToForm(group, i + 1, group.totalQuantity, true);
        }
    } else {
        // Single HU mode: buat 1 HU dengan total quantity
        addSingleHUToForm(group, 1, group.totalQuantity, false);
    }
}

// Fungsi add single HU to form
function addSingleHUToForm(group, sequence, totalQuantity, isSplit) {
    const container = document.getElementById('hus-container');

    const formattedMaterial = formatMaterialNumber(group.material);
    const displayQuantity = isSplit ? 1 : totalQuantity;
    const maxQuantity = isSplit ? 1 : totalQuantity;

    // Generate HU External ID otomatis - FIXED: format 10 digit angka
    // Default sequence: 9900000001, 9900000002, dst
    const defaultStartNumber = 9900000000;
    const sequenceNumber = huCount + 1;
    const autoHuExid = (defaultStartNumber + sequenceNumber).toString().padStart(10, '0');

    console.log(`📝 Creating HU ${huCount}:`, {
        material: formattedMaterial,
        batch: group.batch,
        quantity: displayQuantity,
        totalQuantity: totalQuantity,
        isSplit: isSplit,
        huExid: autoHuExid
    });

    const newHU = document.createElement('div');
    newHU.className = 'compact-hu-item';

    let splitIndicator = '';
    if (isSplit && totalQuantity > 1) {
        splitIndicator = `<span class="split-indicator">${sequence}/${totalQuantity}</span>`;
    }

    let quantityBadge = '';
    if (totalQuantity > 1 && !isSplit) {
        quantityBadge = `<span class="quantity-badge">${totalQuantity} PCs</span>`;
    }

    newHU.innerHTML = `
        <div class="compact-hu-header">
            <div class="compact-hu-title">
                <i class="fas fa-pallet me-1 text-purple-500"></i>
                HU ${huCount + 1}: ${formattedMaterial}
                ${splitIndicator}
                ${quantityBadge}
            </div>
            <div class="compact-hu-badge">${displayQuantity.toLocaleString('id-ID')} PC</div>
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
                       value="${displayQuantity}" step="0.001" min="0.001" max="${maxQuantity}"
                       required data-max-qty="${maxQuantity}" ${isSplit ? 'readonly' : ''}>
                <small class="text-muted mt-1">
                    ${isSplit && totalQuantity > 1 ?
                        `Part ${sequence} of ${totalQuantity} (Auto: 1 PC per HU)` :
                        `Stock: ${totalQuantity.toLocaleString('id-ID')} PC`}
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
}

// Fungsi untuk update semua HU External ID berdasarkan sequence
function updateAllHuExidSequence(startNumber) {
    const huExidInputs = document.querySelectorAll('#hus-container .hu-exid-input');

    huExidInputs.forEach((input, index) => {
        const newNumber = startNumber + index;

        // Validasi agar tidak melebihi 10 digit
        if (newNumber > 9999999999) {
            showMessage(`Sequence melebihi 9999999999. Tidak dapat generate HU External ID.`, 'error');
            return;
        }

        const newHuExid = newNumber.toString().padStart(10, '0');
        input.value = newHuExid;
    });

    showMessage(`Sequence HU External ID berhasil diupdate mulai dari ${startNumber}`, 'success');
}

function applyPackMatToAll() {
    const packMatSelects = document.querySelectorAll('#hus-container .pack-mat-select');

    packMatSelects.forEach(select => {
        select.value = lastPackMat;
    });

    if (lastPackMat) {
        showMessage(`Packaging Material "${lastPackMat}" berhasil diterapkan ke semua HU`, 'success');
    }
}

function validateForm() {
    console.log('🚀 Form submission started');
    console.log('Total HUs:', huCount);
    console.log('Split Quantity Mode:', splitQuantityMode);

    // Validasi: pastikan ada HU
    if (huCount === 0) {
        showMessage('Tidak ada HU yang ditambahkan. Silakan pilih dari halaman utama.', 'error');
        return false;
    }

    // Validasi HU External ID dan Pack Quantity untuk setiap HU
    let validationError = false;
    const huExidInputs = document.querySelectorAll('#hus-container input[name*="[hu_exid]"]');
    const packQtyInputs = document.querySelectorAll('#hus-container input[name*="[pack_qty]"]');
    const packMatSelects = document.querySelectorAll('#hus-container select[name*="[pack_mat]"]');

    // Validasi HU External ID - FIXED: harus 10 digit angka
    huExidInputs.forEach((input, index) => {
        const huExid = input.value.trim();

        // Validasi format 10 digit angka
        if (!/^\d{10}$/.test(huExid)) {
            showMessage(`HU External ID untuk HU ${index + 1} harus 10 digit angka (contoh: 9900000014)`, 'error');
            input.focus();
            validationError = true;
            return;
        }

        // Validasi range angka (1 - 9999999999)
        const huExidNumber = parseInt(huExid);
        if (huExidNumber < 1 || huExidNumber > 9999999999) {
            showMessage(`HU External ID untuk HU ${index + 1} harus antara 0000000001 - 9999999999`, 'error');
            input.focus();
            validationError = true;
            return;
        }
    });

    if (validationError) {
        return false;
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
        return false;
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

        input.value = enteredQty;
    });

    if (validationError) {
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
