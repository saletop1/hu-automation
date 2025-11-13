<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAP HU Automation</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* ... existing styles ... */

        /* Frosted Glass Modal Styles */
        .frosted-glass {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 12px;
        }
        .frosted-glass .modal-content {
            background: transparent;
            border: none;
        }
        .frosted-glass .modal-header {
            background: rgba(255, 255, 255, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px 12px 0 0;
        }
        .frosted-glass .modal-body {
            background: rgba(255, 255, 255, 0.15);
        }
        .frosted-glass .modal-footer {
            background: rgba(255, 255, 255, 0.2);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0 0 12px 12px;
        }
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.5);
        }
        .sap-input {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 12px;
            font-size: 0.9rem;
        }
        .sap-input:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient-primary shadow-lg sticky-top">
        <!-- ... existing navbar content ... -->
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- SAP Credentials Modal -->
    <div class="modal fade" id="sapCredentialsModal" tabindex="-1" aria-labelledby="sapCredentialsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content frosted-glass">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-gray-800" id="sapCredentialsModalLabel">
                        <i class="fas fa-key me-2 text-blue-500"></i>
                        SAP Credentials for HU Creation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info bg-light border-0 mb-4">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Masukkan kredensial SAP khusus untuk pembuatan Handling Unit
                        </small>
                    </div>

                    <form id="sapCredentialForm">
                        <div class="mb-3">
                            <label for="sap_user" class="form-label fw-semibold text-gray-700">
                                SAP User <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control sap-input" id="sap_user"
                                   placeholder="Masukkan username SAP" required>
                            <div class="form-text text-muted">Username SAP untuk create HU</div>
                        </div>

                        <div class="mb-3">
                            <label for="sap_password" class="form-label fw-semibold text-gray-700">
                                SAP Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control sap-input" id="sap_password"
                                   placeholder="Masukkan password SAP" required>
                            <div class="form-text text-muted">Password SAP untuk create HU</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmSapCredentials">
                        <i class="fas fa-check me-2"></i>Confirm & Create HU
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    @stack('styles')
    @stack('scripts')
</body>
</html>
