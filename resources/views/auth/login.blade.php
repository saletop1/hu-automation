<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SAP HU Automation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reset dasar */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
                        url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?ixlib=rb-4.0.1&auto=format&fit=crop&w=2070&q=80') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        /* Pastikan semua overlay hanya background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(30, 64, 175, 0.15));
            z-index: 1;
            pointer-events: none; /* Ini penting! */
        }

        /* Container utama */
        .login-container {
            position: relative;
            z-index: 100;
            width: 100%;
            max-width: 450px;
        }

        /* Kartu login */
        .login-card {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 20px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            position: relative;
            z-index: 200;
        }

        /* Header kartu */
        .card-header {
            background: rgba(59, 130, 246, 0.25);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            z-index: 300;
        }

        /* Body kartu */
        .card-body {
            padding: 2.5rem 2rem;
            position: relative;
            z-index: 300;
        }

        /* Input fields */
        .form-input {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            color: white;
            padding: 14px 16px;
            font-size: 16px;
            transition: all 0.3s ease;
            width: 100%;
            position: relative;
            z-index: 400;
        }

        .form-input:focus {
            background: rgba(255, 255, 255, 0.22);
            border-color: rgba(59, 130, 246, 0.9);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
            outline: none;
            color: white;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        /* Tombol login */
        .btn-login {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
            border-radius: 12px;
            padding: 15px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            width: 100%;
            cursor: pointer;
            position: relative;
            z-index: 400;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        /* Efek teks dan icon */
        .text-glow {
            text-shadow: 0 0 20px rgba(59, 130, 246, 0.6);
        }

        .icon-glow {
            filter: drop-shadow(0 0 12px rgba(59, 130, 246, 0.6));
        }

        /* Link */
        .link-light {
            color: rgba(255, 255, 255, 0.85);
            transition: color 0.3s ease;
            text-decoration: none;
            position: relative;
            z-index: 400;
            display: inline-block;
        }

        .link-light:hover {
            color: white;
            text-decoration: underline;
        }

        /* Pesan error/success */
        .alert-box {
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            position: relative;
            z-index: 400;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.4);
        }

        /* Label form */
        .form-label {
            display: block;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            position: relative;
            z-index: 400;
        }

        /* Grup form */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 400;
        }

        /* Animasi floating ringan */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .floating {
            animation: float 5s ease-in-out infinite;
        }

        /* Responsif */
        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
            }

            .card-header,
            .card-body {
                padding: 2rem 1.5rem;
            }
        }

        /* Fix khusus untuk elemen yang bisa diklik */
        .clickable {
            position: relative;
            z-index: 1000 !important;
            pointer-events: auto !important;
        }

        /* Pastikan form tidak terhalang */
        form {
            position: relative;
            z-index: 500;
        }

        /* Tambahkan ini untuk memastikan input dan button benar-benar bisa diklik */
        input, button, a, .form-group, .btn-login {
            pointer-events: auto !important;
            user-select: auto !important;
            -webkit-user-select: auto !important;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card floating">
            <div class="card-header">
                <div class="icon-glow mb-4">
                    <i class="fas fa-cubes text-white text-5xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-white text-glow mb-2">SAP HU Automation</h1>
                <p class="text-blue-200 text-lg">Warehouse Management System</p>
            </div>

            <div class="card-body">
                <!-- Pesan Error -->
                @if ($errors->any())
                    <div class="alert-box alert-error">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-300 mr-3 text-lg"></i>
                            <div>
                                @foreach ($errors->all() as $error)
                                    <p class="text-red-200 text-sm">{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Pesan Success -->
                @if (session('status'))
                    <div class="alert-box alert-success">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-300 mr-3 text-lg"></i>
                            <span class="text-green-200 text-sm">{{ session('status') }}</span>
                        </div>
                    </div>
                @endif

                <!-- Form Login -->
                <form method="POST" action="{{ route('login') }}" id="loginForm" class="space-y-1">
                    @csrf

                    <div class="form-group">
                        <label class="form-label" for="email">
                            <i class="fas fa-envelope mr-2"></i>Email
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-input clickable"
                            placeholder="Masukkan email Anda"
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
                            class="form-input clickable"
                            placeholder="Masukkan password Anda"
                            required
                            autocomplete="current-password"
                        >
                    </div>

                    <div class="form-group">
                        <button type="submit" id="loginButton" class="btn-login clickable">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login
                        </button>
                    </div>

                    <div class="text-center pt-4">
                        <a href="#" class="link-light text-sm clickable">
                            <i class="fas fa-question-circle mr-1"></i>Butuh bantuan login?
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Fungsi untuk memastikan semua elemen dapat diklik
        function ensureClickable() {
            // Pastikan semua elemen penting bisa diklik
            const importantElements = [
                '#email',
                '#password',
                '#loginButton',
                '#loginForm',
                '.btn-login',
                '.form-input',
                '.link-light'
            ];

            importantElements.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => {
                    el.style.pointerEvents = 'auto';
                    el.style.zIndex = '1000';
                    el.style.position = 'relative';
                    el.classList.add('clickable');
                });
            });

            // Hapus event listener yang mungkin menghalangi
            document.querySelectorAll('*').forEach(el => {
                el.onclick = null;
            });
        }

        // Event listener untuk form submission
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded - initializing login form');

            // Pastikan elemen bisa diklik
            ensureClickable();

            const loginForm = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            // Debug info
            console.log('Form found:', !!loginForm);
            console.log('Button found:', !!loginButton);

            // Validasi form sebelum submit
            function validateForm() {
                if (!emailInput.value.trim()) {
                    alert('Email harus diisi');
                    emailInput.focus();
                    return false;
                }

                if (!passwordInput.value) {
                    alert('Password harus diisi');
                    passwordInput.focus();
                    return false;
                }

                return true;
            }

            // Handle form submission
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    console.log('Form submission attempted');

                    if (!validateForm()) {
                        e.preventDefault();
                        return false;
                    }

                    // Tampilkan loading state
                    if (loginButton) {
                        loginButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';
                        loginButton.disabled = true;
                    }

                    return true;
                });
            }

            // Backup click handler untuk tombol
            if (loginButton) {
                loginButton.addEventListener('click', function(e) {
                    console.log('Login button clicked directly');

                    // Jika form tidak submit secara otomatis
                    if (loginForm && !loginForm.submitted) {
                        e.preventDefault();
                        e.stopPropagation();

                        if (validateForm()) {
                            // Tampilkan loading
                            loginButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';
                            loginButton.disabled = true;

                            // Submit form
                            loginForm.submit();
                            loginForm.submitted = true;
                        }
                    }
                });

                // Pastikan tombol benar-benar bisa diklik
                loginButton.style.cursor = 'pointer';
                loginButton.style.pointerEvents = 'auto';
            }

            // Cek jika ada element yang overlap
            function checkForOverlap() {
                if (loginButton) {
                    const rect = loginButton.getBoundingClientRect();
                    const elementAtPoint = document.elementFromPoint(
                        rect.left + rect.width / 2,
                        rect.top + rect.height / 2
                    );

                    console.log('Element at button center:', elementAtPoint);

                    // Jika ada elemen lain di atas tombol
                    if (elementAtPoint !== loginButton && !loginButton.contains(elementAtPoint)) {
                        console.warn('Another element is overlapping the button:', elementAtPoint);
                        elementAtPoint.style.pointerEvents = 'none';
                    }
                }
            }

            // Jalankan cek overlap setelah delay
            setTimeout(checkForOverlap, 500);

            // Pastikan lagi setelah 1 detik
            setTimeout(ensureClickable, 1000);
        });

        // Fallback jika ada masalah
        window.addEventListener('load', function() {
            console.log('Window loaded - final check');
            ensureClickable();

            // Force form to be submittable
            document.querySelectorAll('form input, form button').forEach(el => {
                el.onclick = function(e) {
                    e.stopPropagation();
                };
            });
        });
    </script>
</body>
</html>
