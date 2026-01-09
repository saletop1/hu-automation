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
                    <button type="button" class="btn btn-success btn-sm px-3" id="createHuButton">
                        <i class="fas fa-save me-1"></i>Create HU
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Header Section -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex align-items-center">
                <div class="bg-green-100 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                    <i class="fas fa-boxes text-green-600"></i>
                </div>
                <div>
                    <h1 class="h4 fw-bold text-gray-800 mb-0">Skenario 2</h1>
                    <p class="text-muted small mb-0">Create Single HU with Multiple Materials</p>
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

    <div class="row justify-content-center">
        <div class="col-xxl-10 col-xl-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-2 border-bottom">
                    <h6 class="card-title mb-0 fw-bold text-gray-800">
                        <i class="fas fa-boxes me-1 text-green-500"></i>
                        Create Single HU with Multiple Materials
                    </h6>
                </div>

                <div class="card-body p-3">
                    <form action="{{ route('hu.store-single-multi') }}" method="POST" id="huForm">
                        @csrf
                        <input type="hidden" id="base_unit_qty" name="base_unit_qty" value="">
                        <input type="hidden" id="sap_user" name="sap_user" value="">
                        <input type="hidden" id="sap_password" name="sap_password" value="">

                        <div class="row">
                            <!-- Left Column: Header Information -->
                            <div class="col-lg-5 col-xl-4 mb-3 mb-lg-0">
                                <h6 class="fw-semibold text-gray-700 mb-2 border-bottom pb-1">
                                    <i class="fas fa-header me-1 text-blue-500"></i>
                                    HU Header Information
                                </h6>

                                <!-- HU External ID -->
                                <div class="mb-3">
                                    <label for="hu_exid" class="form-label fw-semibold small">
                                        HU External ID <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="fas fa-barcode text-green-500"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0 hu-exid-input"
                                               id="hu_exid" name="hu_exid" maxlength="10"
                                               value="{{ old('hu_exid') }}" required
                                               placeholder="Enter 10 digits"
                                               oninput="validateHuExid(this)">
                                    </div>
                                    <div class="form-text small">
                                        <span id="hu_exid_status" class="text-muted">Enter 10 digits</span>
                                    </div>
                                </div>

                                <!-- Packaging Material -->
                                <div class="mb-3">
                                    <label for="pack_mat" class="form-label fw-semibold small">
                                        Packaging Material <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select form-select-sm" id="pack_mat" name="pack_mat">
                                        <option value="">Select Packaging Material</option>
                                        <option value="VSTDPLTBW01">VSTDPLTBW01</option>
                                        <option value="VSTDPLTBW02">VSTDPLTBW02</option>
                                        <option value="50016873">50016873</option>
                                    </select>
                                    <div class="form-text small" id="pack_mat_suggestion">
                                        Select packaging material
                                    </div>
                                </div>

                                <!-- Plant -->
                                <div class="mb-3">
                                    <label for="plant" class="form-label fw-semibold small">
                                        Plant <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control form-control-sm bg-light"
                                           id="plant" name="plant" value="{{ old('plant') }}" required readonly>
                                </div>

                                <!-- Storage Location -->
                                <div class="mb-3">
                                    <label for="stge_loc" class="form-label fw-semibold small">
                                        Storage Location <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control form-control-sm bg-light"
                                           id="stge_loc" name="stge_loc" value="{{ old('stge_loc') }}" required readonly>
                                </div>

                                <!-- Info Alert -->
                                <div class="alert alert-info bg-light border-0 py-1 px-2 small">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Header information will be auto-filled from selected materials
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

                            <!-- Right Column: Materials List -->
                            <div class="col-lg-7 col-xl-8">
                                <!-- Materials Header -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-semibold text-gray-700 mb-0">
                                        <i class="fas fa-list me-1 text-orange-500"></i>
                                        Materials List
                                    </h6>
                                    <span id="materialCount" class="badge bg-success">0 items</span>
                                </div>

                                <!-- Materials Info -->
                                <div class="alert alert-info bg-light border-0 py-1 px-2 small mb-2">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Material data will be auto-filled from drag & drop on main page
                                </div>

                                <!-- Materials Container -->
                                <div id="items-container" class="compact-list-container mb-2">
                                    <!-- Items will be dynamically added here -->
                                </div>

                                <!-- Empty State -->
                                <div id="itemsPreview" class="text-center py-4 border-2 border-dashed rounded bg-light">
                                    <i class="fas fa-boxes fa-2x text-gray-400 mb-2"></i>
                                    <h6 class="text-muted mb-1">No Materials</h6>
                                    <p class="text-muted small mb-0">Material data will appear here after selecting from main page</p>
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
                    <p class="text-center small mt-2 text-muted">Creating HU. Please wait...</p>
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
                    <i class="fas fa-check me-1"></i>Confirm & Create
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
.compact-item {
    background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; padding: 8px; margin-bottom: 8px;
    border-left: 3px solid #28a745;
}
.compact-item:hover { background: #e9ecef; border-color: #dee2e6; }
.compact-item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.compact-item-title { font-weight: 600; color: #333; font-size: 0.8rem; }
.compact-item-badge { background: #28a745; color: white; padding: 1px 6px; border-radius: 10px; font-size: 0.7rem; font-weight: 500; }
.compact-item-content { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; font-size: 0.75rem; }
.compact-item-field { display: flex; flex-direction: column; }
.compact-item-label { font-weight: 500; color: #6c757d; font-size: 0.7rem; margin-bottom: 2px; }
.compact-item-value { color: #333; font-weight: 400; }
.compact-item-input { width: 100%; padding: 4px 6px; border: 1px solid #ced4da; border-radius: 3px; font-size: 0.75rem; }
.compact-item-input:focus { border-color: #28a745; outline: none; box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.15); }
.hu-exid-input { border-color: #6c757d !important; }
.hu-exid-input.valid { border-color: #198754 !important; box-shadow: 0 0 0 2px rgba(25, 135, 84, 0.15); }
.hu-exid-input.warning { border-color: #ffc107 !important; box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.15); }
.hu-exid-input.invalid { border-color: #dc3545 !important; box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.15); }
.status-valid { color: #198754; font-weight: 600; font-size: 0.7rem; }
.status-warning { color: #ffc107; font-weight: 600; font-size: 0.7rem; }
.status-invalid { color: #dc3545; font-weight: 600; font-size: 0.7rem; }
.form-text { font-size: 0.7rem; }
.form-control-sm, .form-select-sm { font-size: 0.8rem; }
.alert { font-size: 0.8rem; }
.small { font-size: 0.8rem; }

/* Responsive adjustments */
@media (max-width: 991.98px) {
    .compact-item-content {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@push('scripts')
<script>
let itemCount = 0;
let materialsData = [];

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== SCENARIO 2 LOADED ===');

    // Cek jika ada pesan sukses dari server dan clear sessionStorage
    const serverSuccessAlert = document.querySelector('.alert-success');
    const serverErrorAlert = document.querySelector('.alert-danger');

    if (serverSuccessAlert) {
        console.log('✅ HU created successfully, clearing sessionStorage');
        sessionStorage.removeItem('scenario2_data');

        // Auto-hide success alert setelah 5 detik
        setTimeout(() => {
            serverSuccessAlert.remove();
        }, 4000);
    }

    if (serverErrorAlert) {
        console.log('❌ HU creation failed');
        // Auto-hide error alert setelah 8 detik
        setTimeout(() => {
            serverErrorAlert.remove();
        }, 6000);
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
                showMessage('Material data invalid. Please reselect from main page.', 'warning');
            }
        } catch (error) {
            console.error('❌ Error parsing scenario data:', error);
            showMessage('Error loading material data. Data may be corrupted. Please reselect.', 'error');
            sessionStorage.removeItem('scenario2_data');
        }
    } else {
        if (!scenarioDataRaw && !serverSuccessAlert && !serverErrorAlert) {
            console.warn('❌ No data found in sessionStorage for scenario2_data');
            showMessage('Please select materials from main page with drag & drop first.', 'info');
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

        // Submit form setelah 500ms untuk memberi waktu progress bar terlihat
        setTimeout(() => {
            document.getElementById('huForm').submit();
        }, 500);
    });

    // Reset modal ketika ditutup
    document.getElementById('sapCredentialsModal').addEventListener('hidden.bs.modal', function () {
        // Reset form
        document.querySelector('#sapCredentialsModal form').reset();

        // Sembunyikan progress bar, tampilkan form dan tombol
        document.getElementById('sapProgressBar').classList.add('d-none');
        document.getElementById('sapCredentialsForm').classList.remove('d-none');
        document.getElementById('confirmSapCredentials').classList.remove('d-none');
        document.getElementById('cancelSapCredentials').classList.remove('d-none');

        // Reset tombol
        const confirmBtn = document.getElementById('confirmSapCredentials');
        confirmBtn.innerHTML = '<i class="fas fa-check me-1"></i>Confirm & Create';
        confirmBtn.disabled = false;
    });

    // Auto-generate HU External ID
    document.getElementById('hu_exid').addEventListener('focus', function() {
        if (!this.value) {
            const timestamp = new Date().getTime();
            this.value = timestamp.toString().slice(-10);
            validateHuExid(this);
        }
    });

    // Auto-set packaging material setelah data dimuat
    setTimeout(autoSetPackagingMaterial, 300);
});

// Validasi HU External ID
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
        statusElement.textContent = 'Enter 10 digits';
        statusElement.className = 'text-muted';
    } else if (length < 10) {
        input.classList.add('warning');
        statusElement.textContent = `Need ${10 - length} more digits`;
        statusElement.className = 'status-warning';
    } else if (length === 10) {
        input.classList.add('valid');
        statusElement.textContent = '✓ Valid format';
        statusElement.className = 'status-valid';
    } else {
        input.classList.add('invalid');
        statusElement.textContent = 'Max 10 digits';
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
                <span class="compact-item-label">Pack Qty <span class="text-danger">*</span></span>
                <input type="number" class="compact-item-input" name="items[${itemCount}][pack_qty]"
                       placeholder="Enter quantity" step="0.001" min="0.001" max="${stockQty}"
                       required data-max-qty="${stockQty}">
            </div>
            <div class="compact-item-field">
                <span class="compact-item-label">Sales Order</span>
                <span class="compact-item-value">${salesOrderNo || '-'}</span>
                <input type="hidden" name="items[${itemCount}][sp_stck_no]" value="${salesOrderNo}">
                <input type="hidden" name="items[${itemCount}][plant]" value="${plant}">
                <input type="hidden" name="items[${itemCount}][storage_location]" value="${storageLocation}">
            </div>
        </div>
        ${materialDescription ? `
            <div class="compact-item-field mt-2">
                <span class="compact-item-label">Description</span>
                <span class="compact-item-value">${materialDescription}</span>
            </div>
        ` : ''}
    `;
    container.appendChild(newItem);
    itemCount++;
}

function validateForm() {
    const huExid = document.getElementById('hu_exid').value.trim();
    if (!huExid) {
        showMessage('HU External ID is required', 'error');
        document.getElementById('hu_exid').focus();
        return false;
    }

    if (huExid.length !== 10) {
        showMessage('HU External ID must be exactly 10 digits', 'error');
        document.getElementById('hu_exid').focus();
        return false;
    }

    if (!/^\d+$/.test(huExid)) {
        showMessage('HU External ID must contain only numbers', 'error');
        document.getElementById('hu_exid').focus();
        return false;
    }

    // Validasi: pastikan ada material
    if (itemCount === 0) {
        showMessage('No materials added. Please select from main page.', 'error');
        return false;
    }

    const packMat = document.getElementById('pack_mat').value;
    if (!packMat) {
        showMessage('Packaging Material is required', 'error');
        document.getElementById('pack_mat').focus();
        return false;
    }

    let qtyError = false;
    document.querySelectorAll('.compact-item-input[name*="[pack_qty]"]').forEach((input, index) => {
        const maxQty = parseFloat(input.dataset.maxQty);
        const qty = parseFloat(input.value || 0);

        console.log(`Validating item ${index}:`, { enteredQty, qty, maxQty });

        if (!enteredQty || isNaN(qty) || qty <= 0) {
            showMessage(`Pack Quantity for item ${index + 1} must be > 0`, 'error');
            input.focus();
            qtyError = true;
            return;
        }

        if (qty > maxQty) {
            showMessage(`Pack Quantity (${qty.toLocaleString('id-ID')}) exceeds available stock (${maxQty.toLocaleString('id-ID')}) for item ${index + 1}`, 'error');
            input.focus();
            qtyError = true;
            return;
        }
    });

    return !qtyError;
}

function resetForm() {
    if (confirm('Cancel? All data will be lost.')) {
        document.getElementById('huForm').reset();
        window.location.href = "{{ route('hu.index') }}";
    }
}

function autoSetPackagingMaterial() {
    const scenarioDataRaw = sessionStorage.getItem('scenario2_data');
    if (!scenarioDataRaw) return;

    try {
        const materials = JSON.parse(scenarioDataRaw);
        if (materials.length === 0) return;

        // Ambil magry dari item pertama (asumsi semua item punya magry yang sama)
        const firstItem = materials[0];
        const magry = firstItem.magry || '';

        const packMatSelect = document.getElementById('pack_mat');
        const suggestionElement = document.getElementById('pack_mat_suggestion');

        if (!packMatSelect) return;

        // Reset ke default
        packMatSelect.value = '';
        suggestionElement.innerHTML = '<span class="text-muted">Select packaging material</span>';

        if (magry === 'ZMG1') {
            packMatSelect.value = '50016873';
            suggestionElement.innerHTML = `<span class="text-success"><i class="fas fa-check-circle me-1"></i>Auto-set: 50016873 (ZMG1)</span>`;
        } else if (magry === 'ZMG2') {
            // Untuk ZMG2, set default pertama
            packMatSelect.value = 'VSTDPLTBW01';
            suggestionElement.innerHTML = `
                <span class="text-success"><i class="fas fa-check-circle me-1"></i>Auto-set: VSTDPLTBW01 (ZMG2)</span>
                <br><small class="text-muted">Alternative: VSTDPLTBW02</small>
            `;
        }

        console.log('Auto-set packaging material for magry:', magry);
    } catch (error) {
        console.error('Error in autoSetPackagingMaterial:', error);
    }
}

function showMessage(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';

    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show shadow-sm mb-3 py-1 px-2" role="alert">
            <i class="fas ${iconClass} me-1"></i>${message}
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
        </div>
    `;

    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);

    setTimeout(() => {
        const alert = document.querySelector('.alert.' + alertClass);
        if (alert && !alert.classList.contains('alert-success') && !alert.classList.contains('alert-danger')) {
            alert.remove();
        }
    }, type === 'error' ? 6000 : 4000);
}
</script>
@endpush
