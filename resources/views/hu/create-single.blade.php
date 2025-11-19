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

    <!-- Custom Alert Messages untuk JavaScript -->
    <div id="js-error-message" class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" style="display: none;" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><span id="js-error-text"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <div id="js-success-message" class="alert alert-success alert-dismissible fade show shadow-sm mb-4" style="display: none;" role="alert">
        <i class="fas fa-check-circle me-2"></i><span id="js-success-text"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

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
                    <button type="button" class="btn btn-primary px-4" id="createHuButton" disabled>
                        <i class="fas fa-save me-2"></i>Create HU
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-blue-100 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                    <i class="fas fa-cube text-blue-600 fa-lg"></i>
                </div>
                <div>
                    <h1 class="h3 fw-bold text-gray-800 mb-1">Skenario 1</h1>
                    <p class="text-muted mb-0">Buat Single HU (1 HU = 1 Material)</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xxl-10 col-xl-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-gray-800">
                        <i class="fas fa-info-circle me-2 text-blue-500"></i>
                        Informasi Handling Unit
                    </h5>
                </div>

                <div class="card-body p-4">
                    <!-- Material Selection Status -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div id="material-status" class="alert alert-info">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <div>
                                        <strong>Status Material:</strong>
                                        <span id="status-text">Menunggu data material...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('hu.store-single') }}" method="POST" id="huForm">
                        @csrf
                        <input type="hidden" id="base_unit_qty" name="base_unit_qty" value="">

                        <!-- Material Information (Auto-filled from session) -->
                        <div class="row mb-4">
                            <div class="col-12 mb-3">
                                <h6 class="fw-semibold text-gray-700 mb-3 border-bottom pb-2">
                                    <i class="fas fa-box me-2 text-green-500"></i>
                                    Informasi Material
                                </h6>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-gray-700">Material</label>
                                <input type="text" class="form-control bg-light" id="material" readonly>
                                <div class="form-text text-muted small" id="material-description">
                                    Deskripsi material akan muncul di sini
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-gray-700">Batch</label>
                                <input type="text" class="form-control bg-light" id="batch" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-gray-700">Plant</label>
                                <input type="text" class="form-control bg-light" id="plant" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-gray-700">Storage Location</label>
                                <input type="text" class="form-control bg-light" id="stge_loc" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-gray-700">Stock Quantity</label>
                                <input type="text" class="form-control bg-light" id="stock_quantity" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-gray-700">Sales Order No</label>
                                <input type="text" class="form-control bg-light" id="sp_stck_no" readonly>
                            </div>
                        </div>

                        <!-- HU Creation Fields (Manual input) -->
                        <div class="row mb-4">
                            <div class="col-12 mb-3">
                                <h6 class="fw-semibold text-gray-700 mb-3 border-bottom pb-2">
                                    <i class="fas fa-edit me-2 text-blue-500"></i>
                                    Informasi Pembuatan HU
                                </h6>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="hu_exid" class="form-label fw-semibold text-gray-700">
                                    HU External ID <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-barcode text-blue-500"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0 hu-exid-input"
                                           id="hu_exid" name="hu_exid" maxlength="10"
                                           value="{{ old('hu_exid') }}"
                                           placeholder="Masukkan 10 digit angka"
                                           oninput="validateHuExid(this)">
                                </div>
                                <div class="form-text text-muted small">
                                    <span id="hu_exid_status" class="text-muted">Masukkan 10 digit angka</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="pack_mat" class="form-label fw-semibold text-gray-700">
                                    Packaging Material <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="pack_mat" name="pack_mat">
                                    <option value="">Pilih Packaging Material</option>
                                    <option value="VSTDPLTBW01">VSTDPLTBW01</option>
                                    <option value="VSTDPLBW002">VSTDPLBW002</option>
                                    <option value="50016873">50016873</option>
                                </select>
                                <div class="form-text text-muted small" id="pack_mat_suggestion">
                                    Pilih packaging material
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="pack_qty" class="form-label fw-semibold text-gray-700">
                                    Pack Quantity <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="pack_qty" name="pack_qty"
                                       step="0.001" min="0.001" value="{{ old('pack_qty') }}">
                                <div class="form-text text-muted small" id="pack_qty_text">
                                    Quantity akan terisi otomatis dari stock
                                </div>
                            </div>
                        </div>

                        <!-- Hidden fields untuk data material -->
                        <input type="hidden" name="material" id="hidden_material">
                        <input type="hidden" name="plant" id="hidden_plant">
                        <input type="hidden" name="stge_loc" id="hidden_stge_loc">
                        <input type="hidden" name="batch" id="hidden_batch">
                        <input type="hidden" name="sp_stck_no" id="hidden_sp_stck_no">

                        <!-- Preview Section -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card bg-light border-0">
                                    <div class="card-header bg-transparent border-bottom">
                                        <h6 class="fw-semibold text-gray-700 mb-0">
                                            <i class="fas fa-eye me-2 text-purple-500"></i>
                                            Preview Data Material
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="materialPreview" class="text-muted">
                                            <div class="text-center py-4">
                                                <i class="fas fa-box-open fa-2x text-gray-400 mb-2"></i>
                                                <p class="mb-0">Data material akan ditampilkan di sini setelah dipilih dari halaman utama</p>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="sapCredentialsModalLabel">
                    <i class="fas fa-key me-2"></i>Konfirmasi SAP Credentials
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Masukkan kredensial SAP untuk membuat Handling Unit
                    </small>
                </div>
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
                    <i class="fas fa-check me-2"></i>Confirm & Create HU
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
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}

.hu-exid-input.warning {
    border-color: #ffc107 !important;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
}

.hu-exid-input.invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.status-valid {
    color: #198754;
    font-weight: 600;
}

.status-warning {
    color: #ffc107;
    font-weight: 600;
}

.status-invalid {
    color: #dc3545;
    font-weight: 600;
}

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
                showSuccess('Data material berhasil dimuat');
            } else {
                showError('Data material tidak valid. Silakan pilih ulang dari halaman utama.');
            }
        } catch (error) {
            console.error('Error parsing material data:', error);
            showError('Error memuat data material. Silakan pilih ulang dari halaman utama.');
        }
    } else {
        showError('Tidak ada data material. Silakan pilih material dari halaman utama terlebih dahulu.');
        // Redirect otomatis setelah 3 detik
        setTimeout(() => {
            window.location.href = "{{ route('hu.index') }}";
        }, 3000);
    }
}

function loadMaterialDataFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const material = urlParams.get('material');

    if (material) {
        const materialData = {
            material: material,
            plant: urlParams.get('plant'),
            storage_location: urlParams.get('stge_loc'),
            batch: urlParams.get('batch'),
            material_description: urlParams.get('description') || 'Deskripsi tidak tersedia',
            stock_quantity: urlParams.get('qty') || '0'
        };
        fillFormWithData(materialData);
    } else {
        loadMaterialData(); // Fallback ke sessionStorage
    }
}

function fillFormWithData(materialData) {
    console.log('Filling form with data:', materialData);

    try {
        // Clear previous messages
        clearMessages();

        // Validate required data
        if (!materialData || typeof materialData !== 'object') {
            throw new Error('Data material tidak valid');
        }

        const requiredFields = ['material', 'plant', 'storage_location'];
        for (const field of requiredFields) {
            if (!materialData[field]) {
                throw new Error(`Field ${field} tidak ditemukan dalam data material`);
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
        document.getElementById('material-description').textContent = materialData.material_description || 'Deskripsi tidak tersedia';
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
        document.getElementById('pack_qty_text').textContent = `Quantity di-set otomatis dari stock: ${stockQty.toLocaleString('id-ID')} PC`;

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
                suggestionElement.innerHTML = `<span class="text-info"><i class="fas fa-info-circle me-1"></i>Disarankan: 50016873 (ZMG1)</span>`;
            } else if (magry === 'ZMG2') {
                suggestionElement.innerHTML = `<span class="text-info"><i class="fas fa-info-circle me-1"></i>Disarankan: VSTDPLTBW01 atau VSTDPLBW002 (ZMG2)</span>`;
            }
        }

        // Update preview
        const previewHtml = `
            <div class="row">
                <div class="col-md-6">
                    <strong>Material:</strong> ${formattedMaterial}<br>
                    <strong>Deskripsi:</strong> ${materialData.material_description || '-'}<br>
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
        document.getElementById('status-text').textContent = `Material ${formattedMaterial} berhasil dimuat`;
        document.getElementById('material-status').className = 'alert alert-success';

        console.log('Form filled successfully');

        // Check form validity setelah data dimuat
        checkFormValidity();

    } catch (error) {
        console.error('Error in fillFormWithData:', error);
        showError('Error memuat data material: ' + error.message);
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
        alert('SAP User harus diisi');
        document.getElementById('sap_user_modal').focus();
        return;
    }

    if (!sapPassword) {
        alert('SAP Password harus diisi');
        document.getElementById('sap_password_modal').focus();
        return;
    }

    // Add SAP credentials to form sebagai hidden fields
    const sapUserInput = document.createElement('input');
    sapUserInput.type = 'hidden';
    sapUserInput.name = 'sap_user';
    sapUserInput.value = sapUser;

    const sapPasswordInput = document.createElement('input');
    sapPasswordInput.type = 'hidden';
    sapPasswordInput.name = 'sap_password';
    sapPasswordInput.value = sapPassword;

    document.getElementById('huForm').appendChild(sapUserInput);
    document.getElementById('huForm').appendChild(sapPasswordInput);

    // Submit form
    document.getElementById('huForm').submit();

    // Show loading state
    document.getElementById('confirmSapCredentials').disabled = true;
    document.getElementById('confirmSapCredentials').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating HU...';
}

function validateForm() {
    if (!currentMaterialData) {
        showError('Silakan pilih material dari halaman utama terlebih dahulu.');
        return false;
    }

    const huExid = document.getElementById('hu_exid').value.trim();
    const packMat = document.getElementById('pack_mat').value;
    const packQty = document.getElementById('pack_qty').value;

    // Validasi HU External ID
    if (!huExid) {
        showError('HU External ID harus diisi');
        document.getElementById('hu_exid').focus();
        return false;
    }

    if (huExid.length !== 10) {
        showError('HU External ID harus tepat 10 digit angka');
        document.getElementById('hu_exid').focus();
        return false;
    }

    if (!/^\d+$/.test(huExid)) {
        showError('HU External ID hanya boleh berisi angka');
        document.getElementById('hu_exid').focus();
        return false;
    }

    if (!packMat) {
        showError('Packaging Material harus dipilih');
        document.getElementById('pack_mat').focus();
        return false;
    }

    if (!packQty || parseFloat(packQty) <= 0) {
        showError('Pack Quantity harus lebih dari 0');
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
        // Potong ke 10 digit
        input.value = numericValue.slice(0, 10);
    }

    checkFormValidity();
}

function showError(message) {
    console.error('Showing error:', message);
    const errorElement = document.getElementById('js-error-message');
    const errorText = document.getElementById('js-error-text');
    if (errorElement && errorText) {
        errorText.textContent = message;
        errorElement.style.display = 'block';
    } else {
        alert('Error: ' + message); // Fallback
    }
}

function showSuccess(message) {
    console.log('Showing success:', message);
    const successElement = document.getElementById('js-success-message');
    const successText = document.getElementById('js-success-text');
    if (successElement && successText) {
        successText.textContent = message;
        successElement.style.display = 'block';

        // Auto hide after 3 seconds
        setTimeout(() => {
            successElement.style.display = 'none';
        }, 3000);
    }
}

function clearMessages() {
    const errorElement = document.getElementById('js-error-message');
    const successElement = document.getElementById('js-success-message');

    if (errorElement) errorElement.style.display = 'none';
    if (successElement) successElement.style.display = 'none';
}

function resetForm() {
    if (confirm('Apakah Anda yakin ingin membatalkan? Semua data yang telah diisi akan hilang.')) {
        // Clear sessionStorage
        sessionStorage.removeItem('scenario1_data');
        window.location.href = "{{ route('hu.index') }}";
    }
}
</script>
@endpush
