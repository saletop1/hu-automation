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

    <!-- Top Bar: Header + Action Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-blue-100 rounded-circle d-flex align-items-center justify-content-center" style="width:30px;height:30px;min-width:30px">
                <i class="fas fa-cube text-blue-600" style="font-size:0.85rem"></i>
            </div>
            <div>
                <h1 class="h6 fw-bold text-gray-800 mb-0 lh-1">Skenario 1 — Create Single HU</h1>
                <span class="text-muted" style="font-size:0.7rem">1 HU = 1 Material</span>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('hu.index') }}" class="btn btn-outline-secondary btn-sm py-1 px-2">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
            <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2" onclick="resetForm()">
                <i class="fas fa-times me-1"></i>Cancel
            </button>
            <button type="button" class="btn btn-primary btn-sm py-1 px-2" id="createHuButton" disabled>
                <i class="fas fa-save me-1"></i>Create HU
            </button>
        </div>
    </div>

    <!-- Status Bar -->
    <div id="material-status" class="alert alert-info mb-2 py-1 px-2 d-flex align-items-center gap-2" style="font-size:0.72rem">
        <i class="fas fa-info-circle"></i>
        <span><strong>Status:</strong> <span id="status-text">Waiting for material data...</span></span>
    </div>

    <form action="{{ route('hu.store-single') }}" method="POST" id="huForm">
        @csrf
        <input type="hidden" id="base_unit_qty" name="base_unit_qty" value="">
        <input type="hidden" id="sap_user" name="sap_user" value="">
        <input type="hidden" id="sap_password" name="sap_password" value="">
        <input type="hidden" name="material" id="hidden_material">
        <input type="hidden" name="plant" id="hidden_plant">
        <input type="hidden" name="stge_loc" id="hidden_stge_loc">
        <input type="hidden" name="batch" id="hidden_batch">
        <input type="hidden" name="sp_stck_no" id="hidden_sp_stck_no">

        <div class="row g-2">

            <!-- ===== KIRI: Material Info ===== -->
            <div class="col-lg-5 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-1 px-3 border-bottom d-flex align-items-center gap-1">
                        <i class="fas fa-box text-green-500" style="font-size:0.78rem"></i>
                        <span class="fw-semibold" style="font-size:0.78rem">Material Information</span>
                    </div>
                    <div class="card-body p-2">

                        <!-- Material utama — baris pertama -->
                        <div class="info-grid">
                            <div class="info-row">
                                <span class="info-label">Material</span>
                                <span class="info-value fw-semibold font-monospace" id="material">—</span>
                            </div>
                            <div class="info-row info-desc-row">
                                <span class="info-label">Deskripsi</span>
                                <span class="info-value text-muted" id="material-description">—</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Batch</span>
                                <span class="info-value font-monospace" id="batch">—</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Plant</span>
                                <span class="info-value" id="plant">—</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Stor. Loc</span>
                                <span class="info-value" id="stge_loc">—</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Stock Qty</span>
                                <span class="info-value">
                                    <span class="badge bg-success text-white" style="font-size:0.72rem" id="stock_quantity">—</span>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Sales Order</span>
                                <span class="info-value font-monospace" id="sp_stck_no">—</span>
                            </div>
                            <div class="info-row" id="magry-row" style="display:none">
                                <span class="info-label">Magry</span>
                                <span class="info-value"><span class="badge bg-primary" style="font-size:0.68rem" id="magry-badge">—</span></span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ===== KANAN: HU Creation Form ===== -->
            <div class="col-lg-7 col-xl-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-1 px-3 border-bottom d-flex align-items-center gap-1">
                        <i class="fas fa-edit text-blue-500" style="font-size:0.78rem"></i>
                        <span class="fw-semibold" style="font-size:0.78rem">HU Creation Information</span>
                    </div>
                    <div class="card-body p-3">

                        <!-- 3 field dalam 1 baris di layar lebar -->
                        <div class="row g-2 align-items-start">

                            <!-- HU External ID -->
                            <div class="col-md-4">
                                <label for="hu_exid" class="form-label fw-semibold mb-1" style="font-size:0.75rem">
                                    HU External ID <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0" style="font-size:0.75rem">
                                        <i class="fas fa-barcode text-blue-500"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0 hu-exid-input"
                                           id="hu_exid" name="hu_exid" maxlength="10"
                                           value="{{ old('hu_exid') }}" required
                                           placeholder="10 digits"
                                           oninput="validateHuExid(this)"
                                           style="font-size:0.78rem">
                                </div>
                                <div style="font-size:0.68rem;margin-top:2px">
                                    <span id="hu_exid_status" class="text-muted">Enter 10 digits</span>
                                </div>
                            </div>

                            <!-- Packaging Material -->
                            <div class="col-md-4">
                                <label for="pack_mat" class="form-label fw-semibold mb-1" style="font-size:0.75rem">
                                    Packaging Material <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm" id="pack_mat" name="pack_mat" style="font-size:0.78rem">
                                    <option value="">— Select —</option>
                                    <option value="VSTDPLTBW01">VSTDPLTBW01</option>
                                    <option value="VSTDPLBW002">VSTDPLBW002</option>
                                    <option value="50016873">50016873</option>
                                </select>
                                <div style="font-size:0.68rem;margin-top:2px" id="pack_mat_suggestion">
                                    <span class="text-muted">Select packaging material</span>
                                </div>
                            </div>

                            <!-- Pack Quantity -->
                            <div class="col-md-4">
                                <label for="pack_qty" class="form-label fw-semibold mb-1" style="font-size:0.75rem">
                                    Pack Quantity <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control form-control-sm" id="pack_qty" name="pack_qty"
                                       step="0.001" min="0.001" value="{{ old('pack_qty') }}" required
                                       style="font-size:0.78rem">
                                <div style="font-size:0.68rem;margin-top:2px" id="pack_qty_text">
                                    <span class="text-muted">Auto-filled from stock</span>
                                </div>
                            </div>
                        </div>

                        <!-- Summary card: ringkasan data yang akan disubmit -->
                        <div class="mt-3 p-2 rounded border bg-light" id="submitSummary" style="font-size:0.72rem;display:none">
                            <div class="fw-semibold text-gray-700 mb-1" style="font-size:0.72rem">
                                <i class="fas fa-check-circle text-success me-1"></i>Ringkasan Submit
                            </div>
                            <div class="row g-0">
                                <div class="col-6">
                                    <span class="text-muted">Material:</span> <span id="sum-material" class="fw-semibold font-monospace"></span><br>
                                    <span class="text-muted">Plant / Loc:</span> <span id="sum-plant"></span><br>
                                    <span class="text-muted">Batch:</span> <span id="sum-batch" class="font-monospace"></span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted">HU Ext ID:</span> <span id="sum-exid" class="fw-semibold font-monospace"></span><br>
                                    <span class="text-muted">Pack Mat:</span> <span id="sum-packmat"></span><br>
                                    <span class="text-muted">Qty:</span> <span id="sum-qty" class="fw-semibold"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden preview element (tetap ada agar JS tidak error) -->
                        <div id="materialPreview" style="display:none"></div>

                    </div>
                </div>
            </div>

        </div><!-- /row -->
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
/* === HU EXID INPUT === */
.hu-exid-input { border-color: #6c757d !important; }
.hu-exid-input.valid { border-color: #198754 !important; box-shadow: 0 0 0 2px rgba(25, 135, 84, 0.15); }
.hu-exid-input.warning { border-color: #ffc107 !important; box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.15); }
.hu-exid-input.invalid { border-color: #dc3545 !important; box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.15); }
.status-valid { color: #198754; font-weight: 600; font-size: 0.68rem; }
.status-warning { color: #ffc107; font-weight: 600; font-size: 0.68rem; }
.status-invalid { color: #dc3545; font-weight: 600; font-size: 0.68rem; }

/* === COLOR UTILS === */
.bg-blue-100 { background-color: #e3f2fd; }
.text-blue-600 { color: #1e88e5; }
.text-blue-500 { color: #2196f3; }
.text-green-500 { color: #4caf50; }
.text-purple-500 { color: #9c27b0; }

/* === INFO GRID (menggantikan input readonly) === */
.info-grid {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.info-row {
    display: flex;
    align-items: baseline;
    gap: 6px;
    padding: 4px 6px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 0.75rem;
    min-height: 26px;
}
.info-row:last-child { border-bottom: none; }
.info-row:nth-child(odd) { background-color: #fafafa; }
.info-label {
    color: #6c757d;
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    min-width: 68px;
    flex-shrink: 0;
}
.info-value {
    color: #1f2937;
    font-size: 0.78rem;
    word-break: break-word;
}
.info-desc-row .info-value {
    color: #4b5563;
    font-size: 0.72rem;
    font-style: italic;
}
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
        const magry = materialData.magry || '';

        // === Isi info-grid spans ===
        document.getElementById('material').textContent = formattedMaterial || '—';
        document.getElementById('material-description').textContent = materialData.material_description || '—';
        document.getElementById('batch').textContent = materialData.batch || '—';
        document.getElementById('plant').textContent = materialData.plant || '—';
        document.getElementById('stge_loc').textContent = materialData.storage_location || '—';
        document.getElementById('stock_quantity').textContent = stockQty.toLocaleString('id-ID') + ' PC';
        document.getElementById('sp_stck_no').textContent = salesOrderNo || '—';

        // Tampilkan magry jika ada
        if (magry) {
            document.getElementById('magry-badge').textContent = magry;
            document.getElementById('magry-row').style.display = '';
        }

        // Set hidden fields untuk form submission
        document.getElementById('hidden_material').value = formattedMaterial;
        document.getElementById('hidden_plant').value = materialData.plant || '';
        document.getElementById('hidden_stge_loc').value = materialData.storage_location || '';
        document.getElementById('hidden_batch').value = materialData.batch || '';
        document.getElementById('hidden_sp_stck_no').value = salesOrderNo;

        // Auto-set pack quantity dari stock
        document.getElementById('pack_qty').value = stockQty;
        document.getElementById('pack_qty_text').innerHTML =
            `<span class="text-success"><i class="fas fa-check-circle me-1"></i>Auto-set: ${stockQty.toLocaleString('id-ID')} PC</span>`;

        // Auto-set Packaging Material berdasarkan magry
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

        // Update status bar
        document.getElementById('status-text').textContent = `Material ${formattedMaterial} loaded successfully`;
        document.getElementById('material-status').className = 'alert alert-success mb-2 py-1 px-2 d-flex align-items-center gap-2';

        console.log('Form filled successfully');
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

    // Tampilkan ringkasan submit jika valid
    const summary = document.getElementById('submitSummary');
    if (isValid && currentMaterialData) {
        document.getElementById('sum-material').textContent = document.getElementById('hidden_material').value;
        document.getElementById('sum-plant').textContent =
            (currentMaterialData.plant || '') + ' / ' + (currentMaterialData.storage_location || '');
        document.getElementById('sum-batch').textContent = currentMaterialData.batch || '—';
        document.getElementById('sum-exid').textContent = huExid;
        document.getElementById('sum-packmat').textContent = packMat;
        document.getElementById('sum-qty').textContent = parseFloat(packQty).toLocaleString('id-ID') + ' PC';
        summary.style.display = '';
    } else {
        summary.style.display = 'none';
    }
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