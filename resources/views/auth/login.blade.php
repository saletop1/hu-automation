<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SAP HU Automation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                        url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?ixlib=rb-4.0.1&auto=format&fit=crop&w=2070&q=80') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
        }

        /* Overlay background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(30, 64, 175, 0.1));
            z-index: -1;
        }

        .frosted-glass {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 10;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 20;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            position: relative;
            overflow: hidden;
        }

        .card-header {
            background: rgba(59, 130, 246, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2rem;
            text-align: center;
        }

        .card-body {
            padding: 2rem;
        }

        .form-input {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            color: white;
            padding: 12px 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            width: 100%;
            position: relative;
            z-index: 30;
        }

        .form-input:focus {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(59, 130, 246, 0.8);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
            outline: none;
            color: white;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .btn-login {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
            border-radius: 12px;
            padding: 12px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            cursor: pointer;
            position: relative;
            z-index: 30;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .text-glow {
            text-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        }

        .icon-glow {
            filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.5));
        }

        .link-light {
            color: rgba(255, 255, 255, 0.8);
            transition: color 0.3s ease;
            cursor: pointer;
            position: relative;
            z-index: 30;
        }

        .link-light:hover {
            color: white;
            text-decoration: none;
        }

        /* Pastikan semua form elements bisa di-click */
        input, button, a {
            pointer-events: auto !important;
        }

        .form-group {
            position: relative;
            z-index: 30;
        }

        /* Animasi subtle */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        /* Responsive adjustments */
        @media (max-width: 480px) {
            .login-card {
                max-width: 350px;
            }

            .card-header,
            .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card frosted-glass floating">
            <div class="card-header">
                <div class="icon-glow mb-4">
                    <i class="fas fa-cubes text-white text-5xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-white text-glow mb-2">SAP HU Automation</h1>
                <p class="text-blue-200">Warehouse Management System</p>
            </div>

            <div class="card-body">
                @if ($errors->any())
                    <div class="bg-red-500 bg-opacity-20 border border-red-400 border-opacity-50 rounded-lg p-3 mb-4">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-300 mr-2"></i>
                            <span class="text-red-200 text-sm">
                                @foreach ($errors->all() as $error)
                                    {{ $error }}
                                @endforeach
                            </span>
                        </div>
                    </div>
                @endif

                @if (session('status'))
                    <div class="bg-green-500 bg-opacity-20 border border-green-400 border-opacity-50 rounded-lg p-3 mb-4">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-300 mr-2"></i>
                            <span class="text-green-200 text-sm">{{ session('status') }}</span>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <div class="form-group">
                        <label class="block text-blue-200 text-sm font-medium mb-2" for="email">
                            <i class="fas fa-envelope mr-2"></i>Email
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-input"
                            placeholder="Masukkan email Anda"
                            required
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label class="block text-blue-200 text-sm font-medium mb-2" for="password">
                            <i class="fas fa-lock mr-2"></i>Password
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Masukkan password Anda"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-login">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login
                        </button>
                    </div>

                    <div class="text-center">
                        <a href="#" class="link-light text-sm">
                            <i class="fas fa-question-circle mr-1"></i>Butuh bantuan?
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Pastikan semua elemen form bisa di-click
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input, button, a');
            inputs.forEach(element => {
                element.style.pointerEvents = 'auto';
                element.style.position = 'relative';
                element.style.zIndex = '1000';
            });

            // Debug: log jika ada elemen yang masih tidak bisa di-click
            document.addEventListener('click', function(e) {
                console.log('Clicked element:', e.target);
            }, true);
        });
    </script>
</body>
</html>
