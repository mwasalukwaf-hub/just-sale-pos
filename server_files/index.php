<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JUSTSALE | Modern POS & Inventory Intelligence</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4cc9f0;
            --dark: #0f172a;
            --light: #f8fafc;
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #fff; 
            color: var(--dark); 
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        /* Navbar */
        .navbar { 
            background: rgba(255, 255, 255, 0.9); 
            backdrop-filter: blur(15px); 
            transition: all 0.3s;
            padding: 1.2rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .navbar-brand { font-weight: 900; letter-spacing: -1px; }
        .nav-link { font-weight: 600; color: var(--dark) !important; margin: 0 10px; font-size: 0.95rem; }

        /* Hero */
        .hero {
            padding: 180px 0 120px;
            background: radial-gradient(circle at 10% 20%, rgba(67, 97, 238, 0.05) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(76, 201, 240, 0.05) 0%, transparent 40%);
        }
        .hero-title { font-size: 4.5rem; font-weight: 900; line-height: 1.1; margin-bottom: 25px; letter-spacing: -2px; }
        .hero-text { font-size: 1.2rem; color: #64748b; margin-bottom: 45px; max-width: 550px; line-height: 1.6; }
        
        .btn-premium {
            padding: 18px 45px;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.3s;
            text-transform: none;
            font-size: 1.05rem;
        }
        .btn-primary-premium { 
            background: var(--primary); 
            color: white; 
            border: none; 
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.25); 
        }
        .btn-primary-premium:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 15px 40px rgba(67, 97, 238, 0.4); 
            color: white;
        }

        /* Features */
        .feature-box {
            padding: 50px 40px;
            border-radius: 30px;
            background: #fff;
            border: 1px solid rgba(0,0,0,0.06);
            height: 100%;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }
        .feature-box:hover { 
            transform: translateY(-12px); 
            border-color: var(--primary); 
            box-shadow: 0 30px 60px rgba(0,0,0,0.08); 
        }
        .feature-icon-wrapper { 
            width: 70px; 
            height: 70px; 
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(76, 201, 240, 0.1)); 
            border-radius: 20px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.8rem; 
            color: var(--primary); 
            margin-bottom: 30px; 
        }

        /* Pricing */
        .pricing-section { background: var(--light); padding: 120px 0; border-radius: 80px 80px 0 0; }
        .pricing-card {
            padding: 60px 45px;
            border-radius: 35px;
            background: white;
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        .pricing-card.featured { 
            border: 3px solid var(--primary); 
            transform: scale(1.05); 
            box-shadow: 0 40px 80px rgba(67, 97, 238, 0.12);
            z-index: 2;
        }

        /* Animations */
        @keyframes floating { 0% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-15px) rotate(1deg); } 100% { transform: translateY(0) rotate(0deg); } }
        .hero-img { 
            border-radius: 40px; 
            box-shadow: 0 50px 100px rgba(0,0,0,0.12); 
            animation: floating 8s ease-in-out infinite; 
        }

        .section-tag {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(67, 97, 238, 0.08);
            color: var(--primary);
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        footer { background: #0b0f19; color: rgba(255,255,255,0.6); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand text-primary fs-3" href="/"><i class="fa-solid fa-cash-register me-2"></i>JUSTSALE</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#pricing">Pricing</a></li>
                <li class="nav-item"><a class="nav-link" href="versions">Roadmap</a></li>
                <li class="nav-item"><a class="nav-link" href="help">Documentation</a></li>
                <li class="nav-item"><a class="nav-link btn btn-primary-premium btn-sm rounded-pill px-4 ms-lg-3 text-white" href="login">Login</a></li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="section-tag">Point of Sale & Inventory Reimagined</div>
                <h1 class="hero-title">Elevate Your Business with <span class="text-primary">Intelligence.</span></h1>
                <p class="hero-text">The all-in-one platform to manage sales, track inventory, and scale your business with advanced analytics and seamless cloud synchronization.</p>
                <div class="d-flex gap-3">
                    <a href="#pricing" class="btn btn-premium btn-primary-premium shadow-lg">Start for Free</a>
                    <a href="login" class="btn btn-premium btn-outline-dark border-2">Portal Login</a>
                </div>
                <div class="mt-5 d-flex gap-4 text-muted small fw-bold">
                    <span><i class="fa-solid fa-circle-check text-success me-2"></i> Trusted by 500+ Shops</span>
                    <span><i class="fa-solid fa-circle-check text-success me-2"></i> Secure SSL</span>
                </div>
            </div>
            <div class="col-lg-6 mt-5 mt-lg-0 text-center">
                <img src="hero.png" class="img-fluid hero-img" alt="JUSTSALE POS Premium Display">
            </div>
        </div>
    </div>
</section>

<section class="py-5" id="features">
    <div class="container py-5">
        <div class="text-center mb-5 pb-4">
            <div class="section-tag">Features Overview</div>
            <h2 class="fw-black fs-1 display-5 mb-3">Built for High-Growth Retail</h2>
            <p class="text-muted fs-5">Everything you need to manage your business anywhere in the world.</p>
        </div>
        <div class="row g-4 pt-4">
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon-wrapper"><i class="fa-solid fa-bolt-lightning"></i></div>
                    <h4 class="fw-bold mb-3">Lightning POS</h4>
                    <p class="text-muted mb-0">Offline-first terminal architecture that ensures your business never stops, even without internet. Instant sync to cloud.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon-wrapper"><i class="fa-solid fa-cubes"></i></div>
                    <h4 class="fw-bold mb-3">AI Inventory</h4>
                    <p class="text-muted mb-0">Smart stock tracking with automated low-stock alerts, procurement forecasting, and batch management controls.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon-wrapper"><i class="fa-solid fa-microchip"></i></div>
                    <h4 class="fw-bold mb-3">Deep Insights</h4>
                    <p class="text-muted mb-0">Comprehensive financial reporting including Net Profit, Tax summaries, and Detailed Cashier performance audits.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pricing-section" id="pricing">
    <div class="container py-5">
        <div class="text-center mb-5 pb-4">
            <div class="section-tag">Global Pricing Plans</div>
            <h2 class="fw-black fs-1 display-5 mb-3">Scale at Your Own Pace</h2>
            <p class="text-muted fs-5">Powerful tools for businesses of all sizes.</p>
        </div>
        <div class="row g-4 justify-content-center align-items-center">
            <div class="col-md-4">
                <div class="pricing-card">
                    <h5 class="text-muted mb-4 fw-bold">STARTER</h5>
                    <h2 class="fw-black display-4 mb-2">TZS 150k</h2>
                    <p class="small text-muted mb-4 uppercase">Per Month</p>
                    <ul class="list-unstyled mb-5 mt-4">
                        <li class="mb-3"><i class="fa-solid fa-circle-check text-success me-2"></i> Single Installation</li>
                        <li class="mb-3"><i class="fa-solid fa-circle-check text-success me-2"></i> Full Inventory Control</li>
                        <li class="mb-3"><i class="fa-solid fa-circle-check text-success me-2"></i> Unlimited Products</li>
                        <li class="mb-3 text-muted opacity-50"><i class="fa-solid fa-circle-xmark me-2"></i> Priority Support</li>
                    </ul>
                    <a href="register?plan=starter" class="btn btn-outline-primary w-100 py-3 rounded-pill fw-bold">Sign Up</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="pricing-card featured">
                    <div class="section-tag mb-4 shadow-sm" style="background:#4361ee; color:#fff;">OUR MOST POPULAR</div>
                    <h5 class="text-primary mb-4 fw-bold">PROFESSIONAL</h5>
                    <h2 class="fw-black display-4 mb-2">TZS 1.2M</h2>
                    <p class="small text-muted mb-4 uppercase">Annual License (save 30%)</p>
                    <ul class="list-unstyled mb-5 mt-4">
                        <li class="mb-3"><i class="fa-solid fa-circle-check text-success me-2"></i> Cloud + Local Sync</li>
                        <li class="mb-3"><i class="fa-solid fa-circle-check text-success me-2"></i> Multi-User Support</li>
                        <li class="mb-3"><i class="fa-solid fa-circle-check text-success me-2"></i> Priority Technical Support</li>
                        <li class="mb-3"><i class="fa-solid fa-circle-check text-success me-2"></i> Custom Header & Logo</li>
                    </ul>
                    <a href="register?plan=pro" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-lg">Activate Pro Now</a>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="py-5">
    <div class="container py-5">
        <div class="row g-5">
            <div class="col-lg-6">
                <h2 class="fw-black text-white mb-4">JUSTSALE</h2>
                <p class="mb-4 pe-lg-5">Premium Inventory & POS Solutions engineered for growing retail businesses in East Africa. Powering efficiency across Tanzania.</p>
                <div class="d-flex gap-3">
                    <a href="#" class="btn btn-outline-light btn-sm px-3 rounded-circle"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="btn btn-outline-light btn-sm px-3 rounded-circle"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="btn btn-outline-light btn-sm px-3 rounded-circle"><i class="fa-brands fa-whatsapp"></i></a>
                </div>
            </div>
            <div class="col-lg-2">
                <h6 class="text-white fw-bold mb-4">Platform</h6>
                <ul class="list-unstyled small opacity-75">
                    <li class="mb-2"><a href="login" class="text-white text-decoration-none">Merchant Login</a></li>
                    <li class="mb-2"><a href="register" class="text-white text-decoration-none">Create Account</a></li>
                    <li class="mb-2"><a href="help" class="text-white text-decoration-none">Support Center</a></li>
                </ul>
            </div>
            <div class="col-lg-4 text-lg-end">
                <p class="small opacity-50">&copy; 2026 JUSTSALE POS System. Developed by <a href="http://franklin.co.tz" class="text-white fw-bold text-decoration-none">Franklin</a>.</p>
                <p class="small opacity-50 mt-2 mb-0">Payments secured via <strong>Flutterwave</strong>.</p>
                <span class="badge bg-white text-dark mt-3 opacity-25">Official Build v2.0.5</span>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
