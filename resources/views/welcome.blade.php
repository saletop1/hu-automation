<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAP HU Automation - Warehouse Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
                        url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?ixlib=rb-4.0.1&auto=format&fit=crop&w=2070&q=80') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .frosted-glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .welcome-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        .welcome-card {
            width: 100%;
            max-width: 800px;
            text-align: center;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-5px);
            border-color: rgba(59, 130, 246, 0.4);
        }

        .btn-login-top {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 12px 24px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            position: fixed;
            top: 30px;
            right: 30px;
            z-index: 1000;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-login-top:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        .text-glow {
            text-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        }

        .icon-glow {
            filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.5));
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        @media (max-width: 768px) {
            .btn-login-top {
                top: 20px;
                right: 20px;
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- GUNAKAN ELEMENT <a> BUKAN <button> -->
    <a href="/login" class="btn-login-top">
        <i class="fas fa-sign-in-alt mr-2"></i>Login
    </a>

    <div class="welcome-container">
        <div class="welcome-card frosted-glass floating fade-in-up">
            <div class="px-8 py-12">
                <div class="icon-glow mb-6">
                    <i class="fas fa-cubes text-white text-6xl"></i>
                </div>

                <h1 class="text-5xl font-bold text-white text-glow mb-4">
                    SAP HU Automation
                </h1>

                <p class="text-xl text-blue-200 mb-8 max-w-2xl mx-auto">
                    Sistem Otomasi Handling Unit Terintegrasi untuk Management Gudang Modern
                </p>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                    <div class="feature-card">
                        <i class="fas fa-robot text-3xl text-blue-400 mb-4"></i>
                        <div class="stats-number">99.9%</div>
                        <p class="text-blue-200">Akurasi Sistem</p>
                    </div>

                    <div class="feature-card">
                        <i class="fas fa-bolt text-3xl text-green-400 mb-4"></i>
                        <div class="stats-number">5x</div>
                        <p class="text-blue-200">Lebih Cepat</p>
                    </div>

                    <div class="feature-card">
                        <i class="fas fa-chart-line text-3xl text-purple-400 mb-4"></i>
                        <div class="stats-number">100%</div>
                        <p class="text-blue-200">Integrasi SAP</p>
                    </div>
                </div>

                <!-- Features -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="feature-card text-left">
                        <i class="fas fa-box-open text-2xl text-blue-400 mb-3"></i>
                        <h3 class="text-white text-lg font-semibold mb-2">Management Handling Unit</h3>
                        <p class="text-blue-200 text-sm">
                            Kelola HU dengan mudah melalui antarmuka yang intuitif dan terintegrasi langsung dengan SAP
                        </p>
                    </div>

                    <div class="feature-card text-left">
                        <i class="fas fa-sync-alt text-2xl text-green-400 mb-3"></i>
                        <h3 class="text-white text-lg font-semibold mb-2">Sync Real-time</h3>
                        <p class="text-blue-200 text-sm">
                            Sinkronisasi data stock secara real-time dengan sistem SAP tanpa delay
                        </p>
                    </div>

                    <div class="feature-card text-left">
                        <i class="fas fa-history text-2xl text-purple-400 mb-3"></i>
                        <h3 class="text-white text-lg font-semibold mb-2">Tracking Lengkap</h3>
                        <p class="text-blue-200 text-sm">
                            Lacak seluruh history HU dari pembuatan hingga pengiriman dengan detail lengkap
                        </p>
                    </div>

                    <div class="feature-card text-left">
                        <i class="fas fa-shield-alt text-2xl text-yellow-400 mb-3"></i>
                        <h3 class="text-white text-lg font-semibold mb-2">Keamanan Terjamin</h3>
                        <p class="text-blue-200 text-sm">
                            Sistem authentication yang aman dengan role-based access control
                        </p>
                    </div>
                </div>

                <!-- Footer Info -->
                <div class="mt-8 pt-6 border-t border-gray-600 border-opacity-30">
                    <p class="text-blue-300 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        PT. Kayu Mebel Indonesia - All Rights Reserved 2025
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Debug script
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📍 Welcome page loaded');
            console.log('🔗 Login URL:', window.location.origin + '/login');

            const loginBtn = document.querySelector('.btn-login-top');

            loginBtn.addEventListener('click', function(e) {
                console.log('✅ Login button clicked');
                console.log('🎯 Navigating to:', this.href);
            });
        });

        // Fallback jika masih tidak bekerja
        function forceLoginRedirect() {
            console.log('🚀 Force redirect to login');
            window.location.href = '/login';
        }
    </script>
</body>
</html>
