<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Management System - Solusi Validasi Ewallet & Bank Terbaik</title>
    <meta name="description" content="API Management System untuk validasi ewallet dan bank account dengan keamanan tinggi, rate limiting, dan monitoring real-time. Hubungi 082279698099 untuk berlangganan.">
    <meta name="keywords" content="API, ewallet, bank validation, GoPay, DANA, OVO, ShopeePay, LinkAja, iSaku">
    
    <!-- Open Graph -->
    <meta property="og:title" content="API Management System - Solusi Validasi Ewallet & Bank Terbaik">
    <meta property="og:description" content="API Management System untuk validasi ewallet dan bank account dengan keamanan tinggi, rate limiting, dan monitoring real-time.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $_SERVER['HTTP_HOST'] ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔐</text></svg>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --gradient-danger: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background: var(--light-color);
        }

        /* Header */
        .header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }

        .nav-link {
            font-weight: 500;
            color: var(--dark-color) !important;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        /* Hero Section */
        .hero {
            background: var(--gradient-primary);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.1"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></radialGradient></defs><circle cx="200" cy="200" r="300" fill="url(%23a)"/><circle cx="800" cy="800" r="400" fill="url(%23a)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .btn-hero {
            background: white;
            color: var(--primary-color);
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-hero:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            color: var(--primary-color);
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background: white;
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 2rem;
            color: white;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--dark-color);
        }

        .feature-card p {
            color: #64748b;
            line-height: 1.6;
        }

        /* Packages Section */
        .packages {
            padding: 100px 0;
            background: var(--light-color);
        }

        .package-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            height: 100%;
        }

        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .package-card.featured {
            border: 3px solid var(--primary-color);
            transform: scale(1.05);
        }

        .package-card.featured::before {
            content: 'POPULER';
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--gradient-primary);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .package-price {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary-color);
            margin: 20px 0;
        }

        .package-features {
            list-style: none;
            padding: 0;
            margin: 30px 0;
        }

        .package-features li {
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
        }

        .package-features li:last-child {
            border-bottom: none;
        }

        .package-features li i {
            color: var(--success-color);
            margin-right: 10px;
        }

        .btn-package {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            width: 100%;
        }

        .btn-package:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
            color: white;
        }

        /* Stats Section */
        .stats {
            padding: 80px 0;
            background: var(--gradient-primary);
            color: white;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Contact Section */
        .contact {
            padding: 100px 0;
            background: white;
        }

        .contact-card {
            background: var(--gradient-primary);
            color: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
        }

        .contact-card h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .contact-card p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .btn-whatsapp {
            background: #25d366;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-whatsapp:hover {
            background: #128c7e;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 211, 102, 0.4);
            color: white;
        }

        /* Footer */
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 60px 0 30px;
        }

        .footer h5 {
            font-weight: 700;
            margin-bottom: 20px;
        }

        .footer p, .footer a {
            color: #94a3b8;
            text-decoration: none;
        }

        .footer a:hover {
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1.1rem;
            }
            
            .package-card.featured {
                transform: none;
                margin-top: 20px;
            }
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.8s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in-left {
            animation: slideInLeft 0.8s ease-in-out;
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .slide-in-right {
            animation: slideInRight 0.8s ease-in-out;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-shield-alt me-2"></i>
                    API Management
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#features">Fitur</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#packages">Paket</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="api-docs.php">Dokumentasi</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#contact">Kontak</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="user/index.php">Login User</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1 class="fade-in">Solusi API Validasi Ewallet & Bank Terbaik</h1>
                    <p class="fade-in">Tingkatkan keamanan dan efisiensi bisnis Anda dengan API Management System yang powerful, aman, dan mudah digunakan.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="user/index.php" class="btn-hero fade-in">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Login User
                        </a>
                        <a href="admin/index.php" class="btn-hero fade-in" style="background: rgba(255,255,255,0.1); color: white; border: 2px solid rgba(255,255,255,0.3); font-size: 0.9rem; padding: 10px 20px;">
                            <i class="fas fa-cog me-2"></i>
                            Admin Panel
                        </a>
                        <a href="#contact" class="btn-hero fade-in" style="background: rgba(255,255,255,0.2); color: white; border: 2px solid white;">
                            <i class="fab fa-whatsapp me-2"></i>
                            Hubungi Sekarang
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="fade-in">
                        <i class="fas fa-mobile-alt" style="font-size: 15rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="display-4 fw-bold mb-4">Fitur Unggulan</h2>
                    <p class="lead">Dapatkan semua yang Anda butuhkan untuk mengelola API validasi dengan mudah dan aman</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card slide-in-left">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Keamanan Tinggi</h3>
                        <p>Enkripsi end-to-end, API key management, dan sistem autentikasi yang robust untuk melindungi data Anda.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <h3>Rate Limiting</h3>
                        <p>Kontrol penggunaan API dengan rate limiting yang dapat dikustomisasi per menit dan per hari.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card slide-in-right">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Monitoring Real-time</h3>
                        <p>Pantau penggunaan API, performa, dan statistik secara real-time dengan dashboard yang intuitif.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card slide-in-left">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3>Multi Ewallet</h3>
                        <p>Support 7 ewallet populer: GoPay, DANA, OVO, ShopeePay, LinkAja, iSaku, dan GoPay Driver.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h3>Easy Integration</h3>
                        <p>Integrasi mudah dengan dokumentasi lengkap dan contoh kode untuk berbagai bahasa pemrograman.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card slide-in-right">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3>24/7 Support</h3>
                        <p>Tim support profesional siap membantu Anda 24/7 melalui WhatsApp dan email.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <div class="stat-number">99.9%</div>
                        <div class="stat-label">Uptime</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <div class="stat-number">7</div>
                        <div class="stat-label">Ewallet Supported</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <div class="stat-number">1000+</div>
                        <div class="stat-label">API Calls/Day</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Support</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Packages Section -->
    <section class="packages" id="packages">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="display-4 fw-bold mb-4">Pilih Paket Terbaik</h2>
                    <p class="lead">Temukan paket yang sesuai dengan kebutuhan bisnis Anda</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="package-card">
                        <h3>Basic</h3>
                        <div class="package-price">Rp 50K</div>
                        <ul class="package-features">
                            <li><i class="fas fa-check"></i> 1 API Key</li>
                            <li><i class="fas fa-check"></i> 1,000 requests/hari</li>
                            <li><i class="fas fa-check"></i> 60 requests/menit</li>
                            <li><i class="fas fa-check"></i> Support 7 ewallet</li>
                            <li><i class="fas fa-check"></i> Basic monitoring</li>
                            <li><i class="fas fa-check"></i> Email support</li>
                        </ul>
                        <a href="#contact" class="btn-package">Pilih Paket</a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="package-card featured">
                        <h3>Premium</h3>
                        <div class="package-price">Rp 100K</div>
                        <ul class="package-features">
                            <li><i class="fas fa-check"></i> 1 API Key</li>
                            <li><i class="fas fa-check"></i> 5,000 requests/hari</li>
                            <li><i class="fas fa-check"></i> 120 requests/menit</li>
                            <li><i class="fas fa-check"></i> Support 7 ewallet</li>
                            <li><i class="fas fa-check"></i> Advanced monitoring</li>
                            <li><i class="fas fa-check"></i> Priority support</li>
                            <li><i class="fas fa-check"></i> Analytics dashboard</li>
                        </ul>
                        <a href="#contact" class="btn-package">Pilih Paket</a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="package-card">
                        <h3>Enterprise</h3>
                        <div class="package-price">Rp 200K</div>
                        <ul class="package-features">
                            <li><i class="fas fa-check"></i> Multiple API Keys</li>
                            <li><i class="fas fa-check"></i> 20,000 requests/hari</li>
                            <li><i class="fas fa-check"></i> 300 requests/menit</li>
                            <li><i class="fas fa-check"></i> Support 7 ewallet</li>
                            <li><i class="fas fa-check"></i> Real-time monitoring</li>
                            <li><i class="fas fa-check"></i> 24/7 support</li>
                            <li><i class="fas fa-check"></i> Custom integration</li>
                            <li><i class="fas fa-check"></i> SLA guarantee</li>
                        </ul>
                        <a href="#contact" class="btn-package">Pilih Paket</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="contact-card">
                        <h2>Ready to Get Started?</h2>
                        <p>Hubungi kami sekarang untuk mendapatkan API key dan mulai menggunakan layanan kami. Tim support kami siap membantu Anda 24/7.</p>
                        <a href="https://wa.me/6282279698099?text=Halo%2C%20saya%20tertarik%20dengan%20API%20Management%20System%20untuk%20validasi%20ewallet%20dan%20bank." class="btn-whatsapp" target="_blank">
                            <i class="fab fa-whatsapp me-2"></i>
                            Hubungi via WhatsApp
                        </a>
                        <div class="mt-4">
                            <p><i class="fas fa-phone me-2"></i> 082279698099</p>
                            <p><i class="fas fa-envelope me-2"></i> admin@api-management.com</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><i class="fas fa-shield-alt me-2"></i> API Management</h5>
                    <p>Solusi terbaik untuk validasi ewallet dan bank account dengan keamanan tinggi dan performa optimal.</p>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#features">Fitur</a></li>
                        <li><a href="#packages">Paket</a></li>
                        <li><a href="api-docs.php">Dokumentasi</a></li>
                        <li><a href="#contact">Kontak</a></li>
                        <li><a href="user/index.php">Login User</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5>Kontak</h5>
                    <p><i class="fab fa-whatsapp me-2"></i> 082279698099</p>
                    <p><i class="fas fa-envelope me-2"></i> admin@api-management.com</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-12 text-center">
                    <p>&copy; 2024 API Management System. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="push-notifications.js"></script>
    
    <script>
        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe elements
        document.querySelectorAll('.feature-card, .package-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>
