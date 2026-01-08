<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SAP HU Automation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reset dan base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Container login */
        .login-container {
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.6s ease-out;
        }

        /* Kartu login */
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.35);
        }

        /* Header kartu */
        .card-header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
        }

        .card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
        }

        /* Body kartu */
        .card-body {
            padding: 2.5rem 2rem;
        }

        /* Form styles */
        .form-group {
            margin-bottom: 1.75rem;
        }

        .form-label {
            display: block;
            color: #4b5563;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #f9fafb;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            background-color: white;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .form-input.error {
            border-color: #ef4444;
            background-color: #fef2f2;
        }

        /* Tombol login */
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Alert messages */
        .alert-box {
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            animation: slideDown 0.3s ease-out;
        }

        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .alert-success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        /* Link */
        .link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .link:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
            }

            .card-header,
            .card-body {
                padding: 2rem 1.5rem;
            }

            body {
                padding: 15px;
                background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            }

            .login-card {
                box-shadow: 0 15px 50px rgba(0, 0, 0, 0.25);
            }
        }

        /* Utility classes */
        .text-center {
            text-align: center;
        }

        .mt-4 {
            margin-top: 1rem;
        }

        .mb-2 {
            margin-bottom: 0.5rem;
        }

        .mb-4 {
            margin-bottom: 1rem;
        }

        .text-sm {
            font-size: 0.875rem;
        }

        .text-blue-100 {
            color: #dbeafe;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="card-header">
                <div class="mb-4">
                    <i class="fas fa-cubes text-5xl"></i>
                </div>
                <h1 class="text-3xl font-bold mb-2">SAP HU Automation</h1>
                <p class="text-blue-100 text-lg">Warehouse Management System</p>
            </div>

            <div class="card-body">
                <!-- Pesan Error -->
                @if ($errors->any())
                    <div class="alert-box alert-error">
                        <i class="fas fa-exclamation-triangle mt-1 mr-3"></i>
                        <div>
                            @foreach ($errors->all() as $error)
                                <p class="mb-1">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Pesan Success -->
                @if (session('status'))
                    <div class="alert-box alert-success">
                        <i class="fas fa-check-circle mt-1 mr-3"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                <!-- Form Login -->
                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf

                    <div class="form-group">
                        <label class="form-label" for="email">
                            <i class="fas fa-envelope mr-2"></i>Email Address
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-input"
                            placeholder="Enter your email"
                            required
                            autofocus
                            autocomplete="email"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">
                            <i class="fas fa-lock mr-2"></i>Password
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                    </div>

                    <div class="form-group">
                        <button type="submit" id="loginButton" class="btn-login">
                            <i class="fas fa-sign-in-alt"></i>
                            <span id="buttonText">Login to System</span>
                        </button>
                    </div>

                    <div class="text-center mt-4">
                        <a href="#" class="link text-sm">
                            <i class="fas fa-question-circle mr-1"></i>
                            Need help logging in?
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');
            const buttonText = document.getElementById('buttonText');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            // Validasi input real-time
            function validateInputs() {
                let isValid = true;

                // Reset error states
                emailInput.classList.remove('error');
                passwordInput.classList.remove('error');

                // Validasi email
                if (!emailInput.value.trim()) {
                    emailInput.classList.add('error');
                    isValid = false;
                } else if (!isValidEmail(emailInput.value)) {
                    emailInput.classList.add('error');
                    isValid = false;
                }

                // Validasi password
                if (!passwordInput.value) {
                    passwordInput.classList.add('error');
                    isValid = false;
                }

                return isValid;
            }

            // Fungsi validasi email sederhana
            function isValidEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }

            // Event listener untuk form submission
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Validasi form
                if (!validateInputs()) {
                    // Tampilkan pesan error
                    if (!emailInput.value.trim() || !isValidEmail(emailInput.value)) {
                        alert('Please enter a valid email address');
                        emailInput.focus();
                    } else if (!passwordInput.value) {
                        alert('Please enter your password');
                        passwordInput.focus();
                    }
                    return false;
                }

                // Tampilkan loading state
                loginButton.disabled = true;
                loginButton.innerHTML = `
                    <i class="fas fa-spinner spin"></i>
                    <span>Processing Login...</span>
                `;

                // Submit form setelah delay kecil untuk menunjukkan loading
                setTimeout(() => {
                    loginForm.submit();
                }, 300);
            });

            // Real-time validation on input
            emailInput.addEventListener('blur', function() {
                if (this.value.trim() && !isValidEmail(this.value)) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });

            passwordInput.addEventListener('blur', function() {
                if (!this.value) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });

            // Clear error state on focus
            emailInput.addEventListener('focus', function() {
                this.classList.remove('error');
            });

            passwordInput.addEventListener('focus', function() {
                this.classList.remove('error');
            });

            // Auto-focus email field jika kosong
            if (!emailInput.value) {
                emailInput.focus();
            }
        });
    </script>
</body>
</html>
