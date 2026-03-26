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

    <!-- Top Bar: Header + Action Buttons dalam 1 baris -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-green-100 rounded-circle d-flex align-items-center justify-content-center" style="width:30px;height:30px;min-width:30px">
                <i class="fas fa-boxes text-green-600" style="font-size:0.85rem"></i>
            </div>
            <div>
                <h1 class="h6 fw-bold text-gray-800 mb-0 lh-1">Skenario 2 — Single HU, Multiple Materials</h1>
                <span class="text-muted" style="font-size:0.8rem">1 HU berisi banyak material</span>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('hu.index') }}" class="btn btn-outline-secondary btn-sm py-1 px-2">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
            <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2" onclick="resetForm()">
                <i class="fas fa-times me-1"></i>Cancel
            </button>
            <button type="button" class="btn btn-success btn-sm py-1 px-2" id="createHuButton">
                <i class="fas fa-save me-1"></i>Create HU
            </button>
        </div>
    </div>

    <form action="{{ route('hu.store-single-multi') }}" method="POST" id="huForm">
        @csrf
        <input type="hidden" id="base_unit_qty" name="base_unit_qty" value="">
        <input type="hidden" id="sap_user" name="sap_user" value="">
        <input type="hidden" id="sap_password" name="sap_password" value="">

        <div class="row g-2">

            <!-- ===== KIRI: HU Header ===== -->
            <div class="col-lg-4 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-1 px-3 border-bottom d-flex align-items-center gap-1">
                        <i class="fas fa-edit text-blue-500" style="font-size:0.85rem"></i>
                        <span class="fw-semibold" style="font-size:0.85rem">HU Header Information</span>
                    </div>
                    <div class="card-body p-3">

                        <!-- HU External ID -->
                        <div class="mb-2">
                            <label for="hu_exid" class="form-label fw-semibold mb-1" style="font-size:0.83rem">
                                HU External ID <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0" style="font-size:0.83rem">
                                    <i class="fas fa-barcode text-green-500"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 hu-exid-input"
                                       id="hu_exid" name="hu_exid" maxlength="10"
                                       value="{{ old('hu_exid') }}" required
                                       placeholder="10 digits"
                                       oninput="validateHuExid(this)"
                                       style="font-size:0.85rem">
                            </div>
                            <div style="font-size:0.75rem;margin-top:2px">
                                <span id="hu_exid_status" class="text-muted">Enter 10 digits</span>
                            </div>
                        </div>

                        <!-- Packaging Material -->
                        <div class="mb-2">
                            <label for="pack_mat" class="form-label fw-semibold mb-1" style="font-size:0.83rem">
                                Packaging Material <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm" id="pack_mat" name="pack_mat" style="font-size:0.85rem">
                                <option value="">— Select —</option>
                                <option value="VSTDPLTBW01">VSTDPLTBW01</option>
                                <option value="VSTDPLTBW02">VSTDPLTBW02</option>
                                <option value="50016873">50016873</option>
                            </select>
                            <div style="font-size:0.75rem;margin-top:2px" id="pack_mat_suggestion">
                                <span class="text-muted">Select packaging material</span>
                            </div>
                        </div>

                        <!-- Plant & Storage Location (read-only info grid) -->
                        <div class="mt-2 rounded border overflow-hidden" style="font-size:0.83rem">
                            <div class="info-row-sm">
                                <span class="info-label-sm">Plant</span>
                                <input type="text" class="info-input-sm" id="plant" name="plant"
                                       value="{{ old('plant') }}" required readonly>
                            </div>
                            <div class="info-row-sm" style="border-bottom:none">
                                <span class="info-label-sm">Stor. Loc</span>
                                <input type="text" class="info-input-sm" id="stge_loc" name="stge_loc"
                                       value="{{ old('stge_loc') }}" required readonly>
                            </div>
                        </div>
                        <p class="text-muted mt-1 mb-0" style="font-size:0.72rem">
                            <i class="fas fa-info-circle me-1"></i>Plant & Lokasi otomatis dari material
                        </p>

                    </div>
                </div>
            </div>

            <!-- ===== KANAN: Materials List ===== -->
            <div class="col-lg-8 col-xl-9">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-1 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-1">
                            <i class="fas fa-list text-orange-500" style="font-size:0.85rem"></i>
                            <span class="fw-semibold" style="font-size:0.85rem">Materials List</span>
                        </div>
                        <span id="materialCount" class="badge bg-success" style="font-size:0.75rem">0 items</span>
                    </div>
                    <div class="card-body p-2">

                        <!-- Empty State -->
                        <div id="itemsPreview" class="text-center py-3 border-dashed rounded" style="border:2px dashed #dee2e6">
                            <i class="fas fa-boxes fa-lg text-gray-400 mb-1"></i>
                            <p class="text-muted mb-0" style="font-size:0.83rem">Belum ada material — pilih dari main page dengan drag & drop</p>
                        </div>

                        <!-- Materials Table Container -->
                        <div id="items-container" style="display:none">
                            <!-- Header row tabel -->
                            <div class="mat-table-header">
                                <div class="mat-col mat-col-no">#</div>
                                <div class="mat-col mat-col-mat">Material</div>
                                <div class="mat-col mat-col-desc">Deskripsi</div>
                                <div class="mat-col mat-col-batch">Batch</div>
                                <div class="mat-col mat-col-stock">Stock</div>
                                <div class="mat-col mat-col-so">Sales Order</div>
                                <div class="mat-col mat-col-qty">Pack Qty <span class="text-danger">*</span></div>
                            </div>
                            <!-- Rows akan di-inject JS di sini -->
                        </div>

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
/* === COLOR UTILS === */
.bg-green-100 { background-color: #d1fae5; }
.text-green-600 { color: #059669; }
.text-green-500 { color: #10b981; }
.text-blue-500  { color: #2196f3; }
.text-orange-500{ color: #f97316; }

/* === HU EXID === */
.hu-exid-input { border-color: #6c757d !important; }
.hu-exid-input.valid   { border-color: #198754 !important; box-shadow: 0 0 0 2px rgba(25,135,84,.15); }
.hu-exid-input.warning { border-color: #ffc107 !important; box-shadow: 0 0 0 2px rgba(255,193,7,.15); }
.hu-exid-input.invalid { border-color: #dc3545 !important; box-shadow: 0 0 0 2px rgba(220,53,69,.15); }
.status-valid   { color: #198754; font-weight:600; font-size:0.68rem; }
.status-warning { color: #e6a817; font-weight:600; font-size:0.68rem; }
.status-invalid { color: #dc3545; font-weight:600; font-size:0.68rem; }

/* === INFO ROW (Plant / Stor.Loc readonly) === */
.info-row-sm {
    display: flex;
    align-items: center;
    border-bottom: 1px solid #f0f0f0;
    padding: 4px 8px;
    background: #fafafa;
}
.info-label-sm {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #6c757d;
    min-width: 60px;
    flex-shrink: 0;
}
.info-input-sm {
    border: none;
    background: transparent;
    font-size: 0.85rem;
    color: #1f2937;
    font-weight: 500;
    padding: 0;
    width: 100%;
    outline: none;
}

/* === MATERIAL TABLE === */
.mat-table-header {
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
.mat-row {
    display: flex;
    align-items: center;
    border: 1px solid #e2e8f0;
    border-top: none;
    padding: 6px 8px;
    font-size: 0.82rem;
    background: #fff;
    transition: background 0.1s;
}
.mat-row:last-child { border-radius: 0 0 4px 4px; }
.mat-row:nth-child(even) { background: #f8fafc; }
.mat-row:hover { background: #f0fdf4; }
.mat-col         { padding: 0 5px; overflow: hidden; }
.mat-col-no      { width: 28px;  flex-shrink:0; color:#94a3b8; font-size:0.72rem; text-align:center; }
.mat-col-mat     { width: 100px; flex-shrink:0; font-weight:600; font-family:monospace; color:#1e40af; font-size:0.82rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.mat-col-desc    { flex: 1;      color:#4b5563; font-size:0.8rem; font-style:italic; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.mat-col-batch   { width: 88px;  flex-shrink:0; font-family:monospace; font-size:0.8rem; color:#374151; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.mat-col-stock   { width: 80px;  flex-shrink:0; text-align:center; }
/* Sales Order: tidak dipotong, biarkan wrap */
.mat-col-so      { width: 120px; flex-shrink:0; font-family:monospace; font-size:0.78rem; color:#6b7280; white-space:normal; word-break:break-all; line-height:1.3; }
.mat-col-qty     { width: 108px; flex-shrink:0; }

.stock-badge {
    background: #16a34a;
    color: white;
    padding: 2px 7px;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}
.qty-input {
    width: 100%;
    padding: 3px 6px;
    border: 1px solid #ced4da;
    border-radius: 3px;
    font-size: 0.8rem;
    text-align: right;
}
.qty-input:focus {
    border-color: #28a745;
    outline: none;
    box-shadow: 0 0 0 2px rgba(40,167,69,.15);
}

@media (max-width: 991.98px) {
    .mat-col-desc, .mat-col-so { display: none; }
}
</style>
@endpush

@push('scripts')
<script>
// Global variable untuk menghitung items
let itemCount = 0;

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

                // Clear container (hanya hapus baris, bukan header tabel)
                const existingRows = document.querySelectorAll('#items-container .mat-row');
                existingRows.forEach(r => r.remove());

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

// Fungsi add item to form — versi tabel padat
function addItemToForm(item, index) {
    const container = document.getElementById('items-container');

    const material         = item.material || '';
    const batch            = item.batch || '';
    const plant            = item.plant || '3000';
    const storageLocation  = item.storage_location || '3D10';
    const materialDescription = item.material_description || '';

    let stockQty = parseFloat(item.stock_quantity);
    if (isNaN(stockQty)) stockQty = 0;

    const salesOrderNo     = getSalesOrderNo(item);
    const formattedMaterial = formatMaterialNumber(material);
    const rowNum           = itemCount + 1;

    console.log(`📝 Creating table row ${itemCount}:`, { material: formattedMaterial, batch, stockQty });

    const row = document.createElement('div');
    row.className = 'mat-row';
    row.innerHTML = `
        <div class="mat-col mat-col-no">${rowNum}</div>
        <div class="mat-col mat-col-mat" title="${formattedMaterial}">
            ${formattedMaterial}
            <input type="hidden" name="items[${itemCount}][material]" value="${formattedMaterial}">
            <input type="hidden" name="items[${itemCount}][batch]" value="${batch}">
            <input type="hidden" name="items[${itemCount}][plant]" value="${plant}">
            <input type="hidden" name="items[${itemCount}][storage_location]" value="${storageLocation}">
            <input type="hidden" name="items[${itemCount}][sp_stck_no]" value="${salesOrderNo}">
        </div>
        <div class="mat-col mat-col-desc" title="${materialDescription}">${materialDescription || '-'}</div>
        <div class="mat-col mat-col-batch" title="${batch}">${batch || '-'}</div>
        <div class="mat-col mat-col-stock">
            <span class="stock-badge">${stockQty.toLocaleString('id-ID')} PC</span>
        </div>
        <div class="mat-col mat-col-so" title="${salesOrderNo}">${salesOrderNo || '-'}</div>
        <div class="mat-col mat-col-qty">
            <input type="number" class="qty-input" name="items[${itemCount}][pack_qty]"
                   placeholder="0" step="1" min="1" max="${stockQty}"
                   value="${stockQty}" required data-max-qty="${stockQty}">
        </div>
    `;
    container.appendChild(row);
    itemCount++;
}

function validateForm() {
    // Validasi HU External ID
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

    // Validasi Qty
    let qtyError = false;
    const qtyInputs = document.querySelectorAll('#items-container input[name*="[pack_qty]"]');

    qtyInputs.forEach((input, index) => {
        const maxQty = parseFloat(input.dataset.maxQty);
        let enteredQty = input.value.replace(/,/g, '.');
        const qty = parseFloat(enteredQty);

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

        input.value = enteredQty;
    });

    if (qtyError) {
        return false;
    }

    return true;
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
</script>
@endpush