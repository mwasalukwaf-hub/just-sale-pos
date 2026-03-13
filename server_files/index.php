<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JUSTSALE | Intelligent POS & Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4cc9f0;
            --dark: #1e293b;
            --light: #f8fafc;
        }
        body { font-family: 'Outfit', sans-serif; background-color: var(--light); color: var(--dark); overflow-x: hidden; }
        
        /* Navbar */
        .navbar { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); }
        .nav-link { font-weight: 600; color: var(--dark) !important; margin: 0 15px; }

        /* Hero Section */
        .hero {
            padding: 160px 0 100px;
            background: radial-gradient(circle at top right, rgba(67, 97, 238, 0.1), transparent),
                        radial-gradient(circle at bottom left, rgba(76, 201, 240, 0.1), transparent);
        }
        .hero-title { font-size: 4rem; font-weight: 800; line-height: 1.1; margin-bottom: 25px; }
        .hero-text { font-size: 1.25rem; color: #64748b; margin-bottom: 40px; max-width: 600px; }
        
        .btn-premium {
            padding: 15px 35px;
            border-radius: 50px;
            font-weight: 700;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-primary-premium { background: var(--primary); color: white; border: none; box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3); }
        .btn-primary-premium:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(67, 97, 238, 0.4); }

        /* Features */
        .feature-card {
            padding: 40px;
            border-radius: 25px;
            background: white;
            border: 1px solid rgba(0,0,0,0.05);
            height: 100%;
            transition: all 0.3s;
        }
        .feature-card:hover { transform: translateY(-10px); border-color: var(--primary); box-shadow: 0 20px 40px rgba(0,0,0,0.05); }
        .feature-icon { width: 60px; height: 60px; background: rgba(67, 97, 238, 0.1); border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--primary); margin-bottom: 25px; }

        /* Pricing */
        .pricing-card {
            padding: 50px 40px;
            border-radius: 30px;
            background: white;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        .pricing-card.featured { border: 2px solid var(--primary); box-shadow: 0 30px 60px rgba(67, 97, 238, 0.15); }
        .pricing-badge { position: absolute; top: 20px; right: -30px; background: var(--primary); color: white; transform: rotate(45deg); padding: 5px 40px; font-size: 0.8rem; font-weight: bold; }

        /* Animations */
        @keyframes float { 0% { transform: translateY(0); } 50% { transform: translateY(-20px); } 100% { transform: translateY(0); } }
        .floating { animation: float 6s ease-in-out infinite; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top py-3">
    <div class="container">
        <a class="navbar-brand fw-bold fs-3 text-primary" href="#"><i class="fa-solid fa-cash-register me-2"></i>JUSTSALE</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto text-uppercase small">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#pricing">Pricing</a></li>
                <li class="nav-item"><a class="nav-link" href="help">Documentation</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                <li class="nav-item"><a class="nav-link btn btn-outline-primary rounded-pill px-4 ms-lg-3" href="login">Login</a></li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold mb-3 small">Version 2.0 is Here!</div>
                <h1 class="hero-title">Take Your Retail Business to the <span class="text-primary">Cloud.</span></h1>
                <p class="hero-text">The all-in-one Intelligence system for Point of Sale, Inventory tracking, and advanced financial analytics. Designed for high-speed performance.</p>
                <div class="d-flex gap-3">
                    <a href="#pricing" class="btn btn-premium btn-primary-premium">Get Started Now</a>
                    <a href="#" class="btn btn-premium btn-outline-dark border-0"><i class="fa-solid fa-play-circle me-2"></i> See Demo</a>
                </div>
            </div>
            <div class="col-lg-6 mt-5 mt-lg-0">
                <div class="position-relative">
                    <img src="https://via.placeholder.com/800x600/4361ee/ffffff?text=JUSTSALE+Dashboard+Mockup" class="img-fluid rounded-4 shadow-lg floating" alt="JUSTSALE Dashboard">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5" id="features">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-black fs-1">Built for Modern Commerce</h2>
            <p class="text-muted">Everything you need to scale your business, from a single shop to a franchise.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-bolt"></i></div>
                    <h4 class="fw-bold">High Speed POS</h4>
                    <p class="text-muted">A lightning-fast terminal with offline support, multi-tab billing, and instant receipt generation.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-box-open"></i></div>
                    <h4 class="fw-bold">Smart Inventory</h4>
                    <p class="text-muted">Real-time stock tracking with SKU generation, low-stock alerts, and automated procurement workflows.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-chart-pie"></i></div>
                    <h4 class="fw-bold">Deep Analytics</h4>
                    <p class="text-muted">Visual charts for trends, Profit & Loss reports, and cashier performance audits at your fingertips.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-white py-5" id="pricing">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-black fs-1">Ready to scale?</h2>
            <p class="text-muted">Choose a plan that fits your business needs.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <!-- Basic Plan -->
            <div class="col-md-4">
                <div class="pricing-card">
                    <h5 class="text-muted mb-4 text-uppercase fw-bold">Starter</h5>
                    <h2 class="fw-black mb-1">TZS 150k</h2>
                    <p class="small text-muted mb-4">Per Month</p>
                    <hr class="my-4 opacity-50">
                    <ul class="list-unstyled mb-5">
                        <li class="mb-3"><i class="fa-solid fa-check text-success me-2"></i> Single Installation</li>
                        <li class="mb-3"><i class="fa-solid fa-check text-success me-2"></i> Full Inventory Control</li>
                        <li class="mb-3"><i class="fa-solid fa-check text-success me-2"></i> Unlimited Products</li>
                        <li class="mb-3 text-muted opacity-50"><i class="fa-solid fa-xmark me-2"></i> Priority Support</li>
                    </ul>
                    <a href="register?plan=starter" class="btn btn-outline-primary w-100 py-3 rounded-pill fw-bold">Sign Up</a>
                </div>
            </div>
            <!-- Professional Plan -->
            <div class="col-md-4">
                <div class="pricing-card featured">
                    <div class="pricing-badge">BEST VALUE</div>
                    <h5 class="text-primary mb-4 text-uppercase fw-bold">Professional</h5>
                    <h2 class="fw-black mb-1">TZS 1.2M</h2>
                    <p class="small text-muted mb-4">Annual License (save 30%)</p>
                    <hr class="my-4 opacity-50">
                    <ul class="list-unstyled mb-5">
                        <li class="mb-3"><i class="fa-solid fa-check text-success me-2"></i> Cloud + Local Sync</li>
                        <li class="mb-3"><i class="fa-solid fa-check text-success me-2"></i> Multi-User Support</li>
                        <li class="mb-3"><i class="fa-solid fa-check text-success me-2"></i> Priority Technical Support</li>
                        <li class="mb-3"><i class="fa-solid fa-check text-success me-2"></i> Custom Header/Logo</li>
                    </ul>
                    <a href="register?plan=pro" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow">Activate Now</a>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="bg-dark text-white py-5 mt-5">
    <div class="container">
        <div class="row gy-4">
            <div class="col-md-6">
                <h3 class="fw-bold mb-3">JUSTSALE</h3>
                <p class="opacity-50">Premium Inventory & POS Solutions for growing businesses in East Africa. Verified and secure.</p>
                <div class="d-flex gap-3 mt-4">
                    <a href="#" class="text-white opacity-50"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="text-white opacity-50"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#" class="text-white opacity-50"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0 small opacity-50">&copy; 2026 JUSTSALE POS. Developed by <a href="http://franklin.co.tz" class="text-white fw-bold">Franklin</a>.</p>
                <p class="small opacity-50 mt-2">Payments secured via <strong>Flutterwave</strong>.</p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
