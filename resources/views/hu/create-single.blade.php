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
                    <button type="button" class="btn btn-primary btn-sm px-3" id="createHuButton" disabled>
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
                <div class="bg-blue-100 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                    <i class="fas fa-cube text-blue-600"></i>
                </div>
                <div>
                    <h1 class="h4 fw-bold text-gray-800 mb-0">Skenario 1</h1>
                    <p class="text-muted small mb-0">Create Single HU (1 HU = 1 Material)</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xxl-10 col-xl-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-2 border-bottom">
                    <h6 class="card-title mb-0 fw-bold text-gray-800">
                        <i class="fas fa-info-circle me-1 text-blue-500"></i>
                        Handling Unit Information
                    </h6>
                </div>

                <div class="card-body p-3">
                    <form action="{{ route('hu.store-single') }}" method="POST" id="huForm">
                        @csrf
                        <input type="hidden" id="base_unit_qty" name="base_unit_qty" value="">
                        <input type="hidden" id="sap_user" name="sap_user" value="">
                        <input type="hidden" id="sap_password" name="sap_password" value="">

                        <div class="row">
                            <!-- Left Column: Material Information -->
                            <div class="col-lg-5 col-xl-4 mb-3 mb-lg-0">
                                <!-- Material Status -->
                                <div id="material-status" class="alert alert-info mb-3 py-1 px-2 small">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <div>
                                            <strong>Material Status:</strong>
                                            <span id="status-text">Waiting for material data...</span>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="fw-semibold text-gray-700 mb-2 border-bottom pb-1">
                                    <i class="fas fa-box me-1 text-green-500"></i>
                                    Material Information
                                </h6>

                                <!-- Material Details -->
                                <div class="mb-2">
                                    <label class="form-label fw-semibold small">Material</label>
                                    <input type="text" class="form-control form-control-sm bg-light" id="material" readonly>
                                    <div class="form-text small" id="material-description">
                                        Material description will appear here
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-semibold small">Batch</label>
                                    <input type="text" class="form-control form-control-sm bg-light" id="batch" readonly>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-semibold small">Plant</label>
                                    <input type="text" class="form-control form-control-sm bg-light" id="plant" readonly>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-semibold small">Storage Location</label>
                                    <input type="text" class="form-control form-control-sm bg-light" id="stge_loc" readonly>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-semibold small">Stock Quantity</label>
                                    <input type="text" class="form-control form-control-sm bg-light" id="stock_quantity" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Sales Order No</label>
                                    <input type="text" class="form-control form-control-sm bg-light" id="sp_stck_no" readonly>
                                </div>

                                <!-- Hidden fields untuk data material -->
                                <input type="hidden" name="material" id="hidden_material">
                                <input type="hidden" name="plant" id="hidden_plant">
                                <input type="hidden" name="stge_loc" id="hidden_stge_loc">
                                <input type="hidden" name="batch" id="hidden_batch">
                                <input type="hidden" name="sp_stck_no" id="hidden_sp_stck_no">
                            </div>

                            <!-- Right Column: HU Creation Information -->
                            <div class="col-lg-7 col-xl-8">
                                <h6 class="fw-semibold text-gray-700 mb-2 border-bottom pb-1">
                                    <i class="fas fa-edit me-1 text-blue-500"></i>
                                    HU Creation Information
                                </h6>

                                <!-- HU External ID -->
                                <div class="mb-3">
                                    <label for="hu_exid" class="form-label fw-semibold small">
                                        HU External ID <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="fas fa-barcode text-blue-500"></i>
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
                                        <option value="VSTDPLBW002">VSTDPLBW002</option>
                                        <option value="50016873">50016873</option>
                                    </select>
                                    <div class="form-text small" id="pack_mat_suggestion">
                                        Select packaging material
                                    </div>
                                </div>

                                <!-- Pack Quantity -->
                                <div class="mb-3">
                                    <label for="pack_qty" class="form-label fw-semibold small">
                                        Pack Quantity <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control form-control-sm" id="pack_qty" name="pack_qty"
                                           step="0.001" min="0.001" value="{{ old('pack_qty') }}" required>
                                    <div class="form-text small" id="pack_qty_text">
                                        Quantity will be auto-filled from stock
                                    </div>
                                </div>

                                <!-- Preview Section -->
                                <div class="card bg-light border-0 mt-3">
                                    <div class="card-header bg-transparent border-bottom py-1">
                                        <h6 class="fw-semibold text-gray-700 mb-0 small">
                                            <i class="fas fa-eye me-1 text-purple-500"></i>
                                            Material Preview
                                        </h6>
                                    </div>
                                    <div class="card-body p-2">
                                        <div id="materialPreview" class="text-muted small">
                                            <div class="text-center py-3">
                                                <i class="fas fa-box-open fa-lg text-gray-400 mb-2"></i>
                                                <p class="mb-0">Material data will appear here after selecting from main page</p>
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
.bg-blue-100 { background-color: #e3f2fd; }
.text-blue-600 { color: #1e88e5; }
.text-blue-500 { color: #2196f3; }
.text-green-500 { color: #4caf50; }
.text-purple-500 { color: #9c27b0; }
</style>
@endpush

@push('scripts')
<script>
// Global variable untuk menyimpan data material
let currentMaterialData = null;

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing HU creation single');

    // Load data dari sessionStorage
    loadMaterialData();

    // Setup create button event
    document.getElementById('createHuButton').addEventListener('click', function() {
        if (validateForm()) {
            // Show SAP credentials modal
            const modal = new bootstrap.Modal(document.getElementById('sapCredentialsModal'));
            modal.show();
        }
    });

    // Setup confirm SAP credentials button
    document.getElementById('confirmSapCredentials').addEventListener('click', function() {
        confirmSapCredentials();
    });

    // Enable/disable create button based on form validity
    document.getElementById('huForm').addEventListener('input', function() {
        checkFormValidity();
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
});

function loadMaterialData() {
    console.log('Loading material data from sessionStorage...');

    // Coba beberapa key yang mungkin digunakan
    const scenarioDataRaw = sessionStorage.getItem('scenario1_data') ||
                           sessionStorage.getItem('selected_material') ||
                           sessionStorage.getItem('current_material');

    if (scenarioDataRaw) {
        try {
            const materialData = JSON.parse(scenarioDataRaw);
            console.log('Material data loaded:', materialData);

            if (materialData && materialData.material) {
                currentMaterialData = materialData;
                fillFormWithData(materialData);
                showSuccess('Material data loaded successfully');
            } else {
                showError('Invalid material data. Please reselect from main page.');
            }
        } catch (error) {
            console.error('Error parsing material data:', error);
            showError('Error loading material data. Please reselect from main page.');
        }
    } else {
        showError('No material data. Please select material from main page first.');
        // Redirect otomatis setelah 3 detik
        setTimeout(() => {
            window.location.href = "{{ route('hu.index') }}";
        }, 3000);
    }
}

function fillFormWithData(materialData) {
    console.log('Filling form with data:', materialData);

    try {
        // Validate required data
        if (!materialData || typeof materialData !== 'object') {
            throw new Error('Invalid material data');
        }

        const requiredFields = ['material', 'plant', 'storage_location'];
        for (const field of requiredFields) {
            if (!materialData[field]) {
                throw new Error(`Field ${field} not found in material data`);
            }
        }

        // Format material number (remove leading zeros)
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

        const salesOrderNo = getSalesOrderNo(materialData);
        const formattedMaterial = formatMaterialNumber(materialData.material);
        const stockQty = parseFloat(materialData.stock_quantity || '0');

        // Set form values (display only)
        document.getElementById('material').value = formattedMaterial;
        document.getElementById('material-description').textContent = materialData.material_description || 'Description not available';
        document.getElementById('plant').value = materialData.plant || '';
        document.getElementById('stge_loc').value = materialData.storage_location || '';
        document.getElementById('batch').value = materialData.batch || '';
        document.getElementById('sp_stck_no').value = salesOrderNo;
        document.getElementById('stock_quantity').value = stockQty.toLocaleString('id-ID') + ' PC';

        // Set hidden fields untuk form submission
        document.getElementById('hidden_material').value = formattedMaterial;
        document.getElementById('hidden_plant').value = materialData.plant || '';
        document.getElementById('hidden_stge_loc').value = materialData.storage_location || '';
        document.getElementById('hidden_batch').value = materialData.batch || '';
        document.getElementById('hidden_sp_stck_no').value = salesOrderNo;

        // Auto-set pack quantity dari stock
        document.getElementById('pack_qty').value = stockQty;
        document.getElementById('pack_qty_text').textContent = `Quantity auto-set from stock: ${stockQty.toLocaleString('id-ID')} PC`;

        // Auto-set Packaging Material berdasarkan magry
        const magry = materialData.magry || '';
        const suggestedPackMat = materialData.suggested_pack_mat || '';
        const packMatSelect = document.getElementById('pack_mat');
        const suggestionElement = document.getElementById('pack_mat_suggestion');

        if (suggestedPackMat) {
            packMatSelect.value = suggestedPackMat;
            if (magry === 'ZMG1') {
                suggestionElement.innerHTML = `<span class="text-success"><i class="fas fa-check-circle me-1"></i>Auto-set: 50016873 (ZMG1)</span>`;
            } else if (magry === 'ZMG2') {
                suggestionElement.innerHTML = `<span class="text-success"><i class="fas fa-check-circle me-1"></i>Auto-set: VSTDPLTBW01 (ZMG2)</span>`;
            }
        } else if (magry) {
            if (magry === 'ZMG1') {
                suggestionElement.innerHTML = `<span class="text-info"><i class="fas fa-info-circle me-1"></i>Recommended: 50016873 (ZMG1)</span>`;
            } else if (magry === 'ZMG2') {
                suggestionElement.innerHTML = `<span class="text-info"><i class="fas fa-info-circle me-1"></i>Recommended: VSTDPLTBW01 or VSTDPLBW002 (ZMG2)</span>`;
            }
        }

        // Update preview
        const previewHtml = `
            <div class="row">
                <div class="col-md-6">
                    <strong>Material:</strong> ${formattedMaterial}<br>
                    <strong>Description:</strong> ${materialData.material_description || '-'}<br>
                    <strong>Plant:</strong> ${materialData.plant || '-'}
                </div>
                <div class="col-md-6">
                    <strong>Storage Location:</strong> ${materialData.storage_location || '-'}<br>
                    <strong>Batch:</strong> ${materialData.batch || '-'}<br>
                    <strong>Stock Quantity:</strong> ${stockQty.toLocaleString('id-ID')} PC
                    ${magry ? `<br><strong>Magry:</strong> <span class="badge bg-primary">${magry}</span>` : ''}
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

        // Update status
        document.getElementById('status-text').textContent = `Material ${formattedMaterial} loaded successfully`;
        document.getElementById('material-status').className = 'alert alert-success';

        console.log('Form filled successfully');

        // Check form validity setelah data dimuat
        checkFormValidity();

    } catch (error) {
        console.error('Error in fillFormWithData:', error);
        showError('Error loading material data: ' + error.message);
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

    if (!sapUser) {
        showMessage('SAP User is required', 'error');
        document.getElementById('sap_user_modal').focus();
        return;
    }

    if (!sapPassword) {
        showMessage('SAP Password is required', 'error');
        document.getElementById('sap_password_modal').focus();
        return;
    }

    console.log('SAP Credentials confirmed:', { sapUser, sapPassword: '***' });

    // Sembunyikan form dan tombol, tampilkan progress bar
    document.getElementById('sapCredentialsForm').classList.add('d-none');
    document.getElementById('confirmSapCredentials').classList.add('d-none');
    document.getElementById('cancelSapCredentials').classList.add('d-none');
    document.getElementById('sapProgressBar').classList.remove('d-none');

    // Set nilai ke hidden fields di form utama
    document.getElementById('sap_user').value = sapUser;
    document.getElementById('sap_password').value = sapPassword;

    // Submit form setelah 500ms untuk memberi waktu progress bar terlihat
    setTimeout(() => {
        console.log('Submitting form with SAP credentials...');
        document.getElementById('huForm').submit();
    }, 500);
}

function validateForm() {
    if (!currentMaterialData) {
        showMessage('Please select material from main page first.', 'error');
        return false;
    }

    const huExid = document.getElementById('hu_exid').value.trim();
    const packMat = document.getElementById('pack_mat').value;
    const packQty = document.getElementById('pack_qty').value;

    // Validasi HU External ID
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

    if (!packMat) {
        showMessage('Packaging Material is required', 'error');
        document.getElementById('pack_mat').focus();
        return false;
    }

    if (!packQty || parseFloat(packQty) <= 0) {
        showMessage('Pack Quantity must be greater than 0', 'error');
        document.getElementById('pack_qty').focus();
        return false;
    }

    return true;
}

// Validasi HU External ID
function validateHuExid(input) {
    const value = input.value;
    const statusElement = document.getElementById('hu_exid_status');

    // Hanya menerima angka
    const numericValue = value.replace(/[^0-9]/g, '');
    if (value !== numericValue) {
        input.value = numericValue;
    }

    const length = numericValue.length;

    // Update styling berdasarkan panjang karakter
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
        // Potong ke 10 digit
        input.value = numericValue.slice(0, 10);
    }

    checkFormValidity();
}

function showMessage(message, type) {
    // Hapus alert existing (kecuali yang dari Laravel session)
    const existingAlerts = document.querySelectorAll('.alert.alert-dismissible:not(.alert-success):not(.alert-danger)');
    existingAlerts.forEach(alert => alert.remove());

    const alertClass = type === 'error' ? 'alert-danger' : type === 'warning' ? 'alert-warning' : 'alert-info';
    const iconClass = type === 'error' ? 'fa-exclamation-triangle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';

    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show shadow-sm mb-3 py-1 px-2" role="alert">
            <i class="fas ${iconClass} me-1"></i>${message}
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
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
    }, type === 'error' ? 6000 : 4000);
}

function showError(message) {
    console.error('Showing error:', message);
    showMessage(message, 'error');
}

function showSuccess(message) {
    console.log('Showing success:', message);
    showMessage(message, 'success');
}

function resetForm() {
    if (confirm('Cancel? All data will be lost.')) {
        // Clear sessionStorage
        sessionStorage.removeItem('scenario1_data');
        window.location.href = "{{ route('hu.index') }}";
    }
}
</script>
@endpush